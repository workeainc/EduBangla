<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_options', function (Blueprint $t) {
            $t->unsignedInteger('sort_order')->default(0)->after('option_text');
            $t->index(['question_version_id', 'sort_order'], 'question_option_order');
        });
    }

    public function down(): void
    {
        Schema::table('question_options', function (Blueprint $t) {
            $t->dropIndex('question_option_order');
            $t->dropColumn('sort_order');
        });
    }
};
