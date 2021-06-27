@extends('default')
@section('content')
    <h1>Auto-Assigned Roles</h1>
    <hr class="my-4">
    <div class="row">
        <div class="col-6 col-xl-4"><h2>Core Group</h2></div>
        <div class="col-6 col-xl-4"><h2>Role Name</h2></div>
    </div><hr class="mt-4 mb-0">
    <div id="items">
    @foreach($roles as $role)
        <div class="row py-4 auto-roles-list-item">
            <div class="col-6 col-xl-4">
                {{ $role->group_name }}
            </div>
            <div class="col-6 col-xl-4">
                {{ $role->role_name }}
                <a class="text-danger" href="#" onclick="deleteRole({{ $role->core_group_id }}, {{ $role->role_id }})"><span class="fa fa-times-circle"></span></a>
            </div>
        </div>
        <hr class="my-0">
    @endforeach
    </div>
    <br />
    <button class="btn btn-success" onclick="addItem();"><span class="fa fa-plus"></span></button><br /><br />
    <button class="btn btn-primary" type="submit" onclick="save();">Save</button>
@endsection
@section('scripts')
    <script type="text/javascript">
        let items = $("#items");

        function deleteRole(group_id, role_id) {
            let data = {
                _token: "{{ csrf_token() }}",
                group_id: group_id,
                role_id: role_id,
            };

            $.post('/api/auto_roles/delete', data, function (e)
            {
                e = JSON.parse(e);
                if (e.success === true)
                {
                    showInfo(e.message + ". Window will reload in 3 seconds.");
                    setTimeout(() => location.reload(), 2000);
                }
                else
                    showError(e.message);
            });
        }

        function save()
        {
            let inputs = $("#items .custom-select");
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
            $.get('/api/auto_roles/template', { _token: "{{ csrf_token() }}" }, (e) => items.append(JSON.parse(e).message));
        }
    </script>
@endsection
