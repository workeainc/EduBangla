<?php

namespace App\Livewire\Admin;

use App\Domain\Finance\Actions\ActivateFeeStructure;
use App\Domain\Finance\Actions\CreateFeeCategory;
use App\Domain\Finance\Actions\CreateFeeStructure;
use App\Domain\Finance\Actions\GenerateFeeAssignments;
use App\Domain\Finance\Actions\GenerateInvoice;
use App\Domain\Finance\Actions\PostFinancialAdjustment;
use App\Domain\Finance\Actions\RecordPayment;
use App\Domain\Finance\Actions\ReverseFinancialAdjustment;
use App\Domain\Finance\Actions\ReversePayment;
use App\Domain\Finance\Actions\VoidInvoice;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\FinancialAdjustment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class FinanceManagement extends Component
{
    public School $school;

    public ?Invoice $invoice = null;

    public string $screen = 'dashboard';

    public $code;

    public $name;

    public $description;

    public $academic_year_id;

    public $class_id;

    public $fee_category_id;

    public $amount;

    public $due_date;

    public $enrollment_id;

    public $assignment_ids = [];

    public $student_id;

    public $invoice_id;

    public $payment_amount;

    public $reason;

    public $payment_id;

    public $adjustment_id;

    public function mount(School $school, string $screen = 'dashboard', ?Invoice $invoice = null): void
    {
        $this->school = $school;
        $this->screen = $screen;
        if ($invoice && $invoice->school_id !== $school->id) {
            abort(404);
        }
        $this->invoice = $invoice;
        Gate::authorize('update', $school);
    }

    public function createCategory(): void
    {
        $this->validate(['code' => 'required|string|max:64', 'name' => 'required|string|max:255', 'description' => 'nullable|string']);
        app(CreateFeeCategory::class)->handle(auth()->user(), $this->school->id, ['code' => $this->code, 'name' => $this->name, 'description' => $this->description]);
        $this->reset(['code', 'name', 'description']);
    }

    public function createStructure(): void
    {
        $this->validate(['name' => 'required|string|max:255', 'academic_year_id' => 'required|integer', 'class_id' => 'required|integer', 'fee_category_id' => 'required|integer', 'amount' => 'required|numeric|min:0', 'due_date' => 'nullable|date']);
        app(CreateFeeStructure::class)->handle(auth()->user(), $this->school->id, ['name' => $this->name, 'academic_year_id' => $this->academic_year_id, 'class_id' => $this->class_id, 'items' => [['fee_category_id' => $this->fee_category_id, 'amount' => $this->amount, 'due_date' => $this->due_date]]]);
        $this->reset(['name', 'academic_year_id', 'class_id', 'fee_category_id', 'amount', 'due_date']);
    }

    public function activateStructure(int $id): void
    {
        app(ActivateFeeStructure::class)->handle(auth()->user(), $this->school->id, $id);
    }

    public function generateAssignments(int $structureId): void
    {
        $this->validate(['enrollment_id' => 'required|integer']);
        app(GenerateFeeAssignments::class)->handle(auth()->user(), $this->school->id, $structureId, [$this->enrollment_id]);
    }

    public function generateInvoice(): void
    {
        $this->validate(['enrollment_id' => 'required|integer', 'assignment_ids' => 'required|array|min:1']);
        app(GenerateInvoice::class)->handle(auth()->user(), $this->school->id, $this->enrollment_id, $this->assignment_ids);
    }

    public function recordPayment(): void
    {
        $this->validate(['student_id' => 'required|integer', 'enrollment_id' => 'required|integer', 'payment_amount' => 'required|numeric|min:0.01', 'invoice_id' => 'required|integer']);
        app(RecordPayment::class)->handle(auth()->user(), $this->school->id, ['student_id' => $this->student_id, 'enrollment_id' => $this->enrollment_id, 'amount' => $this->payment_amount, 'allocations' => [['invoice_id' => $this->invoice_id, 'amount' => $this->payment_amount]]]);
    }

    public function postAdjustment(): void
    {
        $this->validate(['invoice_id' => 'required|integer', 'payment_amount' => 'required|numeric|min:0.01', 'reason' => 'required|string|max:255']);
        app(PostFinancialAdjustment::class)->handle(auth()->user(), $this->school->id, ['invoice_id' => $this->invoice_id, 'amount' => $this->payment_amount, 'reason' => $this->reason]);
    }

    public function reversePayment(int $id): void
    {
        app(ReversePayment::class)->handle(auth()->user(), $this->school->id, $id);
    }

    public function reverseAdjustment(int $id): void
    {
        app(ReverseFinancialAdjustment::class)->handle(auth()->user(), $this->school->id, $id);
    }

    public function voidInvoice(int $id): void
    {
        app(VoidInvoice::class)->handle(auth()->user(), $this->school->id, $id);
    }

    public function render()
    {
        return view('livewire.admin.finance-management', ['categories' => FeeCategory::where('school_id', $this->school->id)->latest()->get(), 'structures' => FeeStructure::with('items.category')->where('school_id', $this->school->id)->latest()->get(), 'invoices' => Invoice::with('student')->where('school_id', $this->school->id)->latest()->get(), 'payments' => Payment::with('student')->where('school_id', $this->school->id)->latest()->get(), 'adjustments' => FinancialAdjustment::with('invoice')->where('school_id', $this->school->id)->latest()->get(), 'years' => AcademicYear::where('school_id', $this->school->id)->orderBy('name')->get(), 'classes' => AcademicClass::where('school_id', $this->school->id)->orderBy('sort_order')->get(), 'students' => Student::where('school_id', $this->school->id)->where('status', 'active')->orderBy('student_code')->get(), 'enrollments' => Enrollment::with(['student', 'academicYear', 'academicClass', 'section'])->where('school_id', $this->school->id)->where('status', 'active')->latest()->get()]);
    }
}
