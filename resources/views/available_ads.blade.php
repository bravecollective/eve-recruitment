@extends('default')
@section('content')
    <h1>Available Ads</h1>
    <div class="card" style="max-width: 25%;">
        <ul class="bg-dark list-group list-group-flush">
        @foreach($ads as $ad)
            <a class="text-white" href="/{{ $ad->slug }}" target="_blank"><li class="bg-dark list-group-item">{{ $ad->group_name }}</li></a>
        @endforeach
        </ul>
    </div>
@endsection