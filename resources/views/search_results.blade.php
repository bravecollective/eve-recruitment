@extends('default')
@section('content')
    <h2>Search Results</h2>
    <hr class="my-4">
@foreach($results as $result)
    <img src="https://image.eveonline.com/Character/{{ $result->character_id }}_32.jpg">&nbsp;
    <a style="color: white;" href="/character/{{ $result->character_id }}" target="_blank">{{ $result->name }}</a><br /><br />
@endforeach
@endsection