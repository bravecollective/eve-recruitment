<?php

namespace App\Connectors;

use Seat\Eseye\Eseye;
use Swagger\Client\Eve\Api\CharacterApi;
use Swagger\Client\Eve\Api\ContactsApi;
use Swagger\Client\Eve\Api\LocationApi;
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

        // TODO: Handle stations
        $location->structure_name = $this->getStructureName($location->getStructureId());

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
            'security_status' => round($public_data->security_status, 4)
        ];
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

        $alliance_info = $this->eseye->invoke('get', '/alliances/{alliance_id}/', [
            'alliance_id' => $alliance_id
        ]);

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

        $corp_info = $this->eseye->invoke('get', '/corporations/{corporation_id}/', [
            'corporation_id' => $corporation_id
        ]);

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

        $char = $this->eseye->invoke('get', '/characters/{character_id}/', [
            'character_id' => $character_id
        ]);

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
        $res = $this->eseye->invoke('get', '/universe/types/{type_id}/', [
            'type_id' => $type_id
        ]);

        return $res->name;
    }
}