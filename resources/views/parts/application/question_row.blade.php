<div class="row">
    <div class="col-3">
        <div class="row">
            <h2>Question Responses</h2>
        </div>
    @foreach($questions as $question)
        <div class="row">
            <h3>{{ $question->question }}</h3>
        </div>
        <div class="row">
            {{ $question->response }}
        </div>
    @endforeach
    </div>
    <div class="col-2">
        <div class="row">
            <h2>Warnings</h2>
        </div>
    @foreach($warnings as $warning)
        <div class="row">
            <li>{{ $warning }}</li>
        </div>
    @endforeach
    </div>
    <div class="col-2">
        <div class="row">
            <h2>Changelog</h2>
        </div>
        @foreach($changelog->reverse() as $changelog)
        <div class="row">
            {{ \App\Models\Application::getStringForState($changelog->old_state) }} -> {{ \App\Models\Application::getStringForState($changelog->new_state) }}
        </div>
        <div class="row">
            <small>{{ $changelog->account->main()->name }} - {{ $changelog->created_at }}</small>
        </div><br />
        @endforeach
    </div>
    <div class="col-2">
        <div class="row">
            <h2>Comments</h2>
        </div>
        <div id="comments">
        @foreach($comments as $comment)
            @include('parts/application/comment', ['comment' => $comment])
        @endforeach
        </div>
        <div class="row">
            <textarea id="new_question" class="form-control" style="display: none;" placeholder="Enter comment..."></textarea>
            <button type="button" class="btn btn-primary" id="add_comment_btn" onclick="handleCommentButtonClick()">Add</button>
        </div>
    </div>
</div>