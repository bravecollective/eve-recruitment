@extends('default')
@section('content')
<h1>{{ $ad->group_name }} Permissions</h1>
<div class="row">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="row">
            <div class="col-10 form-group">
                <h3><label for="search">Character Search</label></h3>
                <input type="hidden" id="character_id" value="" />
                <input autocomplete="off" type="text" class="form-control" id="search" name="search" placeholder="Search..." onkeyup="search(this);" />
                <ul class="list-group search-result"></ul>
            </div>
        </div>
        <div class="row" style="margin-left: 0.1em;">
            <button type="button" class="btn btn-primary" onclick="saveRoles();">Save</button>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="row">
            <h2>Permissions</h2>
        </div>
        @foreach($roles as $role)
            <div class="role row" style="margin-left: 0.1em;">
                <div class="col-5 form-check form-check-inline">
                    <input autocomplete="off" type="checkbox" class="form-check-input role-checkbox" id="{{ $role->id }}" />
                    <label class="text-white form-check-label" for="{{ $role->id }}">{{ $role->name }}</label>
                </div>
            </div>
        @endforeach
    </div>
    <div class="col-12 col-md-6 col-xl-2">
        <div class="row">
            <h2>Recruiters</h2>
        </div>
    @foreach($recruiters as $recruiter)
        <div class="row">
            <div class="col-auto">
                <img src="https://image.eveonline.com/Character/{{ $recruiter->main_user_id }}_32.jpg" />
            </div>
            <div class="col-5">
                {{ $recruiter->name }}
            </div>
        </div><br />
    @endforeach
    </div>
</div>
@endsection
@section('scripts')
    <script type="text/javascript">
        let list = $(".list-group");
        let roles = $(".role");
        let roles_checkboxes = $('.role-checkbox');
        let persistent_checkboxes = $('.persistent-checkbox');

        @if($ad->corp_id == null)
            let base_url = "/api/groups" ;
        @else
            let base_url ="/api/corporations";
        @endif

        function clearCheckboxes()
        {
            roles_checkboxes.prop('checked', false);
            persistent_checkboxes.prop('checked', false);
        }

        function saveRoles()
        {
            let data = {
                _token: "{{ csrf_token() }}",
                userid: $('#character_id').val(),
                ad_id: "{{ $ad->id }}",
                roles: []
            };

            roles_checkboxes.each(function (e) {
                e = $(this)[0];
                data.roles.push({ 'id': e.id, 'active': !!(e.checked), 'persistent': true });
            });

            $.post(base_url + '/roles/save', data, function(e) {
                e = JSON.parse(e);
                if (e.success === true)
                    showInfo("Permissions saved");
                else
                    showError(e.message);
            });
        }

        function loadUserRoles(user_id)
        {
            clearCheckboxes();

            let data = {
                "_token": "{{ csrf_token() }}",
                "user_id": user_id,
                "ad_id": "{{ $ad->id }}"
            };

            $("#character_id").val(user_id);

            $.post(base_url + '/roles', data, function (e) {
                list.hide();

                e = JSON.parse(e);

                if (e.success === false)
                {
                    showError(e.message);
                    return;
                }

                e.message.forEach(function (f) {
                    $("#" + f.role_id).prop('checked', true);
                    if (f.set === 1)
                        $("#persistent-" + f.role_id).prop('checked', true);
                    else
                        $("#persistent-" + f.role_id).prop('checked', false);
                });
            });
        }

        function search(i)
        {
            let data = $(i).serializeObject();

            if (data.search.length < 3)
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
    </script>
@endsection
