<x-app-layout title="Notifications">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Notifications" :subtitle="$unread ? $unread.' unread' : 'You\'re all caught up.'">
            @if ($unread)
                <x-slot:actions>
                    <form method="POST" action="{{ route('tenant.notifications.read-all') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" icon="check">Mark all read</x-button>
                    </form>
                </x-slot:actions>
            @endif
        </x-page-header>

        @if ($notifications->isEmpty())
            <x-empty-state icon="bell" title="No notifications yet" description="Course assignments, reminders and exam results will show up here." />
        @else
            <x-card :padded="false" class="overflow-hidden">
                <div class="divide-y divide-slate-100">
                    @foreach ($notifications as $notification)
                        <x-notification-item :notification="$notification" />
                    @endforeach
                </div>
            </x-card>
            <div class="mt-4">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
