@extends('default')
@section('content')

@include('parts/application/character_header', ['character' => $character])
<div class="row justify-content-center">
@if(isset($application))
    <div class="col-2">
        <select style="width: 100%;" class="custom-select" style="width: 15%; margin-top: 1em;" autocomplete="off" onchange="updateStatus(this);">
            @foreach($states as $id => $state)
                @if($application->status == $id)
                    <option value="{{ $id }}" selected>{{ $state }}</option>
                @else
                    <option value="{{ $id }}">{{ $state }}</option>
                @endif
            @endforeach
        </select>
    </div>
@endif
</div><br />
<div class="row justify-content-center">
    <div class="col-12">
        <ul class="nav nav-pills justify-content-center">
         @if(isset($application))
            <li class="nav-item ml-2">
                <a class="nav-link active show" id="application-tab" data-toggle="pill" href="#tab-application" role="tab" aria-controls="tab-application" aria-selected="true">
                    Application
                </a>
            </li>
            <li class="nav-item ml-2">
                <a class="nav-link" id="overview-tab" data-toggle="pill" href="#tab-overview" role="tab" aria-controls="tab-overview" aria-selected="false">
                    Overview
                    <span id="result-tab-overview" style="color: red;" class="fas fa-times"></span>
                </a>
            </li>
         @else
            <li class="nav-item ml-2">
                <a class="nav-link active show" id="overview-tab" data-toggle="pill" href="#tab-overview" role="tab" aria-controls="tab-overview" aria-selected="true">
                    Overview
                </a>
            </li>
         @endif
             <li class="nav-item ml-2">
                 <a class="nav-link" id="skills-tab" data-toggle="pill" href="#tab-skills" role="tab" aria-controls="tab-skills" aria-selected="false">
                     Skills
                     <span id="result-tab-skills" style="color: red;" class="fas fa-times"></span>
                 </a>
             </li>
             <li class="nav-item ml-2">
                 <a class="nav-link" id="mail-tab" data-toggle="pill" href="#tab-mail" role="tab" aria-controls="tab-mail" aria-selected="false">
                     Mail
                     <span id="result-tab-mail" style="color: red;" class="fas fa-times"></span>
                 </a>
             </li>
            <li class="nav-item ml-2">
                <a class="nav-link" id="assets-tab" data-toggle="pill" href="#tab-assets" role="tab" aria-controls="tab-assets" aria-selected="false">
                    Assets &amp; Journal
                    <span id="result-tab-assets" style="color: red;" class="fas fa-times"></span>
                </a>
            </li>
            <li class="nav-item ml-2">
                <a class="nav-link" id="market-tab" data-toggle="pill" href="#tab-market" role="tab" aria-controls="tab-market" aria-selected="false">
                    Market
                    <span id="result-tab-market" style="color: red;" class="fas fa-times"></span>
                </a>
            </li>
        </ul>
    </div>
</div><br />
<div class="row justify-content-center">
    <div class="col-auto">
        <button type="button" class="btn btn-primary" id="load-esi-button" onclick="loadEsiData();">Load ESI Data</button>
    </div>
</div>
<hr class="my-4">
<div class="tab-content" id="tab-content">
@if(isset($application))
    @include('parts/application/application', ['questions' => $application->questions(),
                                               'changelog' => $application->changelog,
                                               'comments' => $application->comments])
    <div class="tab-pane fade" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview"></div>
@else
    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview">
        @include('parts/application/overview')
    </div>
@endif
    <div class="tab-pane fade" id="tab-skills" role="tabpanel" aria-labelledby="tab-skills"></div>
    <div class="tab-pane fade" id="tab-mail" role="tabpanel" aria-labelledby="tab-mail"></div>
    <div class="tab-pane fade" id="tab-assets" role="tabpanel" aria-labelledby="tab-assets"></div>
    <div class="tab-pane fade" id="tab-market" role="tabpanel" aria-labelledby="tab-market"></div>
