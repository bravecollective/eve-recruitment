<div class="row">
    <div class="esi-container col-4">
        <h2>Corporation History</h2>
    @foreach($corp_history as $corp)
        <div class="row">
            <div class="col-auto">
                <img src="https://image.eveonline.com/Corporation/{{ $corp->corporation_id }}_32.png" />
            </div>
            <div class="col-4">
                {{ $corp->corporation_name }}
            </div>
        @if ($corp->alliance_id != null)
            <div class="col-auto">
                <img src="https://image.eveonline.com/Alliance/{{ $corp->alliance_id }}_32.png" />
            </div>
            <div class="col-4">
                {{ $corp->alliance_name }}
            </div>
        @endif
        </div>
        <hr>
    @endforeach
    </div>
    <div class="esi-container col-4">
        <h2>Contacts</h2>
    </div>
</div>