@props(['variant' => 'primary', 'type' => 'button', 'loading' => null])
@php($variants = ['primary' => 'ui-button-primary', 'secondary' => 'ui-button-secondary', 'danger' => 'ui-button-danger', 'ghost' => 'ui-button-ghost'])
<button type="{{ $type }}" {{ $attributes->merge(['class' => 'ui-button '.($variants[$variant] ?? $variants['primary'])]) }} @if($loading) wire:loading.attr="disabled" wire:loading.attr="aria-busy" @endif>
    @if($loading)<span wire:loading wire:target="{{ $loading }}" class="ui-spinner" aria-hidden="true"></span>@endif
    <span>{{ $slot }}</span>
</button>
