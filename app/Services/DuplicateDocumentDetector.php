<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DuplicateDocumentDetector
{
    public function hash(UploadedFile $file): string
    {
        $hash = hash_file('sha256', (string) $file->getRealPath());
        if ($hash === false) {
            throw new RuntimeException('The uploaded file could not be fingerprinted for duplicate checking.');
        }

        return $hash;
    }

    public function exists(string $hash, int $fileSize): bool
    {
        if (Document::query()->where('file_hash', $hash)->exists()) {
            return true;
        }

        $disk = Storage::disk('private');
        $candidates = Document::query()
            ->whereNull('file_hash')
            ->where('file_size', $fileSize)
            ->get(['id', 'storage_path']);

        foreach ($candidates as $candidate) {
            if (! $disk->exists($candidate->storage_path)) {
                continue;
            }

            $candidateHash = hash_file('sha256', $disk->path($candidate->storage_path));
            if ($candidateHash === false) {
                continue;
            }

            $candidate->update(['file_hash' => $candidateHash]);
            if (hash_equals($candidateHash, $hash)) {
                return true;
            }
        }

        return false;
    }
}
