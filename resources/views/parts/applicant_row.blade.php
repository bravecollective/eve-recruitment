@if($app->account->main())
<div class="row" style="margin-top: 1em;">
    <div class="col-auto">
        <img src="https://image.eveonline.com/Character/{{ $app->account->main_user_id }}_32.jpg">
    </div>
    <div class="col-xl-2 col-3">
        {{ $app->account->main()->name }}
    </div>
    <div class="col-xl-2 col-3">
        <img src="https://image.eveonline.com/Corporation/{{ $app->account->main()->corporation_id }}_32.png">
        {{ $app->account->main()->corporation_name }}
    </div>
    <div class="col-xl-2 col-3">
    @if ($app->account->main()->alliance_name != null)
        <img src="https://image.eveonline.com/Alliance/{{ $app->account->main()->alliance_id }}_32.png">
        {{ $app->account->main()->alliance_name }}
    @endif
    </div>
    <div class="col-xl-2 col-3">
        {{ $app->updated_at->format('Y-m-d H:i') }}
    </div>
    <div class="col-xl-2 col-3">
    @if($app->changelog()->orderBy('created_at', 'desc')->first() && $app->changelog()->orderBy('created_at', 'desc')->first()->new_state == \App\Models\Application::ON_HOLD)
        Assigned recruiter: {{ \App\Models\Account::find($app->changelog()->orderBy('created_at', 'desc')->first()->account_id)->main()->name }}
    @endif
    </div>
    <div class="col-1">
        <a target="_blank" href="/application/{{ $app->id }}"><button type="button" class="btn btn-primary">Open</button></a>
    </div>
</div>
@endif
