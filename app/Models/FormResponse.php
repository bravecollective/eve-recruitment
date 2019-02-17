<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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