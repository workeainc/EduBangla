@props(['label' => null, 'error' => null, 'name' => null])
@php($id = $attributes->get('id', $name ?? 'checkbox-'.uniqid()))
<div class="ui-field"><label for="{{ $id }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-700"><input id="{{ $id }}" type="checkbox" name="{{ $name }}" {{ $attributes->except(['id','class'])->merge(['class' => 'ui-checkbox']) }} @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>{{ $label }}</label>@if($error)<p id="{{ $id }}-error" class="ui-error" role="alert">{{ $error }}</p>@endif</div>
