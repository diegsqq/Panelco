<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppKeyFingerprint extends Command
{
    protected $signature = 'smartdocs:app-key-fingerprint {--check : Prompt for an escrowed APP_KEY and compare fingerprints without displaying either key}';

    protected $description = 'Print the fingerprint of the configured APP_KEY used to encrypt backups, or compare it with an escrowed copy';

    public function handle(): int
    {
        $current = $this->fingerprint((string) config('app.key'));
        if ($current === null) {
            $this->error('APP_KEY is not configured or is invalid.');

            return self::FAILURE;
        }

        if (! $this->option('check')) {
            $this->info('APP_KEY fingerprint (SHA-256): '.$current);
            $this->line('Store this fingerprint with the escrowed key record. It identifies the key without revealing it.');

            return self::SUCCESS;
        }

        $candidate = $this->secret('Escrowed APP_KEY (input is hidden)');
        if (! $candidate) {
            $this->error('No key entered.');

            return self::FAILURE;
        }

        $other = $this->fingerprint(trim((string) $candidate));
        if ($other === null) {
            $this->error('The entered key is not a valid APP_KEY.');

            return self::FAILURE;
        }

        if (! hash_equals($current, $other)) {
            $this->error('MISMATCH: the escrowed key does not match the configured APP_KEY. Backups cannot be decrypted with it.');

            return self::FAILURE;
        }

        $this->info('MATCH: the escrowed key matches the configured APP_KEY.');
        $this->line('Fingerprint: '.$current);

        return self::SUCCESS;
    }

    private function fingerprint(string $key): ?string
    {
        if ($key === '') {
            return null;
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false) {
                return null;
            }

            return hash('sha256', $decoded);
        }

        return hash('sha256', $key);
    }
}
