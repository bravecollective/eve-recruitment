@extends('default')
@section('content')
    <h2>Application Generator</h2>
    <form method="POST" action="/admin/generator/save">
        {{ csrf_field() }}
        <div class="form-group">
            <label for="char_id">EVE Character ID</label>
            <input type="text" class="form-control" id="char_id" name="char_id" />
        </div>
        <div class="form-group">
            <label for="group">Group</label>
            <select class="form-control" id="group" name="group">
            @foreach($groups as $group)
                <option value="{{ $group->id }}">{{ $group->group_name }}</option>
            @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
@endsection
