<div class="row justify-content-center">
    <div class="col-12 col-lg-7 col-xl-5">
        @foreach($skills as $category => $skill)
        @php($escaped_cat = str_replace(' ', '-', $category))
        <div class="accordion" id="{{ $escaped_cat }}">
            <div class="card bg-dark text-white">
                <div class="card-body" data-toggle="collapse" data-target="#collapse-{{ $escaped_cat }}" aria-expanded="false" aria-controls="collapse-{{ $escaped_cat }}">
                    <div class="card-header" id="header-{{ $escaped_cat }}">
                        <button class="btn btn-link text-white float-left" style="padding: 0;" type="button">
                            {{ $category }}
                        </button>
                        <div class="text-right">
                            {{ number_format($skill['skillpoints']) }} SP
                        </div>
                    </div>
                    <div id="collapse-{{ $escaped_cat }}" class="collapse" aria-labelledby="header-{{ $escaped_cat }}" data-parent="#{{ $escaped_cat }}">
                        <ul class="list-group">
                        @foreach($skill as $name => $info)
                            @if($name == "skillpoints")
                                @continue
                            @endif
                            <div class="list-group-item bg-dark text-white">
                                {{ $name }}
                                <div class="float-right">
                                    @if($info['trained'] == 5)
                                        @for($i = 0; $i < $info['trained']; $i++)
                                            <i class="fa fa-star"></i>
                                        @endfor
                                        @for($i = $info['trained']; $i < 5; $i++)
                                            <i class="far fa-star"></i>
                                        @endfor
                                    @else
                                        @for($i = 0; $i < $info['trained']; $i++)
                                            <i class="fa fa-star" style="color: white;"></i>
                                        @endfor
                                        @for($i = $info['trained']; $i < 5; $i++)
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
    <div class="col-12 col-lg-5 col-xl-4">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="card-header">
                    <div class="float-left">
                        Skill Queue
                    @if(count($queue) > 1 /* $queue always has the `queue_end` key set, so count is at least 1 */
                        && $queue[0]['paused'] == true)
                        (PAUSED)
                    @endif
                    </div>
                    <div class="text-right">
                        Est. Completion: {{ ($queue['queue_end']) ? $queue['queue_end'] : '-' }}
                    </div>
                </div>
                <ul class="list-group">
                @foreach($queue as $key => $skill)
                    @if($key === "queue_end")
                        @continue
                    @endif
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
