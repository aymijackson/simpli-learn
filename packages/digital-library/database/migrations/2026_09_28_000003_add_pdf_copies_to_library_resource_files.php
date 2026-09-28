<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_resource_files', function (Blueprint $table) {
            // A PDF copy of a Word/Excel/PowerPoint file, for secure in-platform viewing.
            $table->string('pdf_path')->nullable()->after('disk_path');
        });

        // Older uploads were all "file"; sort them into the new, viewable kinds.
        $kinds = [
            'document' => ['doc', 'docx', 'odt', 'rtf', 'xls', 'xlsx', 'ods', 'csv', 'ppt', 'pptx', 'odp'],
            'text' => ['txt', 'md'],
        ];
        DB::table('library_resource_files')->where('format', 'file')->orderBy('id')->each(function (object $file) use ($kinds) {
            $extension = strtolower(pathinfo($file->disk_path, PATHINFO_EXTENSION));
            foreach ($kinds as $format => $extensions) {
                if (in_array($extension, $extensions, true)) {
                    DB::table('library_resource_files')->where('id', $file->id)->update(['format' => $format]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('library_resource_files')->whereIn('format', ['document', 'text'])->update(['format' => 'file']);

        Schema::table('library_resource_files', function (Blueprint $table) {
            $table->dropColumn('pdf_path');
        });
    }
};
