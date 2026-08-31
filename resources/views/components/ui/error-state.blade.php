@props(['title' => 'Something went wrong', 'message' => 'Please try again.'])
<x-ui.alert type="error" :title="$title" {{ $attributes }}>{{ $message }}{{ $slot }}</x-ui.alert>
