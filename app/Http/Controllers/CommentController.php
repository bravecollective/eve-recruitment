<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Comment;
use App\Models\Permission\AccountRole;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Add a comment to an application
     *
     * @param $id
     * @throws \Throwable
     */
    public function addComment(Request $r, $id)
    {
        $application = Application::find($id);
        $comment = $r->input('comment');

        if (!$application)
            die(json_encode(['success' => false, 'message' => 'Invalid application ID']));

        $ad = $application->recruitmentAd;

        if (!AccountRole::canViewApplications($ad))
            return die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        if (!$comment)
            die(json_encode(['success' => false, 'message' => 'Comment is required']));

        $comment = Comment::addComment($id, $comment);

        die(json_encode(['success' => true, 'message' => view('parts/application/comment', ['comment' => $comment])->render()]));
    }

    /**
     * Delete a comment
     *
     * @param $id
     */
    public function deleteComment(Request $r, $id)
    {
        $application = Application::find($id);
        $comment_id = $r->input('comment_id');

        if (!$application)
            die(json_encode(['success' => false, 'message' => 'Invalid application ID']));

        $ad = $application->recruitmentAd;

        if (!AccountRole::canViewApplications($ad))
            return die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        if (!$comment_id)
            die(json_encode(['success' => false, 'message' => 'Comment is required']));

        Comment::find($comment_id)->delete();

        die(json_encode(['success' => true, 'message' => 'Comment deleted']));

    }
}
