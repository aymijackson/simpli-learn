<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('course_module_id')->nullable()->after('course_id')->constrained('course_modules')->nullOnDelete();
            $table->foreignId('exam_id')->nullable()->after('course_module_id')->constrained('exams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_module_id');
            $table->dropConstrainedForeignId('exam_id');
        });
    }
};
