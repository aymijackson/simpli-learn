<x-app-layout :title="$exam->title.' — Monitoring review'">
    <a href="{{ route('cbt.manage.analytics.show', $exam) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $exam->title }} results
    </a>

    <x-page-header :title="$attempt->user->name" :subtitle="'Monitoring review for this attempt — a signal for your judgement, not an automatic penalty.'" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($idCheck || $snapshots->isNotEmpty() || $exam->webcam_proctoring)
                <x-card>
                    <h2 class="text-base font-semibold text-slate-900">ID check</h2>
                    @if ($idCheck)
                        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
                            <a href="{{ route('cbt.manage.analytics.attempts.snapshot', [$exam, $attempt, $idCheck->id]) }}" target="_blank" rel="noopener" class="block shrink-0">
                                <img src="{{ route('cbt.manage.analytics.attempts.snapshot', [$exam, $attempt, $idCheck->id]) }}" alt="ID check photo of {{ $attempt->user->name }}" class="w-full rounded-lg ring-1 ring-slate-200 sm:w-64">
                            </a>
                            <dl class="space-y-2 text-sm">
                                <div><dt class="text-xs text-slate-500">Candidate</dt><dd class="text-slate-900">{{ $attempt->user->name }} &middot; {{ $attempt->user->email }}</dd></div>
                                <div><dt class="text-xs text-slate-500">Photo taken</dt><dd class="text-slate-900">{{ $idCheck->created_at->format('M j, Y g:i:s a') }}</dd></div>
                                @if ($attempt->proctoring_consented_at)
                                    <div><dt class="text-xs text-slate-500">Agreed to camera monitoring</dt><dd class="text-slate-900">{{ $attempt->proctoring_consented_at->format('M j, Y g:i:s a') }}</dd></div>
                                @endif
                            </dl>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Check the face and the ID card match the candidate.</p>
                    @else
                        <p class="mt-2 text-sm text-slate-500">No ID check photo for this attempt{{ $exam->proctoring_retention_days ? ' — it may have been deleted after '.$exam->proctoring_retention_days.' days' : '' }}.</p>
                    @endif
                </x-card>

                <x-card>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-base font-semibold text-slate-900">Photos during the exam</h2>
                        <span class="text-xs text-slate-500">{{ $snapshots->count() }} {{ \Illuminate\Support\Str::plural('photo', $snapshots->count()) }}</span>
                    </div>
                    @if ($snapshots->isEmpty())
                        <p class="mt-2 text-sm text-slate-500">No photos for this attempt.</p>
                    @else
                        <p class="mt-1 text-xs text-slate-500">Photos taken when something was flagged are outlined in amber. Select a photo to open it full size.</p>
                        <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($snapshots as $snapshot)
                                <li>
                                    <a href="{{ route('cbt.manage.analytics.attempts.snapshot', [$exam, $attempt, $snapshot->id]) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg {{ $snapshot->kind === 'event' ? 'ring-2 ring-amber-400' : 'ring-1 ring-slate-200' }}">
                                        <img src="{{ route('cbt.manage.analytics.attempts.snapshot', [$exam, $attempt, $snapshot->id]) }}" alt="{{ $snapshot->label() }} at {{ $snapshot->created_at->format('g:i:s a') }}" loading="lazy" class="aspect-[4/3] w-full bg-slate-100 object-cover">
                                    </a>
                                    <p class="mt-1 text-xs {{ $snapshot->kind === 'event' ? 'font-medium text-amber-700' : 'text-slate-500' }}">{{ $snapshot->created_at->format('g:i:s a') }} &middot; {{ $snapshot->label() }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif

            <x-card :padded="false">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Flagged events</h2>
                </div>
                @if ($events->isEmpty())
                    <p class="px-6 py-4 text-sm text-slate-500">Nothing was flagged during this attempt.</p>
                @else
                    <ul class="divide-y divide-slate-200">
                        @foreach ($events as $event)
                            <li class="flex items-center justify-between gap-4 px-6 py-4">
                                <span class="text-sm text-slate-700">{{ $event->event_type->label() }}</span>
                                <span class="text-xs text-slate-500">{{ $event->created_at->format('M j, Y g:i:s a') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card>
                <h2 class="text-base font-semibold text-slate-900">Attempt</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Started</dt><dd class="text-slate-900">{{ $attempt->started_at->format('M j, g:i a') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Submitted</dt><dd class="text-slate-900">{{ $attempt->submitted_at?->format('M j, g:i a') ?? 'In progress' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Score</dt><dd class="text-slate-900">{{ $attempt->score !== null ? $attempt->score.'%' : '—' }}</dd></div>
                </dl>
            </x-card>

            <x-card>
                <h2 class="text-base font-semibold text-slate-900">Your review</h2>
                <form method="POST" action="{{ route('cbt.manage.analytics.attempts.review', [$exam, $attempt]) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <fieldset class="space-y-2">
                        <legend class="sr-only">Verdict</legend>
                        @foreach (['' => 'Not reviewed yet'] + \Elibrary\Cbt\Models\ExamAttempt::REVIEW_STATUSES as $value => $label)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="review_status" value="{{ $value }}" class="border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('review_status', $attempt->review_status ?? '') === $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </fieldset>
                    <x-textarea name="review_notes" label="Notes (optional)" rows="4" maxlength="5000">{{ old('review_notes', $attempt->review_notes) }}</x-textarea>
                    <x-button type="submit" class="w-full">Save review</x-button>
                </form>
                @if ($attempt->reviewed_at)
                    <p class="mt-3 text-xs text-slate-500">Last reviewed by {{ $attempt->reviewer?->name ?? 'a former team member' }} on {{ $attempt->reviewed_at->format('M j, Y g:ia') }}.</p>
                @endif
            </x-card>
        </aside>
    </div>
</x-app-layout>
