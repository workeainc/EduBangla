<?php

namespace Tests\Feature;

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
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFiveFTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $enrollment = Enrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);

        return compact('school', 'admin', 'student', 'year', 'class', 'section', 'enrollment');
    }

    private function invoice(array $f, float $amount = 100): object
    {
        $code = 'TUITION-'.(FeeCategory::where('school_id', $f['school']->id)->count() + 1);
        $category = app(CreateFeeCategory::class)->handle($f['admin'], $f['school']->id, ['code' => $code, 'name' => 'Tuition']);
        $structure = app(CreateFeeStructure::class)->handle($f['admin'], $f['school']->id, ['name' => '2026 tuition '.(FeeCategory::where('school_id', $f['school']->id)->count()), 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'items' => [['fee_category_id' => $category->id, 'amount' => $amount]]]);
        app(ActivateFeeStructure::class)->handle($f['admin'], $f['school']->id, $structure->id);
        app(GenerateFeeAssignments::class)->handle($f['admin'], $f['school']->id, $structure->id, [$f['enrollment']->id]);
        $assignmentId = StudentFeeAssignment::where(['school_id' => $f['school']->id, 'enrollment_id' => $f['enrollment']->id])->latest('id')->value('id');

        return app(GenerateInvoice::class)->handle($f['admin'], $f['school']->id, $f['enrollment']->id, [$assignmentId]);
    }

    public function test_invoice_and_balance_are_server_authoritative(): void
    {
        $f = $this->fixture();
        $invoice = $this->invoice($f, 100);
        $this->assertSame('100.00', (string) $invoice->outstanding_total);
        $payment = app(RecordPayment::class)->handle($f['admin'], $f['school']->id, ['student_id' => $f['student']->id, 'enrollment_id' => $f['enrollment']->id, 'amount' => 40, 'allocations' => [['invoice_id' => $invoice->id, 'amount' => 40]]]);
        $invoice->refresh();
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertSame('60.00', (string) $invoice->outstanding_total);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'allocated']);
    }

    public function test_foreign_admin_and_foreign_invoice_are_rejected_without_mutation(): void
    {
        $f = $this->fixture();
        $other = School::factory()->create();
        $foreignAdmin = User::factory()->create();
        SchoolUser::create(['school_id' => $other->id, 'user_id' => $foreignAdmin->id, 'role' => 'school-admin', 'status' => 'active']);
        $this->expectException(AuthorizationException::class);
        app(CreateFeeCategory::class)->handle($foreignAdmin, $f['school']->id, ['code' => 'X', 'name' => 'X']);
    }

    public function test_historical_invoice_snapshot_survives_future_structure_change(): void
    {
        $f = $this->fixture();
        $invoice = $this->invoice($f, 100);
        $snapshot = $invoice->load('items')->toArray();
        $category = FeeCategory::where('school_id', $f['school']->id)->first();
        $category->update(['name' => 'Changed future label']);
        $this->assertSame($snapshot['items'][0]['category_name'], $invoice->fresh()->items->first()->category_name);
    }

    public function test_payment_transaction_rolls_back_after_first_allocation(): void
    {
        $f = $this->fixture();
        $invoice = $this->invoice($f, 100);
        try {
            app(RecordPayment::class)->handle($f['admin'], $f['school']->id, ['student_id' => $f['student']->id, 'enrollment_id' => $f['enrollment']->id, 'amount' => 40, 'allocations' => [['invoice_id' => $invoice->id, 'amount' => 40]]], fn () => throw new \RuntimeException('injected failure'));
        } catch (\RuntimeException $e) {
            $this->assertSame('injected failure', $e->getMessage());
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('payment_allocations', 0);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'outstanding_total' => 100]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'finance.payment_recorded']);
    }

    public function test_adjustment_and_reversals_preserve_history_and_recalculate_due(): void
    {
        $f = $this->fixture();
        $invoice = $this->invoice($f, 100);
        $adjustment = app(PostFinancialAdjustment::class)->handle($f['admin'], $f['school']->id, ['invoice_id' => $invoice->id, 'amount' => 30, 'reason' => 'Approved waiver']);
        $this->assertSame('70.00', (string) $invoice->fresh()->outstanding_total);
        $reversal = app(ReverseFinancialAdjustment::class)->handle($f['admin'], $f['school']->id, $adjustment->id);
        $this->assertSame('100.00', (string) $invoice->fresh()->outstanding_total);
        $this->assertDatabaseHas('financial_adjustments', ['id' => $adjustment->id, 'status' => 'reversed']);
        $this->assertDatabaseHas('financial_adjustments', ['id' => $reversal->id, 'reversal_of_adjustment_id' => $adjustment->id]);
        $payment = app(RecordPayment::class)->handle($f['admin'], $f['school']->id, ['student_id' => $f['student']->id, 'enrollment_id' => $f['enrollment']->id, 'amount' => 25, 'allocations' => [['invoice_id' => $invoice->id, 'amount' => 25]]]);
        app(ReversePayment::class)->handle($f['admin'], $f['school']->id, $payment->id);
        $this->assertSame('100.00', (string) $invoice->fresh()->outstanding_total);
    }

    public function test_issued_invoice_items_and_recorded_payment_cannot_be_silently_changed(): void
    {
        $f = $this->fixture();
        $invoice = $this->invoice($f, 100);
        $this->expectException(\RuntimeException::class);
        $invoice->items->first()->update(['amount' => 1]);
    }

    public function test_lifecycle_boundaries_reject_empty_activation_duplicate_invoice_and_overallocation(): void
    {
        $f = $this->fixture();
        $category = app(CreateFeeCategory::class)->handle($f['admin'], $f['school']->id, ['code' => 'X', 'name' => 'X']);
        $empty = app(CreateFeeStructure::class)->handle($f['admin'], $f['school']->id, ['name' => 'empty', 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'items' => [['fee_category_id' => $category->id, 'amount' => 10]]]);
        $empty->items()->delete();
        $this->expectException(ValidationException::class);
        app(ActivateFeeStructure::class)->handle($f['admin'], $f['school']->id, $empty->id);
    }

    public function test_duplicate_invoice_and_overallocation_are_rejected_without_new_rows(): void
    {
        $f = $this->fixture();
        $invoice = $this->invoice($f, 100);
        $assignment = StudentFeeAssignment::where(['school_id' => $f['school']->id, 'enrollment_id' => $f['enrollment']->id])->first();
        $this->expectException(ValidationException::class);
        app(GenerateInvoice::class)->handle($f['admin'], $f['school']->id, $f['enrollment']->id, [$assignment->id]);
    }

    public function test_payment_cannot_allocate_more_than_invoice_due(): void
    {
        $f = $this->fixture();
        $invoice = $this->invoice($f, 100);
        $this->expectException(ValidationException::class);
        app(RecordPayment::class)->handle($f['admin'], $f['school']->id, ['student_id' => $f['student']->id, 'enrollment_id' => $f['enrollment']->id, 'amount' => 101, 'allocations' => [['invoice_id' => $invoice->id, 'amount' => 101]]]);
    }

    public function test_foreign_finance_ids_and_mismatched_same_tenant_relationships_leave_no_financial_rows(): void
    {
        $f = $this->fixture();
        $foreign = $this->fixture();
        $invoice = $this->invoice($f);
        foreach ([
            fn () => app(GenerateFeeAssignments::class)->handle($f['admin'], $f['school']->id, 999999, [$f['enrollment']->id]),
            fn () => app(GenerateInvoice::class)->handle($f['admin'], $f['school']->id, $foreign['enrollment']->id, [999999]),
            fn () => app(RecordPayment::class)->handle($f['admin'], $f['school']->id, ['student_id' => $foreign['student']->id, 'enrollment_id' => $f['enrollment']->id, 'amount' => 1, 'allocations' => [['invoice_id' => $invoice->id, 'amount' => 1]]]),
            fn () => app(PostFinancialAdjustment::class)->handle($f['admin'], $f['school']->id, ['invoice_id' => $foreign['enrollment']->id, 'amount' => 1, 'reason' => 'x']),
            fn () => app(ReversePayment::class)->handle($f['admin'], $f['school']->id, 999999),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Foreign or nonexistent finance ID must reject.');
            } catch (\Throwable $e) {
                $this->assertTrue($e instanceof ValidationException || $e instanceof ModelNotFoundException);
            }
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('financial_adjustments', 0);
        $this->assertSame('100.00', (string) $invoice->fresh()->outstanding_total);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'finance.payment_recorded']);
    }

    public function test_reversal_and_void_roll_back_after_mutation_without_false_audit(): void
    {
        $f = $this->fixture();
        $invoice = $this->invoice($f);
        $payment = app(RecordPayment::class)->handle($f['admin'], $f['school']->id, ['student_id' => $f['student']->id, 'enrollment_id' => $f['enrollment']->id, 'amount' => 25, 'allocations' => [['invoice_id' => $invoice->id, 'amount' => 25]]]);
        try {
            app(ReversePayment::class)->handle($f['admin'], $f['school']->id, $payment->id, fn () => throw new \RuntimeException('reverse rollback'));
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('reverse rollback', $e->getMessage());
        }
        $this->assertSame('allocated', $payment->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('75.00', (string) $invoice->fresh()->outstanding_total);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'finance.payment_reversed']);

        $second = $this->fixture();
        $clean = $this->invoice($second);
        try {
            app(VoidInvoice::class)->handle($second['admin'], $second['school']->id, $clean->id, fn () => throw new \RuntimeException('void rollback'));
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('issued', $clean->fresh()->status);
            $this->assertSame('100.00', (string) $clean->fresh()->outstanding_total);
            $this->assertDatabaseMissing('audit_logs', ['action' => 'finance.invoice_voided', 'school_id' => $second['school']->id]);
        }
    }

    public function test_repeated_reversal_and_void_are_rejected_with_persisted_state_unchanged(): void
    {
        $f = $this->fixture();
        $invoice = $this->invoice($f);
        $voided = app(VoidInvoice::class)->handle($f['admin'], $f['school']->id, $invoice->id);
        try {
            app(VoidInvoice::class)->handle($f['admin'], $f['school']->id, $voided->id);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertSame('void', $voided->fresh()->status);
        }
        $invoice = $this->invoice($f);
        $payment = app(RecordPayment::class)->handle($f['admin'], $f['school']->id, ['student_id' => $f['student']->id, 'enrollment_id' => $f['enrollment']->id, 'amount' => 10, 'allocations' => [['invoice_id' => $invoice->id, 'amount' => 10]]]);
        app(ReversePayment::class)->handle($f['admin'], $f['school']->id, $payment->id);
        try {
            app(ReversePayment::class)->handle($f['admin'], $f['school']->id, $payment->id);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertSame('reversed', $payment->fresh()->status);
            $this->assertSame('100.00', (string) $invoice->fresh()->outstanding_total);
        }
    }
}
