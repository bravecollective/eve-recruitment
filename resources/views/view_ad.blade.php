@extends('default')
@section('content')
<h1>{{ $name }} Recruitment</h1>
<hr class="my-4" />
<input type="hidden" id="recruitment_id" value="{{ $id }}" />
@markdown($text)
@if(sizeof($questions) > 0)
    <hr class="my-4" />
    <h2>Recruitment Questions</h2>
    <form id="question_form">
    @foreach($questions as $question)
        <div class="form-group">
            <label for="{{ $question->id }}">{{ $question->question }}</label>
            <input type="text" class="form-control" id="{{ $question->id }}" />
        </div>
    @endforeach
    </form>
@endif
<button type="button" class="btn btn-success" id="apply-button" onclick="apply()">Apply</button>
<div class="d-inline-block align-middle" id="application-submitted" style="display: none !important;"><span class="fa fa-check"></span> Application submitted</div>
@endsection
@section('scripts')
    <script type="text/javascript">
        function apply()
        {
            let questions = $("input[type=text]");
            let responses = {
                _token: "{{ csrf_token() }}",
                questions: []
            };

            questions.each((idx, input_object) => responses['questions'].push({ 'id': input_object.id, 'response': input_object.value }));
            $.post('/recruitments/' + $("#recruitment_id").val() + '/apply', responses, function (e) {
                e = JSON.parse(e);
                if (e.success === true)
                {
                    $("#application-submitted").show();
                    $("#apply-button").hide();
                }
                else
                    showError(e.message);
            });
        }
    </script>
@endsection