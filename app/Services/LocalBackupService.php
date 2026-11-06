<?php

namespace App\Services;

use App\Support\SystemHealthThresholds;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;
use ZipArchive;

class LocalBackupService
{
    private const ENCRYPTION_HEADER = 'P3SB1';

    /**
     * @return array{
     *     status: string,
     *     detail: string,
     *     last_backup_at: ?CarbonImmutable,
     *     age_hours: ?int,
     *     stale: ?bool,
     *     integrity_verified_at: ?CarbonImmutable,
     *     integrity: array{status: string, detail: string},
     *     offsite: array{configured: bool, status: string, detail: string}
     * }
     */
    public function health(): array
    {
        if (config('database.connections.'.config('database.default').'.driver') !== 'sqlite'
            || config('filesystems.disks.private.driver') !== 'local') {
            return $this->unavailableHealth('Automated backups are unsupported for the configured database or private storage driver.');
        }

        $directory = $this->backupDirectory();
        if (! is_dir($directory) || ! is_readable($directory)) {
            return $this->unavailableHealth('The private backup directory is missing or unreadable.');
        }

        $files = glob($directory.DIRECTORY_SEPARATOR.'smartdocs-*.zip.enc');
        if ($files === false) {
            return $this->unavailableHealth('Encrypted backup files could not be checked.');
        }

        $latestFile = null;
        $latestTimestamp = null;
        foreach ($files as $file) {
            if (is_link($file) || ! is_file($file)) {
                continue;
            }

            $modifiedAt = filemtime($file);
            if ($modifiedAt !== false && ($latestTimestamp === null || $modifiedAt > $latestTimestamp)) {
                $latestTimestamp = $modifiedAt;
                $latestFile = $file;
            }
        }

        if ($latestTimestamp === null || $latestFile === null) {
            return $this->unavailableHealth('No encrypted local backup was found.');
        }

        $ageSeconds = max(0, now()->getTimestamp() - $latestTimestamp);
        $ageHours = intdiv($ageSeconds, 3600);
        $lastBackupAt = CarbonImmutable::createFromTimestamp($latestTimestamp);
        $isStale = $ageSeconds > SystemHealthThresholds::BACKUP_STALE_HOURS * 3600;

        $status = $isStale ? 'warning' : 'ok';
        $detail = $isStale
            ? 'The latest encrypted backup is '.$ageHours.' hours old; backups older than '.SystemHealthThresholds::BACKUP_STALE_HOURS.' hours need attention.'
            : 'The latest encrypted backup is current.';

        $record = $this->verificationRecords()[basename($latestFile)] ?? null;
        if ($record === null) {
            $integrity = [
                'status' => 'warning',
                'detail' => 'The latest backup has not been integrity-verified; run php artisan smartdocs:verify-backup.',
            ];
            $integrityVerifiedAt = null;
        } elseif (($record['ok'] ?? false) !== true) {
            $integrity = [
                'status' => 'warning',
                'detail' => 'Backup integrity verification failed: '.($record['detail'] ?? 'unknown error.'),
            ];
            $integrityVerifiedAt = isset($record['verified_at']) ? CarbonImmutable::parse((string) $record['verified_at']) : null;
        } else {
            $integrityVerifiedAt = CarbonImmutable::parse((string) ($record['verified_at'] ?? 'now'));
            $integrity = [
                'status' => 'ok',
                'detail' => 'Archive integrity verified at '.$integrityVerifiedAt->timezone(config('app.timezone'))->format('M d, Y H:i:s').'.',
            ];
        }

        if ($integrity['status'] !== 'ok') {
            $status = 'warning';
        }

        $offsite = $this->offsiteHealth(basename($latestFile));
        if ($offsite['configured'] && $offsite['status'] !== 'ok') {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'detail' => $detail,
            'last_backup_at' => $lastBackupAt,
            'age_hours' => $ageHours,
            'stale' => $isStale,
            'integrity_verified_at' => $integrityVerifiedAt,
            'integrity' => $integrity,
            'offsite' => $offsite,
        ];
    }

