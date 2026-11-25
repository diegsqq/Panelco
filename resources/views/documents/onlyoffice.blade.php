@extends('layouts.app')

@section('body-class', 'p3-no-scroll')
@section('full-screen', true)
@section('full-screen-col', 'col-12')

@section('content')
<div style="display:flex;flex-direction:column;height:100vh;overflow:hidden;background:#f5f5f5">
    <div style="flex:0 0 auto;display:flex;align-items:center;gap:.75rem;padding:.5rem 1rem;background:#fff;border-bottom:1px solid #e8e8e8">
        <a href="{{ route('documents.edit-content', $document) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i>Back to editor
        </a>
    </div>
    <div style="flex:1 1 auto;min-height:0;width:100%">
        <div id="onlyofficeEditor"></div>
    </div>
</div>

<script src="{{ $editorScript }}"></script>
<script>
(function () {
    var target = document.getElementById('onlyofficeEditor');
    if (typeof DocsAPI === 'undefined') {
        target.innerHTML = '<div style="padding:2rem;text-align:center;color:#b00">OnlyOffice Document Server is not reachable. Start it with <code>docker compose -f docker-compose.onlyoffice.yml up -d</code>.</div>';
        return;
    }
    var config = @json($config);
    config.token = @json($configToken);
    new DocsAPI.DocEditor('onlyofficeEditor', config);
})();
</script>
@endsection
