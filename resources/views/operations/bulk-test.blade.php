@extends('layouts.app')

@section('title', 'DIDX — Bulk DID Testing')
@section('page-title', 'Bulk DID Testing')
@section('page-crumb', 'DIDX / Operations / Bulk DID Test')

@section('content')
<div class="flex flex-col flex-1 h-full min-h-0 overflow-hidden">
    <!-- 1. TOP TELEMETRY STRIP -->
    <div class="h-9 border-b border-[var(--border)] bg-[var(--surface2)] px-3 flex items-center justify-between flex-shrink-0">
        <!-- Stats Pills -->
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-[var(--surface)] border border-[var(--border)] font-mono text-xs">
                <i class="fa-solid fa-hashtag text-[10px] text-[var(--ink3)]"></i>
                <span class="text-[var(--ink3)] text-[10px]">TOTAL:</span>
                <span class="font-bold text-[var(--ink1)]" id="statTotal">{{ $totalCount }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 font-mono text-xs text-emerald-600 dark:text-emerald-400">
                <i class="fa-solid fa-circle-check text-[10px]"></i>
                <span class="text-[10px]">PASSED:</span>
                <span class="font-bold" id="statPass">{{ $passCount }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/20 font-mono text-xs text-rose-600 dark:text-rose-400">
                <i class="fa-solid fa-circle-xmark text-[10px]"></i>
                <span class="text-[10px]">FAILED:</span>
                <span class="font-bold" id="statFail">{{ $failCount }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-500/10 border border-slate-500/20 font-mono text-xs text-slate-500">
                <i class="fa-solid fa-hourglass-half text-[10px]"></i>
                <span class="text-[10px]">PENDING:</span>
                <span class="font-bold" id="statPending">{{ $pendingCount }}</span>
            </div>
        </div>

        <!-- Progress & Countdown Indicator -->
        <div class="flex items-center gap-3">
            <!-- 10-Second Interval Timer Badge -->
            <div id="timerBadge" class="hidden items-center gap-1 px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 font-mono text-xs font-bold animate-pulse">
                <i class="fa-solid fa-clock text-[10.5px]"></i>
                <span>Next in: <span id="countdownSec">10</span>s</span>
            </div>

            <!-- Progress Bar Strip -->
            <div id="progressContainer" class="hidden items-center gap-2">
                <div class="w-36 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div id="progressBar" class="h-full bg-emerald-500 transition-all duration-300" style="width:0%"></div>
                </div>
                <span class="font-mono text-xs font-bold text-emerald-600" id="progressPct">0%</span>
                <span class="font-mono text-[10.5px] text-[var(--ink3)]" id="progressText">Testing...</span>
            </div>

            <span class="text-[11px] font-mono text-[var(--ink3)]">
                Auto-Interval: 10s
            </span>
        </div>
    </div>

    <!-- SKIPPED / DUPLICATE DIDS WARNING LIST (IF ANY) -->
    @if(session('skipped_dids') && count(session('skipped_dids')) > 0)
        <div class="px-3 py-1 bg-amber-500/10 border-b border-amber-500/30 flex items-center justify-between text-xs font-mono text-amber-700 dark:text-amber-300">
            <div class="flex items-center gap-2 truncate">
                <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                <span class="font-bold">Skipped Duplicate DIDs:</span>
                <span class="truncate">
                    @foreach(session('skipped_dids') as $skipped)
                        <span class="font-semibold">{{ $skipped['phone'] }}</span> ({{ $skipped['reason'] }})@if(!$loop->last), @endif
                    @endforeach
                </span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-xs opacity-70 hover:opacity-100 bg-transparent border-none cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- 2. ACTION BAR (SINGLE HORIZONTAL COMPACT TOOLBAR) -->
    <div class="h-10 px-3 py-1.5 bg-[var(--surface)] border-b border-[var(--border)] flex items-center gap-2 flex-shrink-0 overflow-x-auto">
        <!-- Quick Add Single DID -->
        <form method="POST" action="{{ route('bulk-test.add-single') }}" class="flex items-center gap-1 m-0 flex-shrink-0">
            @csrf
            <div class="relative flex items-center">
                <i class="fa-solid fa-plus text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
                <input type="text" name="phone_number" placeholder="DID (e.g. 44987654320)" required
                       class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-amber-500 w-44 transition-all">
            </div>
            <button type="submit" class="btn-dense btn-dense-primary" title="Add Single DID">
                <i class="fa-solid fa-plus text-[10px]"></i> <span>Add</span>
            </button>
        </form>

        <!-- Bulk CSV Upload Modal Trigger -->
        <button type="button" onclick="document.getElementById('uploadModal').classList.remove('hidden'); document.getElementById('uploadModal').classList.add('flex')" class="btn-dense btn-dense-ghost flex-shrink-0" title="Upload CSV or TXT file">
            <i class="fa-solid fa-file-arrow-up text-[10px] text-sky-500"></i> <span>Upload File</span>
        </button>

        <div class="h-4 w-[1px] bg-[var(--border)] flex-shrink-0"></div>

        <!-- Filter Search Box -->
        <div class="relative flex items-center flex-1 min-w-[150px] max-w-xs">
            <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
            <input type="text" id="bulkSearchInput" placeholder="Filter DIDs or IPs..."
                   class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-amber-500 w-full transition-all"
                   oninput="filterBulkGrid()">
        </div>

        <!-- Status Filter Pills -->
        <div class="flex items-center bg-[var(--surface2)] p-0.5 rounded border border-[var(--border)] flex-shrink-0" id="bulkStatusFilterPills">
            <button type="button" class="bulk-status-btn active px-2 py-0.5 rounded text-[10px] font-mono font-bold" data-status="all">ALL</button>
            <button type="button" class="bulk-status-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400" data-status="pass">PASS</button>
            <button type="button" class="bulk-status-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-rose-600 dark:text-rose-400" data-status="fail">FAIL</button>
            <button type="button" class="bulk-status-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-slate-500" data-status="pending">PENDING</button>
        </div>

        <div class="flex-1"></div>

        <!-- Dialing & Execution Actions -->
        <div class="flex items-center gap-1.5 flex-shrink-0">
            <!-- Start / Pause Bulk Dial Buttons -->
            <button type="button" id="startBulkBtn" class="btn-dense btn-dense-ok" onclick="startBulkTest()" @if($totalCount === 0) disabled style="opacity:.4;cursor:not-allowed" @endif title="Start automated sequential test">
                <i class="fa-solid fa-play text-[10px]"></i> <span>Start Test</span>
            </button>
            <button type="button" id="pauseBulkBtn" class="btn-dense btn-dense-ghost text-amber-500 border-amber-500/40 hidden" onclick="pauseBulkTest()">
                <i class="fa-solid fa-pause text-[10px]"></i> <span>Pause</span>
            </button>

            <!-- Reset All -->
            <form method="POST" action="{{ route('bulk-test.reset-all') }}" class="m-0" onsubmit="return confirm('Reset status of ALL DIDs to Pending?')">
                @csrf
                <button type="submit" class="btn-dense btn-dense-ghost" title="Reset all to pending">
                    <i class="fa-solid fa-rotate-left text-[10px]"></i> <span>Reset All</span>
                </button>
            </form>

            <!-- Export Excel/CSV -->
            <a href="{{ route('bulk-test.export') }}" class="btn-dense bg-emerald-700 hover:bg-emerald-800 text-white border-none" title="Download Report">
                <i class="fa-solid fa-file-excel text-[10px]"></i> <span>Export</span>
            </a>

            <!-- Clear All -->
            <form method="POST" action="{{ route('bulk-test.clear-all') }}" class="m-0" onsubmit="return confirm('Delete ALL DIDs from bulk test list?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-dense btn-dense-ghost hover:text-red-500 hover:border-red-400" title="Delete all records">
                    <i class="fa-solid fa-trash-can text-[10px]"></i> <span>Clear</span>
                </button>
            </form>

            <span class="text-[11px] font-mono text-[var(--ink3)] ml-1 border-l border-[var(--border)] pl-2" id="badgeTotalCount">
                {{ $totalCount }} DIDs
            </span>
        </div>
    </div>

    <!-- 3. HIGH-DENSITY DATA GRID (~34px ROW HEIGHT, STICKY HEADER) -->
    <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative bg-[var(--surface)]">
        <table class="w-full table-fixed text-xs border-collapse text-left select-text">
            <colgroup>
                <col class="w-[4%]">
                <col class="w-[26%]">
                <col class="w-[26%]">
                <col class="w-[14%]">
                <col class="w-[18%]">
                <col class="w-[12%]">
            </colgroup>
            <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 shadow-xs">
                <tr class="h-8 text-[11px] font-mono font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">#</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">DID / Phone Number</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Source IP / Host</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70 text-center">Status</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Last Checked</th>
                    <th class="py-2 px-3 text-right">Action Controls</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="bulkGridTbody">
                @php $serialNumber = $totalCount; @endphp
                @forelse(($dids ?? $bulkDids ?? []) as $did)
                    @php
                        $status = !empty($did->status) ? strtolower(trim($did->status)) : 'pending';
                        if (!in_array($status, ['pass', 'fail', 'dialing'])) $status = 'pending';
                        $sourceIp = $did->source_ip ?? '—';
                    @endphp
                    <tr data-id="{{ $did->id }}"
                        data-status="{{ $status }}"
                        data-did="{{ $did->phone_number }}"
                        data-ip="{{ $sourceIp }}"
                        class="did-row h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors"
                        style="border-left:3px solid {{ $status === 'pass' ? 'var(--ok)' : ($status === 'fail' ? 'var(--danger)' : ($status === 'dialing' ? 'var(--violet)' : 'transparent')) }}">
                        <!-- Serial -->
                        <td class="py-2 px-3 text-center font-mono text-[11px] text-[var(--ink3)] border-r border-slate-100 dark:border-slate-800/60">
                            {{ $serialNumber-- }}
                        </td>

                        <!-- DID Number -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 truncate">
                            <span class="font-mono tracking-tight font-semibold text-xs text-[var(--ink1)]">
                                {{ $did->phone_number }}
                            </span>
                        </td>

                        <!-- Source IP -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 truncate">
                            <span class="font-mono tracking-tight font-semibold text-xs text-[var(--ink2)] did-source">
                                {{ $sourceIp }}
                            </span>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-2 px-3 text-center border-r border-slate-100 dark:border-slate-800/60">
                            <span class="spill s-{{ $status }}">
                                <span class="sdot"></span>
                                <span class="status-text">{{ ucfirst($status) }}</span>
                            </span>
                        </td>

                        <!-- Timestamp -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-[11px] font-mono text-[var(--ink3)]">
                            {{ $did->checked_at ? $did->checked_at->format('Y-m-d H:i:s') : '—' }}
                        </td>

                        <!-- Action Controls (Anchored Right) -->
                        <td class="py-2 px-3 text-right">
                            <div class="inline-flex items-center justify-end gap-1">
                                <button type="button" class="btn-dense btn-dense-amber px-1.5" onclick="dialSingleDID({{ $did->id }})" title="Dial this DID now">
                                    <i class="fa-solid fa-phone text-[9.5px]"></i>
                                </button>
                                <button type="button" class="btn-dense btn-dense-ghost px-1.5" onclick="resetSingleDID({{ $did->id }})" title="Reset to pending">
                                    <i class="fa-solid fa-rotate-left text-[9.5px]"></i>
                                </button>
                                <button type="button" class="btn-dense btn-dense-del px-1.5" onclick="deleteSingleDID({{ $did->id }})" title="Remove DID">
                                    <i class="fa-solid fa-trash-can text-[9.5px]"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="emptyBulkRow">
                        <td colspan="6" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">
                            <i class="fa-solid fa-list-check text-lg mb-2 block opacity-40"></i>
                            No DIDs in bulk test queue. Add a single DID or upload a CSV above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- UPLOAD MODAL -->
<div id="uploadModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 hidden items-center justify-center">
    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-6 max-w-md w-[90%] shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-[var(--bordersoft)] pb-3">
            <div class="flex items-center gap-2 font-disp font-bold text-sm text-[var(--ink1)]">
                <i class="fa-solid fa-file-csv text-sky-500"></i>
                <span>Upload Bulk DIDs File</span>
            </div>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden'); document.getElementById('uploadModal').classList.remove('flex')" class="text-[var(--ink3)] hover:text-[var(--ink1)] bg-transparent border-none cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('bulk-test.upload') }}" enctype="multipart/form-data" class="space-y-4 m-0">
            @csrf
            <div>
                <label class="text-xs font-mono text-[var(--ink2)] block mb-2">Select CSV, TXT, or Excel file (1 DID per line):</label>
                <input type="file" name="did_file" required accept=".csv,.txt,.xlsx,.xls"
                       class="w-full text-xs font-mono border border-dashed border-[var(--border)] rounded-md p-3 bg-[var(--surface2)] text-[var(--ink1)]">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-[var(--bordersoft)]">
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden'); document.getElementById('uploadModal').classList.remove('flex')" class="btn-dense btn-dense-ghost h-8 px-4 text-xs font-semibold">
                    Cancel
                </button>
                <button type="submit" class="btn-dense btn-dense-primary h-8 px-5 text-xs font-bold">
                    <i class="fa-solid fa-cloud-arrow-up mr-1 text-[11px]"></i> Upload DIDs
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
var currentQueue   = [];
var isTestRunning  = false;
var timerInterval  = null;
var completedCount = 0;
var totalToTest    = 0;
var csrfToken      = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute
    ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    : '{{ csrf_token() }}';

// Real-Time Grid Filtering
function filterBulkGrid(){
    var searchVal = (document.getElementById('bulkSearchInput').value || '').toLowerCase().trim();
    var activeStatusBtn = document.querySelector('#bulkStatusFilterPills .bulk-status-btn.active');
    var statusFilter = activeStatusBtn ? activeStatusBtn.getAttribute('data-status') : 'all';

    var rows = document.querySelectorAll('#bulkGridTbody tr.did-row');
    var visibleCount = 0;

    rows.forEach(function(row){
        var did = (row.getAttribute('data-did') || '').toLowerCase();
        var ip = (row.getAttribute('data-ip') || '').toLowerCase();
        var rowStatus = (row.getAttribute('data-status') || '').toLowerCase();

        var matchesSearch = !searchVal || did.includes(searchVal) || ip.includes(searchVal);
        var matchesStatus = (statusFilter === 'all') || (rowStatus === statusFilter);

        if(matchesSearch && matchesStatus){
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    var countElem = document.getElementById('badgeTotalCount');
    if(countElem) countElem.textContent = visibleCount + ' DIDs';
}

(function initStatusFilter(){
    var buttons = document.querySelectorAll('#bulkStatusFilterPills .bulk-status-btn');
    buttons.forEach(function(btn){
        btn.addEventListener('click', function(){
            buttons.forEach(function(b){
                b.classList.remove('active', 'bg-[var(--surface)]', 'text-[var(--ink1)]', 'shadow-xs');
            });
            btn.classList.add('active', 'bg-[var(--surface)]', 'shadow-xs');
            filterBulkGrid();
        });
    });
})();

function updateRowStatus(row, status, labelText, sourceIp) {
    status = String(status || 'pending').toLowerCase().trim();
    row.setAttribute('data-status', status);

    var borderColors = { pass:'var(--ok)', fail:'var(--danger)', dialing:'var(--violet)', pending:'transparent' };
    row.style.borderLeftColor = borderColors[status] || 'transparent';

    var sourceElem = row.querySelector('.did-source');
    if (sourceElem && sourceIp && sourceIp !== 'null') {
        sourceElem.textContent = sourceIp;
        row.setAttribute('data-ip', sourceIp);
    }

    var spill = row.querySelector('.spill');
    var textElem = row.querySelector('.status-text');

    if (spill && textElem) {
        spill.className = 'spill s-' + status;
        textElem.textContent = (labelText || status).toUpperCase();
    }
}

function recalculateStats() {
    var rows    = document.querySelectorAll('.did-row');
    var total   = rows.length;
    var pass    = document.querySelectorAll('.did-row[data-status="pass"]').length;
    var fail    = document.querySelectorAll('.did-row[data-status="fail"]').length;
    var pending = total - pass - fail;
    document.getElementById('statTotal').textContent   = total;
    document.getElementById('statPass').textContent    = pass;
    document.getElementById('statFail').textContent    = fail;
    document.getElementById('statPending').textContent = pending;
}

function updateProgress() {
    var pct = totalToTest > 0 ? Math.round((completedCount / totalToTest) * 100) : 0;
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressPct').textContent = pct + '%';
    document.getElementById('progressText').innerHTML =
        '<i class="fa-solid fa-phone text-[10px]"></i> ' + completedCount + '/' + totalToTest;
}

/* ─── Dial Engine ─────────────────────────────────────────── */
function startBulkTest() {
    var rows = Array.from(document.querySelectorAll('.did-row'));
    if (!rows.length) return;

    currentQueue  = rows.map(function(r) { return parseInt(r.getAttribute('data-id')); });
    isTestRunning = true;
    totalToTest   = currentQueue.length;
    completedCount = 0;

    document.getElementById('startBulkBtn').classList.add('hidden');
    document.getElementById('pauseBulkBtn').classList.remove('hidden');
    document.getElementById('progressContainer').classList.remove('hidden');
    document.getElementById('progressContainer').classList.add('flex');

    updateProgress();
    processNextQueueItem();
}

function pauseBulkTest() {
    isTestRunning = false;
    if (timerInterval) clearInterval(timerInterval);
    document.getElementById('timerBadge').classList.add('hidden');
    document.getElementById('timerBadge').classList.remove('flex');
    var startBtn = document.getElementById('startBulkBtn');
    startBtn.classList.remove('hidden');
    startBtn.innerHTML = '<i class="fa-solid fa-play text-[10px]"></i> <span>Resume</span>';
    document.getElementById('pauseBulkBtn').classList.add('hidden');
}

function finishBulkTest() {
    isTestRunning = false;
    if (timerInterval) clearInterval(timerInterval);
    document.getElementById('timerBadge').classList.add('hidden');
    document.getElementById('timerBadge').classList.remove('flex');
    var startBtn = document.getElementById('startBulkBtn');
    startBtn.classList.remove('hidden');
    startBtn.innerHTML = '<i class="fa-solid fa-circle-check text-[10px]"></i> <span>Finished</span>';
    document.getElementById('pauseBulkBtn').classList.add('hidden');
}

function processNextQueueItem() {
    if (!isTestRunning || !currentQueue.length) { finishBulkTest(); return; }

    var didId = currentQueue.shift();
    var row   = document.querySelector('.did-row[data-id="' + didId + '"]');
    if (!row) { processNextQueueItem(); return; }

    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    updateRowStatus(row, 'dialing', 'DIALING', null);
    document.getElementById('timerBadge').classList.add('hidden');
    document.getElementById('timerBadge').classList.remove('flex');

    fetch('{{ url("/bulk-test/dial") }}/' + didId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept':        'application/json',
            'X-CSRF-TOKEN':  csrfToken
        }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.did) {
            updateRowStatus(row, data.did.status, data.did.status.toUpperCase(), data.did.source_ip);
        } else {
            updateRowStatus(row, 'fail', 'FAIL', null);
        }
        recalculateStats();
        completedCount++;
        updateProgress();

        if (currentQueue.length && isTestRunning) {
            start10SecTimer(processNextQueueItem);
        } else {
            finishBulkTest();
        }
    })
    .catch(function(err) {
        console.error('Dial error:', err);
        updateRowStatus(row, 'fail', 'FAIL', null);
        recalculateStats();
        completedCount++;
        updateProgress();
        if (currentQueue.length && isTestRunning) {
            start10SecTimer(processNextQueueItem);
        } else {
            finishBulkTest();
        }
    });
}

function start10SecTimer(onComplete) {
    var secondsLeft = 10;
    var timerBadge  = document.getElementById('timerBadge');
    var countdownSec = document.getElementById('countdownSec');
    timerBadge.classList.remove('hidden');
    timerBadge.classList.add('flex');
    countdownSec.textContent = secondsLeft;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(function() {
        if (!isTestRunning) { clearInterval(timerInterval); timerBadge.classList.add('hidden'); timerBadge.classList.remove('flex'); return; }
        secondsLeft--;
        countdownSec.textContent = secondsLeft;
        if (secondsLeft <= 0) {
            clearInterval(timerInterval);
            timerBadge.classList.add('hidden');
            timerBadge.classList.remove('flex');
            if (onComplete) onComplete();
        }
    }, 1000);
}

function dialSingleDID(id) {
    var row = document.querySelector('.did-row[data-id="' + id + '"]');
    if (!row) return;
    updateRowStatus(row, 'dialing', 'DIALING', null);

    fetch('{{ url("/bulk-test/dial") }}/' + id, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.did) {
            updateRowStatus(row, data.did.status, data.did.status.toUpperCase(), data.did.source_ip);
        } else {
            updateRowStatus(row, 'fail', 'FAIL', null);
        }
        recalculateStats();
    })
    .catch(function() {
        updateRowStatus(row, 'fail', 'FAIL', null);
        recalculateStats();
    });
}

function resetSingleDID(id) {
    var row = document.querySelector('.did-row[data-id="' + id + '"]');
    if (!row) return;
    fetch('{{ url("/bulk-test/reset") }}/' + id, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) { updateRowStatus(row, 'pending', 'PENDING', '—'); recalculateStats(); }
    })
    .catch(function(err) { console.error('Reset error:', err); });
}

function deleteSingleDID(id) {
    if (!confirm('Remove this DID from bulk test list?')) return;
    var row = document.querySelector('.did-row[data-id="' + id + '"]');
    if (!row) return;
    fetch('{{ url("/bulk-test") }}/' + id, {
        method: 'DELETE',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(function(res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    })
    .then(function(data) {
        if (data.success) {
            row.remove();
            recalculateStats();
            var countElem = document.getElementById('badgeTotalCount');
            if (countElem) countElem.textContent = document.querySelectorAll('.did-row').length + ' DIDs';
        }
    })
    .catch(function(err) { console.error('Delete error:', err); });
}

function autoPollBulkStatuses() {
    if (isTestRunning) return;
    fetch('{{ route("api.bulk-status") }}', { cache: 'no-store' })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        document.querySelectorAll('.did-row').forEach(function(row) {
            var info = data[row.getAttribute('data-id')];
            if (!info) return;
            var newStatus = String(info.status || 'pending').toLowerCase().trim();
            var currentStatus = row.getAttribute('data-status');
            if (currentStatus !== newStatus) {
                updateRowStatus(row, newStatus, newStatus.toUpperCase(), info.source_ip || '—');
            }
        });
        recalculateStats();
    })
    .catch(function() {});
}
setInterval(autoPollBulkStatuses, 3000);
</script>
@endsection
