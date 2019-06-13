@extends('default')
@section('content')
    <h1>{{ $ad->group_name }} Applications</h1>
    <hr class="my-4">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" id="open-tab" data-toggle="tab" role="tab" aria-controls="open" aria-selected="true" href="#open">Open</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="hold-tab" data-toggle="tab" role="tab" aria-controls="hold" aria-selected="false" href="#hold">Awaiting Information</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="accepted-tab" data-toggle="tab" role="tab" aria-controls="accepted" aria-selected="false" href="#accepted">Accepted</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="closed-tab" data-toggle="tab" role="tab" aria-controls="closed" aria-selected="false" href="#closed">Closed/Denied</a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="open" role="tabpanel" aria-labelledby="open-tab">
        @foreach($open_apps as $app)
            @include('parts/applicant_row', ['app' => $app])
        @endforeach
        </div>
        <div class="tab-pane fade" id="hold" role="tabpanel" aria-labelledby="hold-tab">
        @foreach($on_hold_apps as $app)
            @include('parts/applicant_row', ['app' => $app])
        @endforeach
        </div>
        <div class="tab-pane fade" id="accepted" role="tabpanel" aria-labelledby="accepted-tab">
        @foreach($accepted_apps as $app)
            @include('parts/applicant_row', ['app' => $app])
        @endforeach
        </div>
        <div class="tab-pane fade" id="closed" role="tabpanel" aria-labelledby="closed-tab">
        @foreach($closed_apps as $app)
            @include('parts/applicant_row', ['app' => $app])
        @endforeach
        </div>
    </div>
@endsection