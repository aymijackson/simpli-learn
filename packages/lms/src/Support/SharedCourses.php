<?php

namespace Elibrary\Lms\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Raw subqueries (no global scopes, so no recursion) for courses other
 * workspaces share with a given workspace: published, and either open to
 * every workspace or shared with this one by a platform admin.
 */
class SharedCourses
{
    public static function ids(int $tenantId): Builder
    {
        return DB::table('courses')
            ->select('courses.id')
            ->where('courses.tenant_id', '!=', $tenantId)
            ->where('courses.is_published', true)
            ->where(fn (Builder $query) => $query
                ->where('courses.is_public', true)
                ->orWhereIn('courses.id', DB::table('course_shares')->select('course_id')->where('tenant_id', $tenantId)));
    }

    public static function lessonIds(int $tenantId): Builder
    {
        return DB::table('lessons')->select('lessons.id')->whereIn('lessons.course_id', self::ids($tenantId));
    }

    /** Exams used as checkpoints (lesson, module or final) in shared courses. */
    public static function examIds(int $tenantId): Builder
    {
        return DB::table('courses')->select('final_exam_id as exam_id')
            ->whereIn('id', self::ids($tenantId))->whereNotNull('final_exam_id')
            ->union(DB::table('lessons')->select('exam_id')->whereIn('course_id', self::ids($tenantId))->whereNotNull('exam_id'))
            ->union(DB::table('course_modules')->select('exam_id')->whereIn('course_id', self::ids($tenantId))->whereNotNull('exam_id'));
    }
}
