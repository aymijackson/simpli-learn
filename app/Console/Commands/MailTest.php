<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Diagnoses outgoing email: shows the mail settings Laravel is really using,
 * checks the mail server is reachable, sends a test message and explains
 * any failure.
 *
 *   php artisan mail:test you@example.com
 */
class MailTest extends Command
{
    protected $signature = 'mail:test {to : Address to send the test email to} {--timeout=20 : Seconds to wait for the mail server}';

    protected $description = 'Check the mail settings and send a test email';

    public function handle(): int
    {
        $to = (string) $this->argument('to');
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("\"{$to}\" is not a valid email address.");

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $settings = (array) config("mail.mailers.{$mailer}", []);
        $transport = $settings['transport'] ?? $mailer;
        $host = (string) ($settings['host'] ?? '');
        $port = (int) ($settings['port'] ?? 0);
        $scheme = (string) ($settings['scheme'] ?? '');
        $username = (string) ($settings['username'] ?? '');
        $from = (string) config('mail.from.address');

        $this->newLine();
        $this->line('<options=bold>Mail settings in use</>');
        $this->table([], [
            ['Mailer (MAIL_MAILER)', $mailer.($transport !== $mailer ? " ({$transport})" : '')],
            ['Host (MAIL_HOST)', $host ?: '—'],
            ['Port (MAIL_PORT)', $port ?: '—'],
            ['Encryption (MAIL_SCHEME)', $scheme ?: '(none / automatic STARTTLS)'],
            ['Username (MAIL_USERNAME)', $username ?: '—'],
            ['Password (MAIL_PASSWORD)', ($settings['password'] ?? null) ? 'set ('.strlen((string) $settings['password']).' characters)' : 'NOT SET'],
            ['From (MAIL_FROM_ADDRESS)', $from ?: 'NOT SET'],
            ['From name (MAIL_FROM_NAME)', (string) config('mail.from.name')],
            ['Settings cached?', app()->configurationIsCached() ? 'YES — run "php artisan config:clear" after editing .env' : 'no'],
        ]);

        $problems = $this->preflight($transport, $host, $port, $scheme, $username, $from);
        if ($problems === null) {
            return self::FAILURE;
        }

        if ($transport === 'smtp' && $host !== '') {
            $this->line("Checking the server can reach {$host}:{$port} …");
            $errno = 0;
            $errstr = '';
            $socket = @fsockopen(($scheme === 'smtps' ? 'ssl://' : '').$host, $port, $errno, $errstr, 10);
            if (! $socket) {
                $this->error("  Could not connect: {$errstr} (error {$errno})");
                $this->line('  → Check MAIL_HOST and MAIL_PORT. On cPanel use your mail server name (e.g. mail.yourdomain.com) with');
                $this->line('    port 465 + MAIL_SCHEME=smtps, or port 587 with MAIL_SCHEME left empty. Some hosts block other ports.');

                return self::FAILURE;
            }
            fclose($socket);
            $this->info('  Connected.');
        }

        config(["mail.mailers.{$mailer}.timeout" => (int) $this->option('timeout')]);

        $this->line("Sending a test email to {$to} …");
        try {
            Mail::raw(
                "This is a test email from ".config('app.name')." (".config('app.url').").\n\n"
                ."If you're reading this, outgoing email works: password resets, workspace approvals and welcome emails will be delivered.\n\n"
                .'Sent '.now()->toDayDateTimeString().' via '.($host ?: $mailer).'.',
                fn ($message) => $message->to($to)->subject('Test email from '.config('app.name')),
            );
        } catch (Throwable $e) {
            $this->error('  Sending failed: '.get_class($e));
            $this->line('  '.Str::limit(trim($e->getMessage()), 600));
            $this->newLine();
            $this->line('  → '.$this->explain($e->getMessage()));

            return self::FAILURE;
        }

        if (in_array($transport, ['log', 'array'], true)) {
            $this->warn('  Nothing was actually sent — the "'.$transport.'" mailer only '.($transport === 'log' ? 'writes emails to storage/logs/laravel.log.' : 'keeps them in memory.'));

            return self::FAILURE;
        }

        $this->info('  Accepted by the mail server.');
        $this->line("  Check {$to}'s inbox — and the spam folder. If it never arrives, the server accepted it but");
        $this->line('  delivery failed later: check cPanel > Email > Track Delivery, and that SPF/DKIM are set up');
        $this->line('  under cPanel > Email Deliverability.');

        return self::SUCCESS;
    }

