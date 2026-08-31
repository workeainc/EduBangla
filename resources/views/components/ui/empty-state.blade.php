@props(['title' => 'Nothing here yet', 'message' => null])
<div {{ $attributes->merge(['class' => 'ui-state']) }} role="status"><p class="font-semibold text-slate-800">{{ $title }}</p>@if($message)<p class="mt-1 text-sm text-slate-500">{{ $message }}</p>@endif{{ $slot }}</div>
