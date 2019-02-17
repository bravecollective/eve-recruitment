@extends('default')
@section('content')
    <h1>{{ Auth::user()->corporation_name }} Members</h1>
    <hr class="my-4">
    @foreach($corpMembers as $member)
        <p>
            <img src="https://image.eveonline.com/Character/{{ $member->character_id }}_32.jpg">
            <a class="text-white" href="/character/{{ $member->character_id }}" target="_blank">{{ $member->name }}</a>
        </p>
    @endforeach
@endsection