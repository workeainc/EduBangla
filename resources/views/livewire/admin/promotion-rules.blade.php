<div class="p-6 space-y-4">
    <h1 class="text-2xl font-bold">Promotion Rules</h1>
    <form wire:submit="save" class="grid grid-cols-3 gap-2">
        <input wire:model="name" placeholder="Rule name">
        <select wire:model="academic_year_id"><option value="">Academic year</option>@foreach($years as $year)<option value="{{$year->id}}">{{$year->name}}</option>@endforeach</select>
        <select wire:model="source_class_id"><option value="">Source class</option>@foreach($classes as $class)<option value="{{$class->id}}">{{$class->name}}</option>@endforeach</select>
        <select wire:model="target_class_id"><option value="">Target class</option>@foreach($classes as $class)<option value="{{$class->id}}">{{$class->name}}</option>@endforeach</select>
        <input wire:model="minimum_gpa" placeholder="Minimum GPA"><input wire:model="minimum_passed_subjects" placeholder="Minimum passed subjects"><input wire:model="failed_subject_tolerance" placeholder="Allowed failed subjects">
        <button class="bg-blue-600 text-white p-2">Save rule</button>
    </form>
    <table class="w-full"><tbody>@forelse($rules as $r)<tr><td>{{ $r->name ?: 'Unnamed rule' }}</td><td>{{ $r->active ? 'Active' : 'Inactive' }}</td><td><button wire:click="toggle({{$r->id}})">Toggle</button></td></tr>@empty<tr><td>No promotion rules.</td></tr>@endforelse</tbody></table>
</div>
