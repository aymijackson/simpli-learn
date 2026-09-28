<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use App\Support\SafeNotifier;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * The public contact form, with layered protection against abuse:
 *  - CSRF token and per-IP rate limits (the "contact" limiter);
 *  - a hidden honeypot field bots fill in, and a signed timestamp that
 *    rejects forms sent back faster than a person could type them;
 *  - a cap on links (the mark of spam) and plain-text-only content;
 *  - optional Cloudflare Turnstile when TURNSTILE_* keys are set;
 *  - messages encrypted at rest, the IP kept only as a keyed hash, and
 *    everything deleted after a year.
 */
class ContactController extends Controller
{
    /** A person can't fill the form in less than this. */
    private const MIN_SECONDS = 3;

    /** A form older than this has to be reloaded. */
    private const MAX_SECONDS = 2 * 60 * 60;

    private const MAX_LINKS = 3;

    public function create(): View
    {
        return view('contact', [
            'formToken' => Crypt::encryptString((string) now()->timestamp),
            'turnstileSiteKey' => config('services.turnstile.site_key'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $thanks = redirect()->route('contact')->with('status', "Thanks — your message has been sent. We'll reply by email.");

        // Bots fill every field; people never see this one. Pretend it worked.
        if (filled($request->input('website'))) {
            return $thanks;
        }

        $age = $this->formAge((string) $request->input('form_token'));
        if ($age === null || $age > self::MAX_SECONDS) {
            throw ValidationException::withMessages(['message' => 'This form has expired. Please reload the page and try again.']);
        }
        if ($age < self::MIN_SECONDS) {
            return $thanks; // sent back instantly: a script, not a person
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        if (preg_match_all('~(https?://|www\.)~i', $validated['message']) > self::MAX_LINKS) {
            throw ValidationException::withMessages(['message' => 'Please include no more than '.self::MAX_LINKS.' links.']);
        }

        $this->verifyTurnstile($request);

        $contact = ContactMessage::create([
            'name' => $this->plain($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'subject' => filled($validated['subject'] ?? null) ? $this->plain($validated['subject']) : null,
            'message' => $this->plain($validated['message'], multiline: true),
            'ip_hash' => ContactMessage::hashIp($request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        $admins = User::withoutGlobalScopes()->whereNull('tenant_id')->whereNull('deactivated_at')->get();
        SafeNotifier::send($admins, new ContactMessageReceived($contact));

        return $thanks;
    }

    /** Seconds since the form was served, or null if the token was tampered with. */
    private function formAge(string $token): ?int
    {
        try {
            return now()->timestamp - (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return null;
        }
    }

    private function verifyTurnstile(Request $request): void
    {
        $secret = config('services.turnstile.secret');
        if (! $secret) {
            return;
        }

        try {
            $passed = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => (string) $request->input('cf-turnstile-response'),
                'remoteip' => $request->ip(),
            ])->json('success') === true;
        } catch (Throwable $e) {
            report($e);
            $passed = false;
        }

        if (! $passed) {
            throw ValidationException::withMessages(['message' => 'Please complete the security check and try again.']);
        }
    }

    /** Plain text: no control characters, trimmed. It's always shown escaped as well. */
    private function plain(string $value, bool $multiline = false): string
    {
        $value = str_replace("\r\n", "\n", $value);
        $value = preg_replace($multiline ? '/[^\P{C}\n\t]/u' : '/\p{C}/u', '', $value) ?? '';

        return trim($value);
    }
}
