<div>
    <h1>Timetables</h1>
    <form wire:submit="saveDraft">
        <input wire:model="name" placeholder="Timetable name">
        <select wire:model="academic_year_id"><option value="">Academic year</option>@foreach ($years as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select>
        <select wire:model="class_id"><option value="">Class</option>@foreach ($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select>
        <select wire:model="section_id"><option value="">Section</option>@foreach ($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select>
        <button type="button" wire:click="addSlot">Add slot</button>
        @foreach ($slots as $index => $slot)
            <fieldset wire:key="slot-{{ $index }}">
                <select wire:model="slots.{{ $index }}.teacher_assignment_id"><option value="">Teacher assignment</option>@foreach ($assignments as $assignment)<option value="{{ $assignment->id }}">{{ $assignment->teacher?->first_name }} {{ $assignment->teacher?->last_name }} — {{ $assignment->subjectAssignment?->subject?->name }} ({{ $assignment->academicClass?->name }}/{{ $assignment->section?->name }})</option>@endforeach</select>
                <select wire:model="slots.{{ $index }}.subject_assignment_id"><option value="">Subject assignment</option>@foreach ($assignments as $assignment)<option value="{{ $assignment->subject_assignment_id }}">{{ $assignment->subjectAssignment?->subject?->name }} — {{ $assignment->academicClass?->name }}/{{ $assignment->section?->name }}</option>@endforeach</select>
                <input wire:model="slots.{{ $index }}.weekday" placeholder="Weekday (0-6)">
                <input wire:model="slots.{{ $index }}.starts_at" placeholder="Start (HH:MM)">
                <input wire:model="slots.{{ $index }}.ends_at" placeholder="End (HH:MM)">
                <button type="button" wire:click="removeSlot({{ $index }})">Remove slot</button>
            </fieldset>
        @endforeach
        <button type="submit">Save draft</button>
    </form>
    @foreach ($timetables as $item)
        <section wire:key="timetable-{{ $item->id }}">
            <a href="{{ route('admin.timetables.show', [$school, $item]) }}">{{ $item->name }}</a> — {{ $item->status }} ({{ $item->slots_count }} slots)
            @if ($item->status === 'draft') <button wire:click="publish({{ $item->id }})">Publish</button> @endif
            @if ($item->status === 'published') <button wire:click="archive({{ $item->id }})">Archive</button> @endif
        </section>
    @endforeach
</div>
