@extends('default')
@section('content')
<h1>Known Core Groups</h1>
<hr class="my-4">
@foreach($groups as $group)
    <div class="row">{{ $group->name }}</div>
@endforeach
@endsection