<?php

namespace Elibrary\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Enums\IntegrityEventType;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class IntegrityEventController extends Controller
{
    public function store(Request $request, string $tenant, ExamAttempt $attempt): Response
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_unless($attempt->exam->integrity_monitoring_enabled, 404);
        abort_if($attempt->isSubmitted(), 404);

        $validated = $request->validate([
            'event_type' => ['required', Rule::in(array_column(IntegrityEventType::cases(), 'value'))],
        ]);

        $attempt->integrityEvents()->create(['event_type' => $validated['event_type']]);

        return response()->noContent();
    }
}
