{{-- The header bell: unread count and the latest few notifications. --}}
@php
    $bellUser = auth()->user();
    $unreadCount = $bellUser->unreadNotifications()->count();
    $latest = $bellUser->notifications()->take(6)->get();
@endphp
<details data-dropdown class="relative">
    <summary class="relative flex cursor-pointer items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Notifications{{ $unreadCount ? " ({$unreadCount} unread)" : '' }}">
        <x-icon name="bell" class="h-5 w-5" />
        @if ($unreadCount)
            <span class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </summary>
    <div class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-semibold text-slate-900">Notifications</p>
            @if ($unreadCount)
                <form method="POST" action="{{ route('tenant.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-brand-700 hover:text-brand-800">Mark all read</button>
                </form>
            @endif
        </div>
        @if ($latest->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-slate-500">You're all caught up.</p>
        @else
            <div class="max-h-96 space-y-0.5 overflow-y-auto p-1.5">
                @foreach ($latest as $notification)
                    <x-notification-item :notification="$notification" compact />
                @endforeach
            </div>
        @endif
        <a href="{{ route('tenant.notifications.index') }}" class="block border-t border-slate-100 px-4 py-2.5 text-center text-xs font-semibold text-slate-600 hover:bg-slate-50">See all notifications</a>
    </div>
</details>
