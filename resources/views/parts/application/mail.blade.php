<div class="row justify-content-center">
    <div class="col-xl-8 col-12">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="card-header">
                    Mail
                </div>
                <ul class="list-group">
                @foreach($mails as $mail)
                    <div class="list-group-item bg-dark text-white"
                     @if($name == $mail->sender)
                         style="background-color: #8a4141 !important;"
                     @endif
                         onclick="toggleCollapse('#mail-{{ $mail->getMailId() }}', {{ $mail->getMailId() }})">
                        <div class="row">
                            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-5 col-12">
                                <strong>Sender:</strong> {{ $mail->sender }}
                            </div>
                            <div class="col-xl-7 col-lg-7 col-md-7 col-sm-5 col-12">
                                <strong>Subject:</strong> {{ $mail->getSubject() }}
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-2 col-sm-2 col-12">
                            @if($mail->getIsRead())
                                Read
                            @else
                                Unread
                            @endif
                            </div>
                        </div>
                    </div>
                    <div class="collapse list-item-group text-white" id="mail-{{ $mail->getMailId() }}"></div>
                @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>