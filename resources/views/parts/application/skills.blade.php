<div class="row">
    <div class="col-7 card-columns">
        @foreach($skills as $category => $skill)
        @php($escaped_cat = str_replace(' ', '-', $category))
        <div class="accordion" id="{{ $escaped_cat }}">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header" id="header-{{ $escaped_cat }}">
                        <button class="btn btn-link text-white" type="button" data-toggle="collapse" data-target="#collapse-{{ $escaped_cat }}" aria-expanded="false" aria-controls="collapse-{{ $escaped_cat }}">
                            {{ $category }}
                        </button>
                    </div>
                    <div id="collapse-{{ $escaped_cat }}" class="collapse" aria-labelledby="header-{{ $escaped_cat }}" data-parent="#{{ $escaped_cat }}">
                        <ul class="list-group">
                        @foreach($skill as $name => $info)
                            <div class="list-group-item bg-dark text-white">
                                {{ $name }}
                                <div class="float-right">
                                    @if($info['level'] == 5)
                                        @for($i = 0; $i < $info['level']; $i++)
                                            <i class="fa fa-star"></i>
                                        @endfor
                                        @for($i = $info['level']; $i < 5; $i++)
                                            <i class="far fa-star"></i>
                                        @endfor
                                    @else
                                        @for($i = 0; $i < $info['level']; $i++)
                                            <i class="fa fa-star" style="color: white;"></i>
                                        @endfor
                                        @for($i = $info['level']; $i < 5; $i++)
                                            <i class="far fa-star" style="color: white;"></i>
                                        @endfor
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="col-3">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="card-header">
                    Skill Queue
                </div>
                <ul class="list-group">
                @foreach($queue as $skill)
                    <div class="list-group-item bg-dark text-white">
                        {{ $skill['skill'] }}
                        <div class="float-right">
                        @for($i = 0; $i < $skill['end_level'] - 1; $i++)
                            <span style="color: white;" class="fa fa-star"></span>
                        @endfor
                            <span style="color: white;" class="fa fa-star-half"></span>
                        @for($i = $skill['end_level']; $i < 5; $i++)
                            <span style="color: white;" class="far fa-star"></span>
                        @endfor
                        </div>
                    </div>
                @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
