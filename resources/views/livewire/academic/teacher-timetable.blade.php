<div><h1>My timetable</h1>@foreach ($slots as $slot)<div>{{ $slot->weekday }} {{ $slot->starts_at }}–{{ $slot->ends_at }}</div>@endforeach</div>
