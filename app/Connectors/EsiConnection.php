<?php

namespace App\Connectors;

use Seat\Eseye\Eseye;
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
}