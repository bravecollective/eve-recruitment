<?php

namespace App\Connectors;

class CoreConnection
{

    /**
     * Generate the cURL request to core. Only used by class methods
     * @param $url string The URL to send the request to, in the form /path/to/endpoint
     * @param $post [true|false] If the request should be transmitted as post. Defualt is false, which is GET.
     * @return array|null JSON data returned from core
     */
    private static function generateWebRequest($url, $post = false)
    {
        $c = curl_init();
        $headers = ['Authorization: Bearer ' . base64_encode(config('eve-recruitment.core_app_id') . ':' . config('eve-recruitment.core_app_secret'))];

        curl_setopt($c, CURLOPT_URL, config('eve-recruitment.core_url'). $url);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        if ($post === true)
            curl_setopt($c, CURLOPT_POST, 1);

        $output = curl_exec($c);
        curl_close($c);

        return json_decode($output);
    }

    /**
     * Get the characters associated with a user ID
     * @param $userId int The ID of the user
     * @return array|null JSON array of characters, or null if none were found
     */
    public static function getCharactersForUser($userId)
    {
        $output = self::generateWebRequest('/api/app/v1/characters/' . $userId);
        return $output;
    }

    /**
     * Get the account ID for a character
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
     * @param $userId int The ID of the user
     * @return array|null JSON array of groups, or null if none were found
     */
    public static function getCharacterGroups($userId)
    {
        $output = self::generateWebRequest('/api/app/v2/groups/' . $userId);
        return $output;
    }

    /**
     * Get users removed from a core account
     *
     * @param $characterId
     * @return array|null
     */
    public static function getRemovedCharacters($characterId)
    {
        $output = self::generateWebRequest('/api/app/v1/removed-characters/' . $characterId);
        return $output;
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
}
