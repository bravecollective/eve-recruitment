<div class="tab-pane fade show active" id="tab-application" role="tabpanel" aria-labelledby="tab-application">
    <div class="row justify-content-center">
        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
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
        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-12">
            <div class="row">
                <div class="col-12">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <div class="card-header">
                                Alts
                            </div>
                            <ul class="list-group">
                                @foreach($alts as $alt)
                                    <a href="/character/{{ $alt->character_id }}" target="_blank">
                                        <div class="list-group-item bg-dark text-white">
                                            <img src="https://image.eveonline.com/Character/{{ $alt->character_id }}_32.jpg" />
                                            {{ $alt->name }}
                                        </div>
                                    </a>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
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
            </div>
        </div>
        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-12">
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
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Comments
                    </div>
                    <div id="comments">
                    @foreach($comments as $comment)
                        @include('parts/application/comment', ['comment' => $comment])
                    @endforeach
                    </div>
                </div>
                <div class="card-footer">
                    <textarea id="new_question" class="form-control" style="display: none;" placeholder="Enter comment..."></textarea>
                    <button type="button" class="btn btn-primary" id="add_comment_btn" onclick="handleCommentButtonClick()">Add</button>
                </div>
            </div>
        </div>
    </div>
</div>