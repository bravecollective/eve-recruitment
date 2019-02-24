<div class="tab-pane fade" id="tab-contacts" role="tabpanel" aria-labelledby="tab-contacts">
    <div class="row justify-content-center">
        <h2>Contacts</h2>
    </div>
    <div class="row justify-content-center">
        <div class="col-4">
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
        </div>
    </div>
</div>