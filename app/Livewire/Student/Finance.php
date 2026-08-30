<?php

namespace App\Livewire\Student;

use App\Domain\Finance\FinanceAuthorizer;
use App\Domain\Finance\FinanceBalance;
use App\Models\Invoice;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Finance extends Component
{
    public School $school;

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('view', $school);
        FinanceAuthorizer::student(auth()->user(), $school->id);
    }

    public function render()
    {
        $student = FinanceAuthorizer::student(auth()->user(), $this->school->id);
        $invoices = Invoice::with('items')->where(['school_id' => $this->school->id, 'student_id' => $student->id])->whereIn('status', ['issued', 'partially_paid', 'paid'])->latest()->get();

        $balances = $invoices->mapWithKeys(fn (Invoice $invoice) => [$invoice->id => app(FinanceBalance::class)->calculate($invoice, $this->school->id)])->all();

        return view('livewire.student.finance', ['invoices' => $invoices, 'balances' => $balances]);
    }
}
