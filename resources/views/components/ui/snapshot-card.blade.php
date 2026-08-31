@props(['title', 'status' => null, 'meta' => null])
<x-ui.card {{ $attributes }}><div class="flex items-start justify-between gap-4"><h2 class="font-semibold">{{ $title }}</h2>@if($status)<x-ui.status-badge :status="$status" />@endif</div>@if($meta)<p class="mt-1 text-xs text-slate-500">{{ $meta }}</p>@endif<div class="mt-4 border-t border-slate-100 pt-4">{{ $slot }}</div></x-ui.card>
