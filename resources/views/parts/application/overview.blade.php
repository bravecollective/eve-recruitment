<div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview">
    <div class="row">
        <div class="col-4">
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
        <div class="col-2 esi-container">
            <div class="row">
                <h2>Warnings</h2>
            </div>
            @foreach($warnings as $warning)
                <div class="row">
                    <li>{{ $warning }}</li>
                </div>
            @endforeach
        </div>
        <div class="col-2 esi-container">
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
        <div class="col-3 esi-container">
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
    <hr class="my-4">
    <div class="row justify-content-center">
        <h2>Corporation History</h2>
    </div>
    @foreach($corp_history as $corp)
    <div class="row justify-content-center">
        <div class="col-1">
            <img src="https://image.eveonline.com/Corporation/{{ $corp->corporation_id }}_32.png" />
            {{ $corp->corporation_name }}
        </div>
    @if ($corp->alliance_id != null)
        <div class="col-1">
            <img src="https://image.eveonline.com/Alliance/{{ $corp->alliance_id }}_32.png" />
            {{ $corp->alliance_name }}
        </div>
    @else
        <div class="col-1"></div>
    @endif
        <div class="col-2">
            Joined: {{ $corp->start_date }}
        </div>
    </div>
    <hr>
    @endforeach
</div>