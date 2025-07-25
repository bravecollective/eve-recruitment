<div class="row">
    <div class="col-12 col-lg-6 offset-3">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="card-header">
                    Assets
                </div>
                <div class="accordian" id="top-level-accordian">
                @foreach($assets as $location => $location_info)
                    <div class="card bg-dark text-white" id="location-{{ $location_info['id'] }}">
                        <div class="card-header" id="parent-{{ $location_info['id'] }}">
                            <button class="btn btn-link text-white" type="button" data-toggle="collapse" data-target="#children-{{ $location_info['id'] }}" aria-expanded="false" aria-controls="children-{{ $location_info['id'] }}">
                                {{ $location_info['name'] }}
                            </button>
                            <div class="float-right">
                                {{ $location_info['value'] }} isk
                            </div>
                        </div>
                        <div id="children-{{ $location_info['id'] }}" class="collapse" aria-labelledby="parent-{{ $location_info['id'] }}" data-parent="#top-level-accordian">
                            <div class="list-group">
                            @foreach($location_info['containers'] as $key => $asset)
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
                                        <a href="#" class="text-white" data-toggle="collapse"
                                        data-target="#items-{{ $location_info['id'] }}-{{ $asset['id'] }}-{{ $key }}">
                                            @if($asset['item_name'] != 'None')
                                                {{ $asset['item_name'] }} ({{ $asset['name'] }})
                                            @else
                                                {{ $asset['name'] }}
                                            @endif
                                        </a>
                                        <div class="float-right">
                                            {{ number_format($asset['value']) }} ISK
                                        </div>
                                        <div class="collapse" data-parent="#location-{{ $location_info['id'] }}"
                                            id="items-{{ $location_info['id'] }}-{{ $asset['id'] }}-{{ $key }}">
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
                            @endforeach
                            @foreach($location_info['items'] as $key => $asset)
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
                                        <a href="#" class="text-white" data-toggle="collapse"
                                        data-target="#items-{{ $location_info['id'] }}-{{ $asset['id'] }}-{{ $key }}">
                                            @if($asset['item_name'] != 'None')
                                                {{ $asset['item_name'] }} ({{ $asset['name'] }})
                                            @else
                                                {{ $asset['name'] }}
                                            @endif
                                        </a>
                                        <div class="float-right">
                                            {{ number_format($asset['value']) }} ISK
                                        </div>
                                        <div class="collapse" data-parent="#location-{{ $location_info['id'] }}"
                                            id="items-{{ $location_info['id'] }}-{{ $asset['id'] }}-{{ $key }}">
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
                            @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
