@extends('default')
@section('content')
    <h1>Global Roles Manager</h1>
    <hr class="my-4">
    <div class="row">
        <div class="col-lg-4 form-group">
            <h2><label for="search">Character Search</label></h2>
            <input type="text" class="form-control" id="search" name="search" placeholder="Search..." onkeyup="search(this);" />
            <ul class="list-group search-result"></ul>
        </div>
        <div class="col-lg-2 form-group">
            <h2>Roles</h2>
            <input type="hidden" id="character_id" value="" />
        @foreach($roles as $role)
             <div class="form-check">
                 <input type="checkbox" class="form-check-input role-checkbox" id="{{ $role->id }}" />
                 <label class="text-white form-check-label" for="{{ $role->id }}">{{ $role->name }}</label>
             </div>
        @endforeach
        </div>
    </div>
    <div class="row">
        <div class="col-lg-2 offset-4">
            <button type="button" class="btn btn-primary" onclick="saveRoles();">Save</button>
        </div>
    </div>
@endsection

@section('scripts')
<script type="text/javascript">
    let roles_checkboxes = $('.role-checkbox');
    let searchbox = $('#search');

    $("#character_id").val('');
    searchbox.attr('autocomplete', 'off');
    searchbox.val('');
    roles_checkboxes.attr('disabled', true);

    function clearCheckboxes()
    {
        roles_checkboxes.prop('checked', false);
    }

    function saveRoles()
    {
        let data = {
            _token: "{{ csrf_token() }}",
            userid: $('#character_id').val(),
            roles: []
        };

        roles_checkboxes.each(function (e) {
            e = $(this)[0];
            data.roles.push({ 'id': e.id, 'active': !!(e.checked)});
            console.log(data);
        });

        $.post('/api/character/roles/save', data, function(e) {
            e = JSON.parse(e);
            if (e.success === true)
                showInfo("Permissions saved");
            else
                alert(e.message);
        });
    }

    function loadUserRoles(userId)
    {
        clearCheckboxes();

        let list = $(".search-result");
        let data = {
            'userid': userId,
            '_token': "{{ csrf_token() }}"
        };

        $("#character_id").val(userId);

        $.post('/api/character/roles', data, function(e) {
            e = JSON.parse(e);

            if (e.success === false)
                return;

            e.message.forEach(function (e) {
                let item = $("#" + e.id);
                item.prop('checked', true);
            });

            roles_checkboxes.removeAttr('disabled');
            list.hide();
        });
    }

    function search(i)
    {
        let data = $(i).serializeObject();

        if (data.search.length <= 3)
            return;

        $.post('/api/character/search', data, function (e) {
            e = JSON.parse(e);
            let list = $(".search-result");

            if (e.success === false)
                return;

            if (e.message.length === 0)
                list.hide();
            else
            {
                list.empty();

                e.message.forEach(function (e) {
                    list.append('' +
                        '<li class="bg-dark text-white list-group-item list-group-item-action search-result-item" onclick="loadUserRoles(\'' + e.character_id + '\')">' +
                            '<img src="https://image.eveonline.com/Character/' + e.character_id + '_32.jpg" />' +
                             e.name +
                        '</li>');
                });

                list.show();
            }
        });
    }

    clearCheckboxes();
</script>
@endsection