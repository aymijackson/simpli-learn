<x-app-layout title="Import people">
    <a href="{{ route('tenant.team.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> Team &amp; learners
    </a>

    <x-page-header title="Import people from a CSV file" subtitle="Add your whole team at once. Everyone gets an email invitation to set their own password." />

    @if ($result = session('import'))
        <x-card class="mb-6">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><x-icon name="check-circle" /></span>
                <div class="min-w-0 flex-1">
                    <p class="text-base font-semibold text-slate-900">
                        {{ $result['created'] }} {{ \Illuminate\Support\Str::plural('person', $result['created']) }} added and invited
                    </p>
                    @if ($result['course'])
                        <p class="mt-1 text-sm text-slate-600">
                            Assigned <strong>{{ $result['course'] }}</strong> to them{{ $result['assignedExisting'] ? ' and to '.$result['assignedExisting'].' existing '.\Illuminate\Support\Str::plural('member', $result['assignedExisting']) : '' }}.
                        </p>
                    @endif
                    @if ($result['emailFailures'])
                        <p class="mt-2 text-sm font-medium text-amber-700">
                            {{ $result['emailFailures'] }} invitation {{ \Illuminate\Support\Str::plural('email', $result['emailFailures']) }} could not be sent — check the mail settings, then use "Resend invitation" on the Team page.
                        </p>
                    @endif
                    @if (count($result['skipped']))
                        <details class="mt-4" open>
                            <summary class="cursor-pointer text-sm font-medium text-slate-700">{{ count($result['skipped']) }} {{ \Illuminate\Support\Str::plural('row', count($result['skipped'])) }} skipped</summary>
                            <ul class="mt-2 max-h-64 divide-y divide-slate-100 overflow-y-auto rounded-lg text-sm ring-1 ring-slate-200">
                                @foreach ($result['skipped'] as $skip)
                                    <li class="flex gap-3 px-3 py-2">
                                        <span class="w-16 shrink-0 text-slate-400">Row {{ $skip['line'] }}</span>
                                        <span class="min-w-0 flex-1 truncate font-medium text-slate-800">{{ $skip['email'] }}</span>
                                        <span class="shrink-0 text-slate-500">{{ $skip['reason'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            </div>
        </x-card>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <form method="POST" action="{{ route('tenant.team.import.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label for="file" class="mb-1.5 block text-sm font-medium text-slate-700">CSV file</label>
                    <input id="file" type="file" name="file" accept=".csv,text/csv" required
                           class="block w-full rounded-lg text-sm text-slate-700 ring-1 ring-slate-300 file:mr-4 file:rounded-l-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-slate-500">Up to {{ \App\Http\Controllers\TeamImportController::MAX_ROWS }} people per file.</p>
                </div>

                @if ($courses->isNotEmpty())
                    <fieldset class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                        <legend class="px-1 text-sm font-semibold text-slate-900">Assign a course <span class="font-normal text-slate-500">(optional)</span></legend>
                        <p class="mb-3 text-xs text-slate-500">Also applies to anyone in the file who is already a member.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="course_id" class="mb-1.5 block text-sm font-medium text-slate-700">Course</label>
                                <select id="course_id" name="course_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                                    <option value="">Don't assign a course</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->title }}</option>
                                    @endforeach
                                </select>
                                @error('course_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="due_at" class="mb-1.5 block text-sm font-medium text-slate-700">Due date</label>
                                <input id="due_at" type="date" name="due_at" value="{{ old('due_at') }}" min="{{ now()->toDateString() }}"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                                @error('due_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>
                @endif

                <x-button type="submit" icon="check">Import and send invitations</x-button>
            </form>
        </x-card>

        <x-card>
            <h2 class="text-base font-semibold text-slate-900">File format</h2>
            <p class="mt-2 text-sm text-slate-600">One person per row, with a header row:</p>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-3 font-mono text-xs leading-relaxed text-slate-100">name,email,role
Ada Obi,ada@example.com,member
Tunde Bello,tunde@example.com,owner</pre>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                <li><strong>email</strong> is required.</li>
                <li><strong>name</strong> is optional — we'll use the email if it's missing.</li>
                <li><strong>role</strong> is optional — <em>member</em> (default) or <em>owner</em>.</li>
                <li>People already in the workspace are skipped.</li>
            </ul>
            <x-button :href="route('tenant.team.import.template')" variant="secondary" icon="download" class="mt-5 w-full">Download template</x-button>
            <p class="mt-3 text-xs text-slate-500">In Excel or Google Sheets, fill in the template, then save or download it as CSV.</p>
        </x-card>
    </div>
</x-app-layout>
