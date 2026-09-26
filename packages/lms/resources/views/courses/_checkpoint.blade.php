{{-- One exam checkpoint in the course outline: a lesson quiz, module test or final exam. --}}
@php
    $exam = $checkpoint['exam'];
    $status = $checkpoint['status'];
    $canOpen = $exam->is_published && in_array($status, ['open', 'passed', 'marking'], true);
    $tone = match ($status) {
        'passed' => 'bg-emerald-100 text-emerald-700',
        'marking' => 'bg-amber-100 text-amber-700',
        'open' => 'bg-brand-600 text-white',
        default => 'bg-slate-100 text-slate-400',
    };
@endphp
<li class="bg-brand-50/40">
    <a @if ($canOpen) href="{{ route('cbt.exams.show', $exam) }}" @else aria-disabled="true" @endif
       class="flex items-center justify-between gap-4 {{ $compact ?? false ? 'px-5 py-3' : 'px-5 py-3.5' }} {{ $canOpen ? 'hover:bg-brand-50' : 'cursor-not-allowed' }}">
        <span class="flex min-w-0 items-center gap-3">
            <span class="flex {{ $compact ?? false ? 'h-6 w-6' : 'h-7 w-7' }} shrink-0 items-center justify-center rounded-full {{ $tone }}">
                @if ($status === 'passed')
                    <x-icon name="check" class="h-3.5 w-3.5" />
                @elseif ($status === 'locked')
                    <x-icon name="lock" class="h-3 w-3" />
                @else
                    <x-icon name="clipboard-check" class="h-3.5 w-3.5" />
                @endif
            </span>
            <span class="min-w-0">
                <span class="block text-[11px] font-semibold uppercase tracking-wide text-brand-700">{{ $checkpoint['label'] }}</span>
                <span class="block truncate text-sm {{ $canOpen ? 'font-medium text-slate-900' : 'text-slate-500' }}">{{ $exam->title }}</span>
            </span>
        </span>
        @unless ($compact ?? false)
            <span class="shrink-0 text-xs">
                @if (! $exam->is_published)
                    <span class="text-slate-400">Not available yet</span>
                @elseif ($status === 'passed')
                    <x-badge color="green">Passed</x-badge>
                @elseif ($status === 'marking')
                    <x-badge color="amber">Awaiting marking</x-badge>
                @elseif ($status === 'open')
                    <span class="font-semibold text-brand-700">Take it &rarr;</span>
                @else
                    <span class="text-slate-400">Unlocks as you progress</span>
                @endif
            </span>
        @endunless
    </a>
</li>
