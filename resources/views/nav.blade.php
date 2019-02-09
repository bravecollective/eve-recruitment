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