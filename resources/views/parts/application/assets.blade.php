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
                                @include('parts/application/asset_location', ['asset' => $asset, 'key' => $key])
                            @endforeach
                            @foreach($location_info['items'] as $key => $asset)
                                @include('parts/application/asset_location', ['asset' => $asset, 'key' => $key])
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
