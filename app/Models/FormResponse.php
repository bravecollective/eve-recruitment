<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\FormResponse
 *
 * @property int $id
 * @property int $account_id
 * @property int $question_id
 * @property int $application_id
 * @property string $response
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse whereApplicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormResponse whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FormResponse extends Model
{
    protected $table = 'form_response';

    /**
     * Save a user's question responses
     *
     * @param $application_id
     * @param $responses
     */
    public static function saveResponse($application_id, $responses)
    {
        if ($responses == null)
            return;

        foreach ($responses as $response)
        {
            $f = new FormResponse();
            $f->account_id = Auth::user()->id;
            $f->question_id = $response['id'];
            $f->application_id = $application_id;
            $f->response = $response['response'];
            $f->save();
        }
    }
}