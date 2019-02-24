@extends('default')
@section('content')

@include('parts/application/character_header', ['character' => $application->account->main()])
<br />
<div class="row justify-content-center">
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
</div><br />
<div class="row justify-content-center">
    <div class="col-12">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item ml-2">
                <a class="nav-link active show" id="application-tab" data-toggle="pill" href="#tab-application" role="tab" aria-controls="tab-application" aria-selected="false">Application</a>
            </li>
            <li class="nav-item ml-2">
                <a class="nav-link" id="overview-tab" data-toggle="pill" href="#tab-overview" role="tab" aria-controls="tab-overview" aria-selected="true">Overview</a>
            </li>
        </ul>
    </div>
</div>
<hr class="my-4">
<div class="tab-content" id="tab-content">
    @include('parts/application/application', ['questions' => $application->questions(),
                                               'changelog' => $application->changelog,
                                               'comments' => $application->comments])
    @include('parts/application/overview')
</div>
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
        let comment_button = $('#add_comment_btn');
        let new_question_textarea = $('#new_question');
        let box_open = false;
        let application_id = "{{ $application->id }}";

        document.title = "{{ $application->account->main()->name }} - " + document.title;

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
    </script>
@endsection