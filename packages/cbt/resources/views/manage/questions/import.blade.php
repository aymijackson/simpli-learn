<x-app-layout title="Import Questions">
    <a href="{{ route('cbt.manage.exams.edit', $exam) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $exam->title }}
    </a>

    <x-page-header title="Import questions" subtitle="Bulk-add questions to this exam from a CSV file." />

    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-inset ring-red-600/20">
            <p class="font-semibold">The file couldn't be imported — nothing was saved. Fix the following and re-upload:</p>
            <ul class="mt-2 list-disc space-y-0.5 pl-5">
                @foreach ($errors->get('questions_file') as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card class="mb-8">
        <form method="POST" action="{{ route('cbt.manage.questions.import.store', $exam) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label for="questions_file" class="mb-1.5 block text-sm font-medium text-slate-700">CSV file</label>
                <input type="file" name="questions_file" id="questions_file" accept=".csv,text/csv" required
                    class="block w-full rounded-lg border-0 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 file:mr-4 file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100 focus:ring-2 focus:ring-inset focus:ring-brand-600">
            </div>
            <x-button type="submit">Import questions</x-button>
        </form>
    </x-card>

    <x-card>
        <h2 class="mb-3 text-sm font-semibold text-slate-900">File format</h2>
        <p class="mb-3 text-sm text-slate-600">
            <a href="{{ route('cbt.manage.questions.import.template', $exam) }}" class="font-medium text-brand-600 hover:text-brand-500">Download the CSV template &rarr;</a>
            — it already has the correct headers and two example rows (one single-answer, one multiple-answer).
        </p>
        <ul class="space-y-1.5 text-sm text-slate-600">
            <li><span class="font-medium text-slate-800">question_text</span> — required.</li>
            <li><span class="font-medium text-slate-800">answer_type</span> — <code class="rounded bg-slate-100 px-1 py-0.5">single</code> or <code class="rounded bg-slate-100 px-1 py-0.5">multiple</code>. Defaults to <code class="rounded bg-slate-100 px-1 py-0.5">single</code> if left blank.</li>
            <li><span class="font-medium text-slate-800">scoring_method</span> — <code class="rounded bg-slate-100 px-1 py-0.5">all_or_nothing</code> or <code class="rounded bg-slate-100 px-1 py-0.5">partial_credit</code>. Only matters for <code class="rounded bg-slate-100 px-1 py-0.5">multiple</code>; single-answer questions always use all-or-nothing.</li>
            <li><span class="font-medium text-slate-800">section</span> — optional; must exactly match an existing section's title on this exam, or leave blank.</li>
            <li><span class="font-medium text-slate-800">points</span> — optional whole number, defaults to 1. How much this question counts toward the final score relative to the others.</li>
            <li><span class="font-medium text-slate-800">option_1, option_2, &hellip;</span> — option text. At least two per question. Add more <code class="rounded bg-slate-100 px-1 py-0.5">option_N</code> / <code class="rounded bg-slate-100 px-1 py-0.5">correct_N</code> column pairs if you need more than the template's four.</li>
            <li><span class="font-medium text-slate-800">correct_1, correct_2, &hellip;</span> — mark the matching option correct with <code class="rounded bg-slate-100 px-1 py-0.5">yes</code>/<code class="rounded bg-slate-100 px-1 py-0.5">1</code>; leave blank (or <code class="rounded bg-slate-100 px-1 py-0.5">no</code>/<code class="rounded bg-slate-100 px-1 py-0.5">0</code>) otherwise. Single-answer rows need exactly one correct option; multiple-answer rows need at least one.</li>
        </ul>
        <p class="mt-3 text-sm text-slate-500">The whole file is validated before anything is saved — if any row has a problem, nothing is imported and every issue is listed above so you can fix them all at once.</p>
    </x-card>
</x-app-layout>
