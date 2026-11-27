<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'parent_id', 'keywords', 'access_level', 'created_by',
    ];

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('access_level', 'all_employees')
                ->orWhere('created_by', $user->id)
                ->orWhereHas('authorizedUsers', fn (Builder $u) => $u->where('users.id', $user->id));
        });
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->isAdmin() || (int) $this->created_by === (int) $user->id) {
            return true;
        }

        if ($this->access_level === 'all_employees') {
            return true;
        }

        return $this->authorizedUsers()->where('users.id', $user->id)->exists();
    }

    public function isReachableBy(User $user): bool
    {
        return $this->isVisibleTo($user) || $this->access_level === 'link';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DocumentCategory::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function authorizedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'category_user', 'category_id', 'user_id')->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }
}
