<div class="row card bg-dark" id="comment_{{ $comment->id }}">
    <div class="card-header">
        <img src="https://image.eveonline.com/Character/{{ $comment->account->main_user_id }}_32.jpg" />
        {{ $comment->account->main()->name }}
        <div class="float-right">
            {{ $comment->created_at }}
            <a class="text-danger" href="#" onclick="deleteComment({{ $comment->id }})"><span class="fa fa-times-circle"></span></a>
        </div>
    </div>
    <div class="card-body">
        {{ $comment->comment }}
    </div>
</div>