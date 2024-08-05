<?php

namespace App\Connectors;

use App\Models\ESINameResponse;
use App\Models\User;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\EsiScopeAccessDeniedException;
use Seat\Eseye\Exceptions\InvalidAuthenticationException;
use Seat\Eseye\Exceptions\InvalidContainerDataException;
use Seat\Eseye\Exceptions\RequestFailedException;
use Seat\Eseye\Exceptions\UriDataMissingException;
use stdClass;
use Swagger\Client\Eve\Api\KillmailsApi;
use Swagger\Client\Eve\ApiException;
use App\Models\Group;
use App\Models\Type;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Seat\Eseye\Eseye;
use Swagger\Client\Eve\Api\AssetsApi;
use Swagger\Client\Eve\Api\CharacterApi;
use Swagger\Client\Eve\Api\ClonesApi;
use Swagger\Client\Eve\Api\ContactsApi;
use Swagger\Client\Eve\Api\ContractsApi;
use Swagger\Client\Eve\Api\LocationApi;
use Swagger\Client\Eve\Api\MailApi;
use Swagger\Client\Eve\Api\MarketApi;
use Swagger\Client\Eve\Api\SkillsApi;
use Swagger\Client\Eve\Api\UniverseApi;
use Swagger\Client\Eve\Api\WalletApi;
use Swagger\Client\Eve\Configuration;
use Swagger\Client\Eve\Model\GetCharactersCharacterIdContacts200Ok;
use Swagger\Client\Eve\Model\GetCharactersCharacterIdLocationOk;
use Swagger\Client\Eve\Model\GetCharactersCharacterIdMail200Ok;
use Symfony\Component\Yaml\Yaml;

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
     * Guzzle client
     *
     * @var Client
     */
    private $client;

    // The maximum number of mails to load from ESI
    const MAX_MAILS_TO_LOAD = 200;

    /**
     * EsiModel constructor
     *
     * @param int $char_id Char ID to create the instance for
     * @throws InvalidContainerDataException
     */
    public function __construct($char_id)
    {
        $config = new Configuration();
        $config->setHost(env('CORE_URL') . '/api/app/v1/esi');
        $config->setAccessToken(base64_encode(env('CORE_APP_ID') . ':' . env('CORE_APP_SECRET')));

        $eseye_config = \Seat\Eseye\Configuration::getInstance();
        $eseye_config->logfile_location = storage_path() . '/logs';
        $eseye_config->file_cache_location = storage_path() . '/framework/cache';
        $eseye_config->esi_host = Config::get('services.eveonline.esi_domain');

        $this->eseye = new Eseye();
        $this->config = $config;
        $this->char_id = $char_id;

        $this->client = new Client(['timeout' => 0]);
    }

    /**
     * Get the user's last login information
     */
    public function getLoginDetails() {
        $model = new LocationApi($this->client, $this->config);

        try {
            $login_info = $model->getCharactersCharacterIdOnline($this->char_id, $this->char_id);
        } catch(ApiException) {
            return null;
        }

        return $login_info;
    }

    /**
     * Get a user's wallet balance
     *
     * @return string
     */
    public function getWalletBalance()
    {
        $model = new WalletApi($this->client, $this->config);

        try {
            $balance = number_format($model->getCharactersCharacterIdWallet($this->char_id, $this->char_id));
        } catch(ApiException) {
            return null;
        }

        return $balance;
    }

    /**
     * Get a user's corp history
     *
     * @return EsiResponse
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidAuthenticationException
     * @throws InvalidContainerDataException
     * @throws RequestFailedException
     * @throws UriDataMissingException
     */
    public function getCorpHistory()
    {
        $history = $this->eseye->invoke('get', '/characters/{character_id}/corporationhistory/', [
            'character_id' => $this->char_id
        ]);

        $data = json_decode($history->raw);

        // Get corporation names and alliance information
        foreach ($data as $idx => $d)
        {
            $corp_info = $this->eseye->invoke('get', '/corporations/{corporation_id}/', [
                'corporation_id' => $d->corporation_id
            ]);
            $d->corporation_name = $corp_info->name;

            $history = [];
            $alliance_history = $this->eseye->invoke('get', '/corporations/{corporation_id}/alliancehistory/', [
                'corporation_id' => $d->corporation_id
            ]);
            foreach ($alliance_history as $h)
                $history[] = $h;

            usort($history, function ($a, $b) {
                $a = new \DateTime($a->start_date);
                $b = new \DateTime($b->start_date);

                return ($a < $b) ? 1 : -1;
            });

            $alliance_id = null;
            $charStart = new \DateTime($d->start_date);

            foreach ($history as $h)
            {
                $alliStart = new \DateTime($h->start_date);
                if ($charStart > $alliStart)
                {
                    $alliance_id = property_exists($h, 'alliance_id') ? $h->alliance_id : null;
                    break;
                }
            }

            $d->alliance_id = $alliance_id;
            $d->alliance_ticker = $this->getAllianceTicker($alliance_id);
            $d->alliance_name = $this->getAllianceName($alliance_id);

            $start = new DateTime($d->start_date);
            $end = ($idx != 0) ? new DateTime($data[$idx - 1]->start_date) : null;

            $d->formatted_start_date = $start->format("m-d-Y");
            $d->formatted_end_date = ($end != null) ? $end->format("m-d-Y") : null;
            $d->duration = ($end != null) ? $start->diff($end)->format('%a days') : $start->diff(new DateTime())->format('%a days');
        }

        return $data;
    }

    /**
     * Get a character's information
     *
     * @return array
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidAuthenticationException
     * @throws InvalidContainerDataException
     * @throws RequestFailedException
     * @throws UriDataMissingException
     */
    public function getCharacterInfo()
    {
        $locationModel = new LocationApi($this->client, $this->config);

        try {
            $ship = $locationModel->getCharactersCharacterIdShip($this->char_id, $this->char_id);
        } catch (Exception $e) {
            $ship = null;
        }

        try {
            $skillsModel = new SkillsApi($this->client, $this->config);
            $attributes = $skillsModel->getCharactersCharacterIdAttributes($this->char_id, $this->char_id);
        } catch(Exception $e) {
            $attributes = null;
        }

        try {
            $location = $locationModel->getCharactersCharacterIdLocation($this->char_id, $this->char_id);
        } catch (Exception $e) {
            $location = null;
        }

        if ($location !== null)
        {
            try {
                if ($location->getStructureId() == null && $location->getStationId() == null)
                    $location->structure_name = "In Space (" . $this->getSystemName($location->getSolarSystemId()) . ")";
                else if ($location->getStructureId() != null)
                    $location->structure_name = $this->getStructureName($location->getStructureId());
                else
                    $location->structure_name = $this->getStationName($location->getStationId());
            } catch(Exception $e) {
                $location->structure_name = "- Undockable Structure -";
            }
        }
        else
        {
            $location = new stdClass();
            $location->structure_name = "- Undockable Structure -";
        }

        $public_data = $this->eseye->invoke('get', '/characters/{character_id}/', [
            "character_id" => $this->char_id
        ]);

        return [
            'location' => $location,
            'birthday' => explode('T', $public_data->birthday)[0],
            'gender' => ucfirst($public_data->gender),
            'bloodline' => $this->getBloodline($public_data->bloodline_id),
            'race' => $this->getRace($public_data->race_id),
            'current_ship' => ($ship != null) ? $ship->getShipName() . " (" . $this->getTypeName($ship->getShipTypeId()) . ")" : null,
            'security_status' => round($public_data->security_status, 4),
            'region' => $location instanceof GetCharactersCharacterIdLocationOk ?
                $this->getRegionName($location->getSolarSystemId()) : null,
            'attributes' => $attributes
        ];
    }

    /**
     * Get user titles
     *
     * @return array|mixed
     * @throws ApiException
     */
    public function getTitles()
    {
        $cache_key = "character_titles_{$this->char_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new CharacterApi($this->client, $this->config);
        $titles = $model->getCharactersCharacterIdTitlesWithHttpInfo($this->char_id, $this->char_id);
        $out = [];

        foreach ($titles[0] as $title)
            $out[] = strip_tags($title->getName());

        $out = implode(', ', $out);

        Cache::add($cache_key, $out, $this->getCacheExpirationTime($titles));
        return $out;
    }

    /**
     * Get a character's clone information
     *
     * @return array
     * @throws ApiException
     */
    public function getCloneInfo()
    {
        $model = new ClonesApi($this->client, $this->config);

        $implants = $model->getCharactersCharacterIdImplants($this->char_id, $this->char_id);
        foreach ($implants as $idx => $implant)
            $implants[$idx] = $this->getTypeName($implant);

        $clones = $model->getCharactersCharacterIdClones($this->char_id, $this->char_id);
        $home = $clones->getHomeLocation();

        try {
            $home->location_name = $this->getLocationBasedOnStationType($home->getLocationType(), $home->getLocationId());
        } catch(Exception $e) {
            $home->location_name = "- Undockable Station -";
        }

        foreach ($clones->getJumpClones() as $clone)
        {
            try {
                $clone->location_name = $this->getLocationBasedOnStationType($clone->getLocationType(), $clone->getLocationId());
            } catch(Exception $e) {
                $clone->location_name = "- Undockable Station -";
            }
        }

        return ['implants' => $implants, 'clones' => $clones];
    }

    /**
     * Determine if a character ID can fly a fit
     *
     * @param $item_id
     * @return bool
     * @throws ApiException
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    public function characterCanUseItem($item_id)
    {
        // Pulled from SDE
        $requiredSkillDogmaAttributes = [
            182,
            183,
            184,
            1285,
            1289,
            1290
        ];
        $requiredSkillDogmaAttributesLevels = [
            277,
            278,
            279,
            1286,
            1287,
            1288
        ];
        $requiredSkills = [];

        $attributes = $this->eseye->invoke('get', '/universe/types/{type_id}/', [
            'type_id' => $item_id
        ])->dogma_attributes;

        foreach ($attributes as $attribute)
        {
            if (in_array($attribute->attribute_id, $requiredSkillDogmaAttributes))
            {
                $idx = array_search($attribute->attribute_id, $requiredSkillDogmaAttributes);

                if (!array_key_exists($idx, $requiredSkills))
                    $requiredSkills[$idx] = [];

                $requiredSkills[$idx]['skill'] = $this->getTypeName(floor($attribute->value));
            }
            else if (in_array($attribute->attribute_id, $requiredSkillDogmaAttributesLevels))
            {
                $idx = array_search($attribute->attribute_id, $requiredSkillDogmaAttributesLevels);

                if (!array_key_exists($idx, $requiredSkills))
                    $requiredSkills[$idx] = [];

                $requiredSkills[$idx]['level'] = (int) number_format($attribute->value);
            }
        }

        foreach ($requiredSkills as $requirement)
        {
            if (!$this->userHasSkillLevel($requirement['skill'], $requirement['level']))
                return false;
        }

        return true;
    }

    /**
     * Check if a user meets skillplan requirements
     *
     * @param $skillplan
     * @return array
     * @throws ApiException
     */
    public function checkSkillplan($skillplan)
    {
        $missing = [];

        foreach ($skillplan as $skill => $level)
        {
            if (!$this->userHasSkillLevel($skill, $level)) {
                switch ($level) {
                    case 1:
                        $level = 'I';
                        break;

                    case 2:
                        $level = 'II';
                        break;

                    case 3:
                        $level = 'III';
                        break;

                    case 4:
                        $level = 'IV';
                        break;

                    case 5:
                        $level = 'V';
                        break;

                    default:
                        break;
                }
                $missing[] = "$skill $level";
            }
        }

        return $missing;
    }

    /**
     * Given a skill name and level, check if the user has it
     *
     * @param $skill
     * @param $level
     * @return bool
     * @throws ApiException
     */
    private function userHasSkillLevel($skill, $level)
    {
        static $skills = null;

        if (!$skills)
            $skills = $this->getSkills();

        foreach ($skills as $category)
        {
            foreach ($category as $skillName => $attributes)
            {
                if ($skillName == $skill && $attributes['trained'] >= $level)
                    return true;
            }
        }

        return false;
    }

    /**
     * Get a user's mail metadata
     *
     * @return GetCharactersCharacterIdMail200Ok[]
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     * @throws ApiException
     */
    public function getMail()
    {
        $mailCacheKey = "mail_{$this->char_id}";
        $model = new MailApi($this->client, $this->config);

        if (Cache::has($mailCacheKey))
            return Cache::get($mailCacheKey);
        else
        {
            $mail_http = $model->getCharactersCharacterIdMailWithHttpInfo($this->char_id, $this->char_id);
            $mail = $temp = $mail_http[0];

            while (count($temp) >= 50) // If count is < 50, there's no new mail to request
            {
                $last_mail_id = end($mail)->getMailId();
                reset($mail);
                $temp = $model->getCharactersCharacterIdMail($this->char_id, $this->char_id, null, null, $last_mail_id);

                $mail = array_merge($mail, $temp);

                if (count($mail) >= self::MAX_MAILS_TO_LOAD)
                    break;
            }
        }

        $ids = [];
        array_map(function ($e) use(&$ids) { $ids[] = ['id' => $e->getFrom(), 'type' => 'character']; }, $mail);

        $senders = $this->lookupNames($ids);

        foreach ($mail as $m)
        {
            $names = array_filter($senders, function ($e) use ($m) {
                return $e->id == $m->getFrom();
            });
            $name = array_pop($names);

            $m->sender = $name->name;
        }

        Cache::add($mailCacheKey, $mail, $this->getCacheExpirationTime($mail_http));

        return $mail;
    }

    /**
     * Get information about a single mail
     *
     * @param $mailId
     */
    public function getMailDetails($mailId)
    {
        $mailBodyCacheKey = "mail_body_";
        $model = new MailApi($this->client, $this->config);
        $ids = [];

        $mail = $model->getCharactersCharacterIdMailMailId($this->char_id, $mailId, $this->char_id);

        if (Cache::has($mailBodyCacheKey . $mailId))
            $mail->contents = Cache::get($mailBodyCacheKey . $mailId);
        else
        {
            $mail->contents = $model->getCharactersCharacterIdMailMailId($this->char_id, $mailId, $this->char_id)->getBody();
            Cache::add($mailBodyCacheKey . $mailId, $mail->contents, env('CACHE_TIME', 3264));
        }

        $mail->recipients = [];

        foreach ($mail->getRecipients() as $recipient)
        {
            switch ($recipient->getRecipientType())
            {
                case 'character':
                    $mail->recipients[] = [
                        'type' => 'character',
                        'id' => $recipient->getRecipientId(),
                        'name' => null
                    ];
                    break;

                case 'corporation':
                    $mail->recipients[] = [
                        'type' => 'corporation',
                        'id' => $recipient->getRecipientId(),
                        'name' => null
                    ];
                    break;

                case 'alliance':
                    $mail->recipients[] = [
                        'type' => 'alliance',
                        'id' => $recipient->getRecipientId(),
                        'name' => null
                    ];
                    break;

                case 'mailing_list':
                    $mail->recipients[] = [
                        'type' => 'mailing list',
                        'name' => $this->getMailingListName($recipient->getRecipientId()),
                        'id' => $recipient->getRecipientId()
                    ];
                    break;

                default:
                    break;
            }

            if (in_array($recipient->getRecipientType(), ['character', 'corporation', 'alliance']) &&
                !in_array(['id' => $recipient->getRecipientId(), 'type' => $recipient->getRecipientType()], $ids))
                $ids[] = ['id' => $recipient->getRecipientId(), 'type' => $recipient->getRecipientType()];
        }

        if (count($ids) == 0)
            return $mail;

        $data = $this->lookupNames($ids);
        $new_ids = [];

        foreach ($data as $d)
            $new_ids[$d->id] = $d->name;

        foreach ($mail->recipients as &$recipient)
        {
            if ($recipient['name'] == null)
                $recipient['name'] = array_key_exists($recipient['id'], $new_ids) ? $new_ids[$recipient['id']] : 'Unknown recipient';
        }

        return $mail;
    }

    /**
     * Get a character's skills
     *
     * @return array|mixed
     * @throws ApiException
     */
    public function getSkills()
    {
        $cache_key = "skills_{$this->char_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new SkillsApi($this->client, $this->config);
        $skills = $model->getCharactersCharacterIdSkillsWithHttpInfo($this->char_id, $this->char_id);
        $unprocessed_skills = $skills[0]->getSkills();
        $out = [];

        foreach ($unprocessed_skills as $skill)
        {
            $skill_name = $this->getTypeName($skill->getSkillId());
            $skill_category = $this->getGroupName($skill->getSkillId());

            if (!array_key_exists($skill_category, $out))
            {
                $out[$skill_category] = [];
                $out[$skill_category]['skillpoints'] = 0;
            }


            $out[$skill_category]['skillpoints'] += $skill->getSkillpointsInSkill();
            $out[$skill_category][$skill_name] = [
                'skillpoints' => $skill->getSkillpointsInSkill(),
                'level' => $skill->getActiveSkillLevel(),
                'trained' => $skill->getTrainedSkillLevel()
            ];
        }

        foreach ($out as &$category)
            ksort($category);

        ksort($out);

        Cache::add($cache_key, $out, $this->getCacheExpirationTime($skills));
        return $out;
    }

    /**
     * Get a character's skillqueue
     *
     * @return array|mixed
     * @throws ApiException
     */
    public function getSkillQueue()
    {
        $cache_key = "skill_queue_{$this->char_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new SkillsApi($this->client, $this->config);
        $queue = $model->getCharactersCharacterIdSkillqueueWithHttpInfo($this->char_id, $this->char_id);
        $out = [];

        foreach ($queue[0] as $skill)
        {
            $out[] = [
                'skill' => $this->getTypeName($skill->getSkillId()),
                'end_level' => $skill->getFinishedLevel(),
                'paused' => (!method_exists($skill, 'getFinishDate') || $skill->getFinishDate() == null) ? true : false,
            ];
        }

        $out['queue_end'] = null;

        if (count($queue[0]) > 0)
        {
            $queue_end_date = end($queue[0])->getFinishDate();
            $out['queue_end'] = ($queue_end_date) ? $queue_end_date->format('Y-m-d H:i') : null;
            reset($queue[0]);
        }


        Cache::add($cache_key, $out, $this->getCacheExpirationTime($queue));

        return $out;
    }

    /**
     * Get a user's assets
     *
     * @return array
     * @throws ApiException
     */
    public function getAssets()
    {
        $cache_key = "assets_{$this->char_id}";
        $names_to_fetch = [];
        $names = [];
        $stationContentLocationFlags = [
            "Deliveries",
            "Hangar",
            "HangarAll"
        ];

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new AssetsApi($this->client, $this->config);
        $assets = $model->getCharactersCharacterIdAssetsWithHttpInfo($this->char_id, $this->char_id);
        $out = [];
        $parentItems = [];

        for ($i = 2; $i <= $assets[2]['X-Pages'][0]; $i++)
            $assets[0] = array_merge($assets[0], $model->getCharactersCharacterIdAssets($this->char_id, $this->char_id, null, $i));

        // 1a. Asset safety is a bitch. That needs to happen first
        foreach ($assets[0] as $idx => $item)
        {
            if ($item->getLocationFlag() != "AssetSafety")
                continue;

            $this->addStationItem($out, $parentItems, $item);
            $names_to_fetch[] = $item->getItemId();
            unset($assets[0][$idx]);
        }

        // 1b. Create parent container entries
        foreach ($assets[0] as $idx => $item)
        {
            // Items in asset safety wrap show up as "hangar" location flag. Since asset safety wrap is processed first,
            // if the item's locaiton ID is in $parentItems it is asset safety wrap
            if (array_key_exists($item->getLocationId(), $parentItems) || !in_array($item->getLocationFlag(), $stationContentLocationFlags))
                continue;

            $this->addStationItem($out, $parentItems, $item);
            $names_to_fetch[] = $item->getItemId();
            unset($assets[0][$idx]);
        }

        // 2. Add second-level container sub-items
        foreach ($assets[0] as $item)
        {
            // We don't need to do the station content location check again since those were all unset in the
            // previous for loop
            if (!array_key_exists($item->getLocationId(), $parentItems))
                continue; // TODO: Nested containers

            $price = (int) $this->getMarketPrice($item->getTypeId()) * $item->getQuantity();
            $parentItems[$item->getLocationId()]['items'][] = [
                'name' => $this->getTypeName($item->getTypeId()),
                'quantity' => number_format($item->getQuantity()),
                'item_id' => $item->getItemId(),
                'type_id' => $item->getTypeId(),
                'id' => $item->getTypeId(),
                'price' => $price,
                'value' => $price,
                'location' => $this->getLocationName($item->getLocationId()),
                'items' => []
            ];

            $parentItems[$item->getLocationId()]['value'] += $price;
            $names[$item->getItemId()] = 'None';
        }

        // 3. Calculate the value of each location/container
        foreach ($out as &$location_items)
        {
            $location_price = 0;
            foreach ($location_items['items'] as $item)
                $location_price += (int) filter_var($item['value'], FILTER_SANITIZE_NUMBER_INT);

            $location_items['value'] = number_format($location_price);
        }

        // 4a. Fetch container item names
        foreach (array_chunk($names_to_fetch, 1000) as $chunk) {
            $res = $model->postCharactersCharacterIdAssetsNames($this->char_id, $chunk, $this->char_id);
            foreach ($res as $data)
                $names[$data->getItemId()] = $data->getName();
        }

        // 4b. Add names to items and sort based on ISK value
        foreach ($out as &$location)
        {
            // 4a. Convert items that are actually containers to containers
            foreach ($location['items'] as $key => &$item) {
                $name = $names[$item['item_id']];
                $item['item_name'] = $name;
                uasort($item['items'], "self::sort_locations");

                if (count($item['items']) > 0) {
                    // Multiple items inside - move it to containers
                    $item['container'] = true;
                    $location['containers'][] = $item;
                    unset($location['items'][$key]);
                }
            }

            uasort($location['items'], "self::sort_locations");
            uasort($location['containers'], "self::sort_locations");
        }

        uasort($out, "self::sort_locations");

        Cache::add($cache_key, $out, $this->getCacheExpirationTime($assets));
        return $out;
    }

    private function addStationItem(&$out, &$parentItems, $item) {
        $price = (int) $this->getMarketPrice($item->getTypeId()) * $item->getQuantity();
        $location_id = $item->getLocationId();

        if (!array_key_exists($location_id, $out))
        {
            $location = $this->getLocationName($item->getLocationId());
            $out[$location_id] = [
                'id' => $location_id,
                'name' => $location,
                'value' => 0,
                'items' => [],
                'containers' => []
            ];
        }

        $parentItems[$item->getItemId()] = [
            'name' => $this->getTypeName($item->getTypeId()),
            'quantity' => number_format($item->getQuantity()),
            'item_id' => $item->getItemId(),
            'type_id' => $item->getTypeId(),
            'id' => $item->getTypeId(),
            'price' => $price,
            'value' => $price,
            'location' => $this->getLocationName($item->getLocationId()),
            'container' => false,
            'items' => []
        ];

        $out[$item->getLocationId()]['items'][] = &$parentItems[$item->getItemId()];
    }

    private static function sort_locations ($a, $b) {
        $v1 = (int) filter_var($a['value'], FILTER_SANITIZE_NUMBER_INT);
        $v2 = (int) filter_var($b['value'], FILTER_SANITIZE_NUMBER_INT);

        if ($v1 == $v2)
            return 0;

        return ($v1 > $v2) ? -1 : 1;
    }

    /**
     * Get the market price for an item
     *
     * @param $type_id
     * @return int|string
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    private function getMarketPrice($type_id)
    {
        static $lookup_table = null;
        $cache_key = "market_prices";

        if ($lookup_table == null)
        {
            if (Cache::has($cache_key))
                $market = Cache::get($cache_key);
            else
            {
                $res = $this->eseye->invoke('get', '/markets/prices/');
                $market = json_decode($res->raw);
                Cache::add($cache_key, $market, 60);
            }

            $lookup_table = [];
            foreach ($market as $entry)
                $lookup_table[$entry->type_id] = $entry->adjusted_price;
        }

        return (array_key_exists($type_id, $lookup_table) ? $lookup_table[$type_id] : 0);
    }

    /**
     * Get a user's wallet transactions
     *
     * @return mixed
     * @throws ApiException
     */
    public function getTransactions()
    {
        $cache_key = "wallet_transactions_{$this->char_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new WalletApi($this->client, $this->config);
        $res = $model->getCharactersCharacterIdWalletTransactionsWithHttpInfo($this->char_id, $this->char_id);
        $out = [];

        foreach ($res[0] as $transaction)
        {
            $out[] = [
                'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
                'client' => $this->getCharacterName($transaction->getClientId()),
                'item' => $this->getTypeName($transaction->getTypeId()),
                'quantity' => $transaction->getQuantity(),
                'change' => number_format((int) $transaction->getQuantity() * (int) $transaction->getUnitPrice()),
                'buy' => $transaction->getIsBuy(),
                'location' => $this->getLocationName($transaction->getLocationId())
            ];
        }

        Cache::add($cache_key, $out, $this->getCacheExpirationTime($res));
        return $out;
    }

    /**
     * Get a character's market orders
     *
     * @return array|mixed
     * @throws ApiException
     */
    public function getMarketOrders()
    {
        $cache_key = "market_orders_{$this->char_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new MarketApi($this->client, $this->config);
        $res = $model->getCharactersCharacterIdOrdersWithHttpInfo($this->char_id, $this->char_id);
        $out = [];

        foreach ($res[0] as $order)
        {
            $out[] = [
                'date' => $order->getIssued()->format('Y-m-d H:i:s'),
                'time_remaining' => $order->getDuration() - floor((time() - $order->getIssued()->format('U')) / 86400),
                'location' => $this->getLocationName($order->getLocationId()),
                'item' => $this->getTypeName($order->getTypeId()),
                'price' => number_format($order->getPrice(), 2),
                'buy' => $order->getIsBuyOrder(),
                'quantity_total' => $order->getVolumeTotal(),
                'quantity_remain' => $order->getVolumeRemain()
            ];
        }

        Cache::add($cache_key, $out, $this->getCacheExpirationTime($res));
        return $out;
    }

    /**'
     * Get user notifications
     *
     * @return array|mixed
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     * @throws ApiException
     */
    public function getNotifications()
    {
        $cache_key = "notifications_{$this->char_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new CharacterApi($this->client, $this->config);
        $notifications = $model->getCharactersCharacterIdNotificationsWithHttpInfo($this->char_id, $this->char_id);
        $out = [];

        foreach ($notifications[0] as $notification)
        {
            $name = null;
            switch($notification->getSenderType())
            {
                case 'character':
                    $name = $this->getCharacterName($notification->getSenderId());
                    break;
                case 'corporation':
                    $name = $this->getCorporationName($notification->getSenderId());
                    break;
                case 'alliance':
                    $name = $this->getAllianceName($notification->getSenderId());
                    break;
                default:
                    $name = 'Other';
                    break;
            }

            $out[] = [
                'sender' => $name,
                'type' => $notification->getType(),
                'variables' => Yaml::dump(Yaml::parse($notification->getText())),
                'timestamp' => $notification->getTimestamp()->format('Y-m-d H:i')
            ];
        }

        Cache::add($cache_key, $out, $this->getCacheExpirationTime($notifications));

        return $out;
    }

    /**
     * Get a character's contracts
     *
     * @return mixed
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     * @throws ApiException
     */
    public function getContracts()
    {
        $cache_key = "contracts_{$this->char_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new ContractsApi($this->client, $this->config);
        $contracts = $model->getCharactersCharacterIdContractsWithHttpInfo($this->char_id, $this->char_id);
        $out = [];

        $character_ids = [];
        array_map(function ($e) use(&$character_ids) {
            $character_ids[] = ['id' => $e->getAcceptorId(), 'type' => 'character'];
            $character_ids[] = ['id' => $e->getIssuerId(), 'type' => 'character'];
        }, $contracts[0]);
        $character_names = $this->lookupNames($character_ids);

        foreach ($contracts[0] as $contract)
        {
            $model_items = $model->getCharactersCharacterIdContractsContractIdItems($this->char_id, $contract->getContractId(), $this->char_id);
            $items = [];

            foreach ($model_items as $item)
            {
                $items[] = [
                    'id' => $item->getTypeId(),
                    'type' => $this->getTypeName($item->getTypeId()),
                    'quantity' => number_format($item->getQuantity()),
                    'price' => number_format($this->getMarketPrice($item->getTypeId()) * $item->getQuantity())
                ];
            }

            $type = $contract->getType();
            $collateral = null;
            $start = $this->getLocationName($contract->getStartLocationId());
            $end = $this->getLocationName($contract->getEndLocationId());

            switch($type)
            {
                case 'item_exchange':
                case 'auction':
                    $price = number_format($contract->getPrice());
                    break;
                case 'courier':
                    $price = number_format($contract->getReward());
                    $collateral = number_format($contract->getCollateral());
                    break;
                default:
                    $price = "Unknown";
                    break;
            }

            $assignee = null;

            $assignee = $this->getCharacterName($contract->getAssigneeId());

            if ($assignee == "Unknown Character")
            {
                try {
                    $assignee = $this->getCorporationName($contract->getAssigneeId());
                } catch (Exception $e) {
                    $assignee = "Unknown Assignee";
                }
            }

            $assignee = ($assignee == null) ? "Unknown" : $assignee;

            $acceptor = array_filter($character_names, function ($e) use (&$contract) {
                return $e->id == $contract->getAcceptorId();
            });

            if ($contract->getAcceptorId() > 0)
                $acceptor = sizeof($acceptor) > 0 ? array_pop($acceptor)->name : "Unknown Acceptor";
            else
                $acceptor = '';

            $issuer = array_filter($character_names, function ($e) use (&$contract) {
                return $e->id == $contract->getIssuerId();
            });
            $issuer = sizeof($issuer) > 0 ? array_pop($issuer)->name : "Unknown Issuer";

            $out[] = [
                'id' => $contract->getContractId(),
                'issued' => $contract->getDateIssued()->format('Y-m-d H:i'),
                'expired' => $contract->getDateExpired()->format('Y-m-d H:i'),
                'assignee' => $assignee,
                'acceptor' => $acceptor,
                'issuer' => $issuer,
                'type' => ucwords(implode(' ', explode('_', $type))),
                'status' => ucwords(implode(' ', explode('_', $contract->getStatus()))),
                'price' => $price,
                'reward' => number_format($contract->getReward()),
                'start' => $start,
                'end' => $end,
                'collateral' => $collateral,
                'title' => $contract->getTitle(),
                'items' => $items,
                'volume' => number_format($contract->getVolume()),
            ];
        }

        Cache::add($cache_key, $out, $this->getCacheExpirationTime($contracts));

        return $out;
    }

    /**
     * Given a location ID, figure out what type it is and return the name
     *
     * @param $id
     * @return mixed|string
     */
    private function getLocationName($id)
    {
        $cache_key = "user_location_{$this->char_id}_{$id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        if ($id >= 60000000 && $id <= 64000000)
        {
            try {
                $res = $this->getStationName($id);
                Cache::add($cache_key, $res, env('CACHE_TIME', 3264));
                return $res;
            } catch (Exception $e) { }
        }
        else if ($id == 2004)
            return "Asset Safety";
        else if ($id >= 40000000 && $id <= 50000000)
            return "Deleted PI Structure";

        try {
            $res = $this->getStructureName($id);
            Cache::add($cache_key, $res, env('CACHE_TIME', 3264));
            return $res;
        } catch (Exception $e) { }

        Cache::add($cache_key, "Unknown Location", env('CACHE_TIME', 3264));
        return "Unknown Location";
    }

    /**
     * Get a user's journal transactions
     *
     * @param int $page
     * @return array|mixed
     * @throws ApiException
     */
    public function getJournal($page = 1)
    {
        $cache_key = "wallet_journal_{$this->char_id}";
        $out = [];
        $ids = [];

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new WalletApi($this->client, $this->config);
        $journal = $model->getCharactersCharacterIdWalletJournalWithHttpInfo($this->char_id, $this->char_id, null, $page);

        for ($i = 2; $i <= $journal[2]['X-Pages'][0]; $i++)
        {
            $second = $model->getCharactersCharacterIdWalletJournal($this->char_id, $this->char_id, null, $i);

            if (!is_array($second))
                continue;

            $journal[0] = array_merge($journal[0], $second);
        }

        array_map(function ($e) use(&$ids) {
            $ids[] = ['id' => $e->getFirstPartyId(), 'type' => null];
            $ids[] = ['id' => $e->getSecondPartyId(), 'type' => null]; }, $journal[0]);

        $names = $this->lookupNames($ids);
        $account_id = User::where('character_id', $this->char_id)->first()->account_id;
        $alts = User::where('account_id', $account_id)->get()->pluck('name')->toArray();

        foreach ($journal[0] as $entry)
        {
            $sender = array_filter($names, function ($e) use(&$entry) { return $e->id == $entry->getFirstPartyId(); });
            $sender = sizeof($sender) > 0 ? array_pop($sender)->name : "Unknown Sender";

            $receiver = array_filter($names, function ($e) use(&$entry) { return $e->id == $entry->getSecondPartyId(); });
            $receiver = sizeof($receiver) > 0 ? array_pop($receiver)->name : "Unknown Receiver";

            $out[] = [
                'sender' => $sender,
                'receiver' => $receiver,
                'description' => $entry->getDescription(),
                'type' => ucwords(str_replace('_', ' ', $entry->getRefType())),
                'raw_amount' => $entry->getAmount(),
                'amount' => number_format($entry->getAmount()),
                'raw_balance' => $entry->getBalance(),
                'balance' => number_format($entry->getBalance()),
                'date' => $entry->getDate()->format('Y-m-d H:i:s'),
                'note' => $entry->getReason(),
                'between_alts' => (in_array($sender, $alts) && in_array($receiver, $alts)),
            ];
        }

        Cache::add($cache_key, $out, $this->getCacheExpirationTime($journal));
        return $out;
    }

    /**
     * Get a user's skillpoints
     *
     * @return mixed|string
     */
    public function getSkillpoints()
    {
        $cache_key = "skillpoints_{$this->char_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new SkillsApi($this->client, $this->config);
        try {
            $sp = $model->getCharactersCharacterIdSkillsWithHttpInfo($this->char_id, $this->char_id);
        } catch(ApiException $e) {
            return null;
        }

        $out = number_format($sp[0]->getTotalSp());

        Cache::add($cache_key, $out, $this->getCacheExpirationTime($sp));

        return $out;
    }

    /**
     * Get a race name given an ID
     *
     * @param $race_id
     * @return mixed
     */
    public function getRace($race_id)
    {
        $cache_key = "races";

        if (Cache::has($cache_key))
            $races = Cache::get($cache_key);
        else
        {
            try {
                $res = $this->eseye->invoke('get', '/universe/races');
            } catch (Exception $e) {
                Log::error('EsiConnection->getRace(): ' . $e->getMessage());
                $res = (object)['raw' => '[]'];
            }
            $races = json_decode($res->raw);
            Cache::add($cache_key, $races, env('CACHE_TIME', 3264));
        }

        foreach ($races as $race)
            if ($race->race_id == $race_id)
                return $race->name;

        return 'UNKNOWN';
    }

    /**
     * Get an anestry name given an ID
     *
     * @param $ancestry_id
     * @return mixed
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    public function getAncestry($ancestry_id)
    {
        $cache_key = "ancestries";

        if (Cache::has($cache_key))
            $ancestries = Cache::get($cache_key);
        else
        {
            try {
                $res = $this->eseye->invoke('get', '/universe/ancestries');
            } catch (\Exception $e) {
                return "UNKNOWN";
            }

            $ancestries = json_decode($res->raw);
            Cache::add($cache_key, $ancestries, env('CACHE_TIME', 3264));
        }

        foreach($ancestries as $ancestry)
            if ($ancestry->id == $ancestry_id)
                return $ancestry->name;

        return "UNKNOWN";
    }

    /**
     * Get a bloodline name given an ID
     *
     * @param $bloodline_id
     * @return mixed
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    public function getBloodline($bloodline_id)
    {
        $cache_key = "bloodlines";

        if (Cache::has($cache_key))
            $bloodlines = Cache::get($cache_key);
        else
        {
            $res = $this->eseye->invoke('get', '/universe/bloodlines/');
            $bloodlines = json_decode($res->raw);
            Cache::add($cache_key, $bloodlines, env('CACHE_TIME', 3264));
        }

        foreach ($bloodlines as $bloodline)
            if ($bloodline->bloodline_id == $bloodline_id)
                return $bloodline->name;

        return "UNKNOWN";
    }

    public function getKillmails()
    {
        $cache_key = "killmails_{$this->char_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new KillmailsApi($this->client, $this->config);
        $data = $model->getCharactersCharacterIdKillmailsRecent($this->char_id, $this->char_id);
        $killmailHashes = array_map(function ($e) { return ['id' => $e->getKillmailId(), 'hash' => $e->getKillmailHash()]; }, $data);
        $killmails = [];

        foreach ($killmailHashes as $killmailHash)
        {
            $data = $this->eseye->invoke('get', '/killmails/{killmail_id}/{killmail_hash}/', [
                'killmail_id' => $killmailHash['id'],
                'killmail_hash' => $killmailHash['hash']
            ]);

            foreach ($data->attackers as $attacker)
            {
                $attacker->name = property_exists($attacker, 'character_id') ? $this->getCharacterName($attacker->character_id) : null;
                $attacker->corporation_name = property_exists($attacker, 'corporation_id') ? $this->getCorporationName($attacker->corporation_id) : null;
                $attacker->alliance_name = property_exists($attacker, 'alliance_id') ? $this->getAllianceName($attacker->alliance_id) : null;

                if ($attacker->final_blow == true)
                    $data->final_blow = $attacker;
            }

            $data->victim->name = property_exists($data->victim, 'character_id') ? $this->getCharacterName($data->victim->character_id) : null;
            $data->victim->corporation_name = property_exists($data->victim, 'corporation_id') ? $this->getCorporationName($data->victim->corporation_id) : null;
            $data->victim->alliance_name = property_exists($data->victim, 'alliance_id') ? $this->getAllianceName($data->victim->alliance_id) : null;
            $data->victim->ship_type_name = $this->getTypeName($data->victim->ship_type_id);

            $data->solar_system = $this->eseye->invoke('get', '/universe/systems/' . $data->solar_system_id);
            $constellation = $this->eseye->invoke('get', '/universe/constellations/' . $data->solar_system->constellation_id);
            $data->region = $this->eseye->invoke('get', '/universe/regions/' . $constellation->region_id);
            $killmails[] = $data;
        }

        Cache::add($cache_key, $killmails, env('CACHE_TIME', 3264));

        return $killmails;
    }

    /**
     * Get a user's contacts
     *
     * @return GetCharactersCharacterIdContacts200Ok[]
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     * @throws ApiException
     */
    public function getContacts()
    {
        $model = new ContactsApi($this->client, $this->config);
        $contacts = $model->getCharactersCharacterIdContacts($this->char_id, $this->char_id);
        $ids = array_map(function ($e) { return ['id' => $e->getContactId(), 'type' => $e->getContactType()]; }, $contacts);

        $IDs = $this->lookupNames($ids);
        $char_ids = [];

        foreach ($contacts as $contact)
            if ($contact->getContactType() == "character")
                $char_ids[] = $contact->getContactId();

        $affiliations = [];
        if (count($char_ids) > 0) {
            $affiliations = $this->eseye->setBody($char_ids)->invoke('post', '/characters/affiliation');
            $affiliations = json_decode($affiliations->raw);
        }

        foreach ($contacts as $contact)
        {
            $names = array_filter($IDs,
                    function ($e) use(&$contact) {
                        return $e->id == $contact->getContactId();
                    }
                );
            $name = array_pop($names);
            $contact->contact_name = $name->name;
            $contact->alliance_name = $contact->alliance_ticker = null;

            if ($contact->getContactType() == "character")
            {
                $affiliation = array_filter($affiliations,
                    function ($e) use(&$contact) {
                        return $e->character_id == $contact->getContactId();
                    }
                );
                $affiliation = array_pop($affiliation);

                $contact->corp_id = $affiliation->corporation_id;
                $contact->corp_name = $this->getCorporationName($contact->corp_id);

                if (property_exists($affiliation, 'alliance_id'))
                {
                    $contact->alliance_name = $this->getAllianceName($affiliation->alliance_id);
                    $contact->alliance_ticker = $this->getAllianceTicker($affiliation->alliance_id);
                }
            }
            else if ($contact->getContactType() == "corporation")
            {
                $corp_info = $this->eseye->invoke('get', '/corporations/{corporation_id}/', [
                    'corporation_id' => $contact->getContactId()
                ]);

                if (!isset($corp_info->alliance_id))
                    continue;

                $contact->alliance_name = $this->getAllianceName($corp_info->alliance_id);
                $contact->alliance_ticker = $this->getAllianceTicker($corp_info->alliance_id);
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
     * @throws ApiException
     */
    public function getMailingListName($mailing_list_id)
    {

        $model = new MailApi($this->client, $this->config);
        $lists = $model->getCharactersCharacterIdMailListsWithHttpInfo($this->char_id, $this->char_id);

        foreach ($lists[0] as $list)
        {
            if ($list->getMailingListId() == $mailing_list_id)
                return $list->getName();
        }

        return "Unknown mailing list";
    }

    /**
     * Get the name of an alliance
     *
     * @param $alliance_id
     * @return string|null
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    public function getAllianceName($alliance_id)
    {
        if ($alliance_id == null)
            return null;

        $cache_key = "alliance_{$alliance_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $alliance_info = $this->eseye->invoke('get', '/alliances/{alliance_id}/', [
            'alliance_id' => $alliance_id
        ]);

        Cache::add($cache_key, $alliance_info->name, env('CACHE_TIME', 3264));

        return $alliance_info->name;
    }

    /**
     * Get the ticker for an alliance
     *
     * @param $alliance_id
     * @return mixed|null
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    public function getAllianceTicker($alliance_id)
    {
        if ($alliance_id == null)
            return null;

        $cache_key = "alliance_ticker_{$alliance_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $alliance_info = $this->eseye->invoke('get', '/alliances/{alliance_id}/', [
            'alliance_id' => $alliance_id
        ]);

        Cache::add($cache_key, $alliance_info->ticker, env('CACHE_TIME', 3264));

        return $alliance_info->ticker;
    }

    /**
     * Get the name of a corporation
     *
     * @param $corporation_id
     * @return |null
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    public function getCorporationName($corporation_id)
    {
        if ($corporation_id == null)
            return null;

        $cache_key = "corporation_{$corporation_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $corp_info = $this->eseye->invoke('get', '/corporations/{corporation_id}/', [
            'corporation_id' => $corporation_id
        ]);

        Cache::add($cache_key, $corp_info->name, env('CACHE_TIME', 3264));

        return $corp_info->name;
    }

    /**
     * Get a character name given an ID
     *
     * @param $character_id
     * @return mixed
     */
    public function getCharacterName($character_id)
    {
        if ($character_id == null)
            return null;

        $cache_key = "character_{$character_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        try {
            $char = $this->eseye->invoke('get', '/characters/{character_id}/', [
                'character_id' => $character_id
            ]);
        } catch(Exception $e) {
            return "Unknown Character";
        }

        // for unknown reasons the name is sometimes not set
        if (!isset($char->name)) {
            return "Unknown Error";
        }

        Cache::add($cache_key, $char->name, env('CACHE_TIME', 3264));

        return $char->name;
    }

    /**
     * Get a structure name based on the type
     *
     * @param $type
     * @param $id
     * @return mixed|string|null
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     * @throws ApiException
     */
    public function getLocationBasedOnStationType($type, $id)
    {
        switch($type)
        {
            case "structure":
                return $this->getStructureName($id);
                break;

            case "station":
                return $this->getStationName($id);
                break;

            default:
                return null;
        }
    }

    /**
     * Get the name of a structure
     * @param $structure_id
     * @return string
     * @throws ApiException
     */
    public function getStructureName($structure_id)
    {
        $cache_key = "structure_{$structure_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $model = new UniverseApi($this->client, $this->config);
        $res = $model->getUniverseStructuresStructureId($structure_id, $this->char_id)->getName();

        Cache::add($cache_key, $res, env('CACHE_TIME', 3264));
        return $res;
    }

    /**
     * Get a system name given the ID
     *
     * @param $system_id
     * @return mixed
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    public function getSystemName($system_id)
    {
        $cache_key = "system_{$system_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $res = $this->eseye->invoke('get', '/universe/systems/{system_id}/', [
            'system_id' => $system_id
        ]);

        Cache::add($cache_key, $res->name, env('CACHE_TIME', 3264));

        return $res->name;
    }

    /**
     * Get a region name, given the system ID
     * @param $system_id
     * @return mixed
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    public function getRegionName($system_id)
    {
        $cache_key = "system_region_{$system_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $system = $this->eseye->invoke('get', '/universe/systems/{system_id}/', [
            'system_id' => $system_id
        ]);
        $constellation = $this->eseye->invoke('get', '/universe/constellations/{constellation_id}/', [
            'constellation_id' => $system->constellation_id
        ]);
        $region = $this->eseye->invoke('get', '/universe/regions/{region_id}/', [
            'region_id' => $constellation->region_id
        ]);

        Cache::add($cache_key, $region->name, env('CACHE_TIME', 3264));

        return $region->name;
    }

    /**
     * Get a station name, given the ID
     *
     * @param $station_id
     * @return mixed
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     */
    public function getStationName($station_id)
    {
        $cache_key = "station_{$station_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $res = $this->eseye->invoke('get', '/universe/stations/{station_id}/', [
            'station_id' => $station_id
        ]);

        Cache::add($cache_key, $res->name, env('CACHE_TIME', 3264));

        return $res->name;
    }

    /**
     * Given a type ID, get its name
     *
     * @param $type_id
     * @return mixed
     */
    public function getTypeName($type_id)
    {
        $dbItem = Type::where('typeID', $type_id)->first();

        if (!$dbItem)
            return null;

        return $dbItem->typeName;
    }

    /**
     * Get the name of a group, given an item ID
     *
     * @param $typeId
     * @return Group|mixed
     */
    public function getGroupName($typeId)
    {
        $item = Type::where('typeID', $typeId)->first();

        if (!$item)
            return null;

        $group = Group::where('groupID', $item->groupID)->first();

        if (!$group)
            return null;

        return $group->groupName;
    }

    /**
     * Get a name from an ID from /universe/names
     *
     * @param $name_id
     * @return mixed|string|null
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidAuthenticationException
     * @throws InvalidContainerDataException
     * @throws RequestFailedException
     * @throws UriDataMissingException
     */
    public function getUnknownTypeName($name_id)
    {
        if (!$name_id)
            return null;

        if ($name_id == 2)
            return "Insurance";

        $cache_key = "universe_names_{$name_id}";

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        $res = $this->eseye->setBody([$name_id])->invoke('post', '/universe/names/');

        if (!$res)
            return null;

        $data = json_decode($res->raw);
        $name = $data[0]->name;
        Cache::add($cache_key, $name, env('CACHE_TIME', 3264));
        return $name;
    }

    /**
     * Lookup names from ESI
     *
     * @param $ids
     * @return array|mixed|EsiResponse
     */
    public function lookupNames($ids)
    {
        if (sizeof($ids) == 0)
            return [];

        $names = [];
        $ids = array_unique(array_column($ids, 'id'));
        $chunked_names = array_chunk($ids, 200);

        foreach ($chunked_names as $lookupChunk)
        {
            try {
                $res = $this->eseye->setBody($lookupChunk)->invoke('post', '/universe/names');
                $res = json_decode($res->raw);
                $names = array_merge($names, $res);
            } catch (\Exception $e) {
                // One of the IDs was invalid - step through them one by one
                foreach ($lookupChunk as $id) {
                    try {
                        $res = $this->eseye->setBody([$id])->invoke('post', '/universe/names');
                        $res = json_decode($res->raw);
                        $names = array_merge($names, $res);
                    } catch (Exception $e) {
                        // Found the invalid one - append it as an object in the proper format
                        $names[] = json_decode(json_encode([
                            'id' => $id,
                            'name' => 'Unknown ID (' . $id . ')',
                            'category' => 'unknown',
                        ]));
                    }
                }
            }
        }

        return $names;
    }

    /**
     * Get the cache key expiration seconds
     * Takes the array output of the model function *withHTTPInfo()
     * @param array $time
     * @return mixed
     */
    public function getCacheExpirationTime($time)
    {
        $time = $time[2]['Expires'][0];
        $time = Carbon::parse($time);
        return $time->diffInMinutes(now());
    }
}
