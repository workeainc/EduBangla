<div class="space-y-6">
    <x-ui.breadcrumbs :items="[['label' => 'My assignments']]" />
    <x-ui.page-header title="My assignments" :description="trim($teacher->first_name.' '.$teacher->last_name).' · '.$teacher->employee_code" />
    <x-ui.card title="Teaching assignments" subtitle="Only assignments linked to your active teacher profile are shown.">
        <x-ui.data-table caption="My teaching assignments"><thead><tr><th>Academic year</th><th>Subject</th><th>Class / section</th><th>Group</th></tr></thead><tbody>@forelse($assignments as $assignment)<tr><td data-label="Academic year">{{ $assignment->academicYear?->name }}</td><td data-label="Subject">{{ $assignment->subjectAssignment?->subject?->name }}</td><td data-label="Class / section">{{ $assignment->academicClass?->name }} · {{ $assignment->section?->name }}</td><td data-label="Group">{{ $assignment->group?->name ?? 'All groups' }}</td></tr>@empty<tr><td colspan="4"><x-ui.empty-state title="No assignments yet" message="Your school administrator has not assigned a class, section, and subject to your teacher profile." /></td></tr>@endforelse</tbody></x-ui.data-table>
    </x-ui.card>
</div>
