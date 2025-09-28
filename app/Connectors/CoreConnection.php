<?php

namespace App\Connectors;

use Illuminate\Support\Facades\Log;

class CoreConnection
{
    /**
     * Get the characters associated with a user ID
     *
     * @param $userId int The ID of the user
     * @return array|null JSON array of characters, or null if none were found
     */
    public static function getCharactersForUser($userId)
    {
        return self::generateWebRequest('/api/app/v1/characters/' . $userId);
    }

    /**
     * Get the account ID for a character
     *
     * @param $userId int The ID of the user
     * @return int The account ID
     */
    public static function getCharacterAccount($userId)
    {
        $output = self::generateWebRequest('/api/app/v1/player/' . $userId);
        return $output->id;
    }

    /**
     * Get the core groups associated with a userID
     *
     * @param $userId int The ID of the user
     * @return array|null JSON array of groups, or null if none were found
     */
    public static function getCharacterGroups($userId)
    {
        return self::generateWebRequest('/api/app/v2/groups/' . $userId);
    }

    /**
     * Get users removed from a core account
     *
     * @param $characterId
     * @return array|null
     */
    public static function getRemovedCharacters($characterId)
    {
        return self::generateWebRequest('/api/app/v1/removed-characters/' . $characterId);
    }

    /**
     * Get users moved from another core account to this account
     *
     * @param $characterId
     * @return array|null
     */
    public static function getAddedCharacters($characterId)
    {
        $output =  self::generateWebRequest('/api/app/v1/incoming-characters/' . $characterId);
        return is_array($output) ? $output : [];
    }

    /**
     * Get the main from the core account based on character ID
     *
     * @param $characterId
     * @return array|null
     */
    public static function getMainFromCharacterID($characterId)
    {
        $output = self::generateWebRequest('/api/app/v2/main/' . $characterId);
        return $output->id;
    }

    /**
     * Get an ESI Access Token for a given character
     *
     * @param $characterId
     * @return array|null
     */
    public static function getAccessTokenForCharacter($characterId)
    {
        return self::generateWebRequest('/api/app/v1/esi/access-token/' . $characterId);
    }

    /**
     * Generate the cURL request to core. Only used by class methods.
     *
     * @param string $url The URL to send the request to, in the form /path/to/endpoint
     * @return string|array|object|null JSON data returned from Core.
     */
    private static function generateWebRequest(string $url): string|array|object|null
    {
        $c = curl_init();

        $headers = ['Authorization: Bearer ' . base64_encode(env('CORE_APP_ID') . ':' . env('CORE_APP_SECRET'))];

        curl_setopt($c, CURLOPT_URL, env('CORE_URL') . $url);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($c);

        if (curl_errno($c)) {
            Log::warning(curl_error($c));
            return null;
        }

        curl_close($c);

        return json_decode($output);
    }
}
