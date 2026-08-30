<div>
    <h1>Notices</h1>
    <form wire:submit="saveDraft">
        <input wire:model="title" placeholder="Title">
        <textarea wire:model="body" placeholder="Message"></textarea>
        <select wire:model="audiences.0.type"><option value="school">School</option><option value="role">Role</option><option value="class_section">Class and section</option></select>
        <input wire:model="audiences.0.role" placeholder="Role for role audience">
        <input wire:model="audiences.0.academic_year_id" placeholder="Academic year ID">
        <input wire:model="audiences.0.class_id" placeholder="Class ID">
        <input wire:model="audiences.0.section_id" placeholder="Section ID">
        <button type="submit">Save draft</button>
    </form>
    @foreach ($notices as $item)
        <section wire:key="notice-{{ $item->id }}">
            <a href="{{ route('admin.notices.show', [$school, $item]) }}">{{ $item->title }}</a> — {{ $item->status }} ({{ $item->deliveries_count }} deliveries)
            @if ($item->status === 'draft') <button wire:click="publish({{ $item->id }})">Publish</button> @endif
            @if ($item->status === 'published') <button wire:click="withdraw({{ $item->id }})">Withdraw</button> @endif
        </section>
    @endforeach
</div>
