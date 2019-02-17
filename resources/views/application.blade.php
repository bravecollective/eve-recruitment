@extends('default')
@section('content')

@include('parts/application/character_header', ['character' => $application->account->main()])

<select class="custom-select" style="width: 15%; margin-top: 1em;" autocomplete="off" onchange="updateStatus(this);">
@foreach($states as $id => $state)
    @if($application->status == $id)
        <option value="{{ $id }}" selected>{{ $state }}</option>
    @else
        <option value="{{ $id }}">{{ $state }}</option>
    @endif
@endforeach
</select>
<hr class="my-4">

@include('parts/application/question_row', ['questions' => $application->questions(), 'changelog' => $application->changelog, 'comments' => $application->comments])

@endsection
@section('scripts')
    <script type="text/javascript">
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
    </script>
@endsection