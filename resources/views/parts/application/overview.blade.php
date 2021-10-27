<div class="row justify-content-center">
    <div class="col-xl-6 col-lg-6 col-12">
        <div class="row">
            <div class="col-xl-6 col-12">
            <div class="card bg-dark">
            <div class="card-body">
                <div class="card-header">
                    Character Info
                </div>
                <ul class="list-group">
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Birthday</strong>
                        {{ $character_info['birthday'] }}
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Gender</strong>
                        {{ $character_info['gender'] }}
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Race</strong>
                        {{ $character_info['race'] }}
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Bloodline</strong>
                        {{ $character_info['bloodline'] }}
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Security Status</strong>
                        {{ $character_info['security_status'] }}
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Location</strong>
                        {{ $character_info['location']->structure_name }}
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Region</strong>
                        {{ $character_info['region'] }}
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Current Ship</strong>
                        {{ $character_info['current_ship'] }}
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Last Login</strong>
                        {{ $login_details['last_login']->format('Y-m-d H:i:s') }}
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <strong>Last Logout</strong>
                        {{ $login_details['last_logout']->format('Y-m-d H:i:s') }}
                    </div>
                </ul>
                <br />
                <a href="https://zkillboard.com/character/{{ $character->character_id }}/" target=_blank class="btn btn-primary">zKillboard</a>
                <a href="https://evewho.com/character/{{ $character->character_id }}" target=_blank class="btn btn-primary">Eve Who</a>
            </div>
        </div>
        </div>
            <div class="col-xl-6 col-12">
                <div class="card bg-dark">
                    <div class="card-body">
                        <div class="card-header">
                            Corporation History<br />
                            <small>Note: The shown alliance is the alliance that the corporation was in <i>when the character was in it</i>.</small>
                        </div>
                        <ul class="list-group">
                        @foreach($corp_history as $corp)
                            <div class="list-group-item bg-dark text-white">
                                <h5>
                                @if ($corp->alliance_id != null)
                                    <div style="display: inline-block;" data-toggle="tooltip" title="{{ $corp->alliance_name }}">
                                        <img src="https://image.eveonline.com/Alliance/{{ $corp->alliance_id }}_32.png" />
                                        [{{ $corp->alliance_ticker }}]
                                    </div>
                                @endif
                                    <img src="https://image.eveonline.com/Corporation/{{ $corp->corporation_id }}_32.png" />
                                    {{ $corp->corporation_name }}
                                @if($corp->corporation_id > 1000000 && $corp->corporation_id < 2000000)
                                    (NPC)
                                @endif
                                </h5>
                                <p class="mb-0">Joined: {{ $corp->formatted_start_date }}</p>
                                <p class="mb-0">Left: {{ $corp->formatted_end_date }}</p>
                                <p class="mb-0">Duration: {{ $corp->duration }}</p>
                            </div>
                        @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-6 col-lg-6 col-12">
                <div class="card bg-dark">
                    <div class="card-body">
                        <div class="card-header">
                            Implants
                        </div>
                        <ul class="list-group">
                        @if($clones)
                                @foreach($clones['implants'] as $implant)
                                    <div class="list-group-item bg-dark text-white">
                                        {{ $implant }}
                                    </div>
                                @endforeach
                        @else
                                Unable to retrieve clone information
                        @endif
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-12">
                <div class="card bg-dark">
                    <div class="card-body">
                        <div class="card-header">
                            Jump Clones
                        </div>
                        <ul class="list-group">
                        @if($clones)
                            <div class="list-group-item bg-dark text-white">
                                <b>Home: </b> {{ $clones['clones']->getHomeLocation()->location_name }}
                            </div>
                        @foreach($clones['clones']->getJumpClones() as $clone)
                            @if($clone->getLocationId() == $clones['clones']->getHomeLocation()->getLocationId())
                                @continue
                            @endif
                            <div class="list-group-item bg-dark text-white">
                                {{ $clone->location_name }}
                            </div>
                        @endforeach
                        @else
                                Unable to retrieve clone information
                        @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-6 col-lg-6 col-12">
                <div class="card bg-dark text-white">
                    <div class="card-body">
                        <div class="card-header">
                            Attributes
                        </div>
                        <div class="list-group-item bg-dark text-white">
                            Charisma
                            <div class="float-right">
                                {{ $character_info['attributes']->getCharisma() }}
                            </div>
                        </div>
                        <div class="list-group-item bg-dark text-white">
                            Intelligence
                            <div class="float-right">
                                {{ $character_info['attributes']->getIntelligence() }}
                            </div>
                        </div>
                        <div class="list-group-item bg-dark text-white">
                            Memory
                            <div class="float-right">
                                {{ $character_info['attributes']->getMemory() }}
                            </div>
                        </div>
                        <div class="list-group-item bg-dark text-white">
                            Perception
                            <div class="float-right">
                                {{ $character_info['attributes']->getPerception() }}
                            </div>
                        </div>
                        <div class="list-group-item bg-dark text-white">
                            Willpower
                            <div class="float-right">
                                {{ $character_info['attributes']->getWillpower() }}
                            </div>
                        </div>
                        <div class="list-group-item bg-dark text-white">
                            Bonus Remaps Remaining
                            <div class="float-right">
                                {{ $character_info['attributes']->getBonusRemaps() }}
                            </div>
                        </div>
                        <div class="list-group-item bg-dark text-white">
                            Last Remap
                            <div class="float-right">
                            @if($character_info['attributes']->getLastRemapDate() != null)
                                {{ $character_info['attributes']->getLastRemapDate()->format('Y-m-d H:i') }}
                            @else
                                Never
                            @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-12">
                <div class="card bg-dark text-white">
                    <div class="card-body">
                        <div class="card-header">
                            Core Character Removals
                        </div>
                    @foreach($deleted_characters as $character)
                        <div class="list-group-item bg-dark text-white">
                            {{ $character->characterName }}
                            <div class="float-right">
                                {{ $character->reason }}
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
                <div class="card bg-dark text-white">
                    <div class="card-body">
                        <div class="card-header">
                            Core Characters Additions (moved from another account)
                        </div>
                        @foreach($added_characters as $character)
                            <div class="list-group-item bg-dark text-white">
                                {{ $character->characterName }}
                                <div class="float-right">
                                    @if($character->player)
                                        ({{ $character->player->name }})
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6 col-12">
        <div class="card bg-dark">
        <div class="card-body">
            <div class="card-header">
                Contacts
            </div>
            <ul class="list-group">
            @foreach($contacts as $contact)
                @switch($contact->getContactType())
                    @case("character")
                    @php($evewho_link = "https://evewho.com/pilot/" . str_replace(' ', '+', $contact->contact_name))
                    @php($img_link = "https://image.eveonline.com/Character/" . $contact->getContactId() . "_64.jpg")
                    @break
                    @case("corporation")
                    @php($evewho_link = "https://evewho.com/corp/" . str_replace(' ', '+', $contact->contact_name))
                    @php($img_link = "https://image.eveonline.com/Corporation/" . $contact->getContactId() . "_64.png")
                    @break
                    @case("alliance")
                    @php($evewho_link = "https://evewho.com/alli/" . str_replace(' ', '+', $contact->contact_name))
                    @php($img_link = "https://image.eveonline.com/Alliance/" . $contact->getContactId() . "_64.png")
                    @break
                    @case("faction")
                    @php($evewho_link = "")
                    @php($img_link = "")
                    @break
                @endswitch
                @switch($contact->getStanding())
                    @case(-10)
                    @php($class = "badge-danger")
                    @break
                    @case(-5)
                    @php($class = "badge-warning")
                    @break
                    @case(0)
                    @php($class = "badge-secondary")
                    @break
                    @case(5)
                    @php($class = "badge-light")
                    @break
                    @case(10)
                    @php($class = "badge-info")
                    @break
                    @default
                    @php($class = null)
                    @break
                @endswitch
                <a class="list-group-item bg-dark text-white" href="{{ $evewho_link }}" target="_blank">
                    <div class="media">
                        @if($contact->getContactType() != "faction")
                        <img class="mr-3 rounded img-fluid" src="{{ $img_link }}" />
                        @endif
                        <div class="media-body">
                            <h5 class="mt-0" style="margin: 0;">
                            @if($contact->getContactType() != "faction")
                                {{ $contact->contact_name }}
                            @else
                                (Faction)
                            @endif
                            </h5>
                        @if($contact->getContactType() == "character")
                            @if($contact->alliance_name != null)
                                <div style="display: inline-block;" data-toggle="tooltip" title="{{ $contact->alliance_name }}">[{{ $contact->alliance_ticker }}]</div>/{{ $contact->corp_name }}
                            @else
                                {{ $contact->corp_name }}
                            @endif
                            <br />
                        @elseif($contact->getContactType() == "corporation" && $contact->alliance_name != null)
                            <div style="display: inline-block;" data-toggle="tooltip" title="{{ $contact->alliance_name }}">[{{ $contact->alliance_ticker }}]</div><br />
                        @endif
                            <span class="badge badge-pill {{ $class }}">Standing: {{ $contact->getStanding() }}</span>
                        @if($contact->getContactId() > 3000000 && $contact->getContactId() < 4000000)
                            <span class="badge badge-pill badge-primary">NPC</span>
                        @endif
                        @if(sizeof(array_filter($account->alts()->toArray(), function ($e) use (&$contact) { return $e['character_id'] == $contact->getContactId(); })) > 0)
                            <span class="badge badge-pill badge-success">Included Alt</span>
                        @endif
                        </div>
                    </div>
                </a>
            @endforeach
            </ul>
        </div>
    </div>
    </div>
</div>
