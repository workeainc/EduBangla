<div class="space-y-6">
    <h1 class="text-2xl font-semibold">Assigned results</h1>
    @forelse ($results as $result)
        <section class="rounded border p-4">
            <h2 class="font-medium">{{ $result->exam->name }} — {{ $result->student->name }}</h2>
            <p class="text-sm text-gray-600">Status: {{ ucfirst($result->status) }} · {{ $result->percentage }}%</p>
            <table class="mt-3 w-full text-sm"><tbody>
                @foreach ($result->items as $item)
                    <tr><td>{{ $item->subject->name }}</td><td>{{ $item->obtained_marks }}/{{ $item->maximum_marks }}</td></tr>
                @endforeach
            </tbody></table>
        </section>
    @empty
        <p>No assigned result records are available.</p>
    @endforelse
</div>
