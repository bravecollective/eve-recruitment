<?php

namespace App\Models;

use App\Connectors\CoreConnection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\User
 *
 * @property int $account_id
 * @property int $character_id
 * @property string $name
 * @property int $corporation_id
 * @property string $corporation_name
 * @property int|null $alliance_id
 * @property string|null $alliance_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $core_account_id
 * @property int $has_valid_token
 * @property-read Account $account
 * @property-read Collection|AccountGroup[] $groups
 * @property-read int|null $groups_count
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereAccountId($value)
 * @method static Builder|User whereAllianceId($value)
 * @method static Builder|User whereAllianceName($value)
 * @method static Builder|User whereCharacterId($value)
 * @method static Builder|User whereCoreAccountId($value)
 * @method static Builder|User whereCorporationId($value)
 * @method static Builder|User whereCorporationName($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereHasValidToken($value)
 * @method static Builder|User whereName($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'character_id';
    public $incrementing = false;

    /**
     * Update account when ESI is viewed
     *
     * @param $char_id
     */
    public static function updateUsersOnApplicationLoad($char_id)
    {
        $core_users = CoreConnection::getCharactersForUser($char_id);
        $main = null;

        if ($core_users == null)
            return;

        foreach ($core_users as $user)
        {
            if ($user->main == true)
                $main = $user;
        }

        self::addUsersToDatabase($core_users, $main);
    }

    /**
     * Insert or update users in the database
     * @param $users array JSON array of users
     * @param $main User Main user on the account
     */
    public static function addUsersToDatabase($users, $main)
    {
        $core_account_id = CoreConnection::getCharacterAccount($users[0]->id);

        $account = Account::where('core_account_id', $core_account_id)->first();
        $account = ($account == null) ? new Account() : $account;

        $new_user_ids = [];
        $old_accounts = [];

        $account->main_user_id = $main->id;
        $account->core_account_id = $core_account_id;
        $account->save();

        foreach ($users as $user)
        {
            $dbUser = User::where('character_id', $user->id)->first();

            if (!$dbUser)
                $dbUser = new User();
            else if ($dbUser->core_account_id != $account->core_account_id)
                $old_accounts[] = $dbUser->account_id; // Used to check for orphaned accounts. This char switched accounts

            $dbUser->account_id = $account->id;
            $dbUser->core_account_id = $core_account_id;
            $dbUser->name = $user->name;
            $dbUser->character_id = $user->id;
            $dbUser->corporation_id = $user->corporation->id;
            $dbUser->corporation_name = $user->corporation->name;
            $dbUser->has_valid_token = $user->validToken;

            if ($user->corporation->alliance !== null)
            {
                $dbUser->alliance_id = $user->corporation->alliance->id;
                $dbUser->alliance_name = $user->corporation->alliance->name;
            }
            else
                $dbUser->alliance_id = $dbUser->alliance_name = null;

            $dbUser->save();
            $new_user_ids[] = $dbUser->character_id;
        }

        // Delete old characters from this account
        User::where('core_account_id', $account->core_account_id)->whereNotIn('character_id', $new_user_ids)->delete();

        // Delete potentially orphaned accounts
        foreach ($old_accounts as $old_account_id)
        {
            $users = User::where('account_id', $old_account_id)->get();

            if (count($users) == 0) {
                $applications = Application::where('account_id', $old_account_id)->get();
                foreach ($applications as $application) {
                    $application->account_id = $account->id;
                    $application->save();
                }

                $form_responses = FormResponse::where('account_id', $old_account_id)->get();
                foreach ($form_responses as $response) {
                    $response->account_id = $account->id;
                    $response->save();
                }

                $changelogs = ApplicationChangelog::where('account_id', $old_account_id)->get();
                foreach ($changelogs as $changelog) {
                    $changelog->account_id = $account->id;
                    $changelog->save();
                }

                Account::where('core_account_id', $old_account_id)->delete();
            }
            else
            {
                $account = Account::where('core_account_id', $old_account_id)->first();
                $account->main_user_id = 0;
                $account->save();
            }
        }
    }

    /**
     * Get users on an account
     * @param $accountId int The account ID
     * @return User[]|null Found users or null
     */
    public static function getUsers($accountId)
    {
        return User::where('account_id', $accountId)->get();
    }

    /**
     * Given a character ID, get the account id
     * @param $userId int the user id
     * @return User|null User object, or null if doesn't exist
     */
    public static function getAccountIdForUserId($userId)
    {
        $user = User::where('character_id', $userId)->first();

        if (!$user)
            return null;
        else
            return $user->account_id;
    }

    /**
     * Get the members of a corporation given the corporation's ID
     * @param $corpId int Corp ID to get members of
     * @return LengthAwarePaginator Corporation members
     */
    public static function getCorpMembers($corpId, $perPage = 50)
    {
        return User::where('corporation_id', $corpId)->orderBy('name')->paginate($perPage);
    }

    /**
     * Entity relationship
     * @return HasMany
     */
    public function groups()
    {
        return $this->hasMany('App\Models\AccountGroup');
    }

    /**
     * Entity relationship
     */
    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'account_id', 'id');
    }

}
