<?php
namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class EveOnlineSocialiteProvider extends AbstractProvider implements ProviderInterface
{

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            'https://login.eveonline.com/v2/oauth/authorize',
            $state
        );
    }

    protected function getTokenUrl(): string
    {
        return (string)Config::get('services.eveonline.token_url');
    }

    /**
     * @throws \Exception
     */
    protected function getUserByToken($token): array
    {
        $payload = json_decode(
            base64_decode(
                str_replace(
                    '_',
                    '/',
                    str_replace(
                        '-',
                        '+',
                        explode('.', $token)[1]
                    )
                )
            )
        );

        if ($payload->iss !== "https://login.eveonline.com") {
            // Potential attack
            throw new \Exception("Invalid JWT issuer");
        }

        $scopes = isset($payload->scp) ?
            (is_string($payload->scp) ? $payload->scp : implode(' ', $payload->scp)) :
            '';

        return [
            'CharacterID' => (int) str_replace('CHARACTER:EVE:', '', $payload->sub),
            'CharacterName' => $payload->name,
            'ExpiresOn' => gmdate('Y-m-d\TH:i:s', $payload->exp),
            'Scopes' => $scopes,
            #'TokenType' => 'Character',
            'CharacterOwnerHash' => $payload->owner,
            #'IntellectualProperty' => 'EVE',
        ];
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['CharacterID'],
            'name' => $user['CharacterName'],
            'owner_hash' => $user['CharacterOwnerHash'],
            'avatar' => 'https://image.eveonline.com/Character/' . $user['CharacterID'] . '_128.jpg',
        ]);
    }

    /**
     * @param string $code
     */
    protected function getTokenFields($code): array
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
