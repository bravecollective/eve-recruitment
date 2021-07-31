@if(count($killmails) == 0)
<h3 style="text-align: center;">No killmails found</h3>
@endif
<table class="table table-condensed table-hover table-striped" id="killmails-table">
    @foreach($killmails as $killmail)
        <tr>
            <td style="width: 1rem; vertical-align: middle;">
                {{ $killmail->killmail_time }}<br />
                <a href="https://zkillboard.com/kill/{{ $killmail->killmail_id }}" target="_blank">zKillboard</a>
            </td>
            <td style="width: 1rem;">
                <img src="https://images.evetech.net/types/{{ $killmail->victim->ship_type_id }}/render?size=64" />
            </td>
            <td style="width: 3rem; vertical-align: middle">
                {{ $killmail->solar_system->name }}<br />
                {{ $killmail->region->name }}
            </td>
            <td style="width: 1rem; padding-right: 0; text-align: right;">
                @if($killmail->victim->alliance_name != null)
                    <img src="https://images.evetech.net/alliances/{{ $killmail->victim->alliance_id }}/logo?size=64" />
                @elseif($killmail->victim->corporation_name != null)
                    <img src="https://images.evetech.net/corporations/{{ $killmail->victim->corporation_id }}/logo?size=64" />
                @endif
            </td>
            <td style="width: 20rem; text-align: left; vertical-align: middle;">
                {{ $killmail->victim->name }} ({{ $killmail->victim->ship_type_name }})<br />
                @if($killmail->victim->alliance_name != null)
                    {{ $killmail->victim->alliance_name }}
                @else
                    {{ $killmail->victim->corporation_name }}
                @endif
            </td>
            <td style="width: 1rem; padding-right: 0; text-align: right;">
                @if($killmail->final_blow->alliance_name != null)
                    <img src="https://images.evetech.net/alliances/{{ $killmail->final_blow->alliance_id }}/logo?size=64" />
                @elseif($killmail->final_blow->corporation_name != null)
                    <img src="https://images.evetech.net/corporations/{{ $killmail->final_blow->corporation_id }}/logo?size=64" />
                @endif
            </td>
            <td style="width: 20rem; text-align: left; vertical-align: middle;">
                {{ $killmail->final_blow->name }} ({{ count($killmail->attackers) }})<br />
                @if($killmail->final_blow->alliance_name != null)
                    {{ $killmail->final_blow->alliance_name }}
                @elseif($killmail->final_blow->corporation_name != null)
                    {{ $killmail->final_blow->corporation_name }}
                @else
                    <i>Unknown</i>
                @endif
            </td>
        </tr>
    @endforeach
</table>
