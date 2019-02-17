<div class="row" style="margin-top: 1em;">
    <div class="col-auto">
        <img src="https://image.eveonline.com/Character/{{ $app->account->main_user_id }}_32.jpg">
    </div>
    <div class="col-1">
        {{ $app->account->main()->name }}
    </div>
    <div class="col-2">
        <img src="https://image.eveonline.com/Corporation/{{ $app->account->main()->corporation_id }}_32.png">
        {{ $app->account->main()->corporation_name }}
    </div>
    <div class="col-2">
    @if ($app->account->main()->alliance_name != null)
        <img src="https://image.eveonline.com/Alliance/{{ $app->account->main()->alliance_id }}_32.png">
        {{ $app->account->main()->alliance_name }}
    @endif
    </div>
    <div class="col-1">
        <a target="_blank" href="/application/{{ $app->id }}"><button type="button" class="btn btn-primary">Open</button></a>
    </div>
</div>