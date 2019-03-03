<div class="tab-pane fade" id="tab-assets" role="tabpanel" aria-labelledby="tab-assets">
    <div class="row">
        <div class="col-4">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Assets
                    </div>
                </div>
            </div>
        </div>
        <div class="col-8">
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
                                <tr>
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
</div>