    /** Warnings about common misconfigurations. Returns null when sending can't work at all. */
    private function preflight(string $transport, string $host, int $port, string $scheme, string $username, string $from): ?int
    {
        $warnings = 0;

        if (in_array($transport, ['log', 'array'], true)) {
            $this->error("MAIL_MAILER is \"{$transport}\" — emails are not sent at all.");
            $this->line('→ Set MAIL_MAILER=smtp plus MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_SCHEME and');
            $this->line('  MAIL_FROM_ADDRESS in .env (see .env.example), then run: php artisan config:clear');

            return null;
        }

        if ($from === '' || str_ends_with($from, '@example.com')) {
            $this->warn("MAIL_FROM_ADDRESS is \"{$from}\" — use a real mailbox on your domain, usually the same as MAIL_USERNAME.");
            $warnings++;
        }

        if ($transport === 'smtp') {
            if ($port === 465 && $scheme !== 'smtps') {
                $this->warn('Port 465 needs MAIL_SCHEME=smtps (SSL from the start).');
                $warnings++;
            }
            if ($port === 587 && $scheme === 'smtps') {
                $this->warn('Port 587 uses STARTTLS — leave MAIL_SCHEME empty (or set it to smtp), not smtps.');
                $warnings++;
            }
            if ($username !== '' && $from !== '' && strcasecmp($username, $from) !== 0 && str_contains($username, '@')) {
                $this->warn("MAIL_FROM_ADDRESS ({$from}) differs from MAIL_USERNAME ({$username}). Many cPanel servers reject");
                $this->line('  mail whose sender is not the account that logged in — make them the same if sending fails.');
                $warnings++;
            }
            if (in_array($host, ['127.0.0.1', 'localhost', ''], true) && $port === 2525) {
                $this->warn('MAIL_HOST/MAIL_PORT are still the development defaults (127.0.0.1:2525).');
                $warnings++;
            }
        }

        if ($warnings === 0) {
            $this->info('Settings look sensible.');
        }

        return $warnings;
    }

    private function explain(string $message): string
    {
        $m = Str::lower($message);

        return match (true) {
            str_contains($m, '535') || str_contains($m, 'authentication') || str_contains($m, 'auth') && str_contains($m, 'fail')
                => 'The mail server rejected the username/password. Use the full email address as MAIL_USERNAME and the mailbox password (reset it in cPanel > Email Accounts if unsure). If the password contains # or spaces, wrap it in double quotes in .env.',
            str_contains($m, 'certificate') || str_contains($m, 'ssl') || str_contains($m, 'tls') || str_contains($m, 'peer')
                => 'Encryption handshake failed. Use the host name on the mail server\'s certificate (often the server\'s own hostname shown in cPanel > Email Accounts > Connect Devices) instead of mail.yourdomain.com, and check the port/MAIL_SCHEME pair.',
            str_contains($m, 'timed out') || str_contains($m, 'timeout')
                => 'The mail server did not respond in time. Check MAIL_HOST/MAIL_PORT, or ask your host whether outgoing SMTP on that port is blocked.',
            str_contains($m, 'refused') || str_contains($m, 'unable to connect') || str_contains($m, 'connection could not be established')
                => 'Could not connect to the mail server. Check MAIL_HOST and MAIL_PORT (465 with MAIL_SCHEME=smtps, or 587 without).',
            str_contains($m, '550') || str_contains($m, '553') || str_contains($m, 'sender') || str_contains($m, 'not permitted') || str_contains($m, 'relay')
                => 'The server refused the sender or recipient. Make MAIL_FROM_ADDRESS the same mailbox as MAIL_USERNAME, on a domain hosted in this cPanel account.',
            default => 'Check the error above against your .env mail settings, then run "php artisan config:clear" and try again.',
        };
    }
}
