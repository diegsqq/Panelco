<?php

namespace App\Console\Commands;

use App\Mail\TestMessage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestMailDelivery extends Command
{
    protected $signature = 'smartdocs:test-mail {email? : Recipient address; defaults to the configured from address, then the newest active administrator}';

    protected $description = 'Send a test message through the configured mailer to confirm email delivery works';

    public function handle(): int
    {
        $driver = (string) config('mail.default');
        if (in_array($driver, ['log', 'array'], true)) {
            $this->error("The '{$driver}' mailer does not deliver email. Configure a delivery mailer (for example SMTP) in .env before enabling operational alerts.");

            return self::FAILURE;
        }

        $recipient = (string) $this->argument('email');
        if ($recipient === '') {
            $recipient = (string) config('mail.from.address');
        }
        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $recipient = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->whereNotNull('email')
                ->orderByDesc('id')
                ->value('email');
        }
        if (! $recipient || ! filter_var((string) $recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('No valid recipient address. Provide one as an argument or set MAIL_FROM_ADDRESS.');

            return self::FAILURE;
        }

        try {
            Mail::to($recipient)->send(new TestMessage('smartdocs:test-mail'));
        } catch (Throwable $exception) {
            report($exception);
            $this->error("Email delivery failed for mailer '{$driver}': ".$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Test email sent to {$recipient} using the '{$driver}' mailer.");
        $this->line('Confirm the message was received; delivery failures that the mailer accepts silently still require checking the recipient inbox and mail logs.');

        return self::SUCCESS;
    }
}
