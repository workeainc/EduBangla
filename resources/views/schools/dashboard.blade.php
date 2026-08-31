<x-layouts.app :school="$school" :title="$school->name.' · Dashboard'">
    <x-ui.breadcrumbs :items="[['label' => 'Workspace']]" />
    <x-ui.page-header title="Workspace" description="Use the workspace shortcuts below to continue your school workflow." />
    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($links as $label => $url)
            <a href="{{ $url }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md"><div class="flex items-center justify-between"><h2 class="font-semibold text-slate-900">{{ $label }}</h2><span class="text-xl text-slate-300 transition group-hover:text-indigo-600" aria-hidden="true">→</span></div><p class="mt-2 text-sm text-slate-500">Open {{ strtolower($label) }} workspace</p></a>
        @endforeach
    </section>
</x-layouts.app>
