<div class="row">
    <div class="col-auto">
        <img src="https://image.eveonline.com/Character/{{ $character->character_id }}_128.jpg">
    </div>
    <div class="col-6">
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