<div>
    <h1>Notices</h1>
    @foreach ($deliveries as $item)
        <section wire:key="delivery-{{ $item->id }}">
            <a href="{{ route($role.'.notices.show', [$school, $item]) }}">{{ $item->notice->title }}</a>
            — {{ $item->notice->status }}
            @if (! $item->read_at)<button wire:click="markRead({{ $item->id }})">Mark read</button>@endif
        </section>
    @endforeach
    @if ($delivery)
        <article><h2>{{ $delivery->notice->title }}</h2><p>{{ $delivery->notice->body }}</p></article>
    @endif
</div>
