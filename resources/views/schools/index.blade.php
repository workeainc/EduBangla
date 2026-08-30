<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose school · EduBangla</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-3xl px-6 py-12">
        <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-semibold text-indigo-700">EduBangla</p><h1 class="mt-1 text-2xl font-bold">Choose a school</h1><p class="mt-1 text-sm text-slate-600">Signed in as {{ auth()->user()->name }}.</p></div><form method="POST" action="{{ route('logout') }}">@csrf <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">Sign out</button></form></div>
        @if ($memberships->isEmpty())
            <section class="mt-8 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><h2 class="font-semibold">No active school access</h2><p class="mt-2 text-sm text-slate-600">Your account is authenticated but does not currently have an active school membership. Contact a school administrator.</p></section>
        @else
            <section class="mt-8 grid gap-4 sm:grid-cols-2">
                @foreach ($memberships as $membership)
                    <form method="POST" action="{{ route('schools.select') }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">@csrf <input type="hidden" name="school_id" value="{{ $membership->school_id }}"><h2 class="font-semibold">{{ $membership->school->name }}</h2><p class="mt-1 text-sm capitalize text-slate-600">{{ str_replace('-', ' ', $membership->role) }}</p><button class="mt-5 rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800">Enter school</button></form>
                @endforeach
            </section>
        @endif
    </main>
</body>
</html>
