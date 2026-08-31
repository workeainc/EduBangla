@props(['label' => 'Time', 'error' => null, 'name' => null, 'required' => false])
<x-ui.input type="time" :label="$label" :error="$error" :name="$name" :required="$required" {{ $attributes }} />
