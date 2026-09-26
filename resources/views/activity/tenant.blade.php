<x-app-layout title="Activity log">
    <x-page-header title="Activity log" subtitle="A record of sign-ins, team changes, payments and settings changes in your workspace." />
    @include('activity._list', ['formAction' => route('tenant.manage.activity'), 'showWorkspace' => false])
</x-app-layout>
