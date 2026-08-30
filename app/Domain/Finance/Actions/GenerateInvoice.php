<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\StudentFeeAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateInvoice
{
    public function handle(User $actor, int $schoolId, int $enrollmentId, array $assignmentIds, ?\Closure $afterItems = null): Invoice
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $enrollmentId, $assignmentIds, $afterItems) {
            $enrollment = Enrollment::where(['school_id' => $schoolId, 'id' => $enrollmentId, 'status' => 'active'])->lockForUpdate()->firstOrFail();
            $assignments = StudentFeeAssignment::where('school_id', $schoolId)->where('enrollment_id', $enrollment->id)->whereIn('id', $assignmentIds)->where('status', 'assigned')->lockForUpdate()->get();
            if ($assignments->count() !== count(array_unique($assignmentIds)) || $assignments->isEmpty()) {
                throw ValidationException::withMessages(['assignments' => 'Assignments are missing, duplicated or already invoiced.']);
            }
            $student = $enrollment->student()->where('school_id', $schoolId)->firstOrFail();
            $charged = $assignments->sum(fn ($assignment) => (float) $assignment->amount);
            $invoice = Invoice::create(['school_id' => $schoolId, 'student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'academic_year_id' => $enrollment->academic_year_id, 'class_id' => $enrollment->class_id, 'section_id' => $enrollment->section_id, 'invoice_number' => 'INV-'.$schoolId.'-'.strtoupper(bin2hex(random_bytes(5))), 'currency' => 'BDT', 'status' => 'issued', 'issued_at' => now()->toDateString(), 'due_date' => $assignments->max('due_date'), 'student_snapshot' => $student->only(['id', 'student_code', 'first_name', 'last_name']), 'enrollment_snapshot' => $enrollment->only(['id', 'academic_year_id', 'class_id', 'section_id', 'roll']), 'charged_total' => $charged, 'outstanding_total' => $charged]);
            foreach ($assignments as $assignment) {
                $invoice->items()->create(['school_id' => $schoolId, 'student_fee_assignment_id' => $assignment->id, 'fee_category_id' => $assignment->fee_category_id, 'category_code' => $assignment->category_code, 'category_name' => $assignment->category_name, 'amount' => $assignment->amount, 'due_date' => $assignment->due_date]);
                $assignment->update(['status' => 'invoiced']);
                if ($afterItems) {
                    $afterItems($assignment);
                }
            }
            app(RecordAudit::class)->handle($actor, $schoolId, 'finance.invoice_issued', $invoice, null, ['invoice_number' => $invoice->invoice_number, 'charged_total' => (string) $invoice->charged_total, 'item_count' => $assignments->count()]);

            return $invoice->refresh()->load('items');
        });
    }
}
