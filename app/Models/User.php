<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use App\Mail\SecurityAlert;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'document_manager';
    public const ROLE_EMPLOYEE = 'employee';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_EMPLOYEE];

    /** Display label for a role (admin shows as "System Administrator"). */
    public static function roleLabel(?string $role): string
    {
        return match ($role) {
            self::ROLE_ADMIN => 'System Administrator',
            self::ROLE_MANAGER => 'Document Manager',
            self::ROLE_EMPLOYEE => 'Employee',
            default => str_replace('_', ' ', (string) $role),
        };
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'pending_email',
        'pending_phone',
        'email_change_code',
        'phone_change_code',
        'verification_expires_at',
        'password',
        'role',
        'is_active',
        'department_id',
        'avatar_path',
        'job_title',
        'organization',
        'locale',
        'theme',
        'notify_doc_done',
        'notify_doc_failed',
        'notify_doc_approval',
        'notify_login_alert',
        'two_factor_enabled',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_expires_at' => 'datetime',
            'password' => 'hashed',
            'notify_doc_done' => 'boolean',
            'notify_doc_failed' => 'boolean',
            'notify_doc_approval' => 'boolean',
            'notify_login_alert' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** Initials shown when no avatar photo is uploaded (e.g. "JD"). */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name));
        $letters = array_filter(array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), $parts));

        return mb_substr(implode('', $letters), 0, 2) ?: 'U';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)->withTimestamps();
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function authorizedDocuments(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_user')->withTimestamps();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->where('is_read', false)->latest();
    }

    public function passwordResetRequests(): HasMany
    {
        return $this->hasMany(PasswordResetRequest::class);
    }

    /**
     * Notify all active admins that a password was changed/reset for a user.
     * Creates an in-app notification and sends a security email to each admin.
     */
    public static function notifyAdminsOfPasswordChange(User $user, string $action): void
    {
        $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get();

        $title = $action === 'password_reset_requested'
            ? 'Password reset requested'
            : 'Password was reset';

        $message = $action === 'password_reset_requested'
            ? "{$user->name} ({$user->email}) requested a password reset."
            : "{$user->name} ({$user->email}) has reset their SmartDocs password.";

        foreach ($admins as $admin) {
            $admin->notifications()->create([
                'type' => $action,
                'title' => $title,
                'message' => $message,
            ]);

            try {
                Mail::to($admin->email)->send(new SecurityAlert($title, $message));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER], true);
    }

    public function canAccessDepartment(?int $departmentId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if ($departmentId === null) {
            return true;
        }
        if ((int) $this->department_id === $departmentId) {
            return true;
        }

        return $this->departments()->where('departments.id', $departmentId)->exists();
    }
}
