@extends('default')
@section('content')
    <h1>{{ $ad->group_name }} Applications</h1>
    <hr class="my-4">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" id="open-tab" data-toggle="tab" role="tab" aria-controls="open" aria-selected="true" href="#open">
                Open ({{ count($open_apps) }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="review-requested-tab" data-toggle="tab" role="tab" aria-controls="review-requested" aria-selected="true" href="#review-requested">
                Review Requested ({{ $review_requested_apps }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="hold-tab" data-toggle="tab" role="tab" aria-controls="hold" aria-selected="false" href="#hold">
                Awaiting Information ({{ $on_hold_apps }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="trial-tab" data-toggle="tab" role="tab" aria-controls="trial" aria-selected="false" href="#trial">
                Trial ({{ $trial_apps }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="accepted-tab" data-toggle="tab" role="tab" aria-controls="accepted" aria-selected="false" href="#accepted">
                Accepted ({{ $accepted_apps }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="closed-tab" data-toggle="tab" role="tab" aria-controls="closed" aria-selected="false" href="#closed">
                Closed/Denied ({{ $closed_apps }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="in-progress-tab" data-toggle="tab" role="tab" aria-controls="in-progress" aria-selected="false" href="#in-progress">
                In Progress ({{ $in_progress_apps }})
            </a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="open" role="tabpanel" aria-labelledby="open-tab">
            <div id="open-content">@include('application_ajax_page', ['apps' => $open_apps])</div>
        </div>
        <div class="tab-pane fade" id="review-requested" role="tabpanel" aria-labelledby="review-requested-tab">
            <div id="review-requested-content"></div>
        </div>
        <div class="tab-pane fade" id="hold" role="tabpanel" aria-labelledby="hold-tab">
            <div id="hold-content"></div>
        </div>
        <div class="tab-pane fade" id="trial" role="tabpanel" aria-labelledby="trial-tab">
            <div id="trial-content"></div>
        </div>
        <div class="tab-pane fade" id="accepted" role="tabpanel" aria-labelledby="accepted-tab">
            <div id="accepted-content"></div>
        </div>
        <div class="tab-pane fade" id="closed" role="tabpanel" aria-labelledby="closed-tab">
            <div id="closed-content"></div>
        </div>
        <div class="tab-pane fade" id="in-progress" role="tabpanel" aria-labelledby="in-progress-tab">
            <small>Note: These applications are currently being worked on by another recruiter. Do not touch the applications without asking the recruiter first.</small>
            <div id="in-progress-content"></div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr("href");
            $(target + '-content').html("<h3>Loading...</h3>");
            $.post(
                '/applications/{{ $ad->id }}',
                {
                    type: target,
                    _token: "{{ csrf_token() }}"
                },
                (res) => $(target + '-content').html(res)
            );
        });
    </script>
@endsection