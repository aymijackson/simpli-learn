<?php

namespace App\Http\Controllers;

use App\Enums\Module;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Invitations;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Http\Controllers\Manage\CourseReportController;
use Elibrary\Lms\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Add many people at once from a CSV file (name, email, optional role),
 * email each an invitation to set their password, and optionally assign
 * them a course with a due date.
 */
class TeamImportController extends Controller
{
    public const MAX_ROWS = 500;

    public function create(Tenancy $tenancy): View
    {
        $tenant = $tenancy->current();

        return view('team.import', [
            'courses' => $tenant->hasModule(Module::Lms) ? Course::orderBy('title')->get(['id', 'title']) : collect(),
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'email', 'role']);
            fputcsv($out, ['Ada Obi', 'ada.obi@example.com', 'member']);
            fputcsv($out, ['Tunde Bello', 'tunde.bello@example.com', 'member']);
            fclose($out);
        }, 'team-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $tenant = $tenancy->current();
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('tenant_id', $tenant->id)],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'file.mimes' => 'Upload a .csv file (in Excel or Google Sheets: File → Save as / Download → CSV).',
            'due_at.after_or_equal' => 'The due date can\'t be in the past.',
        ]);

        [$rows, $error] = $this->readRows($request->file('file')->getRealPath());
        if ($error) {
            return back()->withErrors(['file' => $error]);
        }

        $existing = $tenant->users()->get()->keyBy(fn ($user) => Str::lower($user->email));
        $created = collect();
        $alreadyMembers = collect();
        $skipped = [];
        $seen = [];
        $emailFailures = 0;

        foreach ($rows as $line => $row) {
            $email = Str::lower(trim($row['email'] ?? ''));
            $name = trim($row['name'] ?? '') ?: Str::of($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->value();
            $roleInput = Str::lower(trim($row['role'] ?? '')) ?: UserRole::Member->value;

            $reason = match (true) {
                $email === '' => 'missing email',
                ! filter_var($email, FILTER_VALIDATE_EMAIL) => 'not a valid email address',
                isset($seen[$email]) => 'listed twice in the file',
                UserRole::tryFrom($roleInput) === null => "unknown role \"{$roleInput}\" (use member or owner)",
                default => null,
            };
            $seen[$email] = true;

            if ($reason) {
                $skipped[] = ['line' => $line, 'email' => $email ?: '—', 'reason' => $reason];

                continue;
            }

            if ($existing->has($email)) {
                $person = $existing->get($email);
                $skipped[] = ['line' => $line, 'email' => $email, 'reason' => $person->isDeactivated() ? 'already in the workspace (deactivated)' : 'already in the workspace'];
                if (! $person->isDeactivated()) {
                    $alreadyMembers->push($person);
                }

                continue;
            }

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => Str::limit($name, 255, ''),
                'email' => $email,
                'role' => $roleInput,
                'password' => Hash::make(Str::random(64)),
            ]);
            $created->push($user);

            if (! Invitations::send($user, $tenant, $request->user()->name)) {
                $emailFailures++;
            }
        }

        $assignedCourse = null;
        if (! empty($validated['course_id']) && ($created->isNotEmpty() || $alreadyMembers->isNotEmpty())) {
            $assignedCourse = Course::find($validated['course_id']);
            $dueAt = isset($validated['due_at']) ? Carbon::parse($validated['due_at'])->endOfDay() : null;
            CourseReportController::assignTo($assignedCourse, $created->concat($alreadyMembers), $dueAt, $request->user());
        }

        ActivityLog::record(
            'team.imported',
            "Imported {$created->count()} ".str('person')->plural($created->count()).' from a CSV file'
                .($assignedCourse ? " and assigned {$assignedCourse->title}" : ''),
        );

        return redirect()->route('tenant.team.import.create')->with('import', [
            'created' => $created->count(),
            'skipped' => $skipped,
            'emailFailures' => $emailFailures,
            'course' => $assignedCourse?->title,
            'assignedExisting' => $assignedCourse ? $alreadyMembers->count() : 0,
        ]);
    }

    /**
     * @return array{0: array<int, array<string, string>>, 1: ?string} rows keyed by file line number, or an error
     */
    private function readRows(string $path): array
    {
        $handle = fopen($path, 'r');
        $columns = ['name', 'email', 'role']; // used when the file has no header row
        $rows = [];
        $line = 0;

        while (($cells = fgetcsv($handle)) !== false) {
            $line++;

            if ($line === 1) {
                // Strip a UTF-8 byte-order mark (Excel adds one), then detect a header row.
                $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cells[0]);
                $header = array_map(fn ($cell) => Str::lower(trim((string) $cell)), $cells);
                if (in_array('email', $header, true)) {
                    $columns = $header;

                    continue;
                }
            }

            if (trim(implode('', array_map('strval', $cells))) === '') {
                continue; // blank line
            }

            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);

                return [[], 'That file has more than '.self::MAX_ROWS.' people. Split it into smaller files.'];
            }

            $rows[$line] = collect($columns)
                ->mapWithKeys(fn ($column, $index) => [$column => (string) ($cells[$index] ?? '')])
                ->all();
        }

        fclose($handle);

        return [$rows, $rows === [] ? 'No people found in the file.' : null];
    }
}
