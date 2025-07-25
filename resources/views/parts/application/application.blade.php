<div class="tab-pane fade show active" id="tab-application" role="tabpanel" aria-labelledby="tab-application">
    <div class="row justify-content-center">
        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Question Responses
                    </div>
                    <ul class="list-group">
                    @php
                        $questions = $questions->sortByDesc('created_at');
                        if (count($questions) > 0)
                            $previous = (new \DateTime($questions->first()->created_at))->format('Y-m-d H:i');
                    @endphp
                    @if(count($questions) > 0)
                        <hr />
                        <div class="list-group-item bg-dark text-white">{{ $previous }}</div>
                    @endif
                    @foreach($questions as $question)
                        @php($current = (new \DateTime($question->created_at))->format('Y-m-d H:i'))
                        @if ($current != $previous)
                            <hr />
                            @php($previous = $current)
                            <div class="list-group-item bg-dark text-white">
                                {{ $current }}
                            </div>
                        @endif
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
                            <ul class="list-group" id="warnings">
                                @if(!$character->has_valid_token)
                                    No valid token - Unable to load warnings
                                @endif
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
                                {{ \App\Models\Application::$state_names[$changelog->old_state] }} -> {{ \App\Models\Application::$state_names[$changelog->new_state] }}<br />
                                <small>{{ $changelog->account->main()->name }} - {{ $changelog->created_at }}</small>
                            </div>
                        @endforeach
                            <div class="list-group-item bg-dark text-white">
                                Applied<br />
                                <small>{{ $application->created_at }}</small>
                            </div>
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
                        <div class="card bg-dark" id="comment_{{ $comment->id }}">
                            <div class="card-header">
                                <img src="https://image.eveonline.com/Character/{{ $comment->account->main_user_id }}_32.jpg" />
                                {{ $comment->account->main()->name }}
                                <div class="float-right">
                                    {{ $comment->created_at }}
                                    <a class="text-danger" href="#" onclick="deleteComment({{ $comment->id }})"><span class="fa fa-times-circle"></span></a>
                                </div>
                            </div>
                            <div class="card-body">
                                {!! nl2br(e($comment->comment)) !!}
                            </div>
                        </div>
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
