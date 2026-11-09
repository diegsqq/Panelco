@extends('layouts.app')

@section('content')
<style>
    /* ── Layout ── */
    .p3-dash { display: flex; flex-direction: column; gap: 1.5rem; }
    html:has(#p3Dashboard), body:has(#p3Dashboard) { overflow: auto; }
    body:has(#p3Dashboard) .p3-topbar { display: none; }
    @media (max-width: 767.98px) {
        body:has(#p3Dashboard) .p3-topbar { display: flex !important; }
    }

    /* ── Welcome header ── */
    .p3-welcome-title { font-size: 1.65rem; font-weight: 800; color: #00A868; line-height: 1.15; }
    .p3-welcome-sub   { color: #5a9e7a; font-size: .875rem; margin-top: .2rem; }
    [data-theme="dark"] .p3-welcome-title { color: #7fe0ac; }
    [data-theme="dark"] .p3-welcome-sub   { color: #8fa59a; }

    /* ── Panel cards ── */
    .p3-panel { border: 1px solid #d5e8db; border-radius: 1rem; background: #fff; overflow: hidden; }
    [data-theme="dark"] .p3-panel { background: #16211c; border-color: #26362e; }

    /* ── Overview left ── */
    .p3-ov-header { padding: 1rem 1.1rem .6rem; display: flex; justify-content: space-between; align-items: flex-start; }
    .p3-ov-title  { font-weight: 700; font-size: .95rem; color: #00743F; }
    .p3-ov-sub    { font-size: .75rem; color: #5a9e7a; margin-top: .15rem; }
    [data-theme="dark"] .p3-ov-title { color: #7fe0ac; }
    [data-theme="dark"] .p3-ov-sub   { color: #8fa59a; }

    .p3-ov-preview { margin: 0 1.1rem; border: 1px solid #d5e8db; border-radius: .65rem; overflow: hidden; background: #f0f9f3; min-height: 170px; display: flex; align-items: center; justify-content: center; }
    [data-theme="dark"] .p3-ov-preview { background: #0f1512; border-color: #26362e; }

    .p3-ov-view-btn { display: block; margin: .8rem 1.1rem; text-align: center; background: #00A868; color: #fff; border: none; border-radius: .65rem; padding: .65rem 1rem; font-weight: 700; font-size: .88rem; text-decoration: none; transition: background-color .15s ease; }
    .p3-ov-view-btn:hover { background: #00743F; color: #fff; }

    .p3-ov-meta { padding: .75rem 1.1rem 1.1rem; border-top: 1px solid #e6f3ea; display: flex; flex-direction: column; gap: .7rem; }
    [data-theme="dark"] .p3-ov-meta { border-color: #26362e; }
    .p3-ov-meta-item { display: flex; flex-direction: column; gap: .12rem; }
    .p3-ov-meta-label { font-size: .72rem; color: #5a9e7a; display: flex; align-items: center; gap: .35rem; }
    .p3-ov-meta-val   { font-size: .88rem; font-weight: 600; color: #00743F; }
    [data-theme="dark"] .p3-ov-meta-val { color: #7fe0ac; }

    /* ── Activity right ── */
    .p3-act-header { padding: 1rem 1.1rem .5rem; }
    .p3-act-title  { font-weight: 700; font-size: .95rem; color: #00743F; }
    .p3-act-sub    { font-size: .75rem; color: #5a9e7a; margin-top: .1rem; }
    [data-theme="dark"] .p3-act-title { color: #7fe0ac; }
    [data-theme="dark"] .p3-act-sub   { color: #8fa59a; }

    /* Tab row */
    .p3-act-tabs  { display: flex; gap: .4rem; padding: 0 1.1rem .6rem; }
    .p3-act-tab   { flex: 1; text-align: center; padding: .45rem .5rem; border-radius: .55rem; border: 1px solid #d5e8db; font-size: .8rem; font-weight: 600; color: #5a9e7a; background: #fff; cursor: pointer; transition: all .15s ease; white-space: nowrap; }
    .p3-act-tab.active { background: #00A868; color: #fff; border-color: #00A868; }
    .p3-act-tab:not(.active):hover { background: #e6f3ea; border-color: #b7e0c8; color: #00743F; }
    [data-theme="dark"] .p3-act-tab         { background: #1d2b24; border-color: #2c3d34; color: #b8c8bf; }
    [data-theme="dark"] .p3-act-tab.active  { background: #00A868; border-color: #00A868; color: #fff; }

    /* Week label */
    .p3-act-week  { padding: .3rem 1.1rem .45rem; font-size: .8rem; font-weight: 700; color: #00743F; display: flex; align-items: center; gap: .3rem; }
    [data-theme="dark"] .p3-act-week { color: #7fe0ac; }

    /* Activity rows */
    .p3-act-body  { max-height: 400px; overflow-y: auto; }
    .p3-act-row   { display: flex; align-items: center; gap: .75rem; padding: .75rem 1.1rem; border-top: 1px solid #e6f3ea; transition: background-color .15s ease; }
    .p3-act-row:hover { background: #f0f9f3; }
    [data-theme="dark"] .p3-act-row           { border-color: #1d2b24; }
    [data-theme="dark"] .p3-act-row:hover     { background: #1a2820; }
    .p3-act-check { color: #00A868; font-size: 1.1rem; flex: 0 0 auto; }
    .p3-act-info  { flex: 1 1 auto; min-width: 0; }
    .p3-act-time  { font-size: .72rem; color: #5a9e7a; }
    .p3-act-name  { font-weight: 600; font-size: .88rem; color: #00743F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    [data-theme="dark"] .p3-act-name { color: #7fe0ac; }
    .p3-act-see   { font-size: .75rem; color: #5a9e7a; white-space: nowrap; display: flex; align-items: center; gap: .2rem; flex: 0 0 auto; text-decoration: none; }
    .p3-act-see:hover { color: #00A868; }

    /* Stat pills inside overview */
    .p3-stat-row { display: flex; gap: .5rem; padding: .6rem 1.1rem; flex-wrap: wrap; }
    .p3-stat-pill { flex: 1 1 0; min-width: 0; background: #e6f3ea; border: 1px solid #d5e8db; border-radius: .65rem; padding: .55rem .7rem; text-align: center; }
    [data-theme="dark"] .p3-stat-pill { background: #0f1512; border-color: #1d2b24; }
    .p3-stat-pill-val  { font-weight: 800; font-size: 1.1rem; color: #00A868; }
    .p3-stat-pill-lbl  { font-size: .67rem; color: #5a9e7a; margin-top: .05rem; }
    [data-theme="dark"] .p3-stat-pill-val { color: #7fe0ac; }

    /* Create new button */
    .p3-create-btn { background: #00A868; color: #fff; border: none; border-radius: .75rem; font-weight: 700; font-size: .88rem; padding: .6rem 1.15rem; display: inline-flex; align-items: center; gap: .4rem; text-decoration: none; transition: background-color .15s ease; white-space: nowrap; }
    .p3-create-btn:hover { background: #00743F; color: #fff; }
</style>

<div class="p3-dash" id="p3Dashboard">

    {{-- ── Welcome header ── --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <div class="p3-welcome-title">Welcome, {{ strtok(auth()->user()->name, ' ') }} !</div>
            <div class="p3-welcome-sub">Let's take a look at your latest activity.</div>
        </div>
        <a href="{{ route('documents.upload') }}" class="p3-create-btn">
            Upload Document <i class="bi bi-plus-lg"></i>
        </a>
    </div>

    {{-- ── Two-column body ── --}}
    <div class="row g-3 align-items-start">

        {{-- ── LEFT: Docs Overview ── --}}
        <div class="col-12 col-lg-5">
            <div class="p3-panel">
                <div class="p3-ov-header">
                    <div>
                        <div class="p3-ov-title">Docs Overview</div>
                        <div class="p3-ov-sub">
                            Last updated:
                            @if($recent->first())
                                <strong>{{ $recent->first()->updated_at->diffForHumans() }}</strong>
                                <i class="bi bi-arrow-repeat ms-1" title="Auto-synced"></i>
                            @else
                                <strong>No documents yet</strong>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('documents.library') }}" class="text-muted" title="Go to library"><i class="bi bi-pencil-square"></i></a>
                </div>

                {{-- Stat pills --}}
                <div class="p3-stat-row">
                    @foreach([
                        ['val' => $stats['total'],     'lbl' => 'Total',    'color' => '#00A868'],
                        ['val' => $stats['completed'], 'lbl' => 'Done',     'color' => '#00743F'],
                        ['val' => $stats['pending'],   'lbl' => 'Pending',  'color' => '#fd7e14'],
                        ['val' => $stats['failed'],    'lbl' => 'Failed',   'color' => '#dc3545'],
                    ] as $s)
                    <div class="p3-stat-pill">
                        <div class="p3-stat-pill-val" style="color:{{ $s['color'] }}">{{ number_format($s['val']) }}</div>
                        <div class="p3-stat-pill-lbl">{{ $s['lbl'] }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- Preview: recent doc list --}}
                <div class="p3-ov-preview p-0" style="min-height:140px;">
                    @if($recent->isNotEmpty())
                    <div class="w-100">
                        @foreach($recent->take(4) as $doc)
                        <a href="{{ route('documents.show', $doc) }}" class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none border-bottom" style="border-color:#f0f0f0!important;transition:background-color .12s ease" onmouseover="this.style.background='#f0f9f3'" onmouseout="this.style.background=''">
                            <i class="bi bi-file-earmark-text" style="color:#00A868;font-size:1rem;flex:0 0 auto"></i>
                            <div class="min-w-0 flex-fill overflow-hidden">
                                <div class="fw-semibold small text-truncate" style="color:#111;max-width:100%">{{ $doc->title ?? $doc->original_filename }}</div>
                                <div style="font-size:.7rem;color:#aaa">{{ $doc->updated_at->format('M j, Y') }}</div>
                            </div>
                            <span class="badge rounded-pill px-2 py-1" style="font-size:.65rem;background:
                                @if($doc->processing_status === 'completed') #d4edda;color:#155724
                                @elseif($doc->processing_status === 'pending') #fff3cd;color:#856404
                                @else #f8d7da;color:#721c24 @endif
                            ">{{ ucfirst($doc->processing_status) }}</span>
                        </a>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center text-muted p-4">
                        <i class="bi bi-folder2-open fs-2 d-block mb-2 opacity-50"></i>
                        <span class="small">No documents uploaded yet.</span>
                    </div>
                    @endif
                </div>

                {{-- View all button --}}
                <a href="{{ route('documents.library') }}" class="p3-ov-view-btn">
                    View Document Library →
                </a>

                {{-- Meta info --}}
                <div class="p3-ov-meta">
                    <div class="p3-ov-meta-item">
                        <span class="p3-ov-meta-label"><i class="bi bi-building"></i> Department</span>
                        <span class="p3-ov-meta-val">
                            {{ auth()->user()->department?->name ?? 'All Departments' }}
                        </span>
                    </div>
                    <div class="p3-ov-meta-item">
                        <span class="p3-ov-meta-label"><i class="bi bi-person-badge"></i> Role</span>
                        <span class="p3-ov-meta-val">{{ ucfirst(auth()->user()->role ?? 'Employee') }}</span>
                    </div>
                    <div class="p3-ov-meta-item">
                        <span class="p3-ov-meta-label"><i class="bi bi-lightning-charge"></i> Lightning AI</span>
                        <span class="p3-ov-meta-val">
                            <a href="{{ route('chat.index') }}" class="text-decoration-none fw-semibold" style="color:#00A868">
                                {{ $recentConversations->count() }} conversation{{ $recentConversations->count() !== 1 ? 's' : '' }}
                            </a>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── RIGHT: Recent Activity ── --}}
        <div class="col-12 col-lg-7">
            <div class="p3-panel">
                <div class="p3-act-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="p3-act-title">Recent Activity</div>
                            <div class="p3-act-sub">Recent document changes history</div>
                        </div>
                        <button class="btn btn-sm p-1" style="background:none;border:none;color:#5a9e7a" title="More options"><i class="bi bi-three-dots-vertical"></i></button>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="p3-act-tabs">
                    <button class="p3-act-tab active" id="tabCompleted" onclick="switchTab('completed')">
                        <i class="bi bi-link-45deg me-1"></i>Completed Docs
                    </button>
                    <button class="p3-act-tab" id="tabPending" onclick="switchTab('pending')">
                        <i class="bi bi-hourglass-split me-1"></i>Pending Docs
                    </button>
                </div>

                {{-- This Week label --}}
                <div class="p3-act-week">
                    This Week <i class="bi bi-chevron-down" style="font-size:.7rem"></i>
                </div>

                {{-- Activity list --}}
                <div class="p3-act-body" id="actCompleted">
                    @forelse($recentActivity->filter(fn($a) => optional($a['document'])->processing_status === 'completed')->take(8) as $activity)
                    <div class="p3-act-row">
                        <div class="p3-act-check"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="p3-act-info">
                            <div class="p3-act-time">{{ $activity['created_at']->format('D, g:i A') }}</div>
                            <div class="p3-act-name">{{ $activity['document']->title ?? $activity['document']->original_filename }}</div>
                        </div>
                        <a href="{{ route('documents.show', $activity['document']) }}" class="p3-act-see">
                            See details <i class="bi bi-chevron-down"></i>
                        </a>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4 small">
                        <i class="bi bi-check-circle d-block fs-3 mb-2 opacity-40"></i>
                        No completed document activity this week.
                    </div>
                    @endforelse
                </div>

                <div class="p3-act-body d-none" id="actPending">
                    @forelse($recentActivity->filter(fn($a) => in_array(optional($a['document'])->processing_status, ['pending','processing']))->take(8) as $activity)
                    <div class="p3-act-row">
                        <div class="p3-act-check" style="color:#00A868"><i class="bi bi-hourglass-split"></i></div>
                        <div class="p3-act-info">
                            <div class="p3-act-time">{{ $activity['created_at']->format('D, g:i A') }}</div>
                            <div class="p3-act-name">{{ $activity['document']->title ?? $activity['document']->original_filename }}</div>
                        </div>
                        <a href="{{ route('documents.show', $activity['document']) }}" class="p3-act-see">
                            See details <i class="bi bi-chevron-down"></i>
                        </a>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4 small">
                        <i class="bi bi-hourglass d-block fs-3 mb-2 opacity-40"></i>
                        No pending documents.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function switchTab(tab) {
    document.getElementById('actCompleted').classList.toggle('d-none', tab !== 'completed');
    document.getElementById('actPending').classList.toggle('d-none', tab !== 'pending');
    document.getElementById('tabCompleted').classList.toggle('active', tab === 'completed');
    document.getElementById('tabPending').classList.toggle('active', tab === 'pending');
}
</script>
@endsection
