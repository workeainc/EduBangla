@props(['label', 'value', 'detail' => null, 'tone' => 'indigo'])
<x-ui.card {{ $attributes }}><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ $value }}</p>@if($detail)<p class="mt-1 text-xs text-slate-500">{{ $detail }}</p>@endif</x-ui.card>
