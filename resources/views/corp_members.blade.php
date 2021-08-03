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

    {{ $corpMembers->links() }}

    <form action="/character/add" method="post">
        <hr>
        <h5>Add missing character from Core</h5>
        <div class="form-group">
            <label for="id">Character ID</label>
            <input class="form-control" type="text" name="id">
        </div>
        <input type="hidden" id="_token" name="_token" value="{{ csrf_token() }}" />
        <button type="submit" class="btn btn-primary">Add</button>
    </form>

@endsection
