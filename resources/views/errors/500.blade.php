@extends('default')
@section('content')
    <h2>500 - Internal server error</h2>
    <p>Please report the following error:</p>
    <pre style="color: white;">{{ $exception->getMessage() }}</pre>
@endsection