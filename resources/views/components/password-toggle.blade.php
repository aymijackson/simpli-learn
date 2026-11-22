{{--
    Show/hide button for a password field. Place it inside a "relative"
    wrapper right after the <input type="password"> (give the input pr-10 so
    text doesn't run under the button). public/js/app.js does the toggling.
--}}
<button type="button" data-password-toggle aria-pressed="false"
        aria-label="{{ __('Show password') }}" title="{{ __('Show password') }}"
        data-label-show="{{ __('Show password') }}" data-label-hide="{{ __('Hide password') }}"
        class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-slate-400 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600">
    <x-icon name="eye" class="h-5 w-5" data-password-icon="show" />
    <x-icon name="eye-slash" class="h-5 w-5" data-password-icon="hide" style="display: none" />
</button>
