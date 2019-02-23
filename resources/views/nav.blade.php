<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="/">{{ env('APP_NAME') }}</a>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link" href="/">Home</a>
            </li>
        @auth
            <li class="nav-item">
                <a class="nav-link" href="/applications">Apply</a>
            </li>
        @endauth
        </ul>
        <ul class="nav navbar-nav navbar-right">
        @auth
            @role('recruiter')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="applications" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Applications
                </a>
                <div class="dropdown-menu" aria-labelledby="applications">
                @foreach($recruitment_ads as $ad)
                    <a class="dropdown-item" href="/applications/{{ $ad->id }}">{{ $ad->corp_name === null ? $ad->group_name : $ad->corp_name }}</a>
                @endforeach
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="corporations_dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Corp Members
                </a>
                <div class="dropdown-menu" aria-labelledby="corporations_dropdown">
                    @foreach($corporations as $corp)
                        <a class="dropdown-item" href="/corporations/{{ $corp->corp_id }}">{{ $corp->corp_name }}</a>
                    @endforeach
                </div>
            </li>
            @endrole
            @role('director')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="corp_ads_dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Corp Ads
                    </a>
                    <div class="dropdown-menu" aria-labelledby="corp_ads_dropdown">
                    @foreach($corp_ad as $ad)
                        <a class="dropdown-item" href="/corporations/{{ $ad->corp_id }}/ad">{{ $ad->corp_name }}</a>
                    @endforeach
                    </div>
                </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="management_dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Group Ads
                </a>
                <div class="dropdown-menu" aria-labelledby="management_dropdown">
                    <a class="dropdown-item" href="/group/ads">My Group Ads</a>
                    <a class="dropdown-item" href="/group/permissions">Group Permissions</a>
                </div>
            </li>
            @endrole
            @role('admin')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="admin_dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Admin
                </a>
                <div class="dropdown-menu" aria-labelledby="admin_dropdown">
                    <a class="dropdown-item" href="/admin/ads">Global Ad Management</a>
                    <a class="dropdown-item" href="/admin/roles">Global Roles</a>
                    <a class="dropdown-item" href="/admin/roles/auto">Auto Assigned Roles</a>
                    <a class="dropdown-item" href="/admin/coregroups">Known Core Groups</a>
                    <a class="dropdown-item" href="/group/ads/orphaned">Orphaned Group Ads</a>
                    <a class="dropdown-item" href="/admin/system">System Configuration</a>
                </div>
            </li>
            @endrole
            <li class="divider-vertical"></li>
            <li class="nav-item">
                <span class="navbar-brand">
                    <img src="https://image.eveonline.com/Character/{{ Auth::user()->main_user_id }}_32.jpg" />
                    {{ Auth::user()->main()->name }}
                </span>
            </li>
            <li class="nav-item">
                <a class="nav-link btn btn-outline-danger" href="/logout">Logout</a>
            </li>
        @endauth
        </ul>
    </div>
</nav>