@props(['title', 'description' => null])
<div {{ $attributes->merge(['class' => 'ui-page-header']) }}><div><h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h1>@if($description)<p class="mt-2 text-sm text-slate-600">{{ $description }}</p>@endif</div>{{ $actions ?? '' }}</div>
