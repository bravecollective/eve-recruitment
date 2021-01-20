<div class="col-12">
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
                        @php($color = $entry['between_alts'] ? '#827846' : '#8a4141')
                        <tr @if($entry['type'] == 'Player Donation' || $entry['type'] == 'Player Trading' || $entry['type'] == 'Corporation Account Withdrawal') style="background-color: {{ $color }};" @endif>
                            <td>{{ $entry['date'] }}</td>
                            <td data-sort="{{ $entry['raw_balance'] }}">{{ $entry['balance'] }} ISK</td>
                            <td>{{ $entry['type'] }}</td>
                            <td>
                                {{ $entry['description'] }}
                                @if($entry['type'] == 'Player Donation')
                                    <br /><strong>Note: </strong>{{ $entry['note'] }}
                                @endif
                            </td>
                            <td>{{ $entry['sender'] }}</td>
                            <td>{{ $entry['receiver'] }}</td>
                            <td data-sort="{{ $entry['raw_amount'] }}">{{ $entry['amount'] }} ISK</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
