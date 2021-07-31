<?php

namespace App\Models;

use App\Models\Permission\HasPermissionTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * App\Models\Account
 *
 * @property int $id
 * @property int $main_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $core_account_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $characters
 * @property-read int|null $characters_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Permission\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCoreAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereMainUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Account extends Authenticatable
{
    use HasPermissionTrait;

    protected $table = 'account';

    /**
     * Entity relationship
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function characters()
    {
        return $this->hasMany('App\Models\User', 'account_id');
    }

    /**
     * Overrides the method to ignore the remember token.
     */
    public function setAttribute($key, $value)
    {
        $isRememberTokenAttribute = $key == $this->getRememberTokenName();

        if (!$isRememberTokenAttribute)
            parent::setAttribute($key, $value);
    }

    /**
     * Get the main user name
     *
     * @return mixed
     */
    public function main()
    {
        return User::find($this->main_user_id);
    }

    /**
     * Get an accounts alts
     *
     * @return mixed
     */
    public function alts()
    {
        return User::where('account_id', $this->id)->where('character_id', '!=', $this->main_user_id)->get();
    }
}
