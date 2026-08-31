@props(['label' => 'Date', 'error' => null, 'name' => null, 'required' => false])
<x-ui.input type="date" :label="$label" :error="$error" :name="$name" :required="$required" {{ $attributes }} />
