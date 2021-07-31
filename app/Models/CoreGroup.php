<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CoreGroup
 *
 * @property int $id
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|CoreGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CoreGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CoreGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|CoreGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CoreGroup whereName($value)
 * @mixin \Eloquent
 */
class CoreGroup extends Model
{
    protected $table = 'group';
    public $timestamps = false;
}