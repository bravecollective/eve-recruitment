<div class="tab-pane fade" id="tab-market" role="tabpanel" aria-labelledby="tab-market">
    <div class="row">
        <div class="col-6">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Market Transactions
                    </div>
                    <div class="table-responsive">
                        <table id="transactions-table" class="table table-hover table-striped table-bordered bg-dark text-white">
                            <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Client</th>
                                <th scope="col">Item</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">ISK Change</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction['date'] }}</td>
                                    <td>{{ $transaction['client'] }}</td>
                                    <td>{{ $transaction['item'] }}</td>
                                    <td>{{ $transaction['quantity'] }}</td>
                                    <td>
                                    @if($transaction['buy'])
                                        -
                                    @else
                                        +
                                    @endif
                                        {{ $transaction['change'] }}
                                    </td>
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