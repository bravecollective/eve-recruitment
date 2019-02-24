@if(isset($application))
<div class="tab-pane fade" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview">
@else
<div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview">
@endif
    <div class="row justify-content-center">
        <div class="col-3">
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
        <div class="col-3">
            <div class="card bg-dark">
                <div class="card-body">
                    <div class="card-header">
                        Corporation History
                    </div>
                    <ul class="list-group">
                    @foreach($corp_history as $corp)
                        <div class="list-group-item bg-dark text-white">
                            <p>
                                <img src="https://image.eveonline.com/Corporation/{{ $corp->corporation_id }}_32.png" />
                                {{ $corp->corporation_name }}
                            </p>
                            @if ($corp->alliance_id != null)
                                <p>
                                    <img src="https://image.eveonline.com/Alliance/{{ $corp->alliance_id }}_32.png" />
                                    {{ $corp->alliance_name }}
                                </p>
                            @endif
                            Joined: {{ $corp->start_date }}
                        </div>
                    @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-3">
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
                                </div>
                            </div>
                        </a>
                    @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>