@props(['status'])
@php($tone = match(strtolower((string) $status)) { 'published','paid','active','finalized','locked' => 'success', 'draft','open','scheduled','partially_paid' => 'warning', 'archived','withdrawn','void','cancelled','expired','inactive' => 'neutral', 'failed','rejected' => 'danger', default => 'info' })
<x-ui.badge :tone="$tone" {{ $attributes }}>{{ str_replace('_', ' ', ucfirst($status)) }}</x-ui.badge>
