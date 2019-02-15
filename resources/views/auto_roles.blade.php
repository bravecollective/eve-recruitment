@extends('default')
@section('content')
    <h1>Auto-Assigned Roles</h1>
    <hr class="my-4">
    <div class="row">
        <div class="col-1">Core Group</div>
        <div class="col-1">Role Name</div>
    </div><br />
    <div id="items">
    @foreach($roles as $role)
        <div class="row">
            <div class="col-1"><input class="form-control" type="text" value="{{ $role->group_name }}"></div>
            <div class="col-1"><input class="form-control" type="text" value="{{ $role->role_name }}"></div>
        </div>
    @endforeach
    </div>
    <br />
    <button class="btn btn-success" onclick="addItem();"><span class="fa fa-plus"></span></button><br /><br />
    <button class="btn btn-primary" type="submit" onclick="save();">Save</button>
@endsection
@section('scripts')
    <script type="text/javascript">
        let items = $("#items");

        function save()
        {
            let inputs = $("#items .form-control");
            let first = true;
            let save = null;
            let data = {};

            data._token = "{{ csrf_token() }}";
            data.roles = [];

            inputs.each(function(e) {
                if (first === true)
                {
                    first = false;
                    save = inputs[e].value;
                }
                else
                {
                    data.roles.push({
                        'group': save,
                        'role': inputs[e].value
                    });
                    first = true;
                    save = null;
                }
            });

            $.post('/api/admin/roles/auto/save', data, function (e) {
                e = JSON.parse(e);

                if (e.success === true)
                    showInfo(e.message);
                else
                    showError(e.message);
            });
        }

        function addItem()
        {
            items.append(" \
            <div class='row'>\
                <div class='col-1'><input class='form-control' type='text'></div>\
                <div class='col-1'><input class='form-control' type='text'></div>\
            </div><br />\
            ");
        }
    </script>
@endsection