    /**
     * @return array{
     *     status: string,
     *     detail: string,
     *     last_backup_at: ?CarbonImmutable,
     *     age_hours: ?int,
     *     stale: ?bool,
     *     integrity_verified_at: ?CarbonImmutable,
     *     integrity: array{status: string, detail: string},
     *     offsite: array{configured: bool, status: string, detail: string}
     * }
     */
    private function unavailableHealth(string $detail): array
    {
        return [
            'status' => 'warning',
            'detail' => $detail,
            'last_backup_at' => null,
            'age_hours' => null,
            'stale' => null,
            'integrity_verified_at' => null,
            'integrity' => ['status' => 'skipped', 'detail' => 'Not checked because no backup could be examined.'],
            'offsite' => [
                'configured' => (bool) config('backup.offsite_path'),
                'status' => 'skipped',
                'detail' => 'Not checked because no backup could be examined.',
            ],
        ];
    }

    /**
     * @return array{configured: bool, status: string, detail: string}
     */
    private function offsiteHealth(string $latestFilename): array
    {
        $offsite = (string) (config('backup.offsite_path') ?: '');
        if ($offsite === '') {
            return [
                'configured' => false,
                'status' => 'skipped',
                'detail' => 'No off-site backup destination is configured; local copies do not protect against server or storage loss.',
            ];
        }

        if (! is_dir($offsite) || ! is_readable($offsite)) {
            return ['configured' => true, 'status' => 'warning', 'detail' => 'The off-site backup destination is missing or unreadable.'];
        }

        $copy = rtrim($offsite, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$latestFilename;
        if (is_link($copy) || ! is_file($copy)) {
            return ['configured' => true, 'status' => 'warning', 'detail' => 'The off-site copy of the latest backup is missing.'];
        }

        $modifiedAt = filemtime($copy);
        $ageSeconds = max(0, now()->getTimestamp() - ($modifiedAt ?: now()->getTimestamp()));
        if ($ageSeconds > SystemHealthThresholds::BACKUP_STALE_HOURS * 3600) {
            return [
                'configured' => true,
                'status' => 'warning',
                'detail' => 'The off-site copy of the latest backup is '.intdiv($ageSeconds, 3600).' hours old; off-site copies must stay within '.SystemHealthThresholds::BACKUP_STALE_HOURS.' hours.',
            ];
        }

        return ['configured' => true, 'status' => 'ok', 'detail' => 'An off-site copy of the latest backup is present.'];
    }

    public function backupDirectory(): string
    {
        return (string) config('backup.path', storage_path('app/private/backups'));
    }

    public function create(): string
    {
        $this->assertSupportedConfiguration();

        $databasePath = (string) config('database.connections.'.config('database.default').'.database');
        $documentsPath = (string) config('filesystems.disks.private.root');
        $backupDirectory = $this->backupDirectory();
        $this->ensureDistinctPaths($databasePath, $documentsPath, $backupDirectory);

        if (! is_file($databasePath) || ! is_dir($documentsPath) || is_link($documentsPath)) {
            throw new RuntimeException('The SQLite database or private document directory is missing.');
        }

        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0700, true) && ! is_dir($backupDirectory)) {
            throw new RuntimeException('The private backup directory could not be created.');
        }
        chmod($backupDirectory, 0700);

        $temporaryDirectory = $backupDirectory.DIRECTORY_SEPARATOR.'.tmp-'.Str::uuid();
        if (! mkdir($temporaryDirectory, 0700)) {
            throw new RuntimeException('A temporary backup directory could not be created.');
        }

