<?php

namespace App\Connectors;

use Curl;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;
use Seat\Eseye\Cache\NullCache;
use App\User;

/**
 * Generic class for use by controllers or queued jobs that need to request information
 * from the ESI API.
 *
 * Source: https://github.com/matthewpennell/moon-mining-manager/blob/master/app/Classes/EsiConnection.php
 */
class EsiConnection
{
    public $esi; // Eseye object for performing all ESI requests
    public $character_id; // reference to the prime user's character ID
    public $corporation_id; // reference to the prime user's corporation ID
    public $token; // reference to the renewed token, needed by the raw curl check for X-Pages header

    /**
     * Class constructor. Create an ESI API object to handle all requests.
     *
     * @param $needKey bool Specify if the `ESI_PRIME_USER_ID`'s token needs to be refreshed
     */
    public function __construct($needKey = true)
    {
        // Set config datasource using environment variable.
        $configuration = Configuration::getInstance();
        $configuration->logfile_location = env('ESI_LOG_PATH', '');
        $configuration->cache = NullCache::class; // TODO: Use Redis

        if (!$needKey) {
            $this->esi = new Eseye();
            return;
        }

        // Create authentication with app details and refresh token from nominated prime user.
        $user = User::where('eve_user_id', env('ESI_PRIME_USER_ID', 0))->first();

        if (!$user)
            die('Please ensure <pre>ESI_PRIME_USER_ID</pre> is set to a valid ESI user in .env');

        $url = 'https://login.eveonline.com/oauth/token';
        $secret = env('APP_SECRET');
        $client_id = env('APP_ID');

        // Need to request a new valid access token from EVE SSO using the refresh token of the original request.
        $response = Curl::to($url)
            ->withData(array(
                'grant_type' => "refresh_token",
                'refresh_token' => $user->refreshToken
            ))
            ->withHeaders(array(
                'Authorization: Basic ' . base64_encode($client_id . ':' . $secret)
            ))
            //->enableDebug('logFile.txt')
            ->post();

        $new_token = json_decode($response);

        if (isset($new_token->refresh_token)) {
            $user->refreshToken = $new_token->refresh_token;
            $user->save();
        }

        $authentication = new EsiAuthentication([
            'secret' => $secret,
            'client_id' => $client_id,
            'access_token' => $new_token->access_token,
            'refresh_token' => $user->refreshToken,
            'scopes' => [
                'esi-characters.read_titles.v1',
                'esi-assets.read_corporation_assets.v1',
            ],
            'token_expires' => date('Y-m-d H:i:s', time() + $new_token->expires_in),
        ]);

        // Create ESI API object
        $this->esi = new Eseye($authentication);

        // Retrieve the prime user's character details
        $character = $this->esi->invoke('get', '/characters/{character_id}/', [
            'character_id' => $user->id,
        ]);

        // Set object variables for use by other classes
        $this->character_id = $user->id;
        $this->corporation_id = $character->corporation_id;
        $this->token = $new_token->access_token;
    }
}