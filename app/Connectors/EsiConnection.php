<?php

namespace App\Connectors;

use App\Models\Type;
use Illuminate\Support\Facades\Cache;
use Seat\Eseye\Eseye;
use Swagger\Client\Eve\Api\ContactsApi;
use Swagger\Client\Eve\Api\LocationApi;
use Swagger\Client\Eve\Api\MailApi;
use Swagger\Client\Eve\Api\UniverseApi;
use Swagger\Client\Eve\Configuration;

/**
 * Handles the connection between the recruitment site and core
 *
 * Class EsiModel
 * @package App\Models
 */
class EsiConnection
{

    /**
     * Store the configuration instance
     *
     * @var Configuration $config
     */
    private $config;

    /**
     * Character ID the instance is created for
     *
     * @var int $char_id
     */
    private $char_id;

    /**
     * Eseye instance
     *
     * @var Eseye $eseye
     */
    private $eseye;

    /**
     * EsiModel constructor
     *
     * @param int $char_id Char ID to create the instance for
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct($char_id)
    {
        $config = new Configuration();
        $config->setHost(env('CORE_URL') . '/api/app/v1/esi');
        $config->setAccessToken(base64_encode(env('CORE_APP_ID') . ':' . env('CORE_APP_SECRET')));

        $this->eseye = new Eseye();
        $this->config = $config;
        $this->char_id = $char_id;
    }

    /**
     * Get a user's corp history
     *
     * @return \Seat\Eseye\Containers\EsiResponse
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getCorpHistory()
    {
        $history = $this->eseye->invoke('get', '/characters/{character_id}/corporationhistory/', [
            'character_id' => $this->char_id
        ]);

        $data = json_decode($history->raw);

        // Get corporation names and alliance information
        foreach ($data as $d)
        {
            // TODO: Error handling?
            $corp_info = $this->eseye->invoke('get', '/corporations/{corporation_id}/', [
                'corporation_id' => $d->corporation_id
            ]);
            $d->corporation_name = $corp_info->name;

            $alliance_id = (isset($corp_info->alliance_id)) ? $corp_info->alliance_id : null;
            $d->alliance_id = $alliance_id;
            $d->alliance_name = $this->getAllianceName($alliance_id);
        }

        return $data;
    }

    /**
     * Get a character's information
     *
     * @return array
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     */
    public function getCharacterInfo()
    {
        $locationModel = new LocationApi(null, $this->config);
        $location = $locationModel->getCharactersCharacterIdLocation($this->char_id, $this->char_id);

        if ($location->getStructureId() == null && $location->getStationId() == null)
            $location->structure_name = "In Space (" . $this->getSystemName($location->getSolarSystemId()) . ")";
        else if ($location->getStructureId() != null)
            $location->structure_name = $this->getStructureName($location->getStructureId());
        else
            $location->structure_name = $this->getStationName($location->getStationId());

        $ship = $locationModel->getCharactersCharacterIdShip($this->char_id, $this->char_id);

        $public_data = $this->eseye->invoke('get', '/characters/{character_id}/', [
            "character_id" => $this->char_id
        ]);

        return [
            'location' => $location,
            'birthday' => explode('T', $public_data->birthday)[0],
            'gender' => ucfirst($public_data->gender),
            'ancestry' => $this->getAncestry($public_data->ancestry_id),
            'bloodline' => $this->getBloodline($public_data->bloodline_id),
            'race' => $this->getRace($public_data->race_id),
            'current_ship' => $ship->getShipName() . " (" . $this->getTypeName($ship->getShipTypeId()) . ")",
            'security_status' => round($public_data->security_status, 4),
            'region' => $this->getRegionName($location->getSolarSystemId())
        ];
    }

    /**
     * Get a user's mail
     *
     * @return \Swagger\Client\Eve\Model\GetCharactersCharacterIdMail200Ok[]
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     */
    public function getMail()
    {
        $model = new MailApi(null, $this->config);
        $mail = $model->getCharactersCharacterIdMail($this->char_id, $this->char_id);

        foreach ($mail as $m)
        {
            $m->contents = $model->getCharactersCharacterIdMailMailId($this->char_id, $m->getMailId(), $this->char_id)->getBody();
            $m->sender = $this->getCharacterName($m->getFrom());
            $m->recipients = [];

            foreach ($m->getRecipients() as $recipient)
            {
                switch ($recipient->getRecipientType())
                {
                    case 'character':
                        $m->recipients[] = [
                            'type' => 'character',
                            'name' => $this->getCharacterName($recipient->getRecipientId())
                        ];
                        break;

                    case 'corporation':
                        $m->recipients[] = [
                            'type' => 'corporation',
                            'name' => $this->getCorporationName($recipient->getRecipientId())
                        ];
                        break;

                    case 'alliance':
                        $m->recipients[] = [
                            'type' => 'alliance',
                            'name' => $this->getAllianceName($recipient->getRecipientId())
                        ];
                        break;

                    case 'mailing_list':
                        $m->recipients[] = [
                            'type' => 'mailing list',
                            'name' => $this->getMailingListName($recipient->getRecipientId())
                        ];
                        break;

                    default:
                        break;
                }
            }
        }

        return $mail;
    }

