{{-- Webcam proctoring during an attempt: a small self-view, regular photos, a full-screen prompt, and a block while the camera is off. --}}
@if ($exam->webcam_proctoring)
    <div data-proctor-session
         data-snapshot-url="{{ route('cbt.attempts.snapshots.store', $attempt) }}"
         data-event-url="{{ route('cbt.attempts.integrity.store', $attempt) }}"
         data-interval="{{ $exam->snapshot_interval_seconds }}"
         data-attempt="{{ $attempt->id }}"
         data-csrf="{{ csrf_token() }}">
        @php
            $proctorText = [
                'blocked' => __('Camera access is blocked. Allow the camera for this site in your browser’s address bar, then try again.'),
                'missing' => __('No camera was found. Connect a camera and try again.'),
                'busy' => __('Your camera is being used by another app. Close it and try again.'),
                'unsupported' => __('This browser cannot use a camera. Try a recent version of Chrome, Edge, Firefox or Safari.'),
            ];
        @endphp
        <script type="application/json" data-proctor-text>@json($proctorText)</script>

        <video data-proctor-video autoplay playsinline muted aria-hidden="true"
               class="fixed bottom-4 left-4 z-40 h-24 w-32 -scale-x-100 rounded-lg bg-slate-900 object-cover shadow-lg ring-2 ring-white"></video>

        <div data-proctor-fullscreen class="fixed inset-x-0 top-0 z-40 hidden">
            <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 rounded-b-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-lg ring-1 ring-amber-200">
                <span>{{ __('This exam should be taken in full screen.') }}</span>
                <x-button type="button" size="sm" data-proctor-enter-fullscreen>{{ __('Enter full screen') }}</x-button>
            </div>
        </div>

        <div data-proctor-blocker role="alertdialog" aria-modal="true" aria-labelledby="proctor-blocker-title" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/90 p-6">
            <div class="max-w-md rounded-2xl bg-white p-6 text-center shadow-xl">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600"><x-icon name="camera" class="h-6 w-6" /></span>
                <h2 id="proctor-blocker-title" class="mt-4 text-lg font-bold text-slate-900">{{ __('Your camera is off') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('This exam needs your camera on. Turn it back on to continue — the timer keeps running.') }}</p>
                <p data-proctor-message class="mt-2 text-sm text-rose-700"></p>
                <x-button type="button" class="mt-5" icon="camera" data-proctor-retry>{{ __('Turn camera on') }}</x-button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ \App\Support\Asset::url('js/exam-proctor.js') }}" defer></script>
    @endpush
@endif
