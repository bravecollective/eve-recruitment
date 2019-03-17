<div class="row justify-content-center">
    <div class="col-8">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="card-header">
                    Contracts
                </div>
                <div class="table-responsive">
                    <table id="contracts-table" class="table table-hover table-striped table-bordered bg-dark text-white">
                        <thead>
                        <tr>
                            <th scope="col" class="align-text-top">Issued/Expired</th>
                            <th scope="col" class="align-text-top">Title</th>
                            <th scope="col" class="align-text-top">Volume</th>
                            <th scope="col" class="align-text-top">Type/Status</th>
                            <th scope="col" class="align-text-top">Issuer</th>
                            <th scope="col" class="align-text-top">Assignee/Acceptor</th>
                            <th scope="col" class="align-text-top">Price/Reward</th>
                            <th scope="col" class="align-text-top">Collateral<br />(If Applicable)</th>
                            <th scope="col" class="align-text-top">Start/End Location</th>
                        </tr>
                        </thead>
                        @foreach($contracts as $contract)
                            <tbody>
                                <tr data-toggle="collapse" data-target="#items-{{ $contract['id'] }}" aria-expanded="false" aria-controls="items-{{ $contract['id'] }}" class="clickable">
                                    <td>{{ $contract['issued'] }}<br />{{ $contract['expired'] }}</td>
                                    <td>{{ $contract['title'] }}</td>
                                    <td>{{ $contract['volume'] }} m<sup>3</sup></td>
                                    <td>{{ $contract['type'] }}<br />{{ $contract['status'] }}</td>
                                    <td>{{ $contract['issuer'] }}</td>
                                    <td>{{ $contract['assignee'] }}<br />{{ $contract['acceptor'] }}</td>
                                    <td>{{ $contract['price'] }} ISK</td>
                                    <td>
                                        @if($contract['collateral'] != null)
                                            {{ $contract['collateral'] }} ISK
                                        @endif
                                    </td>
                                    <td>{{ $contract['start'] }}<br />{{ $contract['end'] }}</td>
                                </tr>
                            </tbody>
                            <tbody id="items-{{ $contract['id'] }}" class="collapse">
                            @foreach($contract['items'] as $item)
                                <tr>
                                    <td>
                                        <img src="https://image.eveonline.com/Type/{{ $item['id'] }}_32.png" />
                                        {{ $item['type'] }} x{{ $item['quantity'] }}
                                    </td>
                                    <td>{{ $item['price'] }} isk</td>
                                </tr>
                            @endforeach
                            </tbody>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>