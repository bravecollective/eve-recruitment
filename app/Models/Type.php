<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Type
 *
 * @property int $typeID
 * @property int|null $groupID
 * @property string|null $typeName
 * @property string|null $description
 * @property float|null $mass
 * @property float|null $volume
 * @property float|null $capacity
 * @property int|null $portionSize
 * @property int|null $raceID
 * @property string|null $basePrice
 * @property int|null $published
 * @property int|null $marketGroupID
 * @property int|null $iconID
 * @property int|null $soundID
 * @property int|null $graphicID
 * @method static \Illuminate\Database\Eloquent\Builder|Type newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Type newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Type query()
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereGraphicID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereGroupID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereIconID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereMarketGroupID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereMass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type wherePortionSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type wherePublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereRaceID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereSoundID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereTypeID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereTypeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Type whereVolume($value)
 * @mixin \Eloquent
 */
class Type extends Model
{
    protected $table = 'invTypes';
    public $timestamps = false;
}