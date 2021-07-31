<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Group
 *
 * @property int $groupID
 * @property int|null $categoryID
 * @property string|null $groupName
 * @property int|null $iconID
 * @property int|null $useBasePrice
 * @property int|null $anchored
 * @property int|null $anchorable
 * @property int|null $fittableNonSingleton
 * @property int|null $published
 * @method static \Illuminate\Database\Eloquent\Builder|Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group query()
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereAnchorable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereAnchored($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereCategoryID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereFittableNonSingleton($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereGroupID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereGroupName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereIconID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group wherePublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereUseBasePrice($value)
 * @mixin \Eloquent
 */
class Group extends Model
{
    protected $table = 'invGroups';
    public $timestamps = false;
}