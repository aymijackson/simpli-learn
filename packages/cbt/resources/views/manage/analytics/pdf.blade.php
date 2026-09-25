<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $exam->title }} — Results</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        p.subtitle { color: #64748b; margin-top: 0; margin-bottom: 20px; }
        table.summary { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.summary td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: center; }
        table.summary td .value { display: block; font-size: 16px; font-weight: bold; }
        table.summary td .label { display: block; font-size: 10px; color: #64748b; margin-top: 2px; }
        table.results { width: 100%; border-collapse: collapse; }
        table.results th, table.results td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; font-size: 11px; }
        table.results th { background: #f8fafc; }
        .pass { color: #059669; font-weight: bold; }
        .fail { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $exam->title }} — Results</h1>
    <p class="subtitle">Generated {{ now()->format('M j, Y g:ia') }}</p>

    <table class="summary">
        <tr>
            <td><span class="value">{{ $stats['count'] }}</span><span class="label">Attempts</span></td>
            <td><span class="value">{{ $stats['uniqueLearners'] }}</span><span class="label">Learners</span></td>
            <td><span class="value">{{ $stats['passRate'] ?? '—' }}{{ ! is_null($stats['passRate']) ? '%' : '' }}</span><span class="label">Pass rate</span></td>
            <td><span class="value">{{ $stats['minScore'] ?? '—' }} / {{ $stats['avgScore'] ?? '—' }} / {{ $stats['maxScore'] ?? '—' }}</span><span class="label">Score (min/avg/max %)</span></td>
            <td><span class="value">{{ $stats['minMinutes'] ?? '—' }} / {{ $stats['avgMinutes'] ?? '—' }} / {{ $stats['maxMinutes'] ?? '—' }}</span><span class="label">Time (min/avg/max, minutes)</span></td>
        </tr>
    </table>

    <table class="results">
        <thead>
            <tr>
                <th>Learner</th>
                <th>Email</th>
                <th>Score</th>
                <th>Result</th>
                <th>Submitted</th>
                <th>Time taken</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attempts as $attempt)
                <tr>
                    <td>{{ $attempt->user->name }}</td>
                    <td>{{ $attempt->user->email }}</td>
                    <td>{{ $attempt->score }}%</td>
                    <td class="{{ $attempt->passed() ? 'pass' : 'fail' }}">{{ $attempt->passed() ? 'Passed' : 'Failed' }}</td>
                    <td>{{ $attempt->submitted_at->format('M j, Y g:ia') }}</td>
                    <td>{{ round($attempt->durationMinutes(), 1) }} min</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No matching attempts.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
