@extends('default')
@section('content')
    <h1>{{ $title }} Recruitment Ad</h1>
    <form onsubmit="return saveAd(this);" id="corpAdForm">
    @if($title != 'Group')
        <input type="hidden" id="corpId" value="{{ $corp_id }}" />
    @endif
        <input type="hidden" value="{{ $ad->id }}" id="ad_id" name="ad_id" />
        <input type="hidden" id="_token" name="_token" value="{{ csrf_token() }}" />
    @if($title == 'Group')
        <div class="form-group">
            <label for="name">Group Name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Name..." value="{{ $ad->group_name }}" />
        </div>
    @endif
        <div class="form-group">
            <label for="slug">Page Slug</label>
            <input type="text" class="form-control" id="slug" name="slug" placeholder="Slug..." value="{{ $ad->slug }}" />
        </div>
        <div class="form-group">
            <label for="text">Ad Text</label>
            <textarea class="form-control" rows="10" id="text" name="text" placeholder="Ad Text...">{{ $ad->text }}</textarea>
            <small id="textInfo" class="form-text">Markdown is supported.</small>
        </div>
        <div class="form-group">
            <div class="row">
                <div class="col-6 col-xl-4">
                    <label>Form Questions</label>
                    <div class="questions">
                        @foreach($questions as $question)
                            <div class="input-group" style="margin-bottom: 1em;">
                                <input type="text" class="form-control question_{{ $question->id }}" value="{{ $question->question }}" name="questions[{{ $question->id }}][]" id="questions[{{ $question->id }}][]"/>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="deleteQuestion({{ $question->id }});">X</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-success" onClick="addQuestion();"><span class="fa fa-plus"></span></button>
                </div>
                <div class="col-6 col-xl-4">
                    <label>Application Requirements</label>
                    <div class="requirements">
                        @foreach($requirements as $requirement)
                        <div class="input-group" style="margin-bottom: 1em;">
                            {!! $requirement !!}
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-success" onClick="addRequirement();"><span class="fa fa-plus"></span></button>
                </div>
            </div>
        </div>
    <div class="form-check">
    @if($ad->allow_listing == 1)
        <input autocomplete="off" type="checkbox" id="allow_listing" class="form-check-input" name="allow_listing" checked>
    @else
        <input autocomplete="off" type="checkbox" id="allow_listing" class="form-check-input" name="allow_listing">
    @endif
        <label for="allow_listing" class="form-check-label">Allow Application Listing</label>
    </div>
        <small>If unchecked, application listing will not show up on the "Available Applications" page.</small><br /><br />
        <button type="submit" class="btn btn-primary">Submit</button>
    @if($ad->id)
        <a class="btn btn-danger" href="/recruitments/{{ $ad->id }}/delete">Delete Ad</a>
    @endif
    </form>
@endsection
@section('scripts')
    <script type="text/javascript">
        let counter = $(".questions").children().length + 1;
        let requirement = null;
        let newQuestionCounter = -1;
        let ad_id = $("#ad_id").val();

    @if($title != 'Group')
        $.get('/api/corp/' + {{ $corp_id }} + '/requirements/template', (e) => requirement = '<div class="input-group" style="margin-bottom: 1em;">' + e + '</div>');
    @elseif($ad->id)
        $.get('/api/group/' + {{ $ad->id }} + '/requirements/template', (e) => requirement = '<div class="input-group" style="margin-bottom: 1em;">' + e + '</div>');
    @else
        $.get('/api/group/0/requirements/template', (e) => requirement = '<div class="input-group" style="margin-bottom: 1em;">' + e + '</div>');
    @endif

        function deleteRequirement(requirement_id)
        {
            let url = '/api/recruitments/'+ ad_id +'/requirements/' + requirement_id;

            if (!confirm("Are you sure you wish to delete this requirement?"))
                return;

            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: "{{ csrf_token() }}" },
                success: function(e) {
                    e = JSON.parse(e);
                    if (e.success === false)
                        showError(e.message);
                    else
                    {
                        showInfo(e.message);
                        $(".requirement_" + requirement_id).parent().remove();
                    }
                }
            });
        }

        function deleteQuestion(question_id)
        {
            let ad_id = $("#ad_id").val();
            let url = '/api/recruitments/'+ ad_id +'/questions/' + question_id;

            if (!confirm("Are you sure you wish to delete this form question?"))
                return;

            if (question_id < 0)
            {
                $(".question_" + question_id).parent().remove();
                return;
            }

            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: "{{ csrf_token() }}" },
                success: function(e) {
                    e = JSON.parse(e);
                    if (e.success === false)
                        showError(e.message);
                    else
                    {
                        showInfo(e.message);
                        $(".question_" + question_id).parent().remove();
                    }
                }
            });
        }

        function addQuestion() {
            let toAppend = "<div class=\"input-group\" style=\"margin-bottom: 1em;\">\n" +
"                                <input type=\"text\" class=\"form-control question_" + newQuestionCounter +"\" placeholder=\"Question " + counter + "...\" name=\"questions[0][]\" id=\"questions[0][]\"/>\n" +
"                                <div class=\"input-group-append\">\n" +
"                                    <button type=\"button\" class=\"btn btn-outline-secondary\" onclick=\"deleteQuestion(" + newQuestionCounter + ");\">X</button>\n" +
"                                </div>\n" +
"                            </div>";
            $(".questions").append(toAppend);
            newQuestionCounter--;
            counter++;
        }

        function addRequirement()
        {
            $(".requirements").append(requirement);
        }

        function saveAd(f)
        {
           f = $(f).serializeObject();
           let c = $("#corpId").val();
           let url = (c !== undefined) ? '/corporations/' + c + '/ad/save' : '/group/ad/save';

           $.post(url, f, function(e) {
                e = JSON.parse(e);

                if (e.success === false)
                    showError(e.message);
                else
                {
                    if (c !== undefined)
                        setTimeout(() => location.reload(), 2000);
                    else
                        setTimeout(() => window.location.href = '/group/ad/' + e.data, 2000);

                    showInfo(e.message + ". Page will reload in a few seconds");
                }
           });

            return false;
        }
    </script>
@endsection