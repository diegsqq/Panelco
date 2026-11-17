@extends('layouts.app')

@section('content')
<style>
    .nf-page { max-width: 1040px; margin: 0 auto; }
    .nf-hero {
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, #00A868 0%, #00743F 60%, #064b34 100%);
        border-radius: 1.25rem; padding: 1.6rem 1.8rem; color: #fff;
        box-shadow: 0 12px 30px rgba(0,120,60,.22); margin-bottom: 1.25rem;
    }
    .nf-hero::before, .nf-hero::after {
        content: ""; position: absolute; border-radius: 50%; pointer-events: none;
        border: 1px solid rgba(255,255,255,.14);
    }
    .nf-hero::before { width: 260px; height: 260px; right: -90px; top: -110px; }
    .nf-hero::after { width: 180px; height: 180px; right: -40px; bottom: -90px; }
    .nf-hero-inner { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; flex-wrap: wrap; }
    .nf-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
    .nf-hero-ico { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.16); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex: 0 0 52px; backdrop-filter: blur(4px); }
    .nf-hero-title { font-size: 1.55rem; font-weight: 800; margin: 0; line-height: 1.15; }
    .nf-hero-sub { color: rgba(255,255,255,.82); font-size: .88rem; margin: .25rem 0 0; }
    .nf-hero-stats { display: flex; gap: .6rem; flex-wrap: wrap; }
    .nf-stat { background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.22); border-radius: .8rem; padding: .55rem .9rem; min-width: 84px; text-align: center; backdrop-filter: blur(4px); }
    .nf-stat-val { font-weight: 800; font-size: 1.25rem; line-height: 1.1; }
    .nf-stat-lbl { font-size: .66rem; text-transform: uppercase; letter-spacing: .5px; opacity: .85; margin-top: .15rem; }

    .nf-toolbar {
        display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap;
        background: #fff; border: 1px solid #ebebeb; border-radius: 1rem; padding: .75rem 1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(10,31,92,.04);
    }
    .nf-search { display: flex; align-items: center; gap: .5rem; flex: 1 1 260px; max-width: 360px; background: #f5f5f5; border: 1px solid #e6e6e6; border-radius: .7rem; padding: .45rem .8rem; transition: border-color .15s ease, box-shadow .15s ease; }
    .nf-search:focus-within { border-color: #00A868; box-shadow: 0 0 0 .2rem rgba(0,168,104,.12); background: #fff; }
    .nf-search i { color: #9a9ab0; font-size: .9rem; }
    .nf-search input { border: 0; outline: none; background: transparent; flex: 1; font-size: .85rem; color: #1a1a2e; min-width: 0; }
    .nf-search input::placeholder { color: #9a9ab0; }
    .nf-filters { display: flex; gap: .35rem; flex-wrap: wrap; }
    .nf-filter { border: 1px solid #d5e8db; background: #fff; color: #00743F; border-radius: 2rem; padding: .32rem .85rem; font-size: .78rem; font-weight: 600; cursor: pointer; transition: all .15s ease; display: inline-flex; align-items: center; gap: .35rem; }
    .nf-filter:hover { background: #f0f9f3; }
    .nf-filter.active { background: #00A868; color: #fff; border-color: #00A868; }
    .nf-filter .nf-count { background: rgba(0,0,0,.08); border-radius: 2rem; padding: 0 .45rem; font-size: .7rem; }
    .nf-filter.active .nf-count { background: rgba(255,255,255,.25); }
    .nf-actions { display: flex; gap: .5rem; }
    .nf-btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: .65rem; padding: .5rem .95rem; font-size: .82rem; font-weight: 600; border: 1px solid #d5e8db; background: #fff; color: #00743F; cursor: pointer; transition: all .15s ease; }
    .nf-btn:hover { background: #f0f9f3; border-color: #00A868; }
    .nf-btn.primary { background: #00A868; color: #fff; border-color: #00A868; }
    .nf-btn.primary:hover { background: #00743F; border-color: #00743F; color: #fff; }
    .nf-btn:disabled { opacity: .5; cursor: not-allowed; }

    .nf-group-label { font-size: .72rem; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: .6px; margin: 1.1rem 0 .5rem .25rem; display: flex; align-items: center; gap: .5rem; }
    .nf-group-label::after { content: ""; flex: 1 1 auto; height: 1px; background: #ebebeb; }
    .nf-list { display: flex; flex-direction: column; gap: .6rem; }

    .nf-card {
        position: relative; display: flex; align-items: center; gap: .7rem;
        background: #fff; border: 1px solid #ebebeb; border-radius: .85rem;
        padding: .55rem .7rem .55rem .8rem; text-decoration: none; color: inherit;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        overflow: hidden;
    }
    .nf-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(10,31,92,.08); border-color: #b7e0c8; }
    .nf-card.unread { background: linear-gradient(180deg, #f6fbf7 0%, #ffffff 100%); border-color: #cde9d6; }
    .nf-card.unread::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg, #00A868, #7ed99f); }
    .nf-card.fade-out { opacity: 0; transform: translateX(20px); transition: all .25s ease; }

    .nf-icon { width: 30px; height: 30px; flex: 0 0 30px; border-radius: .55rem; display: flex; align-items: center; justify-content: center; font-size: .85rem; position: relative; }
    .nf-icon.primary { background: #e6f3ea; color: #00743F; }
    .nf-icon.warning { background: #fff3cd; color: #856404; }
    .nf-icon.danger { background: #f8d7da; color: #721c24; }
    .nf-icon.secondary { background: #eef1f4; color: #555; }
    .nf-icon.info { background: #e7f1fb; color: #0d6efd; }
    .nf-icon .nf-dot { position: absolute; top: -3px; right: -3px; width: 11px; height: 11px; border-radius: 50%; background: #00A868; border: 2px solid #fff; box-shadow: 0 0 0 0 rgba(0,168,104,.5); animation: nf-pulse 1.8s infinite; }
    @keyframes nf-pulse { 0% { box-shadow: 0 0 0 0 rgba(0,168,104,.5); } 70% { box-shadow: 0 0 0 8px rgba(0,168,104,0); } 100% { box-shadow: 0 0 0 0 rgba(0,168,104,0); } }

    .nf-body { flex: 1 1 auto; min-width: 0; }
    .nf-row { display: flex; align-items: flex-start; justify-content: space-between; gap: .6rem; }
    .nf-title { font-weight: 700; font-size: .82rem; color: #1a1a2e; margin: 0 0 .1rem; line-height: 1.2; }
    .nf-msg { color: #5a5a5a; font-size: .75rem; line-height: 1.35; margin: 0 0 .25rem; word-break: break-word; }
    .nf-meta { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; font-size: .72rem; color: #9a9ab0; }
    .nf-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .14rem .55rem; border-radius: 2rem; font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
    .nf-badge.unread { background: #00A868; color: #fff; }
    .nf-badge.read { background: #ebebeb; color: #6c757d; }
    .nf-badge.action { background: #fff3cd; color: #856404; }
    .nf-time { display: inline-flex; align-items: center; gap: .25rem; }
    .nf-actions-inline { display: flex; gap: .35rem; opacity: 0; transition: opacity .15s ease; flex: 0 0 auto; }
    .nf-card:hover .nf-actions-inline { opacity: 1; }
    .nf-act { width: 28px; height: 28px; border-radius: .5rem; border: 1px solid #e2e2e2; background: #fff; color: #555; display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; cursor: pointer; transition: all .12s ease; }
    .nf-act:hover { background: #f0f9f3; border-color: #00A868; color: #00A868; }
    .nf-act.read:hover { background: #e6f3ea; }

    .nf-empty { text-align: center; padding: 3.5rem 1rem; color: #9a9ab0; }
    .nf-empty-ico { width: 84px; height: 84px; border-radius: 50%; background: #f0f9f3; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: #00A868; font-size: 2.2rem; }
    .nf-empty h5 { color: #1a1a2e; font-weight: 700; margin-bottom: .35rem; }
    .nf-empty p { font-size: .85rem; margin: 0; }

    .nf-toast { position: fixed; bottom: 1.25rem; right: 1.25rem; background: #1a1a2e; color: #fff; padding: .7rem 1rem; border-radius: .7rem; font-size: .82rem; font-weight: 600; box-shadow: 0 8px 24px rgba(0,0,0,.2); opacity: 0; transform: translateY(12px); transition: all .25s ease; z-index: 1080; display: flex; align-items: center; gap: .5rem; }
    .nf-toast.show { opacity: 1; transform: translateY(0); }

    [data-theme="dark"] .nf-toolbar { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .nf-search { background: #0f1512; border-color: #2c3d34; }
    [data-theme="dark"] .nf-search input { color: #e5eee8; }
    [data-theme="dark"] .nf-search:focus-within { background: #0f1512; }
    [data-theme="dark"] .nf-filter { background: #16211c; border-color: #2c3d34; color: #7fe0ac; }
    [data-theme="dark"] .nf-filter.active { background: #00A868; color: #fff; }
    [data-theme="dark"] .nf-btn { background: #16211c; border-color: #2c3d34; color: #7fe0ac; }
    [data-theme="dark"] .nf-card { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .nf-card.unread { background: linear-gradient(180deg, #131d18 0%, #16211c 100%); border-color: #2f6b4f; }
    [data-theme="dark"] .nf-title { color: #e5eee8; }
    [data-theme="dark"] .nf-msg { color: #c2d2ca; }
    [data-theme="dark"] .nf-icon.primary { background: #12291d; color: #7fe0ac; }
    [data-theme="dark"] .nf-icon.warning { background: #2b2410; color: #ecd78a; }
    [data-theme="dark"] .nf-icon.danger { background: #2d1414; color: #f1a8a8; }
    [data-theme="dark"] .nf-icon.secondary { background: #1d2b24; color: #8fa59a; }
    [data-theme="dark"] .nf-icon.info { background: #10304d; color: #a8d5ff; }
    [data-theme="dark"] .nf-icon .nf-dot { border-color: #16211c; }
    [data-theme="dark"] .nf-badge.read { background: #1d2b24; color: #8fa59a; }
    [data-theme="dark"] .nf-badge.action { background: #2b2410; color: #ecd78a; }
    [data-theme="dark"] .nf-act { background: #131d18; border-color: #2c3d34; color: #8fa59a; }
    [data-theme="dark"] .nf-empty h5 { color: #e5eee8; }
    [data-theme="dark"] .nf-empty-ico { background: #12291d; }
    [data-theme="dark"] .nf-group-label { color: #8fa59a; }
    [data-theme="dark"] .nf-group-label::after { background: #26362e; }
    [data-theme="dark"] .nf-toast { background: #0f1512; border: 1px solid #2c3d34; }

    @media (max-width: 575.98px) {
        .nf-hero { padding: 1.2rem 1.25rem; }
        .nf-hero-title { font-size: 1.25rem; }
        .nf-card { padding: .85rem .9rem .85rem 1rem; }
        .nf-actions-inline { opacity: 1; }
    }
</style>

<div class="nf-page">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="nf-hero-title" style="color:#1a1a2e;font-size:1.5rem;"><i class="bi bi-bell-fill me-2" style="color:#00A868"></i>Notifications</h1>
            <p class="nf-hero-sub" style="color:#5a9e7a;margin:0;">All your alerts, updates and activity in one place.</p>
        </div>
    </div>

    <div class="nf-toolbar">
        <div class="nf-filters">
            <button class="nf-filter active" data-filter="all">All <span class="nf-count" id="cAll">{{ count($notifications) }}</span></button>
            <button class="nf-filter" data-filter="unread">Unread <span class="nf-count" id="cUnread">{{ $unreadCount }}</span></button>
            <button class="nf-filter" data-filter="read">Read <span class="nf-count" id="cRead">{{ count($notifications) - $unreadCount }}</span></button>
            <button class="nf-btn primary" id="markAllReadPage" {{ $unreadCount === 0 ? 'disabled' : '' }} style="margin-left:.35rem;">
                <i class="bi bi-check2-all"></i> Mark all read
            </button>
        </div>
        <div class="nf-search">
            <i class="bi bi-search"></i>
            <input type="text" id="nfSearch" placeholder="Search notifications..." aria-label="Search notifications">
        </div>
    </div>

    <div id="nfContainer">
        @if(count($notifications) === 0)
            <div class="nf-empty">
                <div class="nf-empty-ico"><i class="bi bi-bell-slash"></i></div>
                <h5>No notifications yet</h5>
                <p>You're all caught up. New alerts will appear here.</p>
            </div>
        @else
            @php
                $groups = ['Today' => [], 'Yesterday' => [], 'Earlier' => []];
                foreach ($notifications as $n) {
                    try {
                        $d = \Carbon\Carbon::parse($n['created_at']);
                        if ($d->isToday()) $groups['Today'][] = $n;
                        elseif ($d->isYesterday()) $groups['Yesterday'][] = $n;
                        else $groups['Earlier'][] = $n;
                    } catch (\Throwable $e) {
                        $groups['Earlier'][] = $n;
                    }
                }
            @endphp
            @foreach($groups as $label => $items)
                @if(count($items) > 0)
                    <div class="nf-group" data-group="{{ $label }}">
                        <div class="nf-group-label">{{ $label }}</div>
                        <div class="nf-list">
                            @foreach($items as $n)
                                @php
                                    $isUnread = !($n['is_read'] ?? false);
                                    $meta = $n['metadata'] ?? [];
                                    $link = '#';
                                    $isAdminViewer = auth()->user()?->isAdmin() ?? false;
                                    if ($isAdminViewer && ($n['type'] ?? '') === 'password_reset_request' && isset($meta['password_reset_request_id'])) {
                                        try { $link = route('admin.password-reset-requests.show', ['passwordResetRequest' => $meta['password_reset_request_id']]); } catch (\Throwable $e) { $link = '#'; }
                                    }
                                    $icon = $n['icon'] ?? 'bi-bell';
                                    if (!str_starts_with($icon, 'bi-')) $icon = 'bi-' . ltrim($icon, 'bi-');
                                @endphp
                                <a href="{{ $link }}" class="nf-card {{ $isUnread ? 'unread' : '' }}"
                                   data-id="{{ $n['id'] }}" data-read="{{ $isUnread ? '0' : '1' }}"
                                   data-category="{{ $n['category'] ?? 'info' }}"
                                   data-search="{{ strtolower(($n['title'] ?? '') . ' ' . ($n['message'] ?? '')) }}">
                                    <div class="nf-icon {{ $n['category'] ?? 'info' }}">
                                        <i class="bi {{ $icon }}"></i>
                                        @if($isUnread)<span class="nf-dot"></span>@endif
                                    </div>
                                    <div class="nf-body">
                                        <div class="nf-row">
                                            <div style="min-width:0;flex:1 1 auto;">
                                                <div class="nf-title">{{ $n['title'] ?? 'Notification' }}</div>
                                                <div class="nf-msg">{{ $n['message'] ?? '' }}</div>
                                            </div>
                                            <div class="nf-actions-inline">
                                                @if($isUnread)
                                                    <button class="nf-act read" data-action="read" title="Mark as read" aria-label="Mark as read">
                                                        <i class="bi bi-check2"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="nf-meta">
                                            <span class="nf-time"><i class="bi bi-clock"></i> {{ $n['readable_created_at'] ?? '' }}</span>
                                            @if($isUnread)
                                                <span class="nf-badge unread">Unread</span>
                                            @else
                                                <span class="nf-badge read">Read</span>
                                            @endif
                                            @if($isAdminViewer && ($n['type'] ?? '') === 'password_reset_request')
                                                <span class="nf-badge action"><i class="bi bi-arrow-right-short"></i> Action required</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
            <div class="nf-empty d-none" id="nfNoResults">
                <div class="nf-empty-ico"><i class="bi bi-search"></i></div>
                <h5>No matches found</h5>
                <p>Try a different search or filter.</p>
            </div>
        @endif
    </div>
</div>

<div class="nf-toast" id="nfToast"><i class="bi bi-check-circle-fill"></i><span id="nfToastText">Done</span></div>

<div class="modal fade" id="nfDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content" style="border-radius:1.1rem;overflow:hidden;border:none;box-shadow:0 20px 50px rgba(0,0,0,.2);">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div id="nfModalIcon" class="nf-icon primary" style="width:36px;height:36px;font-size:1rem;"></div>
                    <h5 class="modal-title fw-bold mb-0" id="nfModalTitle">Notification</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-3" id="nfModalMsg" style="font-size:.9rem;color:#444;line-height:1.55;"></p>
                <div class="d-flex align-items-center gap-2 flex-wrap" id="nfModalMeta" style="font-size:.75rem;color:#888;"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    var container = document.getElementById('nfContainer');
    var toast = document.getElementById('nfToast');
    var toastText = document.getElementById('nfToastText');

    // ── Notification ringtone: Web Audio API beep (no audio file needed) ──
    var nfAudioCtx = null;
    function playNotifSound() {
        if (!nfAudioCtx) {
            try { nfAudioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { return; }
        }
        if (nfAudioCtx.state === 'suspended') nfAudioCtx.resume();
        var t = nfAudioCtx.currentTime;
        // Double beep: 880Hz → 1320Hz, then 1100Hz → 1650Hz
        [880, 1100].forEach(function (f, i) {
            var tt = t + (i * 0.14);
            var osc = nfAudioCtx.createOscillator();
            var gain = nfAudioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(f, tt);
            osc.frequency.exponentialRampToValueAtTime(f * 1.5, tt + 0.10);
            gain.gain.setValueAtTime(0.35, tt);
            gain.gain.exponentialRampToValueAtTime(0.001, tt + 0.22);
            osc.connect(gain);
            gain.connect(nfAudioCtx.destination);
            osc.start(tt);
            osc.stop(tt + 0.22);
        });
    }

    function showToast(msg) {
        toastText.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () { toast.classList.remove('show'); }, 2200);
    }

    function markRead(id) {
        return fetch('/notifications/' + id + '/read', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).catch(function () {});
    }

    function updateCounts() {
        var cards = container.querySelectorAll('.nf-card');
        var unread = container.querySelectorAll('.nf-card[data-read="0"]').length;
        var total = cards.length;
        var read = total - unread;
        var sT = document.getElementById('statTotal');
        var sU = document.getElementById('statUnread');
        var sR = document.getElementById('statRead');
        if (sT) sT.textContent = total;
        if (sU) sU.textContent = unread;
        if (sR) sR.textContent = read;
        var cA = document.getElementById('cAll'); var cU = document.getElementById('cUnread'); var cR = document.getElementById('cRead');
        if (cA) cA.textContent = total;
        if (cU) cU.textContent = unread;
        if (cR) cR.textContent = read;
        var btn = document.getElementById('markAllReadPage');
        if (btn) btn.disabled = unread === 0;
    }

    function applyFilters() {
        var q = (document.getElementById('nfSearch')?.value || '').trim().toLowerCase();
        var filter = document.querySelector('.nf-filter.active')?.getAttribute('data-filter') || 'all';
        var cards = container.querySelectorAll('.nf-card');
        var shown = 0;
        cards.forEach(function (c) {
            var isUnread = c.getAttribute('data-read') === '0';
            var matchFilter = (filter === 'all') || (filter === 'unread' && isUnread) || (filter === 'read' && !isUnread);
            var matchSearch = !q || (c.getAttribute('data-search') || '').includes(q);
            var show = matchFilter && matchSearch;
            c.style.display = show ? '' : 'none';
            if (show) shown++;
        });
        // hide empty groups
        container.querySelectorAll('.nf-group').forEach(function (g) {
            var visible = g.querySelectorAll('.nf-card:not([style*="display: none"])').length;
            g.style.display = visible ? '' : 'none';
        });
        var noRes = document.getElementById('nfNoResults');
        if (noRes) {
            if (cards.length > 0 && shown === 0) noRes.classList.remove('d-none');
            else noRes.classList.add('d-none');
        }
    }

    // filter buttons
    container.closest('.nf-page').querySelectorAll('.nf-filter').forEach(function (btn) {
        btn.addEventListener('click', function () {
            container.closest('.nf-page').querySelectorAll('.nf-filter').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            applyFilters();
        });
    });

    var searchInput = document.getElementById('nfSearch');
    if (searchInput) searchInput.addEventListener('input', applyFilters);

    // mark read via inline button or card click
    container.addEventListener('click', function (e) {
        var act = e.target.closest('[data-action="read"]');
        var card = e.target.closest('.nf-card');
        if (!card) return;
        var id = card.getAttribute('data-id');
        if (card.getAttribute('data-read') !== '0') return;
        // if clicking a link target, still mark read
        e.preventDefault();
        playNotifSound();
        markRead(id).then(function () {
            card.classList.remove('unread');
            card.setAttribute('data-read', '1');
            var dot = card.querySelector('.nf-dot');
            if (dot) dot.remove();
            var badge = card.querySelector('.nf-badge.unread');
            if (badge) { badge.classList.remove('unread'); badge.classList.add('read'); badge.textContent = 'Read'; }
            var btn = card.querySelector('[data-action="read"]');
            if (btn) btn.remove();
            updateCounts();
            applyFilters();
            showToast('Marked as read');
            // navigate or show details
            var href = card.getAttribute('href');
            if (href && href !== '#') window.location.href = href;
            else showModal(card);
        });
    });

    var markAll = document.getElementById('markAllReadPage');
    if (markAll) {
        markAll.addEventListener('click', function () {
            if (markAll.disabled) return;
            markAll.disabled = true;
            fetch('/notifications/read-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
            }).then(function () {
                container.querySelectorAll('.nf-card[data-read="0"]').forEach(function (c) {
                    c.classList.remove('unread');
                    c.setAttribute('data-read', '1');
                    var dot = c.querySelector('.nf-dot');
                    if (dot) dot.remove();
                    var badge = c.querySelector('.nf-badge.unread');
                    if (badge) { badge.classList.remove('unread'); badge.classList.add('read'); badge.textContent = 'Read'; }
                    var btn = c.querySelector('[data-action="read"]');
                    if (btn) btn.remove();
                });
                updateCounts();
                applyFilters();
                showToast('All notifications marked as read');
                markAll.disabled = false;
            });
        });
    }

    function showModal(card) {
        var title = card.querySelector('.nf-title')?.textContent?.trim() || 'Notification';
        var msg = card.querySelector('.nf-msg')?.textContent?.trim() || '';
        var meta = card.querySelector('.nf-meta')?.innerHTML || '';
        var iconWrap = card.querySelector('.nf-icon');
        var iconClass = iconWrap ? (iconWrap.querySelector('i')?.className || 'bi bi-bell') : 'bi bi-bell';
        var modalIcon = document.getElementById('nfModalIcon');
        if (modalIcon) {
            modalIcon.className = iconWrap ? iconWrap.className : 'nf-icon primary';
            modalIcon.innerHTML = '<i class="' + iconClass + '"></i>';
        }
        var t = document.getElementById('nfModalTitle');
        var m = document.getElementById('nfModalMsg');
        var mt = document.getElementById('nfModalMeta');
        if (t) t.textContent = title;
        if (m) m.textContent = msg;
        if (mt) mt.innerHTML = meta;
        if (window.bootstrap) {
            var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('nfDetailModal'));
            modal.show();
        }
    }

    // show details for already-read cards with no link
    container.addEventListener('click', function (e) {
        if (e.target.closest('[data-action="read"]')) return;
        var card = e.target.closest('.nf-card');
        if (!card) return;
        if (card.getAttribute('data-read') !== '1') return;
        var href = card.getAttribute('href');
        if (href === '#') {
            e.preventDefault();
            showModal(card);
        }
    });

    applyFilters();

    // ringtone on load if there are unread notifications
    var unreadCards = container.querySelectorAll('.nf-card[data-read="0"]').length;
    if (unreadCards > 0) {
        try { playNotifSound(); } catch (e) {}
    }
})();
</script>
@endsection