        try {
            $databaseSnapshot = $temporaryDirectory.DIRECTORY_SEPARATOR.'database.sqlite';
            $this->snapshotSqliteDatabase($databasePath, $databaseSnapshot);
            $archivePath = $temporaryDirectory.DIRECTORY_SEPARATOR.'backup.zip';
            $this->createArchive($archivePath, $databaseSnapshot, $documentsPath);
            $encryptedPath = $backupDirectory.DIRECTORY_SEPARATOR.'smartdocs-'.now()->format('Ymd-His').'-'.Str::uuid().'.zip.enc';
            $this->encryptArchive($archivePath, $encryptedPath);

            $verification = $this->verify($encryptedPath);
            if ($verification['ok'] !== true) {
                throw new RuntimeException('The new backup failed integrity verification: '.$verification['detail']);
            }

            $this->copyToOffsite($encryptedPath);
            $this->pruneExpiredBackups();

            return $encryptedPath;
        } finally {
            $this->removeDirectory($temporaryDirectory);
        }
    }

    public function restore(string $backupPath): void
    {
        $this->assertSupportedConfiguration();
        $backupDirectory = realpath($this->backupDirectory());
        $resolvedBackup = realpath($backupPath);

        if (! $backupDirectory || ! $resolvedBackup
            || dirname($resolvedBackup) !== $backupDirectory
            || ! str_ends_with($resolvedBackup, '.zip.enc')) {
            throw new RuntimeException('Choose an encrypted backup file from the private backup directory.');
        }

        $databasePath = (string) config('database.connections.'.config('database.default').'.database');
        $documentsPath = (string) config('filesystems.disks.private.root');
        $this->ensureDistinctPaths($databasePath, $documentsPath, $backupDirectory);
        if ($databasePath === ':memory:' || ! is_file($databasePath) || ! is_dir($documentsPath)) {
            throw new RuntimeException('Restore requires an existing file-backed SQLite database and private document directory.');
        }

        $stagingDirectory = dirname($databasePath).DIRECTORY_SEPARATOR.'.smartdocs-restore-'.Str::uuid();
        if (! mkdir($stagingDirectory, 0700)) {
            throw new RuntimeException('A private restore staging directory could not be created.');
        }

        try {
            $archivePath = $stagingDirectory.DIRECTORY_SEPARATOR.'backup.zip';
            $this->decryptArchive($resolvedBackup, $archivePath);
            $extractPath = $stagingDirectory.DIRECTORY_SEPARATOR.'contents';
            if (! mkdir($extractPath, 0700)) {
                throw new RuntimeException('The restore staging contents directory could not be created.');
            }
            $this->extractArchiveSafely($archivePath, $extractPath);

            $stagedDatabase = $extractPath.DIRECTORY_SEPARATOR.'database.sqlite';
            $stagedDocuments = $extractPath.DIRECTORY_SEPARATOR.'documents';
            if (! is_file($stagedDatabase) || ! is_dir($stagedDocuments)) {
                throw new RuntimeException('The backup archive is incomplete.');
            }
            $this->assertValidSqliteDatabase($stagedDatabase);

            $currentBackup = $this->create();
            $this->replaceDatabaseAndDocuments($stagedDatabase, $stagedDocuments, $databasePath, $documentsPath, $stagingDirectory);
            logger()->warning('SmartDocs database and documents restored from an encrypted backup.', [
                'backup' => basename($resolvedBackup),
                'pre_restore_backup' => basename($currentBackup),
            ]);
        } finally {
            DB::purge(config('database.default'));
            $this->removeDirectory($stagingDirectory);
        }
    }

    /**
     * Decrypt, extract, and integrity-check an encrypted backup, then record the result.
     *
     * @return array{ok: bool, detail: string, verified_at: CarbonImmutable}
     */
    public function verify(string $backupPath): array
    {
        $this->assertSupportedConfiguration();
        $backupDirectory = realpath($this->backupDirectory());
        $resolvedBackup = realpath($backupPath);

        if (! $backupDirectory || ! $resolvedBackup
            || dirname($resolvedBackup) !== $backupDirectory
            || ! str_ends_with($resolvedBackup, '.zip.enc')) {
            throw new RuntimeException('Choose an encrypted backup file from the private backup directory.');
        }

        $verificationDirectory = $this->backupDirectory().DIRECTORY_SEPARATOR.'.verify-'.Str::uuid();
        if (! mkdir($verificationDirectory, 0700)) {
            throw new RuntimeException('A backup verification staging directory could not be created.');
        }

        $ok = false;
        $detail = '';
        try {
            $archivePath = $verificationDirectory.DIRECTORY_SEPARATOR.'backup.zip';
            $this->decryptArchive($resolvedBackup, $archivePath);
            $extractPath = $verificationDirectory.DIRECTORY_SEPARATOR.'contents';
            if (! mkdir($extractPath, 0700)) {
                throw new RuntimeException('The verification contents directory could not be created.');
            }
            $this->extractArchiveSafely($archivePath, $extractPath);

            $stagedDatabase = $extractPath.DIRECTORY_SEPARATOR.'database.sqlite';
            $stagedDocuments = $extractPath.DIRECTORY_SEPARATOR.'documents';
            if (! is_file($stagedDatabase) || ! is_dir($stagedDocuments)) {
                throw new RuntimeException('The backup archive is incomplete.');
            }
            $this->assertValidSqliteDatabase($stagedDatabase);

            $ok = true;
            $detail = 'Archive decrypted and extracted, and the database snapshot passed its integrity check.';
        } catch (Throwable $exception) {
            $detail = $exception->getMessage();
        } finally {
            $this->removeDirectory($verificationDirectory);
        }

        $result = ['ok' => $ok, 'detail' => $detail, 'verified_at' => now()];
        $this->recordVerification(basename($resolvedBackup), $result);

        return $result;
    }

    /**
     * @return array<string, array{ok: bool, verified_at?: string, detail?: string}>
     */
    public function verificationRecords(): array
    {
        $path = $this->verificationPath();
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array{ok: bool, detail: string, verified_at: CarbonImmutable} $result
     */
    private function recordVerification(string $filename, array $result): void
    {
        $records = $this->verificationRecords();
        $records[$filename] = [
            'ok' => $result['ok'],
            'verified_at' => $result['verified_at']->toIso8601String(),
            'detail' => $result['detail'],
        ];

        $path = $this->verificationPath();
        $temporary = $path.'.tmp';
        if (file_put_contents($temporary, json_encode($records, JSON_PRETTY_PRINT)) === false
            || ! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('The backup verification record could not be written.');
        }
    }

    private function verificationPath(): string
    {
        return rtrim($this->backupDirectory(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'verification.json';
    }

    private function copyToOffsite(string $encryptedPath): void
    {
        $offsite = (string) (config('backup.offsite_path') ?: '');
        if ($offsite === '') {
            return;
        }

        $resolve = static fn (string $path): string => rtrim(str_replace('\\', '/', realpath($path) ?: $path), '/');
        $offsiteNormalized = $resolve($offsite);
        $localNormalized = $resolve((string) config('backup.path', storage_path('app/private/backups')));
        $documentsNormalized = $resolve((string) config('filesystems.disks.private.root'));
        $publicNormalized = $resolve(public_path());
        if (strcasecmp($offsiteNormalized, $localNormalized) === 0
            || strcasecmp($offsiteNormalized, $documentsNormalized) === 0
            || strcasecmp($offsiteNormalized, $publicNormalized) === 0) {
            throw new RuntimeException('The off-site backup destination must differ from the local backup, document, and public directories.');
        }

        $offsite = $offsiteNormalized;

        if (! is_dir($offsite) && ! mkdir($offsite, 0700, true) && ! is_dir($offsite)) {
            throw new RuntimeException('The off-site backup destination could not be created.');
        }

        $destination = $offsite.DIRECTORY_SEPARATOR.basename($encryptedPath);
        $temporary = $destination.'.tmp';
        if (! copy($encryptedPath, $temporary)) {
            @unlink($temporary);
            throw new RuntimeException('The off-site backup copy could not be written.');
        }
        chmod($temporary, 0600);
        if (! rename($temporary, $destination)) {
            @unlink($temporary);
            throw new RuntimeException('The off-site backup copy could not be installed.');
        }

        $cutoff = now()->subDays((int) config('backup.retention_days', 365))->timestamp;
        foreach (glob($offsite.DIRECTORY_SEPARATOR.'smartdocs-*.zip.enc') ?: [] as $copy) {
            if (is_file($copy) && filemtime($copy) < $cutoff) {
                @unlink($copy);
            }
        }
    }

    private function assertSupportedConfiguration(): void
    {
        if (config('database.connections.'.config('database.default').'.driver') !== 'sqlite') {
            throw new RuntimeException('Automated local backups currently support SQLite databases only.');
        }
        if (config('filesystems.disks.private.driver') !== 'local') {
            throw new RuntimeException('Automated local backups require the private document disk to use the local driver.');
        }
        if (! extension_loaded('sqlite3') || ! extension_loaded('zip') || ! extension_loaded('sodium')) {
            throw new RuntimeException('The sqlite3, zip, and sodium PHP extensions are required for encrypted backups.');
        }
        $key = $this->applicationKey();
        if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_KEY is invalid for encrypted backups.');
        }
    }

    private function applicationKey(): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new RuntimeException('APP_KEY must be configured before encrypted backups can be used.');
        }
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false) {
                throw new RuntimeException('APP_KEY is invalid for encrypted backups.');
            }

            return $decoded;
        }

        return hash('sha256', $key, true);
    }

    private function snapshotSqliteDatabase(string $source, string $destination): void
    {
        $database = new \SQLite3($source, SQLITE3_OPEN_READONLY);
        $snapshot = new \SQLite3($destination, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        try {
            if (! $database->backup($snapshot)) {
                throw new RuntimeException('The SQLite database snapshot failed.');
            }
            $this->assertValidSqliteDatabase($destination);
        } finally {
            $snapshot->close();
            $database->close();
        }
    }

    private function assertValidSqliteDatabase(string $path): void
    {
        $database = new \SQLite3($path, SQLITE3_OPEN_READONLY);
        try {
            if ($database->querySingle('PRAGMA integrity_check') !== 'ok') {
                throw new RuntimeException('The SQLite database integrity check failed.');
            }
        } finally {
            $database->close();
        }
    }

    private function createArchive(string $archivePath, string $databasePath, string $documentsPath): void
    {
        $archive = new ZipArchive;
        if ($archive->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The backup archive could not be created.');
        }

        try {
            if (! $archive->addFile($databasePath, 'database.sqlite')) {
                throw new RuntimeException('The database snapshot could not be added to the backup archive.');
            }
            $this->addDirectoryToArchive($archive, $documentsPath, 'documents');
        } finally {
            $archive->close();
        }
    }

    private function addDirectoryToArchive(ZipArchive $archive, string $source, string $prefix): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $entry) {
            if (! $entry instanceof SplFileInfo) {
                continue;
            }
            if ($entry->isLink()) {
                throw new RuntimeException('A symbolic link was found in private storage; backup stopped to avoid an incomplete archive.');
            }
            $relative = substr($entry->getPathname(), strlen(rtrim($source, DIRECTORY_SEPARATOR)) + 1);
            $archivePath = $prefix.'/'.str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            if ($entry->isDir()) {
                if (! $archive->addEmptyDir($archivePath)) {
                    throw new RuntimeException('A private document directory could not be added to the backup archive.');
                }
            } elseif (! $archive->addFile($entry->getPathname(), $archivePath)) {
                throw new RuntimeException('A private document file could not be added to the backup archive.');
            }
        }
    }

    private function encryptArchive(string $archivePath, string $destination): void
    {
        $input = fopen($archivePath, 'rb');
        $output = fopen($destination.'.tmp', 'wb');
        if ($input === false || $output === false) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            @unlink($destination.'.tmp');
            throw new RuntimeException('The backup archive could not be read for encryption.');
        }

        [$state, $streamHeader] = sodium_crypto_secretstream_xchacha20poly1305_init_push($this->applicationKey());
        $temporaryPath = $destination.'.tmp';
        $complete = false;
        try {
            $this->writeAll($output, self::ENCRYPTION_HEADER.$streamHeader);
            while (! feof($input)) {
                $chunk = fread($input, 1024 * 1024);
                if ($chunk === false) {
                    throw new RuntimeException('The backup archive could not be read for encryption.');
                }
                $tag = feof($input) ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
                $encrypted = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '', $tag);
                $this->writeAll($output, pack('N', strlen($encrypted)).$encrypted);
                if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                    $complete = true;
                }
            }
            if (! $complete || ! fflush($output)) {
                throw new RuntimeException('The encrypted backup could not be finalized.');
            }
        } finally {
            fclose($input);
            fclose($output);
        }

        if (! rename($temporaryPath, $destination)) {
            @unlink($temporaryPath);
            throw new RuntimeException('The encrypted backup could not be installed.');
        }
        chmod($destination, 0600);
    }

    private function decryptArchive(string $source, string $destination): void
    {
        $input = fopen($source, 'rb');
        $headerLength = strlen(self::ENCRYPTION_HEADER) + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES;
        $header = $input !== false ? $this->readExact($input, $headerLength) : false;
        if ($input === false || $header === false || ! str_starts_with($header, self::ENCRYPTION_HEADER)) {
            if (is_resource($input)) {
                fclose($input);
            }
            throw new RuntimeException('The selected file is not a valid SmartDocs encrypted backup.');
        }

        $output = fopen($destination, 'wb');
        if ($output === false) {
            fclose($input);
            throw new RuntimeException('The decrypted archive could not be staged for restore.');
        }
        $streamHeader = substr($header, strlen(self::ENCRYPTION_HEADER));
        $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($streamHeader, $this->applicationKey());
        $complete = false;
        try {
            while (! feof($input)) {
                $lengthBytes = $this->readExact($input, 4);
                if ($lengthBytes === false) {
                    break;
                }
                $length = unpack('Nlength', $lengthBytes)['length'];
                if ($length < SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES
                    || $length > 1024 * 1024 + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES) {
                    throw new RuntimeException('The encrypted backup contains an invalid data frame.');
                }
                $frame = $this->readExact($input, $length);
                if ($frame === false) {
                    throw new RuntimeException('The encrypted backup is incomplete.');
                }
                $decrypted = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $frame);
                if ($decrypted === false) {
                    throw new RuntimeException('Backup decryption failed; verify that this deployment uses the original APP_KEY.');
                }
                [$plaintext, $tag] = $decrypted;
                $this->writeAll($output, $plaintext);
                if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                    if (fread($input, 1) !== '') {
                        throw new RuntimeException('The encrypted backup contains data after its final frame.');
                    }
                    $complete = true;
                }
            }
            if (! $complete || ! fflush($output)) {
                throw new RuntimeException('The encrypted backup is incomplete or could not be staged.');
            }
        } finally {
            fclose($input);
            fclose($output);
        }
    }

    private function extractArchiveSafely(string $archivePath, string $destination): void
    {
        $archive = new ZipArchive;
        if ($archive->open($archivePath) !== true) {
            throw new RuntimeException('The decrypted backup archive is invalid.');
        }

        try {
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $name = $archive->getNameIndex($index);
                $normalized = str_replace('\\', '/', (string) $name);
                if ($normalized === '' || str_contains($normalized, '\\') || str_contains($normalized, ':')
                    || str_starts_with($normalized, '/') || preg_match('/(^|\/)\.\.(\/|$)/', $normalized)
                    || ($normalized !== 'database.sqlite' && ! str_starts_with($normalized, 'documents/'))) {
                    throw new RuntimeException('The backup archive contains an unsafe file path.');
                }
                if ($archive->getExternalAttributesIndex($index, $operatingSystem, $attributes)
                    && $operatingSystem === ZipArchive::OPSYS_UNIX
                    && (($attributes >> 16) & 0170000) === 0120000) {
                    throw new RuntimeException('The backup archive contains an unsafe symbolic link.');
                }
            }
            if (! $archive->extractTo($destination)) {
                throw new RuntimeException('The backup archive could not be extracted.');
            }
        } finally {
            $archive->close();
        }
    }

    private function replaceDatabaseAndDocuments(
        string $stagedDatabase,
        string $stagedDocuments,
        string $databasePath,
        string $documentsPath,
        string $stagingDirectory
    ): void {
        $previousDatabase = $stagingDirectory.DIRECTORY_SEPARATOR.'previous.sqlite';
        $previousDocuments = $stagingDirectory.DIRECTORY_SEPARATOR.'previous-documents';
        $incomingDatabase = dirname($databasePath).DIRECTORY_SEPARATOR.'.smartdocs-database-'.Str::uuid().'.sqlite';
        if (! copy($stagedDatabase, $incomingDatabase)) {
            throw new RuntimeException('The restored database could not be staged beside the live database.');
        }

        DB::purge(config('database.default'));
        $databaseWal = $databasePath.'-wal';
        $databaseShm = $databasePath.'-shm';
        $movedDatabase = false;
        $movedDocuments = false;
        $installedDatabase = false;
        $installedDocuments = false;

        try {
            if (! rename($databasePath, $previousDatabase)) {
                throw new RuntimeException('The current database could not be moved aside for restore.');
            }
            $movedDatabase = true;
            if (is_file($databaseWal)) {
                if (! unlink($databaseWal)) {
                    throw new RuntimeException('The SQLite write-ahead log could not be safely cleared for restore.');
                }
            }
            if (is_file($databaseShm)) {
                if (! unlink($databaseShm)) {
                    throw new RuntimeException('The SQLite shared-memory file could not be safely cleared for restore.');
                }
            }
            if (! rename($incomingDatabase, $databasePath)) {
                throw new RuntimeException('The restored database could not be installed.');
            }
            $installedDatabase = true;
            if (! rename($documentsPath, $previousDocuments)) {
                throw new RuntimeException('The current document directory could not be moved aside for restore.');
            }
            $movedDocuments = true;
            if (! rename($stagedDocuments, $documentsPath)) {
                throw new RuntimeException('The restored document directory could not be installed.');
            }
            $installedDocuments = true;
        } catch (\Throwable $exception) {
            $rollbackFailures = [];
            if ($installedDocuments && is_dir($documentsPath)) {
                try {
                    $this->removeDirectory($documentsPath);
                } catch (\Throwable $rollbackException) {
                    $rollbackFailures[] = $rollbackException->getMessage();
                }
            }
            if ($movedDocuments && is_dir($previousDocuments)) {
                if (! rename($previousDocuments, $documentsPath)) {
                    $rollbackFailures[] = 'The previous private document directory could not be restored.';
                }
            }
            if ($installedDatabase && is_file($databasePath)) {
                if (! unlink($databasePath)) {
                    $rollbackFailures[] = 'The partially restored database could not be removed.';
                }
            }
            if ($movedDatabase && is_file($previousDatabase)) {
                if (! rename($previousDatabase, $databasePath)) {
                    $rollbackFailures[] = 'The previous database could not be restored.';
                }
            }
            if ($rollbackFailures !== []) {
                logger()->critical('SmartDocs restore failed and rollback was incomplete.', [
                    'rollback_failures' => $rollbackFailures,
                ]);
                throw new RuntimeException(
                    'Restore failed and rollback was incomplete: '.implode(' ', $rollbackFailures),
                    previous: $exception
                );
            }
            throw $exception;
        } finally {
            if (is_file($incomingDatabase)) {
                unlink($incomingDatabase);
            }
        }
    }

    private function ensureDistinctPaths(string $databasePath, string $documentsPath, string $backupPath): void
    {
        $database = realpath($databasePath) ?: $databasePath;
        $documents = realpath($documentsPath) ?: $documentsPath;
        $backup = realpath($backupPath) ?: $backupPath;
        $privateRoot = realpath(storage_path('app/private')) ?: storage_path('app/private');
        $normalize = static fn (string $path): string => rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $backupNormalized = $normalize($backup);
        $privateNormalized = $normalize($privateRoot);
        $databaseNormalized = $normalize($database);
        $documentsNormalized = $normalize($documents);
        $isWithin = static fn (string $path, string $parent): bool => str_starts_with(
            DIRECTORY_SEPARATOR === '\\' ? strtolower($path) : $path,
            (DIRECTORY_SEPARATOR === '\\' ? strtolower($parent) : $parent).DIRECTORY_SEPARATOR
        );
        if (! $isWithin($backupNormalized, $privateNormalized)
            || $isWithin($backupNormalized, $documentsNormalized)
            || $backupNormalized === $databaseNormalized
            || $isWithin($databaseNormalized, $backupNormalized)
            || $backupNormalized === $documentsNormalized
            || str_starts_with($backupNormalized, rtrim(public_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('The backup destination must be separate from the database and document directory.');
        }
    }

    private function pruneExpiredBackups(): void
    {
        $cutoff = now()->subDays((int) config('backup.retention_days', 365))->timestamp;
        foreach (glob($this->backupDirectory().DIRECTORY_SEPARATOR.'smartdocs-*.zip.enc') ?: [] as $backup) {
            if (is_file($backup) && filemtime($backup) < $cutoff && ! unlink($backup)) {
                throw new RuntimeException('An expired backup could not be removed.');
            }
        }

        $records = $this->verificationRecords();
        $changed = false;
        foreach (array_keys($records) as $filename) {
            if (! is_file($this->backupDirectory().DIRECTORY_SEPARATOR.$filename)) {
                unset($records[$filename]);
                $changed = true;
            }
        }
        if ($changed) {
            $path = $this->verificationPath();
            $temporary = $path.'.tmp';
            if (file_put_contents($temporary, json_encode($records, JSON_PRETTY_PRINT)) !== false) {
                rename($temporary, $path);
            } else {
                @unlink($temporary);
            }
        }
    }

    private function readExact($stream, int $length): string|false
    {
        $result = '';
        while (strlen($result) < $length && ! feof($stream)) {
            $chunk = fread($stream, $length - strlen($result));
            if ($chunk === false || $chunk === '') {
                return false;
            }
            $result .= $chunk;
        }

        return strlen($result) === $length ? $result : false;
    }

    private function writeAll($stream, string $contents): void
    {
        $offset = 0;
        while ($offset < strlen($contents)) {
            $written = fwrite($stream, substr($contents, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Backup data could not be written completely.');
            }
            $offset += $written;
        }
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir() && ! $entry->isLink()) {
                if (! rmdir($entry->getPathname())) {
                    throw new RuntimeException('A temporary backup directory could not be removed.');
                }
            } else {
                if (! unlink($entry->getPathname())) {
                    throw new RuntimeException('A temporary backup file could not be removed.');
                }
            }
        }
        if (! rmdir($path)) {
            throw new RuntimeException('A temporary backup directory could not be removed.');
        }
    }
}
