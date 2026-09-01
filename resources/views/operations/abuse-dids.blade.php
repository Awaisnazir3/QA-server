@extends('layouts.app')

@section('title', 'DIDX — Abuse DIDs Detector')
@section('page-title', 'Abuse DIDs Detector')
@section('page-crumb', 'DIDX / Operations / Abuse DIDs Detector')

@section('content')
<style>
    /* Clean, compact, professional styling */
    .table-compact {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        text-align: left;
    }
    .table-compact th {
        padding: 8px 12px;
        border-bottom: 1px solid var(--border);
        color: var(--ink3);
        font-family: var(--mono);
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        font-weight: 600;
        background: var(--surface2);
    }
    .table-compact td {
        padding: 8px 12px;
        border-bottom: 1px solid var(--bordersoft);
        vertical-align: middle;
    }
    .table-compact tbody tr:hover {
        background: var(--hover);
    }
    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .status-rejected {
        background: var(--danger);
    }
    .row-hit-flash {
        animation: subtleFlash 1s ease-out;
    }
    @keyframes subtleFlash {
        0% { background-color: rgba(97, 83, 246, 0.12) !important; }
        100% { background-color: transparent; }
    }
    .page-btn {
        padding: 4px 10px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--ink2);
        border-radius: 5px;
        font-family: var(--mono);
        font-size: 11.5px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .page-btn:hover:not(:disabled) {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    .page-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .page-btn.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
        font-weight: 700;
    }
    .btn-action-del {
        padding: 3px 8px;
        border: 1px solid rgba(224, 57, 63, 0.3);
        background: var(--danger-dim);
        color: var(--danger);
        border-radius: 4px;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-action-del:hover {
        background: var(--danger);
        color: #fff;
    }
    .btn-icon-copy {
        background: transparent;
        border: none;
        color: var(--ink3);
        cursor: pointer;
        font-size: 11px;
        padding: 2px 4px;
        border-radius: 3px;
        transition: color 0.15s;
    }
    .btn-icon-copy:hover {
        color: var(--primary);
    }
</style>

<!-- STATS OVERVIEW -->
<div class="statrow">
    <div class="stat-card sc-primary">
        <div class="stat-icon"><i class="fa-solid fa-shield-virus"></i></div>
        <div>
            <div class="stat-lbl">Abused DIDs Detected</div>
            <div class="stat-val" id="statTotalDids">{{ $stats['totalCount'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-amber">
        <div class="stat-icon"><i class="fa-solid fa-chart-simple"></i></div>
        <div>
            <div class="stat-lbl">Total Hits Recorded</div>
            <div class="stat-val" id="statTotalHits" style="color:var(--amber)">{{ $stats['totalHits'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-violet">
        <div class="stat-icon"><i class="fa-solid fa-crosshairs"></i></div>
        <div>
            <div class="stat-lbl">Top Targeted DID</div>
            <div class="stat-val" id="statTopDid" style="font-size:14px;color:var(--danger)">
                {{ $stats['topDid'] }} 
                @if($stats['topHits'] > 0)
                    <span style="font-size:11px;color:var(--ink3);font-weight:500">({{ $stats['topHits'] }} hits)</span>
                @endif
            </div>
        </div>
    </div>
    <div class="stat-card sc-teal">
        <div class="stat-icon"><i class="fa-solid fa-server"></i></div>
        <div>
            <div class="stat-lbl">Active Inbound Trunks</div>
            <div class="stat-val" id="statTrunks" style="color:var(--teal)">{{ $stats['uniqueTrunks'] }}</div>
        </div>
    </div>
</div>

<!-- TOP 5 OFFENDER DIDS (SEPARATE DEDICATED SECTION) -->
<div class="slabel"><i class="fa-solid fa-list-check"></i>Top 5 Offender DIDs</div>

<div class="card" style="padding:0;overflow:hidden;margin-bottom:20px">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface2)">
        <div style="font-size:13px;font-weight:700;color:var(--ink1);display:flex;align-items:center;gap:8px">
            <i class="fa-solid fa-arrow-trend-up" style="color:var(--danger);font-size:12px"></i>
            Most Targeted Phone Numbers
        </div>
        <span style="font-size:11px;color:var(--ink3);font-family:var(--mono)">Ranked by total hit frequency</span>
    </div>
    <div style="overflow-x:auto">
        <table class="table-compact">
            <thead>
                <tr>
                    <th style="width:50px;text-align:center">Rank</th>
                    <th style="width:24%;text-align:left">DID / Phone Number</th>
                    <th style="width:18%;text-align:left">Source Trunk</th>
                    <th style="width:10%;text-align:center">Hits</th>
                    <th style="width:12%;text-align:left">Status</th>
                    <th style="width:14%;text-align:left">First Hit</th>
                    <th style="width:14%;text-align:left">Last Hit</th>
                    <th style="width:8%;text-align:right">Action</th>
                </tr>
            </thead>
            <tbody id="top5TableBody">
                @forelse($top5 as $tIdx => $tDid)
                    <tr id="top5-row-{{ $tDid->phone_number }}">
                        <td style="text-align:center;font-family:var(--mono);font-size:11.5px;font-weight:700;color:var(--ink2)">
                            #{{ $tIdx + 1 }}
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px">
                                <span style="font-family:var(--mono);font-weight:700;color:var(--ink1);font-size:13px">{{ $tDid->phone_number }}</span>
                                <button type="button" class="btn-icon-copy" onclick="copyToClipboard('{{ $tDid->phone_number }}')" title="Copy DID">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <span style="font-family:var(--mono);font-size:11.5px;color:var(--ink2)">
                                <i class="fa-solid fa-server" style="font-size:9.5px;color:var(--ink3);margin-right:4px"></i>
                                {{ $tDid->source_trunk ?: 'Asterisk-Inbound' }}
                            </span>
                        </td>
                        <td style="text-align:center">
                            <span style="font-family:var(--mono);font-weight:700;color:var(--danger);font-size:12.5px" id="top5-hits-{{ $tDid->phone_number }}">
                                {{ $tDid->hits_count }}
                            </span>
                        </td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--ink2)">
                                <span class="status-dot status-rejected"></span>
                                {{ ucfirst($tDid->status ?: 'rejected') }}
                            </span>
                        </td>
                        <td style="font-family:var(--mono);font-size:11px;color:var(--ink3)">
                            {{ $tDid->first_hit_at ? $tDid->first_hit_at->format('M d, H:i:s') : '—' }}
                        </td>
                        <td style="font-family:var(--mono);font-size:11px;color:var(--ink1)">
                            <span id="top5-lasthit-{{ $tDid->phone_number }}">
                                {{ $tDid->last_hit_at ? $tDid->last_hit_at->diffForHumans() : '—' }}
                            </span>
                        </td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('abuse-dids.destroy', $tDid->id) }}" style="margin:0" onsubmit="return confirm('Delete DID {{ $tDid->phone_number }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action-del" title="Delete record">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr id="top5EmptyRow">
                        <td colspan="8" style="text-align:center;padding:18px;color:var(--ink3);font-family:var(--mono);font-size:12px">
                            No abuse hits detected yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- MAIN DETECTED ABUSED NUMBERS TABLE (50 PER PAGE) -->
<div class="slabel"><i class="fa-solid fa-table-list"></i>All Detected Abused Numbers</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:var(--surface)">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:13px;font-weight:700;color:var(--ink1)">Live Ingestion Feed</span>
            <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--ok);font-family:var(--mono)" id="liveIndicator">
                <span style="width:6px;height:6px;border-radius:50%;background:var(--ok)"></span>
                <span id="liveStatusText">Live (2.5s)</span>
            </span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <!-- Search -->
            <input type="text" id="tableSearch" placeholder="Filter DID / Trunk..." onkeyup="onSearchInput()" style="padding:5px 10px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:11.5px;outline:none;width:170px">

            <!-- Pause / Resume -->
            <button type="button" class="page-btn" id="toggleLiveBtn" onclick="toggleLiveScanning()">
                <i class="fa-solid fa-pause"></i> Pause
            </button>

            <!-- Export CSV -->
            <a href="{{ route('abuse-dids.export') }}" class="page-btn" style="text-decoration:none">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>

            <!-- Clear All -->
            <form method="POST" action="{{ route('abuse-dids.clear-all') }}" style="margin:0" onsubmit="return confirm('Clear ALL detected abuse DIDs?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-action-del" style="padding:5px 10px;font-size:11.5px">
                    <i class="fa-solid fa-trash-can"></i> Clear All
                </button>
            </form>
        </div>
    </div>

    <!-- MAIN TABLE -->
    <div style="overflow-x:auto">
        <table class="table-compact">
            <thead>
                <tr>
                    <th style="width:45px;text-align:center">#</th>
                    <th style="width:24%;text-align:left">DID / Phone Number</th>
                    <th style="width:18%;text-align:left">Source Trunk</th>
                    <th style="width:10%;text-align:center">Hits</th>
                    <th style="width:12%;text-align:left">Status</th>
                    <th style="width:14%;text-align:left">First Detected</th>
                    <th style="width:14%;text-align:left">Last Hit</th>
                    <th style="width:8%;text-align:right">Action</th>
                </tr>
            </thead>
            <tbody id="abuseTableBody">
                <!-- Rendered dynamically (50 entries per page) -->
            </tbody>
        </table>
    </div>

    <!-- PAGINATION BAR (50 PER PAGE) -->
    <div style="padding:10px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface2);flex-wrap:wrap;gap:10px" id="paginationBar">
        <div style="font-family:var(--mono);font-size:11.5px;color:var(--ink3)" id="paginationInfo">
            Showing 0 to 0 of 0 detected DIDs
        </div>
        <div style="display:flex;align-items:center;gap:4px" id="paginationControls">
            <!-- Rendered by JS -->
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    var isLiveScanning = true;
    var scanInterval = null;
    var lastKnownHits = {};
    var allDidsData = [];
    var filteredDidsData = [];
    var currentPage = 1;
    var pageSize = 50;

    // Initial server dataset
    @foreach($dids as $did)
        lastKnownHits['{{ $did->phone_number }}'] = {{ (int)$did->hits_count }};
        allDidsData.push({
            id: {{ $did->id }},
            phone_number: '{{ $did->phone_number }}',
            source_trunk: '{{ $did->source_trunk ?: "Asterisk-Inbound" }}',
            hits_count: {{ (int)$did->hits_count }},
            status: '{{ $did->status ?: "rejected" }}',
            first_hit_at: '{{ $did->first_hit_at ? $did->first_hit_at->format("M d, H:i:s") : "—" }}',
            last_hit_at: '{{ $did->last_hit_at ? $did->last_hit_at->format("M d, H:i:s") : "—" }}',
            last_hit_human: '{{ $did->last_hit_at ? $did->last_hit_at->diffForHumans() : "—" }}',
            raw_log: '{{ addslashes($did->raw_log ?: "") }}'
        });
    @endforeach

    filteredDidsData = allDidsData.slice();

    function toggleLiveScanning() {
        isLiveScanning = !isLiveScanning;
        var btn = document.getElementById('toggleLiveBtn');
        var statusText = document.getElementById('liveStatusText');

        if (isLiveScanning) {
            btn.innerHTML = '<i class="fa-solid fa-pause"></i> Pause';
            statusText.innerText = 'Live (2.5s)';
            startPolling();
        } else {
            btn.innerHTML = '<i class="fa-solid fa-play"></i> Resume';
            statusText.innerText = 'Paused';
        }
    }

    function startPolling() {
        if (scanInterval) clearInterval(scanInterval);
        scanInterval = setInterval(pollAbuseStream, 2500);
    }

    function pollAbuseStream() {
        if (!isLiveScanning) return;

        fetch('{{ route("api.abuse-dids.stream") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.success) return;

            // Update Stats Overview
            if (data.stats) {
                document.getElementById('statTotalDids').innerText = data.stats.totalCount;
                document.getElementById('statTotalHits').innerText = data.stats.totalHits;
                var topText = data.stats.topDid;
                if (data.stats.topHits > 0) {
                    topText += ' <span style="font-size:11px;color:var(--ink3);font-weight:500">(' + data.stats.topHits + ' hits)</span>';
                }
                document.getElementById('statTopDid').innerHTML = topText;
                document.getElementById('statTrunks').innerText = data.stats.uniqueTrunks;
            }

            // Update Top 5 List
            if (data.top5) {
                renderTop5(data.top5);
            }

            // Update Main Data
            if (data.dids) {
                allDidsData = data.dids;
                applyFilterAndPaginate();
            }
        })
        .catch(function(err) {
            console.warn('Stream poll error:', err);
        });
    }

    function renderTop5(top5) {
        var tbody = document.getElementById('top5TableBody');
        if (!top5 || top5.length === 0) {
            tbody.innerHTML = '<tr id="top5EmptyRow"><td colspan="8" style="text-align:center;padding:18px;color:var(--ink3);font-family:var(--mono);font-size:12px">No abuse hits detected yet.</td></tr>';
            return;
        }

        var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
        var html = '';

        top5.forEach(function(did, idx) {
            var phone = String(did.phone_number);
            var hits = parseInt(did.hits_count) || 1;
            var deleteUrl = '/abuse-dids/' + did.id;

            html += `
                <tr id="top5-row-${escapeHtml(phone)}">
                    <td style="text-align:center;font-family:var(--mono);font-size:11.5px;font-weight:700;color:var(--ink2)">
                        #${idx + 1}
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                            <span style="font-family:var(--mono);font-weight:700;color:var(--ink1);font-size:13px">${escapeHtml(phone)}</span>
                            <button type="button" class="btn-icon-copy" onclick="copyToClipboard('${escapeHtml(phone)}')" title="Copy DID">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <span style="font-family:var(--mono);font-size:11.5px;color:var(--ink2)">
                            <i class="fa-solid fa-server" style="font-size:9.5px;color:var(--ink3);margin-right:4px"></i>
                            ${escapeHtml(did.source_trunk || 'Asterisk-Inbound')}
                        </span>
                    </td>
                    <td style="text-align:center">
                        <span style="font-family:var(--mono);font-weight:700;color:var(--danger);font-size:12.5px" id="top5-hits-${escapeHtml(phone)}">
                            ${hits}
                        </span>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--ink2)">
                            <span class="status-dot status-rejected"></span>
                            ${escapeHtml(capitalize(did.status || 'rejected'))}
                        </span>
                    </td>
                    <td style="font-family:var(--mono);font-size:11px;color:var(--ink3)">
                        ${escapeHtml(did.first_hit_at || '—')}
                    </td>
                    <td style="font-family:var(--mono);font-size:11px;color:var(--ink1)">
                        <span id="top5-lasthit-${escapeHtml(phone)}">
                            ${escapeHtml(did.last_hit_human || did.last_hit_at || 'Just now')}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <form method="POST" action="${deleteUrl}" style="margin:0" onsubmit="return confirm('Delete DID ${escapeHtml(phone)}?')">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn-action-del" title="Delete record">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function onSearchInput() {
        currentPage = 1;
        applyFilterAndPaginate();
    }

    function applyFilterAndPaginate() {
        var query = document.getElementById('tableSearch').value.toLowerCase().trim();

        if (!query) {
            filteredDidsData = allDidsData.slice();
        } else {
            filteredDidsData = allDidsData.filter(function(item) {
                var p = String(item.phone_number || '').toLowerCase();
                var t = String(item.source_trunk || '').toLowerCase();
                return p.indexOf(query) !== -1 || t.indexOf(query) !== -1;
            });
        }

        var totalItems = filteredDidsData.length;
        var totalPages = Math.ceil(totalItems / pageSize) || 1;

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }

        var startIdx = (currentPage - 1) * pageSize;
        var endIdx = Math.min(startIdx + pageSize, totalItems);
        var pageItems = filteredDidsData.slice(startIdx, endIdx);

        renderMainTable(pageItems, startIdx);
        renderPagination(totalItems, totalPages, startIdx, endIdx);
    }

    function renderMainTable(items, startIdx) {
        var tbody = document.getElementById('abuseTableBody');

        if (!items || items.length === 0) {
            tbody.innerHTML = '<tr id="emptyRow"><td colspan="8" style="text-align:center;padding:24px;color:var(--ink3);font-family:var(--mono);font-size:12px">No matching DIDs found.</td></tr>';
            return;
        }

        var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
        var html = '';

        items.forEach(function(did, idx) {
            var phone = String(did.phone_number);
            var hits = parseInt(did.hits_count) || 1;
            var prevHits = lastKnownHits[phone] || 0;
            var isNewHit = (hits > prevHits && prevHits > 0);
            var isBrandNew = (prevHits === 0 && Object.keys(lastKnownHits).length > 0);

            lastKnownHits[phone] = hits;

            var flashClass = (isNewHit || isBrandNew) ? 'row-hit-flash' : '';
            var deleteUrl = '/abuse-dids/' + did.id;
            var itemIndex = startIdx + idx + 1;

            html += `
                <tr id="row-${escapeHtml(phone)}" class="${flashClass}">
                    <td style="text-align:center;font-family:var(--mono);color:var(--ink3);font-size:11px">${itemIndex}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                            <span style="font-family:var(--mono);font-weight:700;color:var(--ink1);font-size:12.5px">${escapeHtml(phone)}</span>
                            <button type="button" class="btn-icon-copy" onclick="copyToClipboard('${escapeHtml(phone)}')" title="Copy DID">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <span style="font-family:var(--mono);font-size:11.5px;color:var(--ink2)">
                            <i class="fa-solid fa-server" style="font-size:9.5px;color:var(--ink3);margin-right:4px"></i>
                            ${escapeHtml(did.source_trunk || 'Asterisk-Inbound')}
                        </span>
                    </td>
                    <td style="text-align:center">
                        <span style="font-family:var(--mono);font-weight:700;color:var(--ink1);font-size:12.5px" id="hits-${escapeHtml(phone)}">
                            ${hits}
                        </span>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--ink2)">
                            <span class="status-dot status-rejected"></span>
                            ${escapeHtml(capitalize(did.status || 'rejected'))}
                        </span>
                    </td>
                    <td style="font-family:var(--mono);font-size:11px;color:var(--ink3)">
                        ${escapeHtml(did.first_hit_at || '—')}
                    </td>
                    <td style="font-family:var(--mono);font-size:11px;color:var(--ink1)">
                        <span id="lasthit-${escapeHtml(phone)}">
                            ${escapeHtml(did.last_hit_human || did.last_hit_at || 'Just now')}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <form method="POST" action="${deleteUrl}" style="margin:0" onsubmit="return confirm('Delete DID ${escapeHtml(phone)}?')">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn-action-del" title="Delete record">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function renderPagination(totalItems, totalPages, startIdx, endIdx) {
        var infoDiv = document.getElementById('paginationInfo');
        var controlsDiv = document.getElementById('paginationControls');

        if (totalItems === 0) {
            infoDiv.innerText = 'Showing 0 of 0 detected DIDs';
            controlsDiv.innerHTML = '';
            return;
        }

        infoDiv.innerText = 'Showing ' + (startIdx + 1) + '–' + endIdx + ' of ' + totalItems + ' detected DIDs (50 per page)';

        var html = '';

        // First & Prev
        html += `<button type="button" class="page-btn" onclick="goToPage(1)" ${currentPage === 1 ? 'disabled' : ''} title="First Page">«</button>`;
        html += `<button type="button" class="page-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} title="Previous Page">‹</button>`;

        // Page Numbers
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (var p = startPage; p <= endPage; p++) {
            var activeClass = (p === currentPage) ? 'active' : '';
            html += `<button type="button" class="page-btn ${activeClass}" onclick="goToPage(${p})">${p}</button>`;
        }

        // Next & Last
        html += `<button type="button" class="page-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} title="Next Page">›</button>`;
        html += `<button type="button" class="page-btn" onclick="goToPage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''} title="Last Page">»</button>`;

        controlsDiv.innerHTML = html;
    }

    function goToPage(page) {
        currentPage = page;
        applyFilterAndPaginate();
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            var textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function capitalize(s) {
        if (!s) return '';
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    // Initial render
    applyFilterAndPaginate();

    // Start auto scanning on load
    startPolling();
</script>
@endsection
