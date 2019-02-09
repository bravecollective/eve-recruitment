@extends('default')
@section('content')
    <h1>{{ $title }} Recruitment Ad</h1>
    @if($title == 'Group')
    <form method='POST' action="/group/ad/save" id="corpAdForm">
    @else
    <form method='POST' action="/corp/ad/save" id="corpAdForm">
    @endif
        <input type="hidden" value="{{ $ad->id }}" id="ad_id" name="ad_id" />
        <input type="hidden" id="_token" name="_token" value="{{ csrf_token() }}" />
    @if($title == 'Group')
        <div class="form-group">
            <label for="name">Group Name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Name..." value="{{ $ad->group_name }}" />
        </div>
    @endif
        <div class="form-group">
            <label for="slug">Page Slug</label>
            <input type="text" class="form-control" id="slug" name="slug" placeholder="Slug..." value="{{ $ad->slug }}" />
        </div>
        <div class="form-group">
            <label for="text">Ad Text</label>
            <textarea class="form-control" rows="10" id="text" name="text" placeholder="Ad Text...">{{ $ad->text }}</textarea>
            <small id="textInfo" class="form-text">Markdown is supported.</small>
        </div>
        <div class="form-group">
            <label>Form Questions</label>
            <div class="questions">
            @foreach($questions as $question)
                <div class="form-group">
                    <input type="text" class="form-control" value="{{ $question->question }}" name="questions[{{ $question->id }}][]" id="questions[{{ $question->id }}][]"/>
                </div>
            @endforeach
            </div>
            <button type="button" class="btn btn-success" onClick="addQuestion();"><span class="fa fa-plus"></span></button>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
@endsection
@section('scripts')
    let counter = 1;

    function addQuestion() {
        let toAppend = "<div class='form-group'> \
            <input type='text' class='form-control' placeholder='Question " + counter + "' name='questions[0][]' id='questions[0][]'> \
        </div>";
        $(".questions").append(toAppend);
        counter++;
    }
@endsection