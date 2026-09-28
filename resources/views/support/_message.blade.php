{{-- One chat message (server-rendered; support-chat.js builds the same markup for new ones). --}}
<li class="flex {{ $message['mine'] ? 'justify-end' : 'justify-start' }}">
    <div class="max-w-[85%]">
        <div class="rounded-2xl px-3.5 py-2 text-sm leading-relaxed whitespace-pre-line {{ $message['mine'] ? 'rounded-br-md bg-brand-600 text-white' : 'rounded-bl-md bg-white text-slate-800 ring-1 ring-slate-200' }}">{{ $message['body'] }}</div>
        <p class="mt-1 px-1 text-[11px] text-slate-500 {{ $message['mine'] ? 'text-right' : '' }}">{{ $message['mine'] ? '' : $message['sender'].' · ' }}{{ $message['time'] }}</p>
    </div>
</li>
