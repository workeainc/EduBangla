@props(['type' => 'info', 'title' => null])
<div {{ $attributes->merge(['class' => 'ui-alert ui-alert-'.$type]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">@if($title)<p class="font-semibold">{{ $title }}</p>@endif<div>{{ $slot }}</div></div>
