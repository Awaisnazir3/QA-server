@extends('layouts.app')

@section('title', 'DIDX — Bulk DID Testing')
@section('page-title', 'Bulk DID Testing')
@section('page-crumb', 'DIDX / Operations / Bulk DID Test')

@section('content')
<!-- STATS BAR -->
<div class="statrow">
    <div class="stat-card sc-primary">
        <div class="stat-icon"><i class="fa-solid fa-hashtag"></i></div>
        <div><div class="stat-lbl">Total DIDs Added</div><div class="stat-val" id="statTotal">{{ $totalCount }}</div></div>
    </div>
    <div class="stat-card sc-teal">
        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="stat-lbl">Passed DIDs</div><div class="stat-val" id="statPass" style="color:var(--ok)">{{ $passCount }}</div></div>
    </div>
    <div class="stat-card sc-amber">
        <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
        <div><div class="stat-lbl">Failed DIDs</div><div class="stat-val" id="statFail" style="color:var(--danger)">{{ $failCount }}</div></div>
    </div>
    <div class="stat-card sc-violet">
        <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
        <div><div class="stat-lbl">Pending DIDs</div><div class="stat-val" id="statPending">{{ $pendingCount }}</div></div>
    </div>
</div>

<!-- ADD DIDS SECTION -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;align-items:stretch">
    <!-- MANUAL SINGLE DID ADDITION -->
    <div class="card" style="margin-bottom:0;padding:14px 18px;display:flex;flex-direction:column;justify-content:space-between">
        <div class="card-head" style="margin-bottom:10px;padding-bottom:8px">
            <div class="card-title" style="font-size:13px"><i class="fa-solid fa-plus-circle"></i>Add Single DID Manually</div>
            <div class="cbadge"><i class="fa-solid fa-keyboard"></i> Quick Entry</div>
        </div>
        <form method="POST" action="{{ route('bulk-test.add-single') }}" style="display:flex;flex-direction:column;gap:8px">
            @csrf
            <div style="display:flex;gap:8px;align-items:center">
                <input type="text" name="phone_number" placeholder="e.g. 44987654320" style="flex:1;height:32px;padding:0 10px;border:1px solid var(--border);border-radius:5px;background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none" required>
                <button type="submit" class="btn-primary" style="height:32px;padding:0 14px;white-space:nowrap"><i class="fa-solid fa-plus"></i> Add DID</button>
            </div>
            <div style="font-size:10.5px;color:var(--ink3);font-family:var(--mono)">
                Adds DID to the Bulk Test list in Pending status.
            </div>
        </form>
    </div>

    <!-- BULK FILE UPLOAD -->
    <div class="card" style="margin-bottom:0;padding:14px 18px;display:flex;flex-direction:column;justify-content:space-between">
        <div class="card-head" style="margin-bottom:10px;padding-bottom:8px">
            <div class="card-title" style="font-size:13px"><i class="fa-solid fa-file-csv"></i>Upload Bulk DIDs File</div>
            <div class="cbadge"><i class="fa-solid fa-file-arrow-up"></i> CSV / TXT</div>
        </div>
        <form method="POST" action="{{ route('bulk-test.upload') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:8px">
            @csrf
            <div style="display:flex;gap:8px;align-items:center">
                <input type="file" name="did_file" id="did_file" accept=".csv,.txt,.xlsx,.xls" style="flex:1;height:32px;padding:4px 8px;border:1px dashed var(--border);border-radius:5px;background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:11.5px;outline:none;cursor:pointer" required>
                <button type="submit" class="btn-primary" style="height:32px;padding:0 14px;white-space:nowrap"><i class="fa-solid fa-cloud-arrow-up"></i> Upload</button>
            </div>
            <div style="font-size:10.5px;color:var(--ink3);font-family:var(--mono)">
                Accepts CSV or TXT file with 1 DID per line.
            </div>
        </form>
    </div>
</div>

<!-- SKIPPED / DUPLICATE DIDS WARNING LIST -->
@if(session('skipped_dids') && count(session('skipped_dids')) > 0)
    <div style="background:var(--amber-dim);border:1px solid rgba(221,139,10,.25);border-radius:6px;padding:10px 14px;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--amber);margin-bottom:6px">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>The following DIDs already exist and were skipped:</span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            @foreach(session('skipped_dids') as $skipped)
                <div style="font-family:var(--mono);font-size:11px;padding:3px 8px;background:var(--surface);border:1px solid rgba(221,139,10,.25);border-radius:4px;color:var(--ink1)">
                    <strong>{{ $skipped['phone'] }}</strong>
                    <span style="color:var(--amber);margin-left:3px">({{ $skipped['reason'] }})</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- SECTION HEADER -->
