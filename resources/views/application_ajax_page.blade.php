@foreach($apps as $app)
    @include('parts/applicant_row', ['app' => $app])
@endforeach