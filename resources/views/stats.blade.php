@extends('default')
@section('content')
    <h1>{{ $name }} Stats</h1>
    <form class="form">
        <input type="hidden" name="ad_id" value="{{ $ad_id }}" />
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
        <div class="row">
            <div class="col-12 col-sm-5 col-lg-3">
                <select class="custom-select" name="start_state">
                    @foreach($states as $state)
                        <option value="{{ $state }}">{{ $state }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                to
            </div>
            <div class="col-12 col-sm-5 col-lg-3">
                <select class="custom-select" name="end_state">
                    @foreach($states as $state)
                        <option value="{{ $state }}">{{ $state }}</option>
                    @endforeach
                </select>
            </div>
        </div><br />
        <div class="row">
            <div class="col-12 col-sm-5 col-lg-3">
                <input type="date" class="form-control" name="start_date">
            </div>
            <div class="col-auto">
                to
            </div>
            <div class="col-12 col-sm-5 col-lg-3">
                <input type="date" class="form-control" name="end_date">
            </div>
        </div><br />
        <button type="submit" class="btn btn-success">Lookup</button>
    </form>
    <div id="stats-data"></div>
@endsection
@section('scripts')
    <script>
        $("form").on("submit", function (e) {
            e.preventDefault();
            let form = $(this).serialize();
            let stats_results = $("#stats-data");

            stats_results.empty();

            $.post('/stats', form, function (e) {
                e = JSON.parse(e);

                if (!e.success)
                    showError(e.message);
                else
                {
                    let changes = JSON.parse(e.message);
                    let keys = Object.keys(changes);

                    if (keys.length == 0)
                        stats_results.text('No results found');
                    else
                        keys.forEach((e) => stats_results.append(e + ": " + changes[e] + "<br />"));
                }
            });
        });
    </script>
@endsection