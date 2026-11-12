<?php

namespace Tests\Feature;

use App\Mail\SecurityAlert;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Services\Ai\AiProvider;
use App\Services\Ai\NullAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase9AdminTest extends TestCase
{
    use RefreshDatabase;

    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('framework/testing-backups/system-health-'.Str::uuid());
        config(['backup.path' => $this->backupPath]);
        File::ensureDirectoryExists($this->backupPath);
        File::put($this->backupPath.DIRECTORY_SEPARATOR.'smartdocs-test.zip.enc', 'test');
        File::put($this->backupPath.DIRECTORY_SEPARATOR.'verification.json', json_encode([
            'smartdocs-test.zip.enc' => [
                'ok' => true,
                'verified_at' => now()->toIso8601String(),
                'detail' => 'test fixture backup',
            ],
        ]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupPath);

        parent::tearDown();
    }

    public function test_admin_can_crud_category_employee_blocked(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('admin.categories.index'))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Energy Audit Reports', 'keywords' => 'audit,energy,consumption',
        ])->assertRedirect(route('admin.categories.index'));
        $cat = DocumentCategory::where('name', 'Energy Audit Reports')->first();
        $this->assertNotNull($cat);

        $this->actingAs($admin)->put(route('admin.categories.update', $cat), [
            'name' => 'Energy Audit Reports', 'keywords' => 'audit,energy,consumption,meter',
        ])->assertRedirect();
        $this->assertStringContainsString('meter', $cat->fresh()->keywords);
        $this->assertTrue(AuditLog::where('action', 'category.created')->exists());
        $this->assertTrue(AuditLog::where('action', 'category.updated')->exists());
    }

    public function test_admin_can_crud_department(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();

        foreach ([
            'Organization General Management (OGM)',
            'Finance Service Management (FSD)',
            'CSD',
            'ISD',
            'ASD',
            'TSD',
        ] as $officialDepartment) {
            $this->assertDatabaseHas('departments', ['name' => $officialDepartment]);
        }

        $this->actingAs($employee)->post(route('admin.departments.store'), [
            'name' => 'Unauthorized Department',
        ])->assertForbidden();
        $this->assertDatabaseMissing('departments', ['name' => 'Unauthorized Department']);

        $this->actingAs($admin)->post(route('admin.departments.store'), [
            'name' => 'Line Maintenance',
            'description' => 'Poles and wires',
            'keywords' => 'line patrol, pole replacement',
        ])->assertRedirect(route('admin.departments.index'));
        $dept = Department::where('name', 'Line Maintenance')->first();
        $this->assertNotNull($dept);
        $this->assertSame('line patrol, pole replacement', $dept->keywords);

        $this->actingAs($admin)->delete(route('admin.departments.destroy', $dept))->assertRedirect();
        $this->assertNull(Department::where('name', 'Line Maintenance')->first());
        $this->assertTrue(AuditLog::where('action', 'department.deleted')->exists());
    }

    public function test_department_in_use_cannot_be_deleted(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $department = Department::where('name', 'IT Services')->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.departments.destroy', $department))
            ->assertSessionHasErrors('department');
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_audit_log_viewer_admin_only(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_admin_can_filter_audit_logs_by_security_category_and_action(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['email' => 'security@example.test']);
        AuditLog::create([
            'action' => 'auth.login_failed',
            'entity_type' => User::class,
            'entity_id' => $employee->id,
            'metadata' => ['identifier' => $employee->email],
        ]);
        AuditLog::create([
            'action' => 'auth.logged_out',
            'user_id' => $employee->id,
            'entity_type' => User::class,
            'entity_id' => $employee->id,
        ]);
        AuditLog::create([
            'action' => 'document.downloaded',
            'entity_id' => 999,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['category' => 'auth', 'action' => 'failed']))
            ->assertOk()
            ->assertSee('Filter by event category')
            ->assertSee('Auth.login Failed')
            ->assertDontSee('logged out')
            ->assertDontSee('downloaded');
    }

    public function test_admin_can_search_access_review_history_by_note_actor_and_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Review Administrator']);
        $employee = User::factory()->create(['name' => 'Target Employee']);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user.access_reviewed',
            'entity_type' => User::class,
            'entity_id' => $employee->id,
            'metadata' => ['note' => 'Confirmed transfer to the field office.'],
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user.updated',
            'entity_type' => User::class,
            'entity_id' => $employee->id,
            'metadata' => ['note' => 'Unrelated account update'],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['category' => 'user', 'action' => 'access_reviewed', 'q' => 'field office']))
            ->assertOk()
            ->assertSee('Search audit users, actions, and review notes')
            ->assertSee('Confirmed transfer to the field office.')
            ->assertDontSee('Unrelated account update');

        $this->get(route('admin.audit-logs.index', ['q' => 'Target Employee']))
            ->assertOk()
            ->assertSee('Confirmed transfer to the field office.')
            ->assertSee('Unrelated account update');

        $this->get(route('admin.audit-logs.index', ['category' => 'invalid']))
            ->assertSessionHasErrors('category');
    }

    public function test_admin_user_edit_shows_only_that_accounts_sign_in_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['email' => 'history@example.test']);
        $otherUser = User::factory()->create(['email' => 'other@example.test']);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.login_succeeded',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'metadata' => ['ip' => '192.0.2.20', 'user_agent' => 'Example Browser'],
        ]);
        AuditLog::create([
            'action' => 'auth.login_locked_out',
            'metadata' => ['identifier' => $user->email, 'ip' => '192.0.2.21'],
        ]);
        AuditLog::create([
            'user_id' => $otherUser->id,
            'action' => 'auth.login_succeeded',
            'entity_type' => User::class,
            'entity_id' => $otherUser->id,
            'metadata' => ['ip' => '198.51.100.20', 'user_agent' => 'Other Browser'],
        ]);

        $this->actingAs($admin)->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Recent sign-in activity')
            ->assertSee('Signed in successfully')
            ->assertSee('Sign-in temporarily locked')
            ->assertSee('192.0.2.20')
            ->assertSee('192.0.2.21')
            ->assertDontSee('Other Browser')
            ->assertDontSee('198.51.100.20');
    }

    public function test_admin_user_edit_summarizes_account_access_for_review(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $primary = Department::create(['name' => 'Review Primary Department']);
        $additional = Department::create(['name' => 'Review Additional Department']);
        $user = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'department_id' => $primary->id,
            'two_factor_enabled' => true,
        ]);
        $user->departments()->sync([$additional->id]);
        $lastSuccessfulSignIn = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.login_succeeded',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'created_at' => now()->subDays(5),
        ]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.login_failed',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Access review')
            ->assertSee('Active')
            ->assertSee('Document Manager')
            ->assertSee('Review Primary Department')
            ->assertSee('Review Additional Department')
            ->assertSee('Enabled')
            ->assertSee($lastSuccessfulSignIn->created_at->format('M d, Y H:i'));
    }

    public function test_admin_can_record_audited_access_review_notes_without_changing_user_access(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.users.access-reviews.store', $user), [
                'review_note' => 'Confirmed department access with the department head.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user.access_reviewed',
            'entity_type' => User::class,
            'entity_id' => $user->id,
        ]);
        $review = AuditLog::where('action', 'user.access_reviewed')->firstOrFail();
        $this->assertSame('Confirmed department access with the department head.', $review->metadata['note']);
        $this->assertTrue($user->fresh()->is_active);

        $this->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Access review notes')
            ->assertSee('Confirmed department access with the department head.');
    }

    public function test_access_review_notes_require_text_and_are_admin_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($admin)
            ->post(route('admin.users.access-reviews.store', $user), ['review_note' => ''])
            ->assertSessionHasErrors('review_note');

        $this->actingAs($employee)
            ->post(route('admin.users.access-reviews.store', $user), [
                'review_note' => 'Unauthorized review.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'user.access_reviewed',
            'entity_id' => $user->id,
        ]);
    }

    public function test_admin_user_list_shows_two_factor_status_without_exposing_secret(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create([
            'name' => 'Protected User',
            'two_factor_enabled' => true,
            'two_factor_secret' => 'must-never-appear-in-user-list',
        ]);
        User::factory()->create([
            'name' => 'Unprotected User',
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('2FA')
            ->assertSee('Protected User')
            ->assertSee('Unprotected User')
            ->assertSee('Not enabled')
            ->assertDontSee('must-never-appear-in-user-list');
    }

    public function test_admin_can_filter_user_list_by_two_factor_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Admin User']);
        User::factory()->create([
            'name' => 'Protected User',
            'two_factor_enabled' => true,
        ]);
        User::factory()->create([
            'name' => 'Unprotected User',
            'two_factor_enabled' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['two_factor' => 'enabled']))
            ->assertOk()
            ->assertSee('Protected User')
            ->assertDontSee('Unprotected User')
            ->assertSee('value="enabled" selected', false);

        $this->get(route('admin.users.index', ['two_factor' => 'disabled']))
            ->assertOk()
            ->assertSee('Unprotected User')
            ->assertDontSee('Protected User')
            ->assertSee('value="disabled" selected', false);

        $this->get(route('admin.users.index', ['two_factor' => 'invalid']))
            ->assertSessionHasErrors('two_factor');
    }

    public function test_admin_system_health_reports_queue_document_and_service_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'Pending health report',
            'original_filename' => 'pending-health.txt',
            'storage_path' => 'documents/pending-health.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'pending',
        ]);
        Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'Failed health report',
            'original_filename' => 'failed-health.txt',
            'storage_path' => 'documents/failed-health.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'failed',
        ]);
        $stuckDocument = Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'Stuck health report',
            'original_filename' => 'stuck-health.txt',
            'storage_path' => 'documents/stuck-health.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'processing',
        ]);
        $stuckDocument->forceFill(['updated_at' => now()->subMinutes(31)])->saveQuietly();
        Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'Active health report',
            'original_filename' => 'active-health.txt',
            'storage_path' => 'documents/active-health.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'processing',
        ]);
        app()->instance(AiProvider::class, new NullAiProvider);
        config(['queue.default' => 'database']);
        config(['mail.default' => 'log']);
        DB::table('jobs')->insert([
            [
                'queue' => 'default',
                'payload' => '{}',
                'attempts' => 0,
                'reserved_at' => now()->subMinutes(31)->timestamp,
                'available_at' => now()->subMinutes(31)->timestamp,
                'created_at' => now()->subMinutes(31)->timestamp,
            ],
            [
                'queue' => 'default',
                'payload' => '{}',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->subMinutes(31)->timestamp,
                'created_at' => now()->subMinutes(31)->timestamp,
            ],
        ]);
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'failure details are not shown',
            'failed_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('System health')
            ->assertSee('Database connection is responding.')
            ->assertSee('2 queued jobs')
            ->assertSee('1 failed jobs')
            ->assertSee('Queue jobs need attention.')
            ->assertSee('Overall status: Needs attention.')
            ->assertSee('The latest encrypted backup is current.')
            ->assertSee('Email driver does not deliver messages')
            ->assertSee('1 failed jobs; 1 stale reservations and 1 overdue queued jobs (over 30 minutes).')
            ->assertSee('1 stale reservations (&gt; 30 min)', false)
            ->assertSee('1 overdue queued jobs (&gt; 30 min)', false)
            ->assertSee('1 pending')
            ->assertSee('1 failed; 1 stuck in processing for more than 30 minutes.')
            ->assertSee('1 stuck (&gt; 30 min)', false)
            ->assertSee('Not delivering')
            ->assertSee('This driver does not deliver email to recipients.')
            ->assertSee('Private storage directory exists and is writable.')
            ->assertDontSee(config('filesystems.disks.private.root'))
            ->assertSee('AI provider is not configured')
            ->assertDontSee('failure details are not shown');

        config(['mail.default' => 'smtp']);
        $this->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('Configured')
            ->assertSee('email delivery has not been tested');

        $this->actingAs($employee)->get(route('admin.system-health.index'))
            ->assertForbidden();
    }

    public function test_admin_system_health_summary_is_healthy_when_checks_find_no_issues(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        app()->instance(AiProvider::class, new NullAiProvider);
        config([
            'queue.default' => 'sync',
            'mail.default' => 'smtp',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('Overall status: Healthy.')
            ->assertSee('No issues detected by the available checks.')
            ->assertSee('Encrypted local backup')
            ->assertSee('The latest encrypted backup is current.')
            ->assertDontSee('Needs attention');
    }

    public function test_admin_system_health_flags_a_missing_or_stale_backup(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $backupFile = $this->backupPath.DIRECTORY_SEPARATOR.'smartdocs-test.zip.enc';
        File::delete($backupFile);

        $this->actingAs($admin)
            ->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('No encrypted local backup was found.')
            ->assertSee('Encrypted backup missing, stale, or unavailable')
            ->assertSee('Overall status: Needs attention.');

        File::put($backupFile, 'test');
        touch($backupFile, now()->subHours(37)->getTimestamp());

        $this->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('37 hours old')
            ->assertSee('Backups older than 36 hours are flagged.');
    }

    public function test_admin_can_review_expired_and_upcoming_document_expirations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $this->createRetentionReviewDocument($employee, 'Expired record', now()->subDay()->toDateString());
        $this->createRetentionReviewDocument($employee, 'Expires today', today()->toDateString());
        $this->createRetentionReviewDocument($employee, 'Upcoming record', now()->addDays(30)->toDateString());
        $this->createRetentionReviewDocument($employee, 'Outside review window', now()->addDays(31)->toDateString());
        $this->createRetentionReviewDocument($employee, 'No expiration date', null);

        $this->actingAs($admin)
            ->get(route('admin.documents.retention-review'))
            ->assertOk()
            ->assertSee('Document expiration review')
            ->assertSee('Expired record')
            ->assertSee('Expires today')
            ->assertSee('Upcoming record')
            ->assertDontSee('Outside review window')
            ->assertDontSee('No expiration date')
            ->assertSee('Expired')
            ->assertSee('Expires soon')
            ->assertSee('does not archive or delete documents');

        $this->actingAs($employee)
            ->get(route('admin.documents.retention-review'))
            ->assertForbidden();
    }

    public function test_admin_can_filter_dormant_accounts_without_automatically_deactivating_them(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Admin User']);
        $neverSignedIn = User::factory()->create(['name' => 'Never Signed In']);
        $dormant = User::factory()->create(['name' => 'Dormant Account']);
        $recent = User::factory()->create(['name' => 'Recent Account']);
        $dormantSignIn = AuditLog::create([
            'user_id' => $dormant->id,
            'action' => 'auth.login_succeeded',
            'entity_type' => User::class,
            'entity_id' => $dormant->id,
        ]);
        $dormantSignIn->forceFill(['created_at' => now()->subDays(91)])->saveQuietly();
        $recentSignIn = AuditLog::create([
            'user_id' => $recent->id,
            'action' => 'auth.login_succeeded',
            'entity_type' => User::class,
            'entity_id' => $recent->id,
        ]);
        $recentSignIn->forceFill(['created_at' => now()->subDays(30)])->saveQuietly();

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['access_review' => 'dormant']))
            ->assertOk()
            ->assertSee('No sign-in in 90+ days')
            ->assertSee('Showing accounts with no recorded successful sign-in')
            ->assertSee('Never Signed In')
            ->assertSee('Dormant Account')
            ->assertSee('Never')
            ->assertDontSee('Recent Account');

        $this->assertTrue($neverSignedIn->fresh()->is_active);
        $this->assertTrue($dormant->fresh()->is_active);
        $this->get(route('admin.users.index', ['access_review' => 'invalid']))
            ->assertSessionHasErrors('access_review');
    }

    private function createRetentionReviewDocument(User $uploader, string $title, ?string $expirationDate): Document
    {
        return Document::create([
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'original_filename' => Str::slug($title).'.txt',
            'storage_path' => 'documents/'.Str::slug($title).'.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'completed',
            'expiration_date' => $expirationDate,
        ]);
    }

    public function test_admin_can_reset_another_users_two_factor_with_audit_and_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => 'private-two-factor-secret',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Reset 2FA');

        $this->post(route('admin.users.two-factor.reset', $user))
            ->assertRedirect()
            ->assertSessionHas('status', 'Two-factor authentication reset. The user was notified by email.');

        $user->refresh();
        $this->assertFalse($user->two_factor_enabled);
        $this->assertNull($user->two_factor_secret);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user.two_factor_reset_by_admin',
            'entity_type' => User::class,
            'entity_id' => $user->id,
        ]);
        Mail::assertSent(SecurityAlert::class, function (SecurityAlert $mail) {
            return $mail->subjectText === 'Two-factor authentication reset'
                && str_contains($mail->bodyText, 'set up an authenticator app again');
        });
    }

    public function test_admin_cannot_reset_own_two_factor_and_inactive_two_factor_is_unchanged(): void
    {
        Mail::fake();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'two_factor_enabled' => true,
            'two_factor_secret' => 'admin-two-factor-secret',
        ]);
        $this->actingAs($admin)
            ->post(route('admin.users.two-factor.reset', $admin))
            ->assertSessionHasErrors('two_factor');
        $this->assertTrue($admin->fresh()->two_factor_enabled);

        $user = User::factory()->create(['two_factor_enabled' => false, 'two_factor_secret' => null]);
        $this->post(route('admin.users.two-factor.reset', $user))
            ->assertSessionHas('status', 'Two-factor authentication is already disabled for this account.');
        $this->assertFalse($user->fresh()->two_factor_enabled);
        $this->assertFalse(AuditLog::where('action', 'user.two_factor_reset_by_admin')->exists());
        Mail::assertNothingSent();
    }

    public function test_admin_password_reset_invalidates_the_users_existing_session(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'password' => Hash::make('Original-password-123!'),
        ]);
        $oldPasswordHash = $user->getAuthPassword();

        $this->actingAs($user)
            ->withSession(['password_hash_web' => $oldPasswordHash])
            ->get(route('home'))
            ->assertOk();

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_EMPLOYEE,
            'password' => 'New-secure-password-123!',
            'password_confirmation' => 'New-secure-password-123!',
        ])->assertRedirect(route('admin.users.index'));

        $this->actingAs($user->fresh())->get(route('home'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_create_update_and_delete_employee_accounts(): void
    {
        Mail::fake();
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $payload = [
            'name' => 'New PANELCO Employee',
            'email' => 'new.employee@panelco3.local',
            'password' => 'Safe-password-123!',
            'password_confirmation' => 'Safe-password-123!',
        ];

        $this->actingAs($employee)->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.users.create'))->assertForbidden();
        $this->post(route('admin.users.store'), $payload)->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            ...$payload,
            'email' => 'weak.password@panelco3.local',
            'password' => 'safe-password-123',
            'password_confirmation' => 'safe-password-123',
        ])->assertSessionHasErrors('password');

        $this->actingAs($employee);
        $this->put(route('admin.users.update', $employee), [
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => User::ROLE_EMPLOYEE,
        ])->assertForbidden();
        $this->delete(route('admin.users.destroy', $employee))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.users.store'), $payload)
            ->assertRedirect(route('admin.users.index'));
        $created = User::where('email', $payload['email'])->firstOrFail();
        $this->assertSame(User::ROLE_EMPLOYEE, $created->role);
        $this->assertTrue(Hash::check($payload['password'], $created->password));

        $this->actingAs($admin)->put(route('admin.users.update', $created), [
            'name' => 'Updated PANELCO Employee',
            'email' => $payload['email'],
            'role' => User::ROLE_EMPLOYEE,
        ])->assertRedirect(route('admin.users.index'));
        $this->assertSame('Updated PANELCO Employee', $created->fresh()->name);

        $this->put(route('admin.users.update', $created), [
            'name' => 'Updated PANELCO Employee',
            'email' => $payload['email'],
            'role' => User::ROLE_EMPLOYEE,
            'password' => 'Another-Password-123!',
            'password_confirmation' => 'Another-Password-123!',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertTrue(AuditLog::where('action', 'user.password_reset')
            ->where('entity_id', $created->id)->exists());
        Mail::assertSent(SecurityAlert::class, function (SecurityAlert $mail) use ($payload) {
            return $mail->subjectText === 'Your SmartDocs password was reset'
                && $mail->hasTo($payload['email'])
                && ! str_contains($mail->bodyText, 'Another-Password-123!');
        });

        $this->actingAs($admin)->delete(route('admin.users.destroy', $created))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $created->id]);
    }

    public function test_saving_a_user_preserves_additional_departments(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $primary = Department::where('name', 'IT Services')->firstOrFail();
        $additional = Department::create(['name' => 'Additional Department']);
        $employee->departments()->sync([$additional->id]);

        $payload = [
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => User::ROLE_EMPLOYEE,
            'department_id' => $primary->id,
        ];

        $this->actingAs($admin)
            ->put(route('admin.users.update', $employee), $payload)
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('department_user', [
            'user_id' => $employee->id,
            'department_id' => $additional->id,
        ]);
    }

    public function test_additional_department_access_can_be_removed_from_user_edit_form(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $first = Department::create(['name' => 'First Additional Department']);
        $second = Department::create(['name' => 'Second Additional Department']);
        $employee->departments()->sync([$first->id, $second->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('Additional Departments')
            ->assertDontSee('name="departments[]"', false);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertDontSee('Additional Departments')
            ->assertDontSee('name="departments[]"', false);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $employee))
            ->assertOk()
            ->assertSee('Additional Departments')
            ->assertSee('name="departments[]"', false)
            ->assertSee('value="'.$first->id.'" checked', false)
            ->assertSee('value="'.$second->id.'" checked', false);

        $this->put(route('admin.users.update', $employee), [
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => User::ROLE_EMPLOYEE,
            'manage_departments' => '1',
            'departments' => [$second->id],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('department_user', [
            'user_id' => $employee->id,
            'department_id' => $first->id,
        ]);
        $this->assertDatabaseHas('department_user', [
            'user_id' => $employee->id,
            'department_id' => $second->id,
        ]);

        $this->put(route('admin.users.update', $employee), [
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => User::ROLE_EMPLOYEE,
            'manage_departments' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('department_user', [
            'user_id' => $employee->id,
            'department_id' => $second->id,
        ]);
    }

    public function test_admin_can_deactivate_and_reactivate_employee_accounts(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.users.status', $employee), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($employee->fresh()->is_active);
        $this->assertTrue(AuditLog::where('action', 'user.deactivated')->where('entity_id', $employee->id)->exists());

        $this->patch(route('admin.users.status', $employee), ['is_active' => true])->assertRedirect();
        $this->assertTrue($employee->fresh()->is_active);
        $this->assertTrue(AuditLog::where('action', 'user.activated')->where('entity_id', $employee->id)->exists());
    }

    public function test_inactive_users_cannot_login_or_use_an_existing_session(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'employee_id' => $user->employee_id,
            'password' => 'password',
        ])->assertSessionHasErrors('password');
        $this->assertGuest();

        $this->actingAs($user)->get(route('home'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_cannot_deactivate_self_or_last_active_administrator(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.users.status', $admin), ['is_active' => false])
            ->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->is_active);

        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $this->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_EMPLOYEE,
        ])->assertSessionHasErrors('role');
        $this->assertTrue($employee->fresh()->is_active);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_last_administrator_cannot_be_demoted_or_deleted(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_EMPLOYEE,
        ])->assertSessionHasErrors('role');
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);

        $this->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => User::ROLE_ADMIN]);
    }

    public function test_edit_page_has_no_delete_controls(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $admin))
            ->assertOk()
            ->assertDontSee('Danger Zone')
            ->assertDontSee('deleteUserModal')
            ->assertDontSee('Delete User');
    }

    public function test_create_form_is_empty_after_adding_a_user(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Freshly Added User',
            'email' => 'freshly.added@panelco3.local',
            'password' => 'Safe-password-123!',
            'password_confirmation' => 'Safe-password-123!',
        ])->assertRedirect(route('admin.users.index'));

        // The create form comes back empty: no repopulated values, no password
        // left behind, and the modal stays closed unless a validation error
        // needs to reopen it with the input to fix.
        $page = $this->actingAs($admin)->get(route('admin.users.index'));
        $page->assertOk();
        $html = $page->getContent();

        $this->assertStringNotContainsString('value="Freshly Added User"', $html);
        $this->assertStringNotContainsString('value="freshly.added@panelco3.local"', $html);
        $this->assertStringNotContainsString('autocomplete="on"', $html);
        $this->assertStringContainsString('autocomplete="off"', $html);
        $this->assertStringContainsString('autocomplete="new-password"', $html);
        $this->assertStringContainsString('id="newUserModal"', $html);
        // Closed by default: auto-show script only runs when there is input
        // to repopulate.
        $this->assertDoesNotMatchRegularExpression(
            '/bootstrap\.Modal\.getOrCreateInstance\(modalEl\);\s*modal\.show\(\);/',
            $html
        );
    }

    public function test_new_user_opens_a_modal_form(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();

        $html = $this->actingAs($admin)->get(route('admin.users.index'))->getContent();

        // The form lives in a centered modal, not an inline block at the top.
        $this->assertStringContainsString('data-bs-target="#newUserModal"', $html);
        $this->assertStringContainsString('modal-dialog-centered', $html);
        $this->assertStringNotContainsString('id="userCreateForm"', $html);
        $this->assertStringNotContainsString('data-bs-target="#userCreateForm"', $html);

        // A failed save reopens the modal with the typed input intact.
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Half Typed User',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors(['email', 'password']);

        $html = $this->actingAs($admin)->get(route('admin.users.index'))->getContent();
        $this->assertStringContainsString('value="Half Typed User"', $html);
        $this->assertMatchesRegularExpression(
            '/bootstrap\.Modal\.getOrCreateInstance\(modalEl\);\s*modal\.show\(\);/',
            $html
        );
    }

    public function test_email_and_password_fields_block_browser_autofill(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();

        foreach (['index', 'create'] as $routeName) {
            $html = $this->actingAs($admin)->get(route('admin.users.'.$routeName))->getContent();

            $this->assertStringContainsString('autocomplete="off"', $html, "email autofill off missing on {$routeName}");
            $this->assertStringContainsString('autocomplete="new-password"', $html, "password new-password missing on {$routeName}");
            $this->assertDoesNotMatchRegularExpression('/name="email"[^>]*autocomplete="on"/', $html);
            $this->assertDoesNotMatchRegularExpression('/name="password"[^>]*autocomplete="on"/', $html);
        }

        $html = $this->actingAs($admin)->get(route('admin.users.edit', $employee))->getContent();
        $this->assertStringContainsString('autocomplete="off"', $html);
        $this->assertStringContainsString('autocomplete="new-password"', $html);
    }

    public function test_password_fields_have_no_suggest_password_text(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();

        foreach (['index', 'create'] as $routeName) {
            $html = $this->actingAs($admin)->get(route('admin.users.'.$routeName))->getContent();

            $this->assertStringNotContainsString('Suggest password', $html, "suggest text still present on {$routeName}");
            $this->assertStringNotContainsString('suggestPwLink', $html);
            $this->assertStringNotContainsString('suggestStandalonePwLink', $html);
            // The 12+ hint stays.
            $this->assertStringContainsString('12+ characters, uppercase and lowercase, number, and symbol.', $html);
        }

        $html = $this->actingAs($admin)->get(route('admin.users.edit', $admin))->getContent();
        $this->assertStringNotContainsString('Suggest password', $html);
        $this->assertStringNotContainsString('suggestEditPwLink', $html);
        $this->assertStringContainsString('12+ characters, uppercase and lowercase, number, and symbol.', $html);
    }

    public function test_another_administrator_can_delete_system_administrator_after_admin_handoff(): void
    {
        $this->seed();
        $systemAdministrator = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $replacementAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($replacementAdmin)
            ->delete(route('admin.users.destroy', $systemAdministrator))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $systemAdministrator->id]);
    }

    public function test_system_administrator_edit_hides_delete_access_and_additional_departments(): void
    {
        $this->seed();
        $systemAdministrator = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $otherAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $department = Department::create(['name' => 'Locked Department']);
        $systemAdministrator->departments()->sync([$department->id]);

        $this->actingAs($otherAdmin)
            ->get(route('admin.users.edit', $systemAdministrator))
            ->assertOk()
            ->assertDontSee('Danger Zone')
            ->assertDontSee('Account access')
            ->assertDontSee('Additional Departments');

        // Saving must not wipe departments hidden behind the removed field.
        $this->actingAs($otherAdmin)
            ->put(route('admin.users.update', $systemAdministrator), [
                'name' => $systemAdministrator->name,
                'email' => $systemAdministrator->email,
                'role' => User::ROLE_ADMIN,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('department_user', [
            'user_id' => $systemAdministrator->id,
            'department_id' => $department->id,
        ]);

        // Account access is gone: deactivating a System Administrator is refused.
        $this->patch(route('admin.users.status', $systemAdministrator), ['is_active' => false])
            ->assertSessionHasErrors('user');
        $this->assertTrue($systemAdministrator->fresh()->is_active);
    }

    public function test_dashboard_shows_permission_filtered_metrics(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('dashboard'))->assertOk();
    }
}
