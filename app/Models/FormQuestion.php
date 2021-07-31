<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FormQuestion
 *
 * @property int $id
 * @property int $recruitment_id
 * @property string $question
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FormQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FormQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FormQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder|FormQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormQuestion whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormQuestion whereRecruitmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormQuestion whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FormQuestion extends Model
{
    protected $table = 'form';
}