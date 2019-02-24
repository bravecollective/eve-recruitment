<div class="tab-pane fade show active" id="tab-application" role="tabpanel" aria-labelledby="tab-application">
    <div class="row">
        <div class="col-4">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Question Responses
                    </div>
                    <ul class="list-group">
                    @foreach($questions as $question)
                        <div class="list-group-item bg-dark text-white">
                            <h4>{{ $question->question }}</h4>
                            <p>{{ $question->response }}</p>
                        </div>
                    @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-2">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Warnings
                    </div>
                    <ul class="list-group">
                    @foreach($warnings as $warning)
                        <div class="list-group-item bg-dark text-white">
                            {{ $warning }}
                        </div>
                    @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-2">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Changelog
                    </div>
                    <ul class="list-group">
                        @foreach($changelog->reverse() as $changelog)
                            <div class="list-group-item bg-dark text-white">
                                <p>{{ \App\Models\Application::getStringForState($changelog->old_state) }} -> {{ \App\Models\Application::getStringForState($changelog->new_state) }}</p>
                                <p><small>{{ $changelog->account->main()->name }} - {{ $changelog->created_at }}</small></p>
                            </div>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Comments
                    </div>
                    <div class="list-group">
                        <div id="comments">
                            @foreach($comments as $comment)
                                @include('parts/application/comment', ['comment' => $comment])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <textarea id="new_question" class="form-control" style="display: none;" placeholder="Enter comment..."></textarea>
                <button type="button" class="btn btn-primary" id="add_comment_btn" onclick="handleCommentButtonClick()">Add</button>
            </div>
        </div>
    </div>
</div>