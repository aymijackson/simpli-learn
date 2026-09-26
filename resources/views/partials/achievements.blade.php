{{--
    Streak and badges for one person. Expects $streak (Achievements::streak)
    and $badges (Achievements::badgesFor); $self says whether it's their own.
--}}
@php
    $earned = $badges->keyBy(fn ($badge) => $badge->badge->value);
    $self ??= true;
@endphp
<div class="grid gap-4 lg:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/80">
        <div class="flex items-center gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $streak['current'] ? 'bg-orange-100 text-orange-600' : 'bg-slate-100 text-slate-400' }}">
                <x-icon name="fire" class="h-6 w-6" />
            </span>
            <div>
                <p class="text-2xl font-bold tracking-tight text-slate-900">{{ $streak['current'] }} {{ \Illuminate\Support\Str::plural('day', $streak['current']) }}</p>
                <p class="text-xs text-slate-500">
                    {{ $self ? 'Your' : 'Current' }} learning streak &middot; best {{ $streak['best'] }}
                </p>
            </div>
        </div>
        <div class="mt-4 flex justify-between gap-1">
            @foreach ($streak['week'] as $day => $active)
                <div class="flex flex-1 flex-col items-center gap-1">
                    <span class="h-2.5 w-full rounded-full {{ $active ? 'bg-orange-400' : 'bg-slate-100' }}" title="{{ \Illuminate\Support\Carbon::parse($day)->format('l j M') }}"></span>
                    <span class="text-[10px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($day)->format('D')[0] }}</span>
                </div>
            @endforeach
        </div>
        @if ($self && ! $streak['today'])
            <p class="mt-3 text-xs text-slate-500">{{ $streak['current'] ? 'Open a lesson today to keep your streak going.' : 'Open a lesson today to start a streak.' }}</p>
        @endif
    </div>

    <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/80 lg:col-span-2">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-900">Badges</p>
            <p class="text-xs text-slate-500">{{ $earned->count() }} of {{ count(\App\Enums\Badge::cases()) }} earned</p>
        </div>
        <ul class="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-7">
            @foreach (\App\Enums\Badge::cases() as $badge)
                @php($got = $earned->get($badge->value))
                <li class="flex flex-col items-center text-center" title="{{ $badge->label() }} — {{ $badge->description() }}{{ $got ? ' Earned '.$got->earned_at?->format('j M Y').'.' : '' }}">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full {{ $got ? $badge->tone().' ring-2 ring-white shadow-sm' : 'bg-slate-100 text-slate-300' }}">
                        <x-icon :name="$got ? $badge->icon() : 'lock'" class="h-5 w-5" />
                    </span>
                    <span class="mt-1.5 text-[11px] leading-tight {{ $got ? 'font-medium text-slate-700' : 'text-slate-400' }}">{{ $badge->label() }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
