@if($warnings)
    @foreach($warnings as $warning)
        <div class="list-group-item bg-dark text-white">
            {{ $warning }}
        </div>
    @endforeach
@endif