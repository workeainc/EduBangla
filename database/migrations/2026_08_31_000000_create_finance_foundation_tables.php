<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->string('code', 64);
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'code']);
            $t->index(['school_id', 'status']);
        });

        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->string('name');
            $t->string('status', 20)->default('draft');
            $t->timestamps();
            $t->unique(['school_id', 'academic_year_id', 'class_id', 'name'], 'fee_structure_scope_name_unique');
            $t->index(['school_id', 'status']);
        });

        Schema::create('fee_structure_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('fee_structure_id')->constrained()->restrictOnDelete();
            $t->foreignId('fee_category_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 12, 2);
            $t->date('due_date')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['fee_structure_id', 'fee_category_id']);
            $t->index(['school_id', 'fee_structure_id', 'sort_order']);
        });

        Schema::create('student_fee_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('section_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('fee_structure_id')->constrained()->restrictOnDelete();
            $t->foreignId('fee_structure_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('fee_category_id')->constrained()->restrictOnDelete();
            $t->string('category_code', 64);
            $t->string('category_name');
            $t->decimal('amount', 12, 2);
            $t->date('due_date')->nullable();
            $t->json('snapshot');
            $t->string('status', 20)->default('assigned');
            $t->timestamps();
            $t->unique(['school_id', 'enrollment_id', 'fee_structure_item_id'], 'assignment_enrollment_item_unique');
            $t->index(['school_id', 'student_id', 'status']);
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('section_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('invoice_number', 64);
            $t->string('currency', 3)->default('BDT');
            $t->string('status', 20)->default('issued');
            $t->date('issued_at');
            $t->date('due_date')->nullable();
            $t->decimal('charged_total', 12, 2)->default(0);
            $t->decimal('allocated_total', 12, 2)->default(0);
            $t->decimal('adjustment_total', 12, 2)->default(0);
            $t->decimal('outstanding_total', 12, 2)->default(0);
            $t->json('student_snapshot');
            $t->json('enrollment_snapshot');
            $t->timestamp('voided_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'invoice_number']);
            $t->index(['school_id', 'enrollment_id', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_fee_assignment_id')->constrained()->restrictOnDelete();
            $t->foreignId('fee_category_id')->constrained()->restrictOnDelete();
            $t->string('category_code', 64);
            $t->string('category_name');
            $t->decimal('amount', 12, 2);
            $t->date('due_date')->nullable();
            $t->timestamps();
            $t->unique(['invoice_id', 'student_fee_assignment_id']);
            $t->index(['school_id', 'invoice_id']);
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->string('receipt_number', 64);
            $t->string('currency', 3)->default('BDT');
            $t->decimal('amount', 12, 2);
            $t->date('received_at');
            $t->string('method', 32)->default('cash');
            $t->string('reference', 128)->nullable();
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->string('status', 20)->default('recorded');
            $t->foreignId('reversal_of_payment_id')->nullable()->constrained('payments')->restrictOnDelete();
            $t->timestamp('reversed_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'receipt_number']);
            $t->unique('reversal_of_payment_id');
            $t->index(['school_id', 'student_id', 'received_at']);
        });

        Schema::create('payment_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('payment_id')->constrained()->restrictOnDelete();
            $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 12, 2);
            $t->timestamps();
            $t->unique(['payment_id', 'invoice_id']);
            $t->index(['school_id', 'invoice_id']);
            $t->index(['school_id', 'payment_id']);
        });

        Schema::create('financial_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $t->string('kind', 20)->default('credit');
            $t->string('reason', 255);
            $t->decimal('amount', 12, 2);
            $t->string('status', 20)->default('draft');
            $t->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('posted_at')->nullable();
            $t->foreignId('reversal_of_adjustment_id')->nullable()->constrained('financial_adjustments')->restrictOnDelete();
            $t->timestamp('reversed_at')->nullable();
            $t->json('snapshot')->nullable();
            $t->timestamps();
            $t->unique('reversal_of_adjustment_id');
            $t->index(['school_id', 'invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_adjustments');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('student_fee_assignments');
        Schema::dropIfExists('fee_structure_items');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_categories');
    }
};
