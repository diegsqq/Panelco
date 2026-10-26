@extends('layouts.app')

@section('content')
<style>
    .recent-row { transition: background-color .15s ease; }
    .recent-row:hover { background: #e6f3ea; }
    .recent-row + .recent-row { border-top: 0; }
    .recent-list .recent-row { flex: 1 0 auto; align-items: center; }
    .recent-list .recent-row:last-child { border-bottom: 0 !important; }

    /* Dashboard fits the viewport: page itself never scrolls,
       inner panels scroll instead. */
    .p3-dashboard { display: flex; flex-direction: column; gap: 1rem; overflow: hidden; }
    .p3-dashboard .row { flex: 1 1 auto; min-height: 0; margin: 0; }
    .p3-dashboard .row > [class*="col-"] { min-height: 0; }
    .p3-dashboard .col-lg-7 .card { height: 100%; }
    .p3-dashboard .col-lg-5 { overflow-y: auto; overflow-x: hidden; }
    /* Hard lock: page itself never scrolls on the dashboard. */
    html:has(#p3Dashboard), body:has(#p3Dashboard) { overflow: hidden; }
    /* Hide the white topbar strip above the Welcome back banner (dashboard only). */
    body:has(#p3Dashboard) .p3-topbar { display: none; }
    @media (max-width: 991.98px) {
        .p3-dashboard .row { overflow-y: auto; overflow-x: hidden; }
    }
</style>
<div class="p3-dashboard" id="p3Dashboard">
<div class="p-4 text-white d-flex flex-wrap justify-content-between align-items-center" style="background:linear-gradient(135deg,#00A868,#1DBF7D);border-radius:1rem">
    <div>
        <h3 class="fw-bold mb-0">Welcome back</h3>
    </div>
    <div class="d-flex gap-3 mt-2 mt-md-0">
        <a href="{{ route('documents.upload') }}" class="btn btn-lg" style="background:#ffffff;color:#00A868;font-weight:700"><i class="bi bi-cloud-upload me-1"></i>Upload</a>
        <a href="{{ route('chat.index') }}" class="btn btn-lg btn-outline-light"><i class="bi bi-chat-dots me-1"></i>Ask Lightning</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-p3 lift p-4 d-flex flex-column" style="min-height:0">
            <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0">Recently uploaded</h5><a href="{{ route('documents.library') }}" class="small">View library</a></div>
            <div class="recent-list flex-grow-1" style="min-height:0;display:flex;flex-direction:column;overflow-y:hidden;overflow-x:hidden;padding-right:4px">
            @forelse ($recent as $doc)
                @php
                    $mime = $doc->mime_type ?? '';
                    $docIcon = str_contains($mime, 'pdf') ? ['bi-file-earmark-pdf-fill', '#dc3545', '#fdeaea']
                        : (str_contains($mime, 'word') ? ['bi-file-earmark-word-fill', '#0d6efd', '#e7f0ff']
                        : (str_starts_with($mime, 'image/') ? ['bi-file-earmark-image-fill', '#00A868', '#e6f3ea']
                        : (str_contains($mime, 'text') ? ['bi-file-earmark-text-fill', '#6c757d', '#f1f3f5']
                        : ['bi-file-earmark-fill', '#00A868', '#e6f3ea'])));
                @endphp
                <a href="{{ route('documents.show', $doc) }}" class="d-flex gap-2 align-items-center recent-row text-decoration-none border-bottom py-1 px-2" style="border-color:#e8f0ea !important;border-radius:.5rem">
                    <span class="stat-icon flex-shrink-0" style="width:30px;height:30px;background:{{ $docIcon[2] }};color:{{ $docIcon[1] }};font-size:.85rem"><i class="bi {{ $docIcon[0] }}"></i></span>
                    <div class="flex-grow-1" style="min-width:0">
                        <div class="fw-semibold text-truncate" style="color:#00743F;font-size:.95rem">{{ $doc->title ?? $doc->original_filename }}</div>
                        <div class="text-truncate" style="font-size:.82rem;color:#7d8f86">{{ $doc->category?->name ?? 'Uncategorized' }}{{ $doc->department ? ' · '.$doc->department->name : '' }} · {{ $doc->created_at->diffForHumans() }}</div>
                        @if($doc->summary)<div class="text-truncate" style="font-size:.84rem;color:#9aa8a1">{{ $doc->summary }}</div>@endif
                    </div>
                    @unless($doc->processing_status === 'completed')<span class="badge status-{{ $doc->processing_status }} flex-shrink-0">{{ $doc->processing_status }}</span>@endunless
                    <i class="bi bi-chevron-right flex-shrink-0" style="font-size:.85rem;color:#b7c4bd"></i>
                </a>
            @empty
                <div class="text-center py-4"><i class="bi bi-inbox fs-2 text-muted"></i><p class="fw-semibold mt-2 mb-2">No documents yet.</p><a href="{{ route('documents.upload') }}" class="btn btn-sm" style="background:#00A868;color:#fff">Upload your first document</a></div>
            @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-5 d-flex flex-column gap-3">
        <div class="card card-p3 p-4 d-flex flex-column" style="min-height:0">
            <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold" style="color:#00A868">Category distribution</span>
            <span class="text-muted small">{{ $stats['total'] }} docs</span>
            </div>

            @if($categoryDistribution->isNotEmpty())
            <div style="width:100%;height:260px;">
                <div id="categoryLine" style="width:100%;height:100%;"></div>
            </div>
            @else
                <p class="text-muted mb-0">No categories yet.</p>
            @endif

        </div>
        <div class="card card-p3 p-4">
            <h5 class="fw-bold mb-2">Recent conversations</h5>
            @forelse ($recentConversations as $c)
                <div class="border-bottom py-2"><a href="{{ route('chat.show', $c) }}" class="text-decoration-none" style="color:#00743F;font-size:1rem">{{ $c->title }}</a> <span class="text-muted">· {{ $c->created_at->diffForHumans() }}</span></div>
            @empty
                <p class="text-muted mb-0">No conversations yet.</p>
            @endforelse
        </div>
    </div>
</div>
</div>
@if($categoryDistribution->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
<script>
(function () {
    var el = document.getElementById('categoryLine');
    if (!el) return;
    function init() {
        if (!el.offsetWidth || !el.offsetHeight) { setTimeout(init, 50); return; }
        var chart = echarts.init(el);
        var categories = @json($categoryDistribution->map(fn ($r) => $r->category?->name ?? 'Uncategorized')->values());
        var values = @json($categoryDistribution->pluck('total')->values());
        chart.setOption({
            tooltip: { trigger: 'axis', textStyle: { fontSize: 11 } },
            grid: { left: '3%', right: '4%', bottom: '10%', containLabel: true },
            xAxis: { type: 'category', data: categories, axisLabel: { show: false } },
            yAxis: { type: 'value' },
            series: [{
                type: 'line',
                data: values,
                smooth: true,
                symbol: 'circle',
                symbolSize: 8,
                showSymbol: false,
                emphasis: { focus: 'series', itemStyle: { color: '#00A868', borderColor: '#fff', borderWidth: 2, shadowBlur: 10, shadowColor: '#00A868' }, scale: 1.5 },
                lineStyle: { width: 2, color: '#00A868', shadowBlur: 8, shadowColor: 'rgba(0,168,104,0.5)' },
                itemStyle: { color: '#00A868' },
                areaStyle: { color: 'rgba(0,168,104,0.12)' }
            }]
        });
        window.addEventListener('resize', function () { chart.resize(); });
    }
    init();
})();
</script>
@endif
<script>
(function () {
    var dash = document.getElementById('p3Dashboard');
    if (!dash) return;
    // Lock the page: only inner panels scroll.
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
    window.scrollTo(0, 0);

    function fit() {
        var main = dash.closest('main');
        var rect = dash.getBoundingClientRect();
        var padBottom = main ? (parseFloat(getComputedStyle(main).paddingBottom) || 0) : 0;
        var available = window.innerHeight - rect.top - padBottom;
        dash.style.height = Math.max(360, Math.floor(available)) + 'px';

        var chartEl = document.getElementById('categorySankey');
        if (chartEl && typeof echarts !== 'undefined') {
            var inst = echarts.getInstanceByDom(chartEl);
            if (inst) inst.resize();
        }
    }

    fit();
    window.addEventListener('resize', fit);
    window.addEventListener('load', fit);
    // Re-run after the main fade-in animation settles.
    setTimeout(fit, 400);
})();
</script>
@endsection