<div class="slabel"><i class="fa-solid fa-microchip"></i>Bulk Test Diagnostics (10s Interval Dialing)</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:var(--surface)">
        <div class="card-title" style="font-size:13.5px"><i class="fa-solid fa-list-check"></i>Bulk DIDs List</div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <!-- Start / Pause Dial Buttons -->
            <button type="button" id="startBulkBtn" class="btn-primary" onclick="startBulkTest()" @if($totalCount === 0) disabled @endif>
                <i class="fa-solid fa-play"></i> Start Bulk Dial Test
            </button>

            <button type="button" id="pauseBulkBtn" class="btn-sm btn-reset" style="display:none" onclick="pauseBulkTest()">
                <i class="fa-solid fa-pause"></i> Pause Test
            </button>

            <!-- 10-Second Timer Countdown Badge -->
            <span id="timerBadge" style="display:none;font-family:var(--mono);font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:4px;background:var(--violet-dim);color:var(--violet);border:1px solid rgba(133,70,232,.3)">
                <i class="fa-solid fa-stopwatch fa-spin"></i> Next call: <span id="countdownSec">10</span>s
            </span>

            <!-- Excel Export -->
            <a href="{{ route('bulk-test.export') }}" class="btn-excel">
                <i class="fa-solid fa-file-excel"></i> Export Report
            </a>

            <!-- Reset All -->
            <form method="POST" action="{{ route('bulk-test.reset-all') }}" style="margin:0">
                @csrf
                <button type="submit" class="btn-sm btn-reset"><i class="fa-solid fa-rotate-left"></i> Reset All</button>
            </form>

            <!-- Clear All -->
            <form method="POST" action="{{ route('bulk-test.clear-all') }}" style="margin:0" onsubmit="return confirm('Delete ALL bulk DIDs?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-sm btn-del"><i class="fa-solid fa-trash-can"></i> Clear All</button>
            </form>

            <div class="cbadge" id="badgeTotalCount">{{ $totalCount }} entries</div>
        </div>
    </div>

    <!-- PROGRESS BAR -->
    <div id="progressContainer" style="display:none;padding:12px 18px 0">
        <div style="display:flex;justify-content:space-between;font-size:11px;font-family:var(--mono);color:var(--ink2);margin-bottom:4px">
            <span id="progressText"><i class="fa-solid fa-phone"></i> Dialing in progress...</span>
            <span id="progressPct">0%</span>
        </div>
        <div style="height:6px;background:var(--surface2);border-radius:3px;overflow:hidden;border:1px solid var(--border)">
            <div id="progressBar" style="height:100%;width:0%;background:var(--primary);transition:width .4s ease"></div>
        </div>
    </div>

    <!-- TABLE -->
    <div style="overflow-x:auto">
        <table class="table-compact">
            <thead>
                <tr>
                    <th style="width:50px;text-align:center">#</th>
                    <th style="width:38%;text-align:left">DID / Source IP</th>
                    <th style="width:32%;text-align:left">Status</th>
                    <th style="width:26%;text-align:right">Action</th>
                </tr>
            </thead>
            <tbody id="didTableBody">
                @php $serialNumber = $totalCount; @endphp
                @forelse($dids as $did)
                    @php
                        $status = strtolower(trim($did->status ?? 'pending'));
                        if (!in_array($status, ['pass', 'fail', 'dialing'])) $status = 'pending';
                    @endphp
                    <tr class="did-row" data-id="{{ $did->id }}" data-status="{{ $status }}">
                        <td style="text-align:center;color:var(--ink3);font-family:var(--mono);font-size:11px">{{ $serialNumber-- }}</td>
                        <td style="text-align:left">
                            <div style="display:flex;flex-direction:column;gap:2px">
                                <div class="did-phone" style="font-family:var(--mono);font-weight:700;color:var(--ink1);font-size:12.5px">{{ $did->phone_number }}</div>
                                <div class="did-source" style="font-family:var(--mono);font-size:10.5px;color:var(--ink3)">{{ $did->source_ip ?? '—' }}</div>
                            </div>
                        </td>
                        <td style="text-align:left">
                            <span class="spill s-{{ $status }}">
                                <span class="sdot"></span>
                                <span class="status-text">{{ ucfirst($status) }}</span>
                            </span>
                        </td>
                        <td style="text-align:right">
                            <div style="display:inline-flex;align-items:center;justify-content:flex-end;gap:4px">
                                <button type="button" class="btn-sm btn-reset" onclick="resetSingleDID({{ $did->id }})" title="Reset">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                                <button type="button" class="btn-sm btn-del" onclick="deleteSingleDID({{ $did->id }})" title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:28px;text-align:center;color:var(--ink3);font-family:var(--mono);font-size:12px">
                            No DIDs added yet. Add a single DID or upload a CSV/TXT file above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
