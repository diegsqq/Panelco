@php
    $selectedAccess = old('access_level', $selectedAccess ?? 'all_employees');
    $selectedUsers = array_map('intval', (array) old('user_ids', $selectedUsers ?? []));
    $accessLabels = [
        'private' => 'Only you',
        'link' => 'Anyone with link',
        'all_employees' => 'All employees',
    ];
@endphp
<style>
    .cat-access-card { border: 1px solid #dfe8e9; border-radius: .85rem; padding: 1rem 1.25rem; background: #fff; }
    [data-theme="dark"] .cat-access-card { background: #16211c; border-color: #26362e; }
    .cat-access-label { display: flex; align-items: center; gap: .5rem; font-weight: 600; font-size: .8rem; color: #1a1a2e; margin-bottom: .45rem; }
    [data-theme="dark"] .cat-access-label { color: #e5eee8; }
    .cat-access-btn { display: flex; align-items: center; justify-content: space-between; gap: .5rem; width: 100%; padding: 0; border: 0; background: transparent; color: inherit; font-size: .95rem; text-align: left; cursor: pointer; }
    .cat-access-btn:focus-visible { outline: 2px solid #00A868; outline-offset: 3px; border-radius: .25rem; }
    .cat-access-btn i.bi-chevron-down { font-size: .75rem; color: #5a9e7a; }
    .cat-access-hint { font-size: .7rem; color: #5a9e7a; margin-top: .4rem; }
    [data-theme="dark"] .cat-access-hint { color: #8fa59a; }
    .cat-access-option { display: flex; align-items: flex-start; gap: .7rem; padding: .7rem .85rem; border: 1px solid #dcebe1; border-radius: .65rem; cursor: pointer; background: #fff; transition: border-color .15s ease, background-color .15s ease; }
    .cat-access-option:hover { border-color: #00A868; }
    .cat-access-option:has(input:checked) { border-color: #00A868; background: #e6f3ea; box-shadow: 0 0 0 3px rgba(0,168,104,.08); }
    .cat-access-option input { margin-top: .2rem; accent-color: #00A868; }
    .cat-access-option strong { font-size: .86rem; color: #1a1a2e; }
    .cat-access-option small { font-size: .72rem; color: #5a9e7a; }
    [data-theme="dark"] .cat-access-option { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .cat-access-option:has(input:checked) { background: #12291d; border-color: #00A868; }
    [data-theme="dark"] .cat-access-option strong { color: #e5eee8; }
    [data-theme="dark"] .cat-access-option small { color: #8fa59a; }
    .cat-access-people { font-size: .78rem; color: #5a9e7a; }
    [data-theme="dark"] .cat-access-people { color: #8fa59a; }
    .cat-access-inline { flex: 0 1 230px; align-self: center; margin-bottom: 0; min-width: 0; }
    .cat-access-inline .cat-access-card { padding: .3rem .55rem; border-radius: .5rem; }
    .cat-access-inline .cat-access-label { font-size: .68rem; gap: .25rem; margin-bottom: .15rem; }
    .cat-access-inline .cat-access-label span.text-muted { display: none; }
    .cat-access-inline .cat-access-btn { font-size: .78rem; }
    .cat-access-inline .cat-access-btn i.bi-chevron-down { font-size: .65rem; }
    .cat-access-inline .cat-access-hint { display: none; }
</style>
<div class="{{ $wrapperClass ?? 'mb-3' }}">
    <div class="cat-access-card">
        <label class="cat-access-label" for="access_level">Access level <span class="text-muted fw-normal">Who can see this folder</span></label>
        <input type="hidden" id="accessLevelInput" name="access_level" value="{{ $selectedAccess }}">
        <button type="button" id="access_level" class="cat-access-btn" data-bs-toggle="modal" data-bs-target="#shareAccessModal" aria-label="Open share access settings">
            <span id="accessLevelLabel">{{ $accessLabels[$selectedAccess] ?? 'All employees' }}</span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
        <div class="cat-access-hint mt-2" id="accessLevelHint">{{ $accessLabels[$selectedAccess] ?? 'All employees' }}</div>
    </div>
</div>
<div class="modal fade" id="shareAccessModal" tabindex="-1" aria-labelledby="shareAccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content p3-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="shareAccessModalLabel"><i class="bi bi-share me-2" style="color:#00A868"></i>Share access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="small text-muted mb-3">Choose who can open this folder.</p>
                <div class="d-grid gap-2 mb-3">
                    @foreach($accessLabels as $value => $label)
                    <label class="cat-access-option">
                        <input type="radio" name="access_level_choice" value="{{ $value }}" @checked($selectedAccess === $value)>
                        <span>
                            <strong class="d-block">{{ $label }}</strong>
                            <small>{{ $label }}</small>
                        </span>
                    </label>
                    @endforeach
                </div>
                <label class="form-label small fw-semibold" for="sharedUserIds">Also share with specific employees <span class="text-muted fw-normal">(optional)</span></label>
                <select id="sharedUserIds" name="user_ids[]" class="form-select form-select-sm" multiple size="5">
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected(in_array($u->id, $selectedUsers, true))>{{ $u->name }} ({{ ucfirst($u->role) }})</option>
                    @endforeach
                </select>
                <div class="cat-access-people mt-2">Hold Ctrl/Cmd to select multiple employees.</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-p3" id="applyShareAccess">Apply sharing</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var input = document.getElementById('accessLevelInput');
    var label = document.getElementById('accessLevelLabel');
    var hint = document.getElementById('accessLevelHint');
    var modal = document.getElementById('shareAccessModal');
    var apply = document.getElementById('applyShareAccess');
    var labels = @json($accessLabels);
    if (!input || !label || !hint || !modal || !apply) return;

    function sync(value) {
        input.value = value;
        label.textContent = labels[value] || labels.all_employees;
        hint.textContent = labels[value] || labels.all_employees;
    }

    modal.addEventListener('show.bs.modal', function () {
        modal.querySelectorAll('input[name="access_level_choice"]').forEach(function (radio) {
            radio.checked = radio.value === input.value;
        });
    });
    apply.addEventListener('click', function () {
        var checked = modal.querySelector('input[name="access_level_choice"]:checked');
        sync(checked ? checked.value : 'all_employees');
        var instance = bootstrap.Modal.getInstance(modal);
        if (instance) instance.hide();
    });
})();
</script>