    /**
     * Get a race name given an ID
     *
     * @param $race_id
     * @return mixed
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getRace($race_id)
    {
        $res = $this->eseye->invoke('get', '/universe/races');
        $data = json_decode($res->raw);
        return $data[$race_id - 1]->name;
    }

    /**
     * Get an anestry name given an ID
     *
     * @param $ancestry_id
     * @return mixed
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getAncestry($ancestry_id)
    {
        $res = $this->eseye->invoke('get', '/universe/ancestries');
        $data = json_decode($res->raw);
        return $data[$ancestry_id - 1]->name;
    }

    /**
     * Get a bloodline name given an ID
     *
     * @param $bloodline_id
     * @return mixed
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getBloodline($bloodline_id)
    {
        $res = $this->eseye->invoke('get', '/universe/bloodlines/');
        $data = json_decode($res->raw);
        return $data[$bloodline_id - 1]->name;
    }

    /**
     * Get a user's contacts
     *
     * @return \Swagger\Client\Eve\Model\GetCharactersCharacterIdContacts200Ok[]
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     */
    public function getContacts()
    {
        $model = new ContactsApi(null, $this->config);
        $contacts = $model->getCharactersCharacterIdContacts($this->char_id, $this->char_id);

        foreach ($contacts as $contact)
        {
            switch($contact->getContactType())
            {
                case "character":
                    $contact->contact_name = $this->getCharacterName($contact->getContactId());
                    break;

                case "alliance":
                    $contact->contact_name = $this->getAllianceName($contact->getContactId());
                    break;

                case "corporation":
                    $contact->contact_name = $this->getCorporationName($contact->getContactId());
                    break;

                default:
                    $contact->contact_name = null;
                    break;
            }
        }

        // Reverse sort by standing
        usort($contacts, function($a, $b) {
            $a_standing = $a->getStanding();
            $b_standing = $b->getStanding();

            if ($a_standing == $b_standing)
                return 0;

            return ($a_standing > $b_standing) ? -1 : 1;
        });

        return $contacts;
    }

    /**
     * Get a mailing list ID from the name
     *
     * @param $mailing_list_id
     * @return mixed
     * @throws \Swagger\Client\Eve\ApiException
     */
    public function getMailingListName($mailing_list_id)
    {
        if (Cache::has($mailing_list_id))
            return Cache::get($mailing_list_id);

        $model = new MailApi(null, $this->config);
        $lists = $model->getCharactersCharacterIdMailLists($this->char_id, $this->char_id);

        foreach ($lists as $list)
        {
            if (!Cache::has($list->getMailingListId()))
                Cache::add($list->getMailingListId(), $list->getName(), env('CACHE_TIME', 3264));
        }

        return Cache::get($mailing_list_id);
    }

    /**
     * Get the name of an alliance
     *
     * @param $alliance_id
     * @return string|null
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getAllianceName($alliance_id)
    {
        if ($alliance_id == null)
            return null;

        if (Cache::has($alliance_id))
            return Cache::get($alliance_id);

        $alliance_info = $this->eseye->invoke('get', '/alliances/{alliance_id}/', [
            'alliance_id' => $alliance_id
        ]);

        Cache::add($alliance_id, $alliance_info->name, env('CACHE_TIME', 3264));

        return $alliance_info->name;
    }

    /**
     * Get the name of a corporation
     *
     * @param $corporation_id
     * @return |null
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getCorporationName($corporation_id)
    {
        if ($corporation_id == null)
            return null;

        if (Cache::has($corporation_id))
            return Cache::get($corporation_id);

        $corp_info = $this->eseye->invoke('get', '/corporations/{corporation_id}/', [
            'corporation_id' => $corporation_id
        ]);

        Cache::add($corporation_id, $corp_info->name, env('CACHE_TIME', 3264));

        return $corp_info->name;
    }

    /**
     * Get a character name given an ID
     *
     * @param $character_id
     * @return mixed
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getCharacterName($character_id)
    {
        if ($character_id == null)
            return null;

        if (Cache::has($character_id))
            return Cache::get($character_id);

        $char = $this->eseye->invoke('get', '/characters/{character_id}/', [
            'character_id' => $character_id
        ]);

        Cache::add($character_id, $char->name, env('CACHE_TIME', 3264));

        return $char->name;
    }

    /**
     * Get the name of a structure
     * @param $structure_id
     * @return string
     * @throws \Swagger\Client\Eve\ApiException
     */
    public function getStructureName($structure_id)
    {
        $model = new UniverseApi(null, $this->config);
        return $model->getUniverseStructuresStructureId($structure_id, $this->char_id)->getName();
    }

    /**
     * Get a system name given the ID
     *
     * @param $system_id
     * @return mixed
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getSystemName($system_id)
    {
        $res = $this->eseye->invoke('get', '/universe/systems/{system_id}/', [
            'system_id' => $system_id
        ]);

        return $res->name;
    }

    /**
     * Get a region name, given the system ID
     * @param $system_id
     * @return mixed
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getRegionName($system_id)
    {
        $system = $this->eseye->invoke('get', '/universe/systems/{system_id}/', [
            'system_id' => $system_id
        ]);
        $constellation = $this->eseye->invoke('get', '/universe/constellations/{constellation_id}/', [
            'constellation_id' => $system->constellation_id
        ]);
        $region = $this->eseye->invoke('get', '/universe/regions/{region_id}/', [
            'region_id' => $constellation->region_id
        ]);

        return $region->name;
    }

    /**
     * Get a station name, given the ID
     *
     * @param $station_id
     * @return mixed
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getStationName($station_id)
    {
        $res = $this->eseye->invoke('get', '/universe/stations/{station_id}/', [
            'station_id' => $station_id
        ]);

        return $res->name;
    }

    /**
     * Given a type ID, get its name
     *
     * @param $type_id
     * @return mixed
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function getTypeName($type_id)
    {
        $dbItem = Type::find($type_id);

        if ($dbItem)
            return $dbItem->name;

        $res = $this->eseye->invoke('get', '/universe/types/{type_id}/', [
            'type_id' => $type_id
        ]);

        $dbItem = new Type();
        $dbItem->id = $type_id;
        $dbItem->name = $res->name;
        $dbItem->save();

        return $res->name;
    }
}