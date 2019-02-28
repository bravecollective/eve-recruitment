<div class="tab-pane fade" id="tab-skills" role="tabpanel" aria-labelledby="tab-skills">
    <div class="row">
    @foreach($skill_groups as $group)
        <div class="col-xl-3 col-lg-6 col-md-12">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Skills
                    </div>
                <ul class="list-group">
                @foreach($skills as $category => $skill)
                    @if(!in_array($category, $group))
                        @continue
                    @endif
                    <div class="list-group-item bg-dark text-white">
                        <h4>{{ $category }}</h4>
                    </div>
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
                    <div class="list-group-item bg-dark text-white"></div>
                    @endforeach
                </ul>
                </div>
            </div>
        </div>
    @endforeach
        <div class="col-xl-3 col-lg-6 col-md-12">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="card-header">
                        Assets
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>