<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="/">{{ env('APP_NAME') }}</a>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link" href="/">Home</a>
            </li>
        @auth
            <li class="nav-item">
                <a class="nav-link" href="/profile/applications">My Applications</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/profile/applications">Submit Application</a>
            </li>
        @endauth
        </ul>
        <ul class="nav navbar-nav navbar-right">
        @auth
            @can(Config::get('constants.permissions')['VIEW_CORP_APPS'])
            <li class="nav-item">
                <a class="nav-link" href="/applications">Applications</a>
            </li>
            @endcan
            @can(Config::get('constants.permissions')['VIEW_CORP_MEMBERS'])
            <li class="nav-item">
                <a class="nav-link" href="/corp/members">Corp Members</a>
            </li>
            @endcan
            @role('director|admin')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Management
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                    <a class="dropdown-item" href="/corp/ad">Corp Ad</a>
                    <a class="dropdown-item" href="/corp/permissions">Corp Permissions</a>
                    <a class="dropdown-item" href="/group/ad">Group Ad</a>
                    <a class="dropdown-item" href="/group/permissions">Group Permissions</a>
                </div>
            </li>
            @endrole
            @role('admin')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Admin
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink1">
                    <a class="dropdown-item" href="/admin/ads">Global Ad Management</a>
                    <a class="dropdown-item" href="/admin/permissions">Global Permissions</a>
                    <a class="dropdown-item" href="/admin/system">System Configuration</a>
                </div>
            </li>
            <li class="divider-vertical"></li>
            @endrole
            <li class="nav-item">
                <span class="navbar-brand">
                    <img src="https://image.eveonline.com/Character/{{ Auth::user()->character_id }}_32.jpg" />
                    {{ Auth::user()->name }}
                </span>
            </li>
            <li class="nav-item">
                <a class="nav-link btn btn-outline-danger" href="/logout">Logout</a>
            </li>
        @endauth
        </ul>
    </div>
</nav>