@props(['caption' => null])
<div {{ $attributes->merge(['class' => 'ui-table-wrap']) }}><table class="ui-table">@if($caption)<caption class="sr-only">{{ $caption }}</caption>@endif{{ $slot }}</table></div>
