<div class="row">
    <div class="col-2">
        <select class="custom-select" autocomplete="off">
        @foreach($groups as $group)
            @if(isset($groupSelect) && $groupSelect == $group->name)
                <option value="{{ $group->id }}" selected>{{ $group->name }}</option>
            @else
                <option value="{{ $group->id }}">{{ $group->name }}</option>
            @endif
        @endforeach
        </select>
    </div>
    <div class="col-2">
        <select class="custom-select" autocomplete="off">
        @foreach($roles as $role)
            @if(isset($roleSelect) && $roleSelect == $role->name)
                <option value="{{ $role->id }}" selected>{{ $role->name }}</option>
            @else
                <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endif
        @endforeach
        </select>
    </div>
</div>