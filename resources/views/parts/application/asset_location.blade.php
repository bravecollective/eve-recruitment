@if(!$asset['container'])
    <div class="list-group-item bg-dark text-white">
        <img src="https://image.eveonline.com/Type/{{ $asset['type_id'] }}_32.png" />
        {{ $asset['name'] }} - {{ $asset['quantity'] }}
        <div class="float-right">
            {{ number_format($asset['price']) }} ISK
        </div>
    </div>
@else
    <div class="list-group-item bg-dark text-white">
        <img src="https://image.eveonline.com/Type/{{ $asset['type_id'] }}_32.png" />
        <a href="#" class="text-white" data-toggle="collapse" data-target="#items-{{ $asset['id'] }}">
            @if($asset['item_name'] != 'None')
                {{ $asset['item_name'] }} ({{ $asset['name'] }})
            @else
                {{ $asset['name'] }}
            @endif
        </a>
        <div class="float-right">
            {{ number_format($asset['value']) }} ISK
        </div>
        <div class="collapse" data-parent="#location-{{ $location_info['id'] }}" id="items-{{ $asset['id'] }}">
            <div class="list-group">
                @foreach($asset['items'] as $item)
                    <div class="list-group-item bg-dark text-white">
                        <img src="https://image.eveonline.com/Type/{{ $item['type_id'] }}_32.png" />
                        {{ $item['name'] }} - {{ $item['quantity'] }}
                        <div class="float-right">
                            {{ number_format($item['value']) }} ISK
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
