<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main_nav" aria-controls="main_nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand" href="/">{{ env('APP_NAME') }}</a>
    <div class="collapse navbar-collapse" id="main_nav">
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
            @if(Auth::user()->hasRoleLike('%recruiter') || Auth::user()->hasRoleLike('%director') || Auth::user()->hasRoleLike('admin'))
                <form class="form-inline mb-0" method="POST" action="/character/search">
                    <div class="input-group">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                        <input class="form-control" name="search" type="text" required minlength="3"
                               placeholder="Character search..." />
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-outline-secondary"><span class="fa fa-search"></span></button>
                        </div>
                    </div>
                </form>
            @endif
            @if(count($recruitment_ads) > 0)
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
            @endif
            @if(count($corporations) > 0)
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
            @endif
            @if(count($corp_ad) > 0)
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="corp_ads_dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Corp Ads
                    </a>
                    <div class="dropdown-menu" aria-labelledby="corp_ads_dropdown">
                    @foreach($corp_ad as $ad)
                        <a class="dropdown-item" href="/corporations/{{ $ad->corp_id }}/ad">{{ $ad->corp_name }}</a>
                    @endforeach
                        <hr>
                        <a class="dropdown-item" href="/corporations/manage/roles">Manage Roles</a>
                    </div>
                </li>
            @endif
            @if(Auth::user()->hasRole('group admin') || Auth::user()->hasRoleLike('%manager'))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="management_dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Group Ads
                </a>
                <div class="dropdown-menu" aria-labelledby="management_dropdown">
                    <a class="dropdown-item" href="/group/ads">My Group Ads</a>
                    <a class="dropdown-item" href="/group/permissions">Group Permissions</a>
                </div>
            </li>
            @endif
            @if(Auth::user()->hasRoleLike('%manager') || Auth::user()->hasRoleLike('%director'))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="stats" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Stats
                </a>
                <div class="dropdown-menu" aria-labelledby="stats">
                    @foreach($stats_ads as $ad)
                        <a class="dropdown-item" href="/stats/{{ $ad->id }}">{{ $ad->corp_name === null ? $ad->group_name : $ad->corp_name }}</a>
                    @endforeach
                </div>
            </li>
            @endif
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('supervisor'))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="admin_dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Admin
                </a>
                <div class="dropdown-menu" aria-labelledby="admin_dropdown">
                    @role('admin')
                    <a class="dropdown-item" href="/admin/roles">Global Roles</a>
                    <a class="dropdown-item" href="/admin/roles/auto">Auto Assigned Roles</a>
                    <a class="dropdown-item" href="/admin/coregroups">Known Core Groups</a>
                    @endrole
                    <a class="dropdown-item" href="/admin/generator">Application Generator</a>
                </div>
            </li>
            @endif
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
