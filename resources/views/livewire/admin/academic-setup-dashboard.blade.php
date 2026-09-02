<div class="space-y-6">
    <x-ui.breadcrumbs :items="[['label' => 'Academic setup']]" />
    <x-ui.page-header title="Academic setup" description="Build your school’s academic foundation in order. Each step uses only records from this school." />
    <x-ui.card title="Setup readiness" subtitle="Complete the required catalog before configuring assignments.">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <a href="{{ route('admin.academic-years', $school) }}" class="rounded-lg border border-slate-200 p-4 hover:border-indigo-300"><p class="font-semibold">1. Academic years</p><p class="mt-1 text-sm text-slate-600">{{ $counts['years'] }} configured · {{ $activeYear ? 'Active: '.$activeYear->name : 'No active academic year' }}</p><p class="mt-3 text-sm font-medium text-indigo-700">{{ $counts['years'] ? 'Manage academic years' : 'Create your first academic year' }}</p></a>
            <a href="{{ route('admin.classes', $school) }}" class="rounded-lg border border-slate-200 p-4 hover:border-indigo-300"><p class="font-semibold">2. Classes</p><p class="mt-1 text-sm text-slate-600">{{ $counts['classes'] }} configured</p><p class="mt-3 text-sm font-medium text-indigo-700">{{ $counts['classes'] ? 'View classes' : 'Create classes' }}</p></a>
            <a href="{{ route('admin.sections', $school) }}" class="rounded-lg border border-slate-200 p-4 hover:border-indigo-300"><p class="font-semibold">3. Sections</p><p class="mt-1 text-sm text-slate-600">{{ $counts['sections'] }} configured</p><p class="mt-3 text-sm font-medium text-indigo-700">{{ $counts['classes'] ? 'Create sections' : 'Create a class first' }}</p></a>
            <a href="{{ route('admin.subjects', $school) }}" class="rounded-lg border border-slate-200 p-4 hover:border-indigo-300"><p class="font-semibold">4. Subjects</p><p class="mt-1 text-sm text-slate-600">{{ $counts['subjects'] }} configured</p><p class="mt-3 text-sm font-medium text-indigo-700">{{ $counts['subjects'] ? 'View subjects' : 'Create subjects' }}</p></a>
            <a href="{{ route('admin.groups', $school) }}" class="rounded-lg border border-slate-200 p-4 hover:border-indigo-300"><p class="font-semibold">5. Academic groups <span class="font-normal text-slate-500">(optional)</span></p><p class="mt-1 text-sm text-slate-600">{{ $counts['groups'] }} configured</p><p class="mt-3 text-sm font-medium text-indigo-700">Add groups if your school uses streams</p></a>
        </div>
    </x-ui.card>
    <x-ui.card title="Academic relationships" subtitle="Complete these after the relevant catalog records exist.">
        <div class="grid gap-4 sm:grid-cols-3">
            <a href="{{ route('admin.class-groups', $school) }}" class="rounded-lg border border-slate-200 p-4 hover:border-indigo-300"><p class="font-semibold">6. Class groups</p><p class="mt-1 text-sm text-slate-600">Optional; requires classes and groups.</p></a>
            <a href="{{ route('admin.subject-assignments', $school) }}" class="rounded-lg border border-slate-200 p-4 hover:border-indigo-300"><p class="font-semibold">7. Subject assignments</p><p class="mt-1 text-sm text-slate-600">Requires year, class and subject.</p></a>
            <a href="{{ route('admin.teacher-assignments', $school) }}" class="rounded-lg border border-slate-200 p-4 hover:border-indigo-300"><p class="font-semibold">8. Teacher assignments</p><p class="mt-1 text-sm text-slate-600">Requires a teacher profile, section and subject assignment.</p></a>
        </div>
    </x-ui.card>
</div>
