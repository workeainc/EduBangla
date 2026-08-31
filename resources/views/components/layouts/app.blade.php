@php
    $activeSchool = $school ?? (session('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null);
    $membership = $activeSchool && auth()->check()
        ? auth()->user()->schoolMemberships()->where('school_id', $activeSchool->id)->where('status', \App\Models\SchoolUser::STATUS_ACTIVE)->first()
        : null;
    $role = $membership?->role;
    $adminLinks = [
        ['label' => 'Academic setup', 'route' => 'admin.class-groups', 'match' => 'admin.class-groups'],
        ['label' => 'Teachers & staff', 'route' => 'admin.teachers', 'match' => 'admin.teachers*'],
        ['label' => 'Attendance', 'route' => 'admin.attendance', 'match' => 'admin.attendance*'],
        ['label' => 'Exams', 'route' => 'admin.exams', 'match' => 'admin.exams*'],
        ['label' => 'Results', 'route' => 'admin.results', 'match' => 'admin.results'],
        ['label' => 'Report cards', 'route' => 'admin.report-cards', 'match' => 'admin.report-cards*'],
        ['label' => 'Promotion', 'route' => 'admin.promotions', 'match' => 'admin.promotions*'],
        ['label' => 'Finance', 'route' => 'admin.finance', 'match' => 'admin.finance*'],
        ['label' => 'Notices', 'route' => 'admin.notices', 'match' => 'admin.notices*'],
        ['label' => 'Timetable', 'route' => 'admin.timetables', 'match' => 'admin.timetables*'],
    ];
    $teacherLinks = [
        ['label' => 'My assignments', 'route' => 'teacher.assignments', 'match' => 'teacher.assignments'],
        ['label' => 'Attendance', 'route' => 'teacher.attendance', 'match' => 'teacher.attendance'],
        ['label' => 'Exams', 'route' => 'teacher.exams', 'match' => 'teacher.exams*'],
        ['label' => 'Results', 'route' => 'teacher.results', 'match' => 'teacher.results'],
        ['label' => 'Report cards', 'route' => 'teacher.report-cards', 'match' => 'teacher.report-cards'],
        ['label' => 'Timetable', 'route' => 'teacher.timetable', 'match' => 'teacher.timetable'],
        ['label' => 'Notices', 'route' => 'teacher.notices', 'match' => 'teacher.notices*'],
    ];
    $studentLinks = [
        ['label' => 'Exams', 'route' => 'student.exams', 'match' => 'student.exams*'],
        ['label' => 'Results', 'route' => 'student.results', 'match' => 'student.results'],
        ['label' => 'Report cards', 'route' => 'student.report-cards', 'match' => 'student.report-cards'],
        ['label' => 'Finance', 'route' => 'student.finance', 'match' => 'student.finance*'],
        ['label' => 'Timetable', 'route' => 'student.timetable', 'match' => 'student.timetable'],
        ['label' => 'Notices', 'route' => 'student.notices', 'match' => 'student.notices*'],
    ];
    $links = $role === 'school-admin' ? $adminLinks : ($role === 'teacher' ? $teacherLinks : ($role === 'student' ? $studentLinks : ($role === 'staff' ? [['label' => 'Notices', 'route' => 'staff.notices', 'match' => 'staff.notices*']] : [])));
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ($activeSchool?->name ? $activeSchool->name.' · EduBangla' : 'EduBangla') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <a href="#main-content" class="ui-skip-link">Skip to main content</a>
    @if($activeSchool && auth()->check())
        <div class="min-h-screen lg:flex">
            <aside class="w-full border-b border-slate-200 bg-white lg:min-h-screen lg:w-64 lg:border-b-0 lg:border-r">
                <div class="flex items-center justify-between px-5 py-5 lg:block">
                    <a href="{{ route('schools.dashboard', $activeSchool) }}" class="text-lg font-bold tracking-tight text-indigo-700">EduBangla</a>
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ str_replace('-', ' ', $role ?? '') }}</span>
                </div>
                <nav class="flex gap-1 overflow-x-auto px-3 pb-3 lg:block lg:space-y-1 lg:px-3" aria-label="Main navigation">
                    @foreach($links as $link)
                        <a href="{{ route($link['route'], $activeSchool) }}" class="block whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs($link['match']) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">{{ $link['label'] }}</a>
                    @endforeach
                </nav>
            </aside>
            <div class="min-w-0 flex-1">
                <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4 lg:px-8">
                    <div><p class="text-xs font-medium uppercase tracking-wide text-slate-400">Current school</p><p class="font-semibold">{{ $activeSchool->name }}</p></div>
                    <div class="flex items-center gap-3 text-sm"><a href="{{ route('schools.index') }}" class="text-slate-600 hover:text-indigo-700">Switch school</a><span class="hidden text-slate-300 sm:inline">|</span><span class="hidden text-slate-600 sm:inline">{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="font-medium text-slate-600 hover:text-red-600">Sign out</button></form></div>
                </header>
                <main id="main-content" tabindex="-1" class="mx-auto w-full max-w-7xl px-5 py-6 outline-none lg:px-8 lg:py-8">
                    <x-ui.flash-message />
                    {{ $slot }}
                </main>
            </div>
        </div>
    @else
        <main id="main-content" tabindex="-1" class="outline-none">{{ $slot }}</main>
    @endif
    @livewireScripts
</body>
</html>
