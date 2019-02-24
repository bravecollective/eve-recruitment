<div class="tab-pane fade" id="tab-mail" role="tabpanel" aria-labelledby="tab-mail">
    <div class="row justify-content-center">
        <div class="col-7">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Mail
                    </div>
                    <ul class="list-group">
                    @foreach($mails as $mail)
                        <div class="list-group-item bg-dark text-white" onclick="toggleCollapse('mail-{{ $mail->getMailId() }}')">
                            <div class="row">
                                <div class="col-2">
                                    <strong>Sender:</strong> {{ $mail->sender }}
                                </div>
                                <div class="col-5">
                                    <strong>Subject:</strong> {{ $mail->getSubject() }}
                                </div>
                                <div class="col-1">
                                @if($mail->getIsRead())
                                    Read
                                @else
                                    Unread
                                @endif
                                </div>
                            </div>
                        </div>
                        <div class="collapse list-item-group text-white" id="mail-{{ $mail->getMailId() }}">
                            <div class="row" style="margin-left: 0;">
                                <div class="col-2">
                                    <div class="row">
                                        <strong>Recipients: </strong>
                                    </div>
                                    @foreach($mail->recipients as $recipient)
                                        <div class="row">
                                            {{ $recipient['name'] }}
                                            @if ($recipient['type'] != 'character')
                                                ({{ $recipient['type'] }})
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="col-10">
                                    {!!  preg_replace('/(<font[^>]*>)|(<\/font>)/', '', $mail->contents) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@section('scripts')
    <script type="text/javascript">
        function toggleCollapse(anchor)
        {
            $('#' + anchor).collapse('toggle');
        }
    </script>
@endsection