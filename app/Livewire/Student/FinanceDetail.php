<?php

namespace App\Livewire\Student;

use App\Domain\Finance\FinanceAuthorizer;
use App\Domain\Finance\FinanceBalance;
use App\Models\Invoice;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class FinanceDetail extends Component
{
    public School $school;

    public Invoice $invoice;

    public function mount(School $school, Invoice $invoice): void
    {
        $this->school = $school;
        Gate::authorize('view', $school);
        $student = FinanceAuthorizer::student(auth()->user(), $school->id);
        abort_unless($invoice->school_id === $school->id && $invoice->student_id === $student->id && $invoice->status !== 'void', 404);
        Gate::authorize('view', $invoice);
        $this->invoice = $invoice->load('items', 'allocations.payment', 'adjustments');
    }

    public function render()
    {
        return view('livewire.student.finance-detail', ['balance' => app(FinanceBalance::class)->calculate($this->invoice, $this->school->id)]);
    }
}
