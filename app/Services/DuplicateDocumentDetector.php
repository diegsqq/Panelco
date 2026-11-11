<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
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

    /**
     * True when an identical file already exists in a document this user can
     * see. Copies hidden from the user never block their upload.
     */
    public function exists(string $hash, int $fileSize, ?User $user = null): bool
    {
        $matches = Document::query()
            ->where('file_hash', $hash)
            ->orWhereHas('versions', fn ($q) => $q->where('file_hash', $hash))
            ->get();

        foreach ($matches as $match) {
            if ($this->visibleTo($match, $user)) {
                return true;
            }
        }

        $disk = Storage::disk('private');
        $candidates = Document::query()
            ->whereNull('file_hash')
            ->where('file_size', $fileSize)
            ->get();

        foreach ($candidates as $candidate) {
            if (! $disk->exists($candidate->storage_path)) {
                continue;
            }

            $candidateHash = hash_file('sha256', $disk->path($candidate->storage_path));
            if ($candidateHash === false) {
                continue;
            }

            $candidate->update(['file_hash' => $candidateHash]);

            if (hash_equals($candidateHash, $hash) && $this->visibleTo($candidate, $user)) {
                return true;
            }
        }

        return false;
    }

    private function visibleTo(Document $document, ?User $user): bool
    {
        if ($user === null) {
            return true;
        }

        return $user->can('view', $document);
    }
}
