<div class="row justify-content-xl-end justify-content-lg-end justify-content-md-end justify-content-sm-center justify-content-center">
    <div class="col-auto">
        <img src="https://image.eveonline.com/Character/{{ $character->character_id }}_128.jpg">
    </div>
    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-auto col-auto">
        <div class="row">
            <h1>{{ $character->name }}</h1>
        </div>
        <div class="row">
            <div class="col-xs-auto">
                <img src="https://image.eveonline.com/Corporation/{{ $character->corporation_id }}_32.png">
            </div>
            <div class="col-auto">
                {{ $character->corporation_name }}
            </div>
        </div>
        <div class="row">
            @if($character->alliance_id != null)
                <div class="col-xs-auto">
                    <img src="https://image.eveonline.com/Alliance/{{ $character->alliance_id }}_32.png">
                </div>
                <div class="col-auto">
                    {{ $character->alliance_name }}
                </div>
            @endif
        </div>
    </div>
</div>
<br />
@if($character->has_valid_token)
<div class="row justify-content-center">
    <div class="col-auto">
        <h5>{{ $sp }} Skillpoints</h5>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-auto">
        <h5>{{ $isk }} isk</h5>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-auto">
        Titles: {{ $titles }}
    </div>
</div>
@else
<div class="row justify-content-center">
    <h4><strong>Invalid ESI Token</strong></h4>
</div>
@endif
<br />
