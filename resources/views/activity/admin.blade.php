<x-admin-layout title="Activity log">
    <x-page-header title="Activity log" subtitle="Security-relevant activity across the platform and every workspace." />
    @include('activity._list', ['formAction' => route('admin.activity'), 'showWorkspace' => true])
</x-admin-layout>
