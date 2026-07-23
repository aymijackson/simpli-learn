<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('assessment_mode')->default('none')->after('is_published');
            $table->foreignId('final_exam_id')->nullable()->after('assessment_mode')->constrained('exams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('final_exam_id');
            $table->dropColumn('assessment_mode');
        });
    }
};
