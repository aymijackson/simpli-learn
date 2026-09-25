<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_resources', function (Blueprint $table) {
            $table->string('isbn')->nullable()->after('category');
            $table->string('publisher')->nullable()->after('isbn');
            $table->unsignedSmallInteger('publication_year')->nullable()->after('publisher');
            $table->string('language')->nullable()->after('publication_year');
            $table->string('cover_image_path')->nullable()->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('library_resources', function (Blueprint $table) {
            $table->dropColumn(['isbn', 'publisher', 'publication_year', 'language', 'cover_image_path']);
        });
    }
};
