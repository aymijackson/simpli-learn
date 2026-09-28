{{-- The support inbox, in the owner back office or the platform admin area. --}}
@if ($platform)
    <x-admin-layout title="Support chats">
        @include('support._inbox')
    </x-admin-layout>
@else
    <x-app-layout title="Support inbox">
        @include('support._inbox')
    </x-app-layout>
@endif
