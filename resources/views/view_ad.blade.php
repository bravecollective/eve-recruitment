@extends('default')
@section('content')
<h1>{{ $name }} Recruitment</h1>
<hr class="my-4" />
@markdown($text)
@if(sizeof($questions) > 0)
    <hr class="my-4" />
    <h2>Recruitment Questions</h2>
    <form>
    @foreach($questions as $question)
        <div class="form-group">
            <label for="question_{{ $question->id }}">{{ $question->question }}</label>
            <input type="text" class="form-control" id="question_{{ $question->id }}" />
        </div>
    @endforeach
    </form>
@endif
<button type="button" class="btn btn-success">Apply</button>
@endsection