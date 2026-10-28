{{-- Before a proctored or supervised exam: camera check with an ID photo, consent, and the invigilator's code. --}}
<x-card id="start-check" class="scroll-mt-24">
    <h2 class="text-lg font-bold text-slate-900">{{ __('Before you start') }}</h2>
    <p class="mt-1 text-sm text-slate-600">
        {{ $exam->webcam_proctoring
            ? __('This exam is proctored by webcam. Turn on your camera, take a photo holding your ID card next to your face, then start.')
            : __('Enter the code your invigilator gives you, then start.') }}
    </p>

    <form method="POST" action="{{ route('cbt.exams.start', $exam) }}" class="mt-5 space-y-5" @if ($exam->webcam_proctoring) data-proctor-check @endif>
        @csrf

        @if ($exam->webcam_proctoring)
            @php
                $proctorText = [
                    'starting' => __('Starting your camera…'),
                    'ready' => __('Hold your ID card next to your face so both are clear, then take the photo.'),
                    'taken' => __('Photo taken. Retake it if your face or ID is not clear.'),
                    'blocked' => __('Camera access is blocked. Allow the camera for this site in your browser’s address bar, then try again.'),
                    'missing' => __('No camera was found. Connect a camera and try again.'),
                    'busy' => __('Your camera is being used by another app. Close it and try again.'),
                    'insecure' => __('The camera only works over a secure (https) connection.'),
                    'unsupported' => __('This browser cannot use a camera. Try a recent version of Chrome, Edge, Firefox or Safari.'),
                    'needPhoto' => __('Please take your ID check photo first.'),
                    'needConsent' => __('Please agree to camera monitoring to start this exam.'),
                ];
            @endphp
            <script type="application/json" data-proctor-text>@json($proctorText)</script>

            <div class="relative aspect-video overflow-hidden rounded-xl bg-slate-900">
                <video data-proctor-video autoplay playsinline muted class="hidden h-full w-full -scale-x-100 object-cover"></video>
                <img data-proctor-preview alt="{{ __('Your ID check photo') }}" class="hidden h-full w-full object-cover">
                <div data-proctor-placeholder class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center text-slate-300">
                    <x-icon name="camera" class="h-10 w-10" />
                    <p class="text-sm">{{ __('Your camera preview will appear here.') }}</p>
                </div>
            </div>

            <p data-proctor-message role="status" aria-live="polite" class="text-sm text-slate-600"></p>

            <div class="flex flex-wrap gap-3">
                <x-button type="button" variant="secondary" icon="camera" data-proctor-camera>{{ __('Turn on camera') }}</x-button>
                <x-button type="button" icon="camera" style="display: none" data-proctor-capture>{{ __('Take ID photo') }}</x-button>
                <x-button type="button" variant="secondary" style="display: none" data-proctor-retake>{{ __('Retake photo') }}</x-button>
            </div>

            <input type="hidden" name="id_photo" data-proctor-photo>
            @error('id_photo')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

            <div>
                <label class="flex gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="proctoring_consent" value="1" data-proctor-consent class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('proctoring_consent'))>
                    <span>{{ __('I agree to my webcam being used during this exam. Photos of me and my ID are seen only by the exam reviewers and deleted after :days days.', ['days' => $exam->proctoring_retention_days]) }}</span>
                </label>
                @error('proctoring_consent')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <ul class="space-y-1 text-xs text-slate-500">
                <li>{{ __('Sit somewhere quiet and well lit, with your face in view.') }}</li>
                <li>{{ __('Take the exam in full screen. Leaving full screen, switching windows or turning off your camera is recorded.') }}</li>
            </ul>
        @endif

        @if ($exam->requiresInvigilatorCode())
            <x-input type="text" name="invigilator_code" :label="__('Invigilator code')" value="{{ old('invigilator_code') }}" maxlength="50" autocomplete="off" autocapitalize="characters" required />
        @endif

        <x-button type="submit" icon="play" data-proctor-start>{{ $attempts->isEmpty() ? __('Start exam') : __('Retake exam') }}</x-button>
    </form>
</x-card>
