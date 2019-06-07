@extends('default')
@section('content')
    <h1>My Group Ads</h1>
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card">
                <ul class="bg-dark list-group list-group-flush">
            @foreach($ads as $ad)
                @if(isset($permissions))
                    <a class="text-white" href="/group/ad/{{ $ad->id }}/permissions"><li class="bg-dark list-group-item">{{ $ad->group_name }}</li></a>
                @else
                    <a class="text-white" href="/group/ad/{{ $ad->id }}"><li class="bg-dark list-group-item">{{ $ad->group_name }}</li></a>
                @endif
            @endforeach
                </ul>
            </div>
        </div>
    </div>
@role('group admin')
    <a href="/group/ad/create"><button type="button" class="btn btn-primary">Create Ad</button></a>
@endrole
@endsection