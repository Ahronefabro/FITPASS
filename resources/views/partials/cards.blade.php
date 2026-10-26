<div class="cards">
    @foreach ($cards as $card)
        <div class="card">
            <span>{{ $card[0] }}</span>
            <strong>{{ $card[1] }}</strong>
            <small @class(['bad' => $card[3] ?? false])>{{ $card[2] }}</small>
        </div>
    @endforeach
</div>