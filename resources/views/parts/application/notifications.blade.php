<div class="row justify-content-center">
    <div class="col-12">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="card-header">
                    Notifications
                </div>
                <div class="table-responsive-xl">
                    <table id="notifications-table" class="table table-hover table-striped table-bordered bg-dark text-white">
                        <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Sender</th>
                            <th scope="col">Type</th>
                            <th scope="col">Variables</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($notifications as $notification)
                            <tr>
                                <td>{{ $notification['timestamp'] }}</td>
                                <td>{{ $notification['sender'] }}</td>
                                <td>{{ $notification['type'] }}</td>
                                <td>{{ $notification['variables'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>