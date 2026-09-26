@props(['notification', 'compact' => false])
@php
    $data = $notification->data;
    $tones = [
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'brand' => 'bg-brand-50 text-brand-600',
    ];
    $unread = $notification->read_at === null;
@endphp
<form method="POST" action="{{ route('tenant.notifications.open', $notification->id) }}">
    @csrf
    <button type="submit" class="flex w-full items-start gap-3 text-left {{ $compact ? 'rounded-lg px-3 py-2.5' : 'px-5 py-4' }} hover:bg-slate-50 {{ $unread ? 'bg-brand-50/40' : '' }}">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $tones[$data['tone'] ?? 'brand'] ?? $tones['brand'] }}">
            <x-icon :name="$data['icon'] ?? 'bell'" class="h-4 w-4" />
        </span>
        <span class="min-w-0 flex-1">
            <span class="flex items-center gap-2">
                <span class="truncate text-sm {{ $unread ? 'font-semibold text-slate-900' : 'font-medium text-slate-700' }}">{{ $data['title'] ?? 'Notification' }}</span>
                @if ($unread)
                    <span class="h-2 w-2 shrink-0 rounded-full bg-brand-600" aria-label="Unread"></span>
                @endif
            </span>
            <span class="{{ $compact ? 'line-clamp-2' : '' }} mt-0.5 block text-xs text-slate-500">{{ $data['body'] ?? '' }}</span>
            <span class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
        </span>
    </button>
</form>