var isTestRunning = false;
var timerInterval = null;
var currentQueue = [];
var completedCount = 0;
var totalToTest = 0;
var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute
    ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    : '{{ csrf_token() }}';

/* ─── Helpers ─────────────────────────────────────────────── */

function updateRowStatus(row, status, labelText, sourceIp) {
    status = String(status || 'pending').toLowerCase().trim();
    row.setAttribute('data-status', status);

    var borderColors = { pass:'var(--ok)', fail:'var(--danger)', dialing:'var(--violet)', pending:'var(--grey)' };
    row.style.borderLeftColor = borderColors[status] || 'var(--grey)';

    var sourceElem = row.querySelector('.did-source');
    if (sourceElem && sourceIp && sourceIp !== 'null') sourceElem.textContent = sourceIp;

    var spill = row.querySelector('.spill');
    var textElem = row.querySelector('.status-text');
    var dot = row.querySelector('.sdot');

    if (spill && textElem) {
        spill.className = 'spill s-' + status;
        textElem.textContent = (labelText || status).toUpperCase();
        var bg   = { pass:'var(--ok-dim)', fail:'var(--danger-dim)', dialing:'var(--violet-dim)', pending:'var(--grey-dim)' };
        var col  = { pass:'var(--ok)',     fail:'var(--danger)',      dialing:'var(--violet)',      pending:'var(--grey)'     };
        var glow = { pass:'0 0 6px var(--ok)', fail:'0 0 6px var(--danger)', dialing:'0 0 6px var(--violet)', pending:'none' };
        spill.style.background = bg[status]  || bg.pending;
        spill.style.color      = col[status] || col.pending;
        if (dot) dot.style.boxShadow = glow[status] || 'none';
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
        '<i class="fa-solid fa-phone"></i> Tested ' + completedCount + ' of ' + totalToTest + ' DIDs…';
}

/* ─── Dial engine ─────────────────────────────────────────── */

function startBulkTest() {
    var rows = Array.from(document.querySelectorAll('.did-row'));
    if (!rows.length) return;

    currentQueue  = rows.map(function(r) { return parseInt(r.getAttribute('data-id')); });
    isTestRunning = true;
    totalToTest   = currentQueue.length;
    completedCount = 0;

    document.getElementById('startBulkBtn').style.display = 'none';
    document.getElementById('pauseBulkBtn').style.display = 'inline-flex';
    document.getElementById('progressContainer').style.display = 'block';

    updateProgress();
    processNextQueueItem();
}

function pauseBulkTest() {
    isTestRunning = false;
    if (timerInterval) clearInterval(timerInterval);
    document.getElementById('timerBadge').style.display = 'none';
    document.getElementById('startBulkBtn').style.display = 'inline-flex';
    document.getElementById('startBulkBtn').innerHTML = '<i class="fa-solid fa-play"></i> Resume Bulk Dial Test';
    document.getElementById('pauseBulkBtn').style.display = 'none';
}

function finishBulkTest() {
    isTestRunning = false;
    if (timerInterval) clearInterval(timerInterval);
    document.getElementById('timerBadge').style.display = 'none';
    document.getElementById('startBulkBtn').style.display = 'inline-flex';
    document.getElementById('startBulkBtn').innerHTML = '<i class="fa-solid fa-circle-check"></i> Bulk Test Completed';
    document.getElementById('pauseBulkBtn').style.display = 'none';
}

function processNextQueueItem() {
    if (!isTestRunning || !currentQueue.length) { finishBulkTest(); return; }

    var didId = currentQueue.shift();
    var row   = document.querySelector('.did-row[data-id="' + didId + '"]');
    if (!row) { processNextQueueItem(); return; }

    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    updateRowStatus(row, 'dialing', 'DIALING', null);
    document.getElementById('timerBadge').style.display = 'none';

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
    timerBadge.style.display = 'inline-flex';
    countdownSec.textContent = secondsLeft;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(function() {
        if (!isTestRunning) { clearInterval(timerInterval); timerBadge.style.display = 'none'; return; }
        secondsLeft--;
        countdownSec.textContent = secondsLeft;
        if (secondsLeft <= 0) {
            clearInterval(timerInterval);
            timerBadge.style.display = 'none';
            if (onComplete) onComplete();
        }
    }, 1000);
}

/* ─── Row actions ─────────────────────────────────────────── */

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
            if (countElem) countElem.textContent = document.querySelectorAll('.did-row').length + ' entries';
        }
    })
    .catch(function(err) { console.error('Delete error:', err); });
}

/* ─── 3-second status poll (reads bulk_dids only) ─────────── */
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
