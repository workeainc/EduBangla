<div class="space-y-6">
    <x-ui.breadcrumbs :items="[['label' => 'Students & enrollment']]" />
    <x-ui.page-header title="Students & enrollment" description="Create a school-scoped student, connect a guardian, and place the student in an academic class." />
    @if($message)<x-ui.alert type="success" role="status">{{ $message }}</x-ui.alert>@endif
    @if($errors->any())<x-ui.alert type="error" title="Please review the form" role="alert">{{ $errors->first() }}</x-ui.alert>@endif
    <form wire:submit="submit" class="space-y-6">
        <x-ui.card title="1. Student details" subtitle="This creates a student profile only; no login account is created.">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.input label="Student code" name="student_code" wire:model="student.student_code" error="{{ $errors->first('student.student_code') }}" required />
                <x-ui.input label="First name" name="first_name" wire:model="student.first_name" error="{{ $errors->first('student.first_name') }}" required />
                <x-ui.input label="Last name" name="last_name" wire:model="student.last_name" error="{{ $errors->first('student.last_name') }}" />
                <x-ui.date-input label="Date of birth" name="date_of_birth" wire:model="student.date_of_birth" error="{{ $errors->first('student.date_of_birth') }}" />
                <x-ui.input label="Phone" name="phone" wire:model="student.phone" error="{{ $errors->first('student.phone') }}" />
                <x-ui.input label="Email" name="email" type="email" wire:model="student.email" error="{{ $errors->first('student.email') }}" />
                <x-ui.textarea label="Address" name="address" wire:model="student.address" error="{{ $errors->first('student.address') }}" class="sm:col-span-2 lg:col-span-3" />
            </div>
        </x-ui.card>
        <x-ui.card title="2. Guardian" subtitle="Choose an existing same-school guardian or create a new one.">
            <div class="mb-4 flex flex-wrap gap-4" role="radiogroup" aria-label="Guardian option">
                <label class="inline-flex items-center gap-2"><input type="radio" wire:model.live="guardianMode" value="new"> <span>New guardian</span></label>
                <label class="inline-flex items-center gap-2"><input type="radio" wire:model.live="guardianMode" value="existing"> <span>Existing guardian</span></label>
            </div>
            @if($guardianMode === 'existing')
                <x-ui.select label="Existing guardian" name="guardian_id" wire:model="guardian_id" error="{{ $errors->first('guardian_id') }}" required><option value="">Choose a guardian</option>@forelse($guardians as $guardian)<option value="{{ $guardian->id }}">{{ $guardian->name }} · {{ $guardian->phone }}</option>@empty<option value="">No existing guardians</option>@endforelse</x-ui.select>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><x-ui.input label="Guardian name" name="guardian_name" wire:model="guardian.name" error="{{ $errors->first('guardian.name') }}" required /><x-ui.input label="Guardian phone" name="guardian_phone" wire:model="guardian.phone" error="{{ $errors->first('guardian.phone') }}" required /><x-ui.input label="Guardian email" name="guardian_email" type="email" wire:model="guardian.email" error="{{ $errors->first('guardian.email') }}" /><x-ui.textarea label="Guardian address" name="guardian_address" wire:model="guardian.address" error="{{ $errors->first('guardian.address') }}" class="sm:col-span-2 lg:col-span-3" /></div>
            @endif
            <div class="mt-4 grid gap-4 sm:grid-cols-2"><x-ui.input label="Relationship" name="relationship_type" wire:model="relationship_type" error="{{ $errors->first('relationship_type') }}" required /><x-ui.checkbox label="Primary guardian" name="is_primary" wire:model="is_primary" /></div>
        </x-ui.card>
        <x-ui.card title="3. Enrollment" subtitle="Selectors are school-scoped and dependent values reset when their parent changes.">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><x-ui.select label="Academic year" name="academic_year_id" wire:model.live="academic_year_id" error="{{ $errors->first('academic_year_id') }}" required><option value="">Choose a year</option>@forelse($years as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@empty<option value="">No academic years provisioned</option>@endforelse</x-ui.select><x-ui.select label="Class" name="class_id" wire:model.live="class_id" :disabled="!$academic_year_id" error="{{ $errors->first('class_id') }}" required><option value="">{{ $academic_year_id ? 'Choose a class' : 'Choose a year first' }}</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</x-ui.select><x-ui.select label="Section" name="section_id" wire:model="section_id" :disabled="!$class_id" error="{{ $errors->first('section_id') }}" required><option value="">{{ $class_id ? 'Choose a section' : 'Choose a class first' }}</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</x-ui.select><x-ui.select label="Group" name="group_id" wire:model="group_id" :disabled="!$class_id" error="{{ $errors->first('group_id') }}"><option value="">No group</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</x-ui.select><x-ui.input label="Roll" name="roll" type="number" min="1" wire:model="roll" error="{{ $errors->first('roll') }}" required /></div>
        </x-ui.card>
        <div class="flex items-center gap-3"><x-ui.button type="submit" loading="submit">Create student and enrollment</x-ui.button><span wire:loading wire:target="submit" role="status" aria-live="polite"><x-ui.loading-state label="Creating records" /></span></div>
    </form>
    <x-ui.card title="Students in this school"><x-ui.data-table caption="Students and enrollment count"><thead><tr><th>Student</th><th>Code</th><th>Enrollments</th><th>Status</th></tr></thead><tbody>@forelse($students as $record)<tr><td data-label="Student">{{ trim($record->first_name.' '.$record->last_name) }}</td><td data-label="Code">{{ $record->student_code }}</td><td data-label="Enrollments">{{ $record->enrollments_count }}</td><td data-label="Status"><x-ui.status-badge :status="$record->status" /></td></tr>@empty<tr><td colspan="4"><x-ui.empty-state title="No students yet" message="Create the first student above." /></td></tr>@endforelse</tbody></x-ui.data-table></x-ui.card>
</div>
