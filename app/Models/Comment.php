<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Comment extends Model
{
    protected $table = 'comment';

    /**
     * Create a new comment
     * @param $application_id
     * @param $new_comment
     * @return mixed
     */
    public static function addComment($application_id, $new_comment)
    {
        $comment = new Comment();
        $comment->application_id = $application_id;
        $comment->account_id = Auth::user()->id;
        $comment->comment = $new_comment;
        $comment->save();

        return $comment;
    }

    /**
     * Account relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne('App\Models\Account', 'id', 'account_id');
    }
}