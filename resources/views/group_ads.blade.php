@extends('default')
@section('content')
    <h1>My Group Ads</h1>
    <div class="card" style="max-width: 25%;">
        <ul class="bg-dark list-group list-group-flush">
        @foreach($ads as $ad)
                <a class="text-white" href="/group/ad/{{ $ad->id }}"><li class="bg-dark list-group-item">{{ $ad->group_name }}</li></a>
        @endforeach
        </ul>
    </div>

    <a href="/group/ad/create"><button type="button" class="btn btn-primary">Create Ad</button></a>
@endsection