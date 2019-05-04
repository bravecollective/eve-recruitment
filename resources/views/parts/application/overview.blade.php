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
                        <strong>Ancestry</strong>
                        {{ $character_info['ancestry'] }}
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
                </ul>
            </div>
        </div>
        </div>
            <div class="col-xl-6 col-12">
                <div class="card bg-dark">
                    <div class="card-body">
                        <div class="card-header">
                            Corporation History
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
                        @foreach($clones['implants'] as $implant)
                            <div class="list-group-item bg-dark text-white">
                                {{ $implant }}
                            </div>
                        @endforeach
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
                    @php($zkill_link = "https://zkillboard.com/character/" . $contact->getContactId())
                    @php($img_link = "https://image.eveonline.com/Character/" . $contact->getContactId() . "_64.jpg")
                    @break
                    @case("corporation")
                    @php($zkill_link = "https://zkillboard.com/corporation/" . $contact->getContactId())
                    @php($img_link = "https://image.eveonline.com/Corporation/" . $contact->getContactId() . "_64.png")
                    @break
                    @case("alliance")
                    @php($zkill_link = "https://zkillboard.com/alliance/" . $contact->getContactId())
                    @php($img_link = "https://image.eveonline.com/Alliance/" . $contact->getContactId() . "_64.png")
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
                <a class="list-group-item bg-dark text-white" href="{{ $zkill_link }}" target="_blank">
                    <div class="media">
                        <img class="mr-3 rounded img-fluid" src="{{ $img_link }}" />
                        <div class="media-body">
                            <h5 class="mt-0">{{ $contact->contact_name }}</h5>
                            <span class="badge badge-pill {{ $class }}">Standing: {{ $contact->getStanding() }}</span>
                        @if($contact->getContactId() > 3000000 && $contact->getContactId() < 4000000)
                            <span class="badge badge-pill badge-primary">NPC</span>
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