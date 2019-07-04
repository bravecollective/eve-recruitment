<div class="row" style="margin-left: 0;">
    <div class="col-xl-2 col-12">
        <div class="row">
            <strong>Recipients:</strong>
        </div>
        @foreach($mail->recipients as $recipient)
            <div class="row">
                {{ $recipient['name'] }}
                @if ($recipient['type'] != 'character')
                    ({{ $recipient['type'] }})
                @endif
            </div>
        @endforeach
        <br />
        <div class="row">
            <strong>Date:</strong>
        </div>
        <div class="row">
            {{ $mail->getTimestamp()->format('Y-m-d H:i') }}
        </div>
    </div>
    <div class="col-xl-10 col-12">
        {!!  preg_replace('/(<font[^>]*>)|(<\/font>)/', '', $mail->contents) !!}
    </div>
</div>