@extends('default')
@section('content')
    <h1 class="display-4">{{ env('APP_NAME') }}</h1>
    <hr class="my-4">
@auth
    <p>To make any changes to your characters, please use <a href="{{ env('CORE_URL') }}" target="_blank">Brave Core</a>.</p>
    <p>Once changes are made, you must logout and back in here for them to take effect.</p>
    <div class="row">
        <div class="col-lg-8">
            <h2 class="color-white">Characters</h2>
    @foreach($characters->chunk(3) as $chunk)
            <div class="card-columns">
            @foreach ($chunk as $character)
                @include('parts.character', ['character' => $character])
            @endforeach
            </div>
    @endforeach
        </div>
        <div class="col-lg-4">
            <h2 class="color-white">My Applications</h2>
            <table class="table">
                <thead>
                    <th>Group</th>
                    <th>State</th>
                    <th>Last Update</th>
                    <th>Actions</th>
                </thead>
                @foreach ($applications as $application)
                    @if($application->status == \App\Models\Application::REVOKED)
                        @continue
                    @endif
                <tr>
                    <td class="vertical-middle">{{ $application->recruitmentAd->group_name }}</td>
                    <td class="vertical-middle">{{ \App\Models\Application::getStringForState($application->status) }}</td>
                    <td class="vertical-middle">{{ $application->last_update }}</td>
                    <td class="vertical-middle">
                    @if(\App\Models\Application::canBeRevoked($application))
                            <a href="/application/{{ $application->id }}/revoke"><button type="button" class="btn btn-danger">Revoke</button></a>
                    @endif
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
@else
    <p>Welcome, capsuleer!</p>
    <p>Before logging in below, please ensure you have created an account on <a href="{{ env('CORE_URL') }}" target="_blank">Brave Core</a>.</p>
    <p>Once you have created an account and have added all of your alts, use the link below to login.</p>
    <a href="/login"><img src="/img/EVE_SSO_Login_Buttons_Large_Black.png" alt="Login with EVE Online"></a>
@endauth
@endsection
