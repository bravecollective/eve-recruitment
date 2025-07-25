@if($warnings)
    @foreach($warnings as $warning)
        <div class="list-group-item bg-dark text-white">
            <strong>{{ $warning['type'] }}</strong> <br/> {{ $warning['character'] }}
        </div>
    @endforeach
@endif