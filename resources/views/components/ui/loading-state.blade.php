@props(['label' => 'Loading'])
<div {{ $attributes->merge(['class' => 'ui-state']) }} role="status" aria-live="polite"><span class="ui-spinner" aria-hidden="true"></span><span>{{ $label }}…</span></div>
