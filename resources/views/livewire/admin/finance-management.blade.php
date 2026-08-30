<div>
    <h1>Finance: {{ ucfirst($screen) }}</h1>
    @if($screen === 'categories')
        <form wire:submit="createCategory"><input wire:model="code" placeholder="Code" required><input wire:model="name" placeholder="Name" required><textarea wire:model="description" placeholder="Description"></textarea><button type="submit">Save category</button></form>
    @elseif($screen === 'structures')
        <form wire:submit="createStructure"><input wire:model="name" placeholder="Structure name" required><select wire:model="academic_year_id" required><option value="">Academic year</option>@foreach($years as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select><select wire:model="class_id" required><option value="">Class</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select><select wire:model="fee_category_id" required><option value="">Fee category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->code }} — {{ $category->name }}</option>@endforeach</select><input type="number" step="0.01" min="0" wire:model="amount" placeholder="Amount" required><input type="date" wire:model="due_date"><button type="submit">Save structure</button></form>
    @elseif($screen === 'assignments')
        <form wire:submit="generateAssignments({{ request()->query('structure') ?: 0 }})"><select wire:model="enrollment_id" required><option value="">Student / enrollment</option>@foreach($enrollments as $enrollment)<option value="{{ $enrollment->id }}">{{ $enrollment->student->student_code }} — {{ $enrollment->student->first_name }} ({{ $enrollment->academicYear->name }} / {{ $enrollment->academicClass->name }} / {{ $enrollment->section->name }})</option>@endforeach</select><button type="submit">Assign selected structure</button></form>
    @elseif($screen === 'payments')
        <form wire:submit="recordPayment"><select wire:model="student_id" required><option value="">Student</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->student_code }} — {{ $student->first_name }}</option>@endforeach</select><select wire:model="enrollment_id" required><option value="">Enrollment</option>@foreach($enrollments as $enrollment)<option value="{{ $enrollment->id }}">{{ $enrollment->student->student_code }} — {{ $enrollment->academicYear->name }} / {{ $enrollment->academicClass->name }}</option>@endforeach</select><select wire:model="invoice_id" required><option value="">Invoice</option>@foreach($invoices as $invoice)<option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} — due {{ $invoice->outstanding_total }}</option>@endforeach</select><input type="number" step="0.01" min="0.01" wire:model="payment_amount" placeholder="Amount" required><button type="submit">Record payment</button></form>
    @elseif($screen === 'adjustments')
        <form wire:submit="postAdjustment"><select wire:model="invoice_id" required><option value="">Invoice</option>@foreach($invoices as $invoice)<option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} — due {{ $invoice->outstanding_total }}</option>@endforeach</select><input type="number" step="0.01" min="0.01" wire:model="payment_amount" placeholder="Credit amount" required><input wire:model="reason" placeholder="Reason" required><button type="submit">Post credit</button></form>
    @endif
    @if($screen === 'categories')
        <ul>@foreach($categories as $category)<li>{{ $category->code }} — {{ $category->name }}</li>@endforeach</ul>
    @elseif($screen === 'structures')
        <ul>@foreach($structures as $structure)<li>{{ $structure->name }} ({{ $structure->status }})</li>@endforeach</ul>
    @elseif($screen === 'invoices')
        <ul>@foreach($invoices as $invoice)<li>{{ $invoice->invoice_number }} — {{ $invoice->status }} — {{ $invoice->outstanding_total }}</li>@endforeach</ul>
    @elseif($screen === 'payments')
        <ul>@foreach($payments as $payment)<li>{{ $payment->receipt_number }} — {{ $payment->amount }} — {{ $payment->status }}</li>@endforeach</ul>
    @elseif($screen === 'adjustments')
        <ul>@foreach($adjustments as $adjustment)<li>{{ $adjustment->reason }} — {{ $adjustment->amount }} — {{ $adjustment->status }}</li>@endforeach</ul>
    @else
        <p>Finance administration for {{ $school->name }}.</p>
    @endif
</div>
