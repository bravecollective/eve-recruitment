<div class="card bg-dark">
    <div class="card-header">
        <img src="https://image.eveonline.com/Character/{{ $character->character_id }}_32.jpg" />
        {{ $character->name }}
    @if($character->account->main_user_id == $character->character_id)
        <span class="fa fa-star"></span>
    @endif
    </div>
    <div class="card-body">
        <p><span class="font-weight-bold">Corporation:</span>&nbsp;{{ $character->corporation_name }}</p>
        <p><span class="font-weight-bold">Alliance:</span>&nbsp;{{ $character->alliance_name }}</p>
    </div>
</div>