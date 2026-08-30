<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $school->name }} · EduBangla</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-5xl px-6 py-12">
        <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-semibold text-indigo-700">{{ $school->name }}</p><h1 class="mt-1 text-2xl font-bold">Operator workspace</h1><p class="mt-1 text-sm capitalize text-slate-600">{{ str_replace('-', ' ', $membership->role) }} access</p></div><div class="flex gap-2"><a href="{{ route('schools.index') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">Switch school</a><form method="POST" action="{{ route('logout') }}">@csrf <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">Sign out</button></form></div></div>
        <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ($links as $label => $url)<a href="{{ $url }}" class="rounded-xl bg-white p-6 font-semibold shadow-sm ring-1 ring-slate-200 transition hover:ring-indigo-400">{{ $label }} <span class="float-right text-slate-400">→</span></a>@endforeach</section>
    </main>
</body>
</html>
