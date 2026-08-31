<div class="space-y-6">
    <x-ui.breadcrumbs :items="[['label' => 'Attendance']]" />
    <x-ui.page-header title="Attendance" description="Record attendance only for classes and subjects assigned to your teacher profile." />
    @if($message)<x-ui.alert type="success">{{ $message }}</x-ui.alert>@endif
    @if($errors->any())<x-ui.alert type="error" title="Attendance could not be saved">{{ $errors->first() }}</x-ui.alert>@endif

    <x-ui.card title="Select attendance scope" subtitle="Choose an assignment, date, and period to start or reopen a session.">
        @if($assignments->isEmpty())
            <x-ui.empty-state title="No teaching assignments" message="Your school administrator must assign a class, section, and subject before you can record attendance." />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.select label="Teaching assignment" wire:model.live="assignmentId" required><option value="">Choose an assignment</option>@foreach($assignments as $a)<option value="{{ $a->id }}">{{ $a->academicYear?->name }} — {{ $a->academicClass?->name }} · {{ $a->section?->name }} — {{ $a->subjectAssignment?->subject?->name }} @if($a->group?->name) · {{ $a->group->name }} @endif</option>@endforeach</x-ui.select>
                <x-ui.date-input label="Attendance date" wire:model.live="date" required />
                <x-ui.input label="Period" wire:model.live="period" required placeholder="Regular" />
            </div>
            <div class="mt-4"><x-ui.button wire:click="loadStudents" loading="loadStudents">Load students</x-ui.button></div>
        @endif
    </x-ui.card>

    @if($assignmentId && $students->isEmpty())
        <x-ui.empty-state title="No enrolled students" message="There are no students enrolled in this assignment's class and section." />
    @elseif($students->isNotEmpty())
        <x-ui.card title="Student attendance" :subtitle="$session ? 'Session: '.$session->attendance_date?->format('M j, Y').' · '.$session->period : 'New attendance session'">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">@if($session)<x-ui.status-badge :status="$session->status" />@else<x-ui.badge tone="info">New session</x-ui.badge>@endif @if(!$session?->isFinalized())<x-ui.button variant="secondary" wire:click="presentAll" loading="presentAll">Mark all present</x-ui.button>@endif</div>
            @if($session?->isFinalized())<x-ui.alert type="warning">This attendance session is finalized and read-only. Contact a school administrator for an authorized correction.</x-ui.alert>@endif
            <x-ui.data-table caption="Student attendance"><thead><tr><th>Student</th><th>Roll</th><th>Status</th></tr></thead><tbody>@foreach($students as $studentId => $enrollment)<tr wire:key="attendance-{{ $studentId }}"><td data-label="Student">{{ trim($enrollment->student?->first_name.' '.$enrollment->student?->last_name) }}</td><td data-label="Roll">{{ $enrollment->roll }}</td><td data-label="Status"><x-ui.select :label="'Attendance status for '.trim($enrollment->student?->first_name.' '.$enrollment->student?->last_name)" wire:model.live="statuses.{{ $studentId }}" :disabled="$session?->isFinalized() ?? false">@foreach(\App\Domain\Attendance\AttendanceStatus::values() as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</x-ui.select></td></tr>@endforeach</tbody></x-ui.data-table>
            @if(!$session?->isFinalized())<div class="mt-5 flex flex-wrap gap-3"><x-ui.button wire:click="save" loading="save">Save attendance</x-ui.button>@if($session)<x-ui.confirm-dialog title="Finalize attendance?" message="Finalizing locks this session. Teachers will no longer be able to edit student statuses." confirm-label="Finalize session" event="finalize">Finalize attendance</x-ui.confirm-dialog>@endif</div>@endif
        </x-ui.card>
    @elseif($assignmentId)
        <x-ui.loading-state label="Load enrolled students for this attendance scope" />
    @else
        <x-ui.empty-state title="Choose an assignment" message="Select one of your teaching assignments to load the enrolled students." />
    @endif
</div>
