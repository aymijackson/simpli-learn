@php
    $route = fn (string $name, ...$args) => $platform ? route("admin.support.{$name}", ...$args) : route("tenant.manage.support.{$name}", ...$args);
@endphp

<x-page-header :title="$platform ? 'Support chats' : 'Support inbox'"
               :subtitle="$platform ? 'Workspace owners asking for help. They are told when you reply.' : 'Questions from your learners. They are told when you reply.'" />

@if (session('status'))
    <div role="status" class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-200">{{ session('status') }}</div>
@endif

<div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
    {{-- Conversations --}}
    <div>
        <div class="mb-3 flex gap-2 text-sm">
            @foreach (['open' => 'Open', 'resolved' => 'Resolved'] as $value => $label)
                <a href="{{ $route('index', ['show' => $value === 'open' ? null : $value]) }}"
                   @if ($filter === $value) aria-current="page" @endif
                   class="rounded-full px-3 py-1 font-medium {{ $filter === $value ? 'bg-ink-900 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">{{ $label }}</a>
            @endforeach
        </div>
        @if ($conversations->isEmpty())
            <x-empty-state icon="chat" :title="$filter === 'open' ? 'No open chats' : 'No resolved chats'" description="New messages appear here, and you're notified." />
        @else
            <x-card :padded="false" class="overflow-hidden">
                <ul class="divide-y divide-slate-100">
                    @foreach ($conversations as $conversation)
                        @php($waiting = $waitingIds->contains($conversation->id))
                        <li>
                            <a href="{{ $route('show', $conversation->id) }}" @if ($open?->id === $conversation->id) aria-current="true" @endif
                               class="flex items-start gap-3 px-4 py-3 {{ $open?->id === $conversation->id ? 'bg-brand-50' : 'hover:bg-slate-50' }}">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $waiting ? 'bg-brand-600' : 'bg-transparent' }}" @if ($waiting) aria-label="Waiting for a reply" @endif></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm {{ $waiting ? 'font-semibold text-slate-900' : 'font-medium text-slate-700' }}">{{ $conversation->user?->name ?? 'Former member' }}</span>
                                    <span class="block truncate text-xs text-slate-500">
                                        @if ($platform) {{ $conversation->tenant?->name }} &middot; @endif
                                        {{ $conversation->last_message_at?->diffForHumans() }}
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <div class="mt-3">{{ $conversations->links() }}</div>
        @endif
    </div>

    {{-- The open conversation --}}
    <div>
        @if ($open)
            <x-card :padded="false" class="flex h-[70vh] flex-col overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $open->user?->name ?? 'Former member' }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $open->user?->email }}@if ($platform) &middot; {{ $open->tenant?->name }}@endif</p>
                    </div>
                    <form method="POST" action="{{ $route('resolve', $open->id) }}">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm" :icon="$open->status === 'resolved' ? null : 'check'">{{ $open->status === 'resolved' ? 'Reopen' : 'Mark resolved' }}</x-button>
                    </form>
                </div>

                <div data-support-thread
                     data-mode="staff"
                     data-poll-url="{{ $route('messages', $open->id) }}"
                     data-send-url="{{ $route('reply', $open->id) }}"
                     data-csrf="{{ csrf_token() }}"
                     data-last-id="{{ $messages->last()['id'] ?? 0 }}"
                     data-send-error="Your message couldn't be sent. Please try again."
                     class="flex min-h-0 flex-1 flex-col">
                    <ol data-support-messages class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-5 py-4" aria-live="polite">
                        @foreach ($messages as $message)
                            @include('support._message', ['message' => $message])
                        @endforeach
                    </ol>
                    <form data-support-form class="flex items-end gap-2 border-t border-slate-100 bg-white p-3">
                        <label for="support-reply" class="sr-only">Reply</label>
                        <textarea id="support-reply" name="body" rows="2" maxlength="2000" required placeholder="Write a reply…"
                                  class="block w-full resize-none rounded-lg border-0 px-3 py-2 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600"></textarea>
                        <x-button type="submit" icon="paper-airplane">Send</x-button>
                    </form>
                    <p data-support-error class="hidden px-4 pb-3 text-xs text-red-600" role="alert"></p>
                </div>
            </x-card>
        @else
            <x-card class="flex h-[70vh] items-center justify-center text-center">
                <div>
                    <x-icon name="chat" class="mx-auto h-8 w-8 text-slate-300" />
                    <p class="mt-2 text-sm text-slate-500">Choose a conversation.</p>
                </div>
            </x-card>
        @endif
    </div>
</div>

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/support-chat.js') }}" defer></script>
@endpush