@endsection
@section('styles')
    <style>
        .nav-pills .nav-link {
            background-image: -webkit-gradient(linear, left top, left bottom, from(#484e55), color-stop(60%, #3A3F44), to(#313539));
            background-image: linear-gradient(#484e55, #3A3F44 60%, #313539);
            background-repeat: no-repeat;
            -webkit-filter: none;
            filter: none;
            border: 1px solid rgba(0,0,0,0.6);
            text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
            color: #fff;
        }

        .nav-pills .nav-link.active, .nav-pills .nav-link:hover {
            background-color: transparent;
            background-image: -webkit-gradient(linear, left top, left bottom, from(#101112), color-stop(40%, #17191b), to(#1b1e20));
            background-image: linear-gradient(#101112, #17191b 40%, #1b1e20);
            background-repeat: no-repeat;
            -webkit-filter: none;
            filter: none;
            border: 1px solid rgba(0, 0, 0, 0.6);
        }
    </style>
@endsection
@section('scripts')
    <script type="text/javascript">
        document.title = "{{ $character->name }} - " + document.title;
        let esiLoaded = false;

        function loadPartial(url, anchor, additionalFunction = null)
        {
            $.get(url, function(e) {
                e = JSON.parse(e);
                if (e.success === true)
                {
                    $("#" + anchor).html(e.message);

                    let res = $("#result-" + anchor);
                    res.attr('style', 'color: green;');
                    res.removeClass('fa-times');
                    res.addClass('fa-check');

                    if (additionalFunction)
                        additionalFunction();
                }
                else
                    showError(e.message);
            });
        }

        function loadEsiData() {
            if (esiLoaded)
            {
                showError('ESI already loaded. To see updated ESI, reload the page and click the button again.');
                return;
            }

            esiLoaded = true;
            let char_id = "{{ $character->character_id }}";

            @if(isset($application))
                loadPartial('/api/esi/' + char_id + '/overview', 'tab-overview');
            @endif
            loadPartial('/api/esi/' + char_id + '/skills', "tab-skills");
            loadPartial('/api/esi/' + char_id + '/mail', "tab-mail");
            loadPartial('/api/esi/' + char_id + '/assets_journal', "tab-assets", () => $("#journal-table").DataTable({"order": [[0, "desc"]]}));
            loadPartial('/api/esi/' + char_id + '/market', "tab-market");
        }

    @if(isset($application))
        let comment_button = $('#add_comment_btn');
        let new_question_textarea = $('#new_question');
        let box_open = false;
        let application_id = "{{ $application->id }}";

        function updateStatus(f)
        {
            let data = {
                _token: "{{ csrf_token() }}",
                state: f.value,
            };

            $.post('/application/' + application_id + '/state/update', data, function (e) {
                e = JSON.parse(e);

                if (e.success === true)
                    showInfo(e.message);
                else
                    showError(e.message);
            });
        }

        function deleteComment(id)
        {
            if (!confirm("Are you sure you wish to delete this comment?"))
                return;

            let data = {
                _token: "{{ csrf_token() }}",
                comment_id: id
            };

            $.post('/application/' + application_id + '/comments/delete', data, function(e) {
                e = JSON.parse(e);

                if (e.success === true)
                {
                    showInfo(e.message);
                    $("#comment_" + id).remove();
                }
                else
                    showError(e.message);
            });
        }

        function handleCommentButtonClick()
        {
            if (box_open === false)
            {
                new_question_textarea.show();
                comment_button.text('Save');
                box_open = true;
            }
            else
            {
                let data = {
                    _token: "{{ csrf_token() }}",
                    comment: new_question_textarea.val()
                };

                $.post('/application/' + application_id + '/comments/add', data, function (e) {
                    e = JSON.parse(e);

                    if (e.success === true)
                    {
                        new_question_textarea.val('');
                        new_question_textarea.hide();
                        comment_button.text('Add');
                        box_open = false;
                        $("#comments").append(e.message);
                        showInfo('Comment saved');
                    }
                    else
                        showError(e.message);
                });
            }
        }
    @endif

        function toggleCollapse(anchor)
        {
            $('#' + anchor).collapse('toggle');
        }
    </script>
@endsection