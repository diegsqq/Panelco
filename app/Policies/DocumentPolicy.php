<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    private function canAccess(User $user, Document $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $document->uploaded_by === (int) $user->id) {
            return true;
        }

        if ($document->authorizedUsers()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($document->department_id !== null && $user->canAccessDepartment((int) $document->department_id)) {
            return true;
        }

        if ($document->department_id === null) {
            return true;
        }

        return false;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->canAccess($user, $document);
    }

    public function download(User $user, Document $document): bool
    {
        return $this->canAccess($user, $document);
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->role === User::ROLE_MANAGER && $this->canAccess($user, $document)) {
            return true;
        }

        return (int) $document->uploaded_by === (int) $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->role === User::ROLE_MANAGER && $this->canAccess($user, $document)) {
            return true;
        }

        return false;
    }

    public function approve(User $user, Document $document): bool
    {
        return $user->isAdmin() || ($user->role === User::ROLE_MANAGER && $this->canAccess($user, $document));
    }
}
