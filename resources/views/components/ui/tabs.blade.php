@props(['tabs' => [], 'active' => null])
<nav {{ $attributes->merge(['class' => 'ui-tabs']) }} role="tablist">@foreach($tabs as $tab)<a href="{{ $tab['url'] ?? '#' }}" role="tab" @if(($tab['key'] ?? null) === $active) aria-selected="true" @endif class="{{ ($tab['key'] ?? null) === $active ? 'ui-tab-active' : '' }}">{{ $tab['label'] }}</a>@endforeach</nav>
