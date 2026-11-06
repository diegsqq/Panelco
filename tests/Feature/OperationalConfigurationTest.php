<?php

namespace Tests\Feature;

use App\Mail\TestMessage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OperationalConfigurationTest extends TestCase
{
    public function test_mail_test_command_rejects_non_delivery_mailers(): void
    {
        Mail::fake();
        config(['mail.default' => 'array']);

        $this->assertSame(1, Artisan::call('smartdocs:test-mail', ['email' => 'admin@example.com']));
        $this->assertStringContainsString('does not deliver email', Artisan::output());
        Mail::assertNothingSent();
    }

    public function test_mail_test_command_sends_through_a_delivery_mailer(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp']);

        $this->assertSame(0, Artisan::call('smartdocs:test-mail', ['email' => 'admin@example.com']));
        $this->assertStringContainsString('Test email sent to admin@example.com', Artisan::output());
        Mail::assertSent(TestMessage::class, fn (TestMessage $mail) => $mail->hasTo('admin@example.com'));
    }

    public function test_app_key_fingerprint_is_printed_without_revealing_the_key(): void
    {
        $this->assertSame(0, Artisan::call('smartdocs:app-key-fingerprint'));
        $output = Artisan::output();

        $this->assertMatchesRegularExpression('/APP_KEY fingerprint \(SHA-256\): [0-9a-f]{64}/', $output);
        $this->assertStringNotContainsString((string) config('app.key'), $output);
    }

    public function test_app_key_fingerprint_command_fails_without_a_key(): void
    {
        config(['app.key' => '']);

        $this->assertSame(1, Artisan::call('smartdocs:app-key-fingerprint'));
        $this->assertStringContainsString('APP_KEY is not configured', Artisan::output());
    }
}
