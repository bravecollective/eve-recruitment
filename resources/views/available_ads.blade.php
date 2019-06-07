@extends('default')
@section('content')
    <h1>Available Ads</h1>
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card">
                <ul class="bg-dark list-group list-group-flush">
                @foreach($ads as $ad)
                    <a class="text-white" href="/{{ $ad->slug }}" target="_blank"><li class="bg-dark list-group-item">{{ $ad->group_name }}</li></a>
                @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection