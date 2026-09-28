{{-- The support chat bubble. Learners reach their workspace's owners;
     owners reach platform support. Driven by public/js/support-chat.js,
     which polls for new messages (no websockets needed on shared hosting). --}}
@php
    $supportUser = auth()->user();
    $supportDesk = $supportUser ? \App\Models\SupportConversation::deskFor($supportUser) : null;
@endphp
@if ($supportDesk)
    @php
        $supportTenant = app(\App\Support\Tenancy\Tenancy::class)->current();
        $supportTitle = $supportDesk === \App\Models\SupportConversation::PLATFORM ? __('Platform support') : __('Help from :name', ['name' => $supportTenant?->name]);
    @endphp
    <div data-support-bubble class="fixed right-4 bottom-4 z-40 print:hidden sm:right-6 sm:bottom-6">
        <div id="support-panel" data-support-panel role="dialog" aria-modal="false" aria-labelledby="support-panel-title" hidden
             class="mb-3 flex h-[min(34rem,calc(100vh-7rem))] w-[min(24rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-3 bg-ink-900 px-4 py-3 text-white">
                <div class="min-w-0">
                    <p id="support-panel-title" class="truncate text-sm font-semibold">{{ $supportTitle }}</p>
                    <p class="text-xs text-slate-300">{{ __('We usually reply within a day.') }}</p>
                </div>
                <button type="button" data-support-close class="rounded-lg p-1.5 text-slate-300 hover:bg-white/10 hover:text-white" aria-label="{{ __('Close chat') }}">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>

            <div data-support-thread
                 data-mode="bubble"
                 data-poll-url="{{ route('tenant.support.chat') }}"
                 data-send-url="{{ route('tenant.support.chat.store') }}"
                 data-csrf="{{ csrf_token() }}"
                 data-send-error="{{ __('Your message couldn\'t be sent. Please try again.') }}"
                 data-you="{{ __('You') }}"
                 class="flex min-h-0 flex-1 flex-col">
                <ol data-support-messages class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-4 py-4" aria-live="polite">
                    <li data-support-empty class="py-8 text-center text-sm text-slate-500">{{ __('Ask us anything — we\'ll get back to you here, and you\'ll get a notification.') }}</li>
                </ol>
                <form data-support-form class="flex items-end gap-2 border-t border-slate-100 bg-white p-3">
                    <label for="support-message" class="sr-only">{{ __('Your message') }}</label>
                    <textarea id="support-message" name="body" rows="2" maxlength="2000" required placeholder="{{ __('Type your message…') }}"
                              class="block w-full resize-none rounded-lg border-0 px-3 py-2 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600"></textarea>
                    <button type="submit" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50" aria-label="{{ __('Send') }}">
                        <x-icon name="paper-airplane" class="h-4 w-4" />
                    </button>
                </form>
                <p data-support-error class="hidden px-4 pb-3 text-xs text-red-600" role="alert"></p>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="button" data-support-toggle aria-expanded="false" aria-controls="support-panel"
                    class="relative flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg shadow-brand-900/20 transition hover:bg-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                <x-icon name="chat" class="h-6 w-6" />
                <span class="sr-only">{{ __('Support chat') }}</span>
                <span data-support-unread hidden class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-bold text-white" aria-label="{{ __('Unread messages') }}"></span>
            </button>
        </div>
    </div>
    @push('scripts')
        <script src="{{ \App\Support\Asset::url('js/support-chat.js') }}" defer></script>
    @endpush
@endif
