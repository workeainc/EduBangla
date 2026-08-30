<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->string('title', 255);
            $t->text('body');
            $t->string('status', 20)->default('draft');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamp('withdrawn_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'status', 'published_at'], 'notice_school_status_published');
        });

        Schema::create('notice_audiences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('notice_id')->constrained()->restrictOnDelete();
            $t->string('type', 32);
            $t->string('role', 32)->nullable();
            $t->foreignId('academic_year_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->nullable()->constrained('classes')->restrictOnDelete();
            $t->foreignId('section_id')->nullable()->constrained()->restrictOnDelete();
            $t->json('snapshot')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'notice_id', 'type'], 'notice_audience_scope_type');
        });

        Schema::create('notice_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('notice_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('recipient_role', 32);
            $t->string('profile_type', 32)->nullable();
            $t->unsignedBigInteger('profile_id')->nullable();
            $t->json('recipient_snapshot');
            $t->timestamp('delivered_at');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->unique(['notice_id', 'user_id'], 'notice_delivery_recipient_unique');
            $t->index(['school_id', 'user_id', 'read_at'], 'notice_delivery_recipient_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_deliveries');
        Schema::dropIfExists('notice_audiences');
        Schema::dropIfExists('notices');
    }
};
