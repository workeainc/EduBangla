<div>
    <h1>Timetables</h1>
    <form wire:submit="saveDraft">
        <input wire:model="name" placeholder="Timetable name">
        <input wire:model="academic_year_id" placeholder="Academic year ID">
        <input wire:model="class_id" placeholder="Class ID">
        <input wire:model="section_id" placeholder="Section ID">
        <button type="button" wire:click="addSlot">Add slot</button>
        @foreach ($slots as $index => $slot)
            <fieldset wire:key="slot-{{ $index }}">
                <input wire:model="slots.{{ $index }}.teacher_assignment_id" placeholder="Teacher assignment ID">
                <input wire:model="slots.{{ $index }}.subject_assignment_id" placeholder="Subject assignment ID">
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
