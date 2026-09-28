<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_checkouts', function (Blueprint $table) {
            // So the "due soon" reminder goes out once per loan.
            $table->timestamp('reminder_sent_at')->nullable()->after('due_at');
        });
    }

    public function down(): void
    {
        Schema::table('library_checkouts', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
