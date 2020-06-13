<div class="row">
    <div class="col-12 col-xl-4">
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
                            @foreach($location_info['containers'] as $asset)
                                @include('parts/application/asset_location', ['asset' => $asset])
                            @endforeach
                            @foreach($location_info['items'] as $asset)
                                @include('parts/application/asset_location', ['asset' => $asset])
                            @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="card-header">
                    Journal
                </div>
                <div class="table-responsive">
                    <table id="journal-table" class="table table-hover table-striped table-bordered bg-dark text-white">
                        <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Balance</th>
                            <th scope="col">Type</th>
                            <th scope="col">Description</th>
                            <th scope="col">Sender</th>
                            <th scope="col">Receiver</th>
                            <th scope="col">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($journal as $entry)
                            <tr @if($entry['type'] == 'Player Donation' || $entry['type'] == 'Player Trading') style="background-color: #8a4141;" @endif>
                                <td>{{ $entry['date'] }}</td>
                                <td>{{ $entry['balance'] }} ISK</td>
                                <td>{{ $entry['type'] }}</td>
                                <td>
                                    {{ $entry['description'] }}
                                    @if($entry['type'] == 'Player Donation')
                                        <br /><strong>Note: </strong>{{ $entry['note'] }}
                                    @endif
                                </td>
                                <td>{{ $entry['sender'] }}</td>
                                <td>{{ $entry['receiver'] }}</td>
                                <td>{{ $entry['amount'] }} ISK</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
