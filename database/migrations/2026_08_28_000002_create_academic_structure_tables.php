<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('code', 32);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'name']);
            $t->unique(['school_id', 'code']);
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->string('name');
            $t->string('code', 32);
            $t->unsignedSmallInteger('capacity')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'class_id', 'name']);
            $t->unique(['school_id', 'class_id', 'code']);
        });
        Schema::create('groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('code', 32);
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'name']);
            $t->unique(['school_id', 'code']);
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('code', 32);
            $t->string('short_name', 64)->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'name']);
            $t->unique(['school_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('classes');
    }
};
