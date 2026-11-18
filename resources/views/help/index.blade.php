@extends('layouts.app')

@section('content')
<style>
    .hp-page { max-width: 1120px; margin: 0 auto; }

    .hp-hero {
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, #00A868 0%, #00743F 55%, #064b34 100%);
        border-radius: 1.5rem; padding: 2.2rem 2.4rem; color: #fff;
        box-shadow: 0 16px 40px rgba(0,120,60,.28); margin-bottom: 1.75rem;
    }
    .hp-hero::before, .hp-hero::after {
        content: ""; position: absolute; border-radius: 50%; pointer-events: none;
        border: 1px solid rgba(255,255,255,.16);
    }
    .hp-hero::before { width: 300px; height: 300px; right: -120px; top: -130px; }
    .hp-hero::after { width: 200px; height: 200px; right: -30px; bottom: -100px; }
    .hp-hero-inner { position: relative; z-index: 1; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
    .hp-hero-ico {
        width: 68px; height: 68px; flex: 0 0 68px; border-radius: 18px;
        background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center;
        font-size: 2rem; backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,.22);
    }
    .hp-hero-title { font-size: 1.9rem; font-weight: 800; margin: 0; line-height: 1.1; }
    .hp-hero-sub { color: rgba(255,255,255,.85); font-size: .95rem; margin: .35rem 0 0; max-width: 560px; }
    .hp-hero-chips { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1rem; position: relative; z-index: 1; }
    .hp-chip { background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.24); border-radius: 2rem; padding: .3rem .8rem; font-size: .75rem; font-weight: 600; color: #fff; display: inline-flex; align-items: center; gap: .35rem; }

    .hp-section { margin-bottom: 1.75rem; }
    .hp-section-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .85rem; flex-wrap: wrap; }
    .hp-section-title { font-size: 1.1rem; font-weight: 800; color: #1a1a2e; margin: 0; display: flex; align-items: center; gap: .55rem; }
    .hp-section-title i { color: #00A868; }
    .hp-section-sub { font-size: .85rem; color: #7a7a8a; margin: .15rem 0 0; }

    .hp-card {
        background: #fff; border: 1px solid #ebebeb; border-radius: 1.1rem;
        padding: 1.5rem 1.6rem; height: 100%;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        position: relative; overflow: hidden;
    }
    .hp-card::before {
        content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, #00A868, #7ed99f); opacity: 0;
        transition: opacity .2s ease;
    }
    .hp-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(10,31,92,.08); border-color: #b7e0c8; }
    .hp-card:hover::before { opacity: 1; }
    .hp-card-head { display: flex; align-items: center; gap: .85rem; margin-bottom: 1.1rem; }
    .hp-card-ico {
        width: 44px; height: 44px; flex: 0 0 44px; border-radius: .85rem;
        display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
    }
    .hp-card-ico.account { background: #e7f1fb; color: #0d6efd; }
    .hp-card-ico.documents { background: #e6f3ea; color: #00A868; }
    .hp-card-ico.notifications { background: #fff3cd; color: #856404; }
    .hp-card-ico.admin { background: #f8d7da; color: #721c24; }
    .hp-card-title { font-weight: 800; font-size: 1rem; color: #1a1a2e; margin: 0; line-height: 1.15; }
    .hp-card-sub { font-size: .78rem; color: #9a9ab0; margin: .15rem 0 0; }
    .hp-link-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: .35rem; }
    .hp-link {
        display: flex; align-items: center; gap: .6rem;
        padding: .55rem .7rem; border-radius: .6rem; text-decoration: none;
        color: #444; font-size: .85rem; font-weight: 500;
        transition: all .15s ease; border: 1px solid transparent;
    }
    .hp-link i { font-size: .95rem; color: #8a8a9a; transition: color .15s ease; }
    .hp-link:hover { background: #f0f9f3; border-color: #b7e0c8; color: #00743F; transform: translateX(2px); }
    .hp-link:hover i { color: #00A868; }
    .hp-link .hp-link-arrow { margin-left: auto; font-size: .75rem; color: #b0b0b8; transition: transform .15s ease; }
    .hp-link:hover .hp-link-arrow { transform: translateX(3px); color: #00A868; }

    .hp-cta {
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, #f0f9f3 0%, #ffffff 100%);
        border: 1px solid #d5e8db; border-radius: 1.25rem; padding: 2rem 2.2rem;
        text-align: center; margin-top: 2rem;
    }
    .hp-cta-ico { width: 64px; height: 64px; border-radius: 50%; background: #e6f3ea; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: #00A868; font-size: 1.75rem; box-shadow: 0 8px 20px rgba(0,168,104,.15); }
    .hp-cta h5 { font-weight: 800; color: #1a1a2e; margin-bottom: .35rem; }
    .hp-cta p { color: #7a7a8a; font-size: .9rem; margin-bottom: 1.1rem; max-width: 480px; margin-left: auto; margin-right: auto; }

    [data-theme="dark"] .hp-card { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .hp-card-title { color: #e5eee8; }
    [data-theme="dark"] .hp-link { color: #c3d0c8; }
    [data-theme="dark"] .hp-link:hover { background: #12291d; border-color: #2f6b4f; color: #7fe0ac; }
    [data-theme="dark"] .hp-link i { color: #8fa59a; }
    [data-theme="dark"] .hp-section-title { color: #e5eee8; }
    [data-theme="dark"] .hp-cta { background: linear-gradient(135deg, #131d18 0%, #16211c 100%); border-color: #2f6b4f; }
    [data-theme="dark"] .hp-cta h5 { color: #e5eee8; }
    [data-theme="dark"] .hp-cta p { color: #8fa59a; }

    @media (max-width: 575.98px) {
        .hp-hero { padding: 1.6rem 1.5rem; border-radius: 1.25rem; }
        .hp-hero-title { font-size: 1.5rem; }
        .hp-hero-ico { width: 56px; height: 56px; font-size: 1.6rem; }
        .hp-card { padding: 1.25rem 1.3rem; }
    }
</style>

<div class="hp-page">
    <div class="hp-hero">
        <div class="hp-hero-inner">
            <div class="hp-hero-ico"><i class="bi bi-question-circle-fill"></i></div>
            <div>
                <h1 class="hp-hero-title">Help &amp; Support</h1>
                <p class="hp-hero-sub">Find answers, manage your account, and get the most out of P-ARCHIVE.</p>
            </div>
        </div>
        <div class="hp-hero-chips">
            <span class="hp-chip"><i class="bi bi-person"></i> Account</span>
            <span class="hp-chip"><i class="bi bi-file-earmark-text"></i> Documents</span>
            <span class="hp-chip"><i class="bi bi-bell"></i> Notifications</span>
            <span class="hp-chip"><i class="bi bi-lightning-charge-fill"></i> Lightning AI</span>
        </div>
    </div>

    <div class="hp-section">
        <div class="hp-section-head">
            <div>
                <h2 class="hp-section-title"><i class="bi bi-compass-fill"></i>Quick links</h2>
                <p class="hp-section-sub">Jump to the most common tasks and pages.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <div class="hp-card">
                    <div class="hp-card-head">
                        <div class="hp-card-ico account"><i class="bi bi-person"></i></div>
                        <div>
                            <h3 class="hp-card-title">Account</h3>
                            <p class="hp-card-sub">Profile, password &amp; preferences</p>
                        </div>
                    </div>
                    <ul class="hp-link-list">
                        <li><a class="hp-link" href="{{ route('settings.index') }}"><i class="bi bi-person-circle"></i>Profile and personal details<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                        <li><a class="hp-link" href="{{ route('settings.security') }}"><i class="bi bi-shield-lock"></i>Password and security<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                        <li><a class="hp-link" href="{{ route('settings.preferences') }}"><i class="bi bi-palette"></i>Appearance and theme<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="hp-card">
                    <div class="hp-card-head">
                        <div class="hp-card-ico documents"><i class="bi bi-file-earmark-text"></i></div>
                        <div>
                            <h3 class="hp-card-title">Documents</h3>
                            <p class="hp-card-sub">Upload, organize &amp; manage</p>
                        </div>
                    </div>
                    <ul class="hp-link-list">
                        <li><a class="hp-link" href="{{ route('documents.upload') }}"><i class="bi bi-cloud-arrow-up-fill"></i>Upload a document<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                        <li><a class="hp-link" href="{{ route('documents.library') }}"><i class="bi bi-folder-fill"></i>Browse document library<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                        <li><a class="hp-link" href="{{ route('documents.trash') }}"><i class="bi bi-trash3"></i>Recycle bin<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="hp-card">
                    <div class="hp-card-head">
                        <div class="hp-card-ico notifications"><i class="bi bi-bell"></i></div>
                        <div>
                            <h3 class="hp-card-title">Notifications</h3>
                            <p class="hp-card-sub">Alerts, updates &amp; activity</p>
                        </div>
                    </div>
                    <ul class="hp-link-list">
                        <li><a class="hp-link" href="{{ route('notifications.index') }}"><i class="bi bi-bell-fill"></i>View all notifications<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                        <li><a class="hp-link" href="{{ route('notifications.index') }}"><i class="bi bi-bag-check"></i>Password reset requests<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @can('admin')
    <div class="hp-section">
        <div class="hp-section-head">
            <div>
                <h2 class="hp-section-title"><i class="bi bi-shield-lock-fill"></i>Administration</h2>
                <p class="hp-section-sub">Admin-only tools for managing the system.</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <div class="hp-card">
                    <div class="hp-card-head">
                        <div class="hp-card-ico admin"><i class="bi bi-people"></i></div>
                        <div>
                            <h3 class="hp-card-title">Users</h3>
                            <p class="hp-card-sub">Manage accounts &amp; roles</p>
                        </div>
                    </div>
                    <ul class="hp-link-list">
                        <li><a class="hp-link" href="{{ route('admin.users.index') }}"><i class="bi bi-people"></i>Manage users<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                        <li><a class="hp-link" href="{{ route('notifications.index') }}"><i class="bi bi-bag-check"></i>Password reset requests<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="hp-card">
                    <div class="hp-card-head">
                        <div class="hp-card-ico admin"><i class="bi bi-journal-text"></i></div>
                        <div>
                            <h3 class="hp-card-title">Audit &amp; Logs</h3>
                            <p class="hp-card-sub">Track system activity</p>
                        </div>
                    </div>
                    <ul class="hp-link-list">
                        <li><a class="hp-link" href="{{ route('admin.audit-logs.index') }}"><i class="bi bi-journal-text"></i>Audit logs<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                        <li><a class="hp-link" href="{{ route('admin.system-health.index') }}"><i class="bi bi-heart-pulse"></i>System health<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="hp-card">
                    <div class="hp-card-head">
                        <div class="hp-card-ico admin"><i class="bi bi-gear"></i></div>
                        <div>
                            <h3 class="hp-card-title">Configuration</h3>
                            <p class="hp-card-sub">Categories, departments &amp; retention</p>
                        </div>
                    </div>
                    <ul class="hp-link-list">
                        <li><a class="hp-link" href="{{ route('admin.categories.index') }}"><i class="bi bi-tags"></i>Categories<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                        <li><a class="hp-link" href="{{ route('admin.departments.index') }}"><i class="bi bi-diagram-3"></i>Departments<span class="hp-link-arrow"><i class="bi bi-arrow-right"></i></span></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <div class="hp-cta">
        <div class="hp-cta-ico"><i class="bi bi-chat-right-text"></i></div>
        <h5>Still need help?</h5>
        <p>Contact your system administrator or ask Lightning AI for instant answers about your documents.</p>
        <a href="{{ route('chat.index') }}" class="btn btn-primary px-4" style="border-radius:.65rem;"><i class="bi bi-lightning-charge-fill me-1"></i>Ask Lightning AI</a>
    </div>
</div>
@endsection
