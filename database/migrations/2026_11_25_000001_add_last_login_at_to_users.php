<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When each person last signed in, so owners can see who hasn't accepted
 * their invitation yet. The activity log can't be used for that because old
 * entries are pruned. Backfilled from the sign-ins still in the log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('deactivated_at');
        });

        DB::table('activity_logs')
            ->where('action', 'auth.login')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->selectRaw('user_id, MAX(created_at) as last_login_at')
            ->orderBy('user_id')
            ->each(function ($row) {
                DB::table('users')->where('id', $row->user_id)->update(['last_login_at' => $row->last_login_at]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });
    }
};
