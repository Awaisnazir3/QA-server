@extends('layouts.app')

@section('title', 'DIDX — Abuse DIDs Detector')
@section('page-title', 'Abuse DIDs Detector')
@section('page-crumb', 'DIDX / Operations / Abuse DIDs Detector')

@section('content')
<div class="flex flex-col flex-1 h-full min-h-0 overflow-hidden">
    <!-- 1. TOP TELEMETRY STRIP -->
    <div class="h-9 border-b border-[var(--border)] bg-[var(--surface2)] px-3 flex items-center justify-between flex-shrink-0">
        <!-- Telemetry Metrics -->
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-[var(--surface)] border border-[var(--border)] font-mono text-xs">
                <i class="fa-solid fa-shield-virus text-[10px] text-rose-500"></i>
                <span class="text-[var(--ink3)] text-[10px]">ABUSED DIDS:</span>
                <span class="font-bold text-[var(--ink1)]" id="statTotalDids">{{ $stats['totalCount'] }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 font-mono text-xs text-amber-600 dark:text-amber-400">
                <i class="fa-solid fa-chart-simple text-[10px]"></i>
                <span class="text-[10px]">TOTAL HITS:</span>
                <span class="font-bold" id="statTotalHits">{{ $stats['totalHits'] }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/20 font-mono text-xs text-rose-600 dark:text-rose-400 max-w-xs truncate">
                <i class="fa-solid fa-crosshairs text-[10px]"></i>
                <span class="text-[10px]">TOP TARGET:</span>
                <span class="font-bold truncate" id="statTopDid">
                    {{ $stats['topDid'] }} @if($stats['topHits'] > 0)({{ $stats['topHits'] }} hits)@endif
                </span>
            </div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-sky-500/10 border border-sky-500/20 font-mono text-xs text-sky-600 dark:text-sky-400">
                <i class="fa-solid fa-server text-[10px]"></i>
                <span class="text-[10px]">TRUNKS:</span>
                <span class="font-bold" id="statTrunks">{{ $stats['uniqueTrunks'] }}</span>
            </div>
        </div>

        <!-- Live Streaming Status -->
        <div class="flex items-center gap-2 font-mono text-xs text-[var(--ink3)]">
            <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-bold" id="liveIndicator">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981] animate-pulse"></span>
                <span id="liveStatusText">Live Ingest (5s)</span>
            </span>
        </div>
    </div>

    <!-- 2. WORKSPACE TAB BAR -->
    <div class="h-8 border-b border-[var(--border)] bg-[var(--surface)] px-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-1" id="abuseTabs">
            <button type="button" class="abuse-tab active flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-semibold text-[var(--ink1)] bg-[var(--surface2)] border border-[var(--border)] transition-all" data-target="panelAllAbuse">
                <i class="fa-solid fa-table-list text-rose-500 text-[10.5px]"></i>
                <span>All Abused DIDs</span>
                <span class="px-1.5 py-0.2 rounded-full text-[9.5px] font-mono bg-rose-500/10 text-rose-600 font-bold" id="tabAbuseBadge">
                    {{ $stats['totalCount'] }}
                </span>
            </button>
            <button type="button" class="abuse-tab flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-semibold text-[var(--ink3)] hover:text-[var(--ink1)] bg-transparent border border-transparent transition-all" data-target="panelTop5">
                <i class="fa-solid fa-arrow-trend-up text-amber-500 text-[10.5px]"></i>
                <span>Top 5 Offenders</span>
            </button>
        </div>

        <!-- Quick Manual Add & Parse Logs Trigger -->
        <div class="flex items-center gap-1.5">
            <button type="button" onclick="document.getElementById('manualAddModal').classList.remove('hidden'); document.getElementById('manualAddModal').classList.add('flex')" class="btn-dense btn-dense-ghost text-[10.5px]" title="Add single DID or simulate hit">
                <i class="fa-solid fa-plus text-rose-500 text-[9.5px]"></i> <span>Add DID</span>
            </button>
            <button type="button" onclick="document.getElementById('parseLogsModal').classList.remove('hidden'); document.getElementById('parseLogsModal').classList.add('flex')" class="btn-dense btn-dense-ghost text-[10.5px]" title="Paste raw Asterisk logs to ingest">
                <i class="fa-solid fa-code text-indigo-500 text-[9.5px]"></i> <span>Parse Logs</span>
            </button>
        </div>
    </div>

    <!-- 3. ACTION BAR (SINGLE HORIZONTAL TOOLBAR) -->
    <div class="h-10 px-3 py-1.5 bg-[var(--surface)] border-b border-[var(--border)] flex items-center gap-2 flex-shrink-0 overflow-x-auto">
        <!-- Instant Filter Search -->
        <div class="relative flex items-center flex-1 min-w-[180px] max-w-xs">
            <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
            <input type="text" id="tableSearch" placeholder="Filter DID, Trunk, IP..." onkeyup="onSearchInput()"
                   class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-rose-500 w-full transition-all">
        </div>

        <div class="h-4 w-[1px] bg-[var(--border)] flex-shrink-0"></div>

        <!-- Action Buttons -->
        <button type="button" class="btn-dense btn-dense-ghost flex-shrink-0" id="toggleLiveBtn" onclick="toggleLiveScanning()">
            <i class="fa-solid fa-pause text-[10px]"></i> <span>Pause Stream</span>
        </button>

        <a href="{{ route('abuse-dids.export') }}" class="btn-dense bg-emerald-700 hover:bg-emerald-800 text-white border-none flex-shrink-0" title="Download CSV">
            <i class="fa-solid fa-file-csv text-[10px]"></i> <span>Export CSV</span>
        </a>

        <div class="flex-1"></div>

        <!-- Clear All -->
        <form method="POST" action="{{ route('abuse-dids.clear-all') }}" class="m-0 flex-shrink-0" onsubmit="return confirm('Clear ALL detected abuse DIDs?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-dense btn-dense-del" title="Purge all abuse records">
                <i class="fa-solid fa-trash-can text-[10px]"></i> <span>Clear All</span>
            </button>
        </form>
    </div>

    <!-- 4. TAB PANES -->
    <div class="flex-1 flex flex-col min-h-0 overflow-hidden relative bg-[var(--surface)]">
        <!-- PANEL A: ALL ABUSED DIDS (HIGH-DENSITY DATA GRID) -->
        <div id="panelAllAbuse" class="abuse-pane flex flex-col flex-1 h-full min-h-0 overflow-hidden">
            <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative">
                <table class="w-full table-fixed text-xs border-collapse text-left select-text">
                    <colgroup>
                        <col class="w-[4%]">
                        <col class="w-[20%]">
                        <col class="w-[20%]">
                        <col class="w-[10%]">
                        <col class="w-[12%]">
                        <col class="w-[15%]">
                        <col class="w-[14%]">
                        <col class="w-[5%]">
                    </colgroup>
                    <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 shadow-xs">
                        <tr class="h-8 text-[11px] font-mono font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">#</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">DID / Target Number</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Source Trunk</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70 text-center">Hits</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70 text-center">Status</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">First Detected</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Last Hit</th>
                            <th class="py-2 px-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs" id="abuseTableBody">
                        <!-- Dynamically Rendered via JS Stream with 50/page pagination -->
                    </tbody>
                </table>
            </div>

            <!-- COMPACT PAGINATION STRIP -->
            <div class="h-9 px-3 border-t border-[var(--border)] bg-[var(--surface2)] flex items-center justify-between flex-shrink-0" id="paginationBar">
                <div class="font-mono text-xs text-[var(--ink3)]" id="paginationInfo">
                    Showing 0 to 0 of 0 detected DIDs
                </div>
                <div class="flex items-center gap-1" id="paginationControls">
                    <!-- Rendered by JS -->
                </div>
            </div>
        </div>

        <!-- PANEL B: TOP 5 OFFENDERS -->
        <div id="panelTop5" class="abuse-pane hidden flex flex-col flex-1 h-full min-h-0 overflow-hidden bg-[var(--surface)]">
            <div class="h-8 px-3 bg-[var(--surface2)] border-b border-[var(--border)] flex items-center justify-between flex-shrink-0">
                <span class="text-xs font-bold text-[var(--ink1)] font-disp flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-trend-up text-rose-500"></i> Top 5 Most Targeted Phone Numbers
                </span>
                <span class="text-[10px] font-mono text-[var(--ink3)]">Ranked by total hit frequency</span>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto">
                <table class="w-full table-fixed text-xs border-collapse text-left font-mono select-text">
                    <colgroup>
                        <col class="w-[4%]">
                        <col class="w-[20%]">
                        <col class="w-[20%]">
                        <col class="w-[10%]">
                        <col class="w-[12%]">
                        <col class="w-[15%]">
                        <col class="w-[14%]">
                        <col class="w-[5%]">
                    </colgroup>
                    <thead class="sticky top-0 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr class="h-8">
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Rank</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">DID / Target Number</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Source Trunk</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Hits Count</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Status</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">First Hit</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Last Activity</th>
                            <th class="py-2 px-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="top5TableBody">
                        @forelse($top5 as $tIdx => $tDid)
                            <tr class="h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors" id="top5-row-{{ $tDid->phone_number }}">
                                <td class="py-2 px-3 text-center font-bold text-[var(--ink3)] border-r border-slate-100 dark:border-slate-800/60">#{{ $tIdx + 1 }}</td>
                                <td class="py-2 px-3 font-mono tracking-tight font-semibold text-[var(--ink1)] border-r border-slate-100 dark:border-slate-800/60 truncate">
                                    <span class="mr-1.5">{{ $tDid->phone_number }}</span>
                                    <button type="button" onclick="copyToClipboard('{{ $tDid->phone_number }}')" class="text-slate-400 hover:text-amber-500 border-none bg-transparent cursor-pointer" title="Copy Number">
                                        <i class="fa-regular fa-copy text-[10px]"></i>
                                    </button>
                                </td>
                                <td class="py-2 px-3 text-[var(--ink2)] border-r border-slate-100 dark:border-slate-800/60 truncate">{{ $tDid->source_trunk ?: 'Asterisk-Inbound' }}</td>
                                <td class="py-2 px-3 text-center font-bold text-rose-500 border-r border-slate-100 dark:border-slate-800/60" id="top5-hits-{{ $tDid->phone_number }}">{{ $tDid->hits_count }}</td>
                                <td class="py-2 px-3 text-center border-r border-slate-100 dark:border-slate-800/60">
                                    <span class="spill s-fail"><span class="sdot"></span>{{ ucfirst($tDid->status ?: 'rejected') }}</span>
                                </td>
                                <td class="py-2 px-3 text-[var(--ink3)] text-[11px] border-r border-slate-100 dark:border-slate-800/60">{{ $tDid->first_hit_at ? $tDid->first_hit_at->format('M d, H:i:s') : '—' }}</td>
                                <td class="py-2 px-3 text-[var(--ink1)] text-[11px] border-r border-slate-100 dark:border-slate-800/60" id="top5-lasthit-{{ $tDid->phone_number }}">{{ $tDid->last_hit_at ? $tDid->last_hit_at->diffForHumans() : '—' }}</td>
                                <td class="py-2 px-3 text-center">
                                    <form method="POST" action="{{ route('abuse-dids.destroy', $tDid->id) }}" class="m-0 inline" onsubmit="return confirm('Delete DID {{ $tDid->phone_number }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-dense btn-dense-del px-1.5" title="Delete record">
                                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">No abuse hits detected yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: MANUAL ADD DID -->
<div id="manualAddModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 hidden items-center justify-center">
    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5 max-w-sm w-[90%] shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-[var(--bordersoft)] pb-2.5">
            <div class="font-disp font-bold text-xs text-[var(--ink1)]">Manual Abuse DID Ingestion</div>
            <button onclick="document.getElementById('manualAddModal').classList.add('hidden'); document.getElementById('manualAddModal').classList.remove('flex')" class="text-[var(--ink3)] hover:text-[var(--ink1)] bg-transparent border-none cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('abuse-dids.add') }}" class="space-y-3 m-0">
            @csrf
            <div>
                <label class="text-[10px] font-mono text-[var(--ink3)] uppercase block mb-1">Phone Number / Target</label>
                <input type="text" name="phone_number" placeholder="e.g. 441687508034" required
                       class="w-full h-8 px-2.5 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-rose-500">
            </div>
            <div>
                <label class="text-[10px] font-mono text-[var(--ink3)] uppercase block mb-1">Source Trunk</label>
                <input type="text" name="source_trunk" value="Asterisk-Inbound"
                       class="w-full h-8 px-2.5 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-rose-500">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-[var(--bordersoft)]">
                <button type="button" onclick="document.getElementById('manualAddModal').classList.add('hidden'); document.getElementById('manualAddModal').classList.remove('flex')" class="btn-dense btn-dense-ghost h-7 px-3 text-xs">
                    Cancel
                </button>
                <button type="submit" class="btn-dense btn-dense-primary h-7 px-4 text-xs font-semibold">
                    <i class="fa-solid fa-plus text-[10px]"></i> Ingest Record
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: PARSE CUSTOM LOGS -->
<div id="parseLogsModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 hidden items-center justify-center">
    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5 max-w-lg w-[90%] shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-[var(--bordersoft)] pb-2.5">
            <div class="font-disp font-bold text-xs text-[var(--ink1)]">Parse Raw Asterisk Log Buffer</div>
            <button onclick="document.getElementById('parseLogsModal').classList.add('hidden'); document.getElementById('parseLogsModal').classList.remove('flex')" class="text-[var(--ink3)] hover:text-[var(--ink1)] bg-transparent border-none cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('abuse-dids.parse-logs') }}" class="space-y-3 m-0">
            @csrf
            <div>
                <label class="text-[10px] font-mono text-[var(--ink3)] uppercase block mb-1">Paste Asterisk Messages / CDR text:</label>
                <textarea name="raw_logs" rows="6" placeholder="Paste lines from /var/log/asterisk/messages..." required
                          class="w-full p-2.5 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-rose-500"></textarea>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-[var(--bordersoft)]">
                <button type="button" onclick="document.getElementById('parseLogsModal').classList.add('hidden'); document.getElementById('parseLogsModal').classList.remove('flex')" class="btn-dense btn-dense-ghost h-7 px-3 text-xs">
                    Cancel
                </button>
                <button type="submit" class="btn-dense btn-dense-primary h-7 px-4 text-xs font-semibold">
                    <i class="fa-solid fa-bolt text-[10px]"></i> Extract &amp; Ingest
                </button>
            </div>
        </form>
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

// Tab switcher
(function initAbuseTabs(){
    var tabs = document.querySelectorAll('.abuse-tab');
    var panes = document.querySelectorAll('.abuse-pane');

    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){
            var target = tab.getAttribute('data-target');
            tabs.forEach(function(t){
                t.classList.remove('active', 'bg-[var(--surface2)]', 'text-[var(--ink1)]', 'border-[var(--border)]');
                t.classList.add('bg-transparent', 'text-[var(--ink3)]', 'border-transparent');
            });
            tab.classList.add('active', 'bg-[var(--surface2)]', 'text-[var(--ink1)]', 'border-[var(--border)]');
            tab.classList.remove('bg-transparent', 'text-[var(--ink3)]', 'border-transparent');

            panes.forEach(function(pane){
                if(pane.id === target){
                    pane.classList.remove('hidden');
                } else {
                    pane.classList.add('hidden');
                }
            });
        });
    });
})();

// Initial server dataset (Fast JSON load without Blade loop overhead)
allDidsData = {!! json_encode($formattedDids ?? []) !!};
allDidsData.forEach(function(item) {
    lastKnownHits[item.phone_number] = item.hits_count;
});
filteredDidsData = allDidsData.slice();

function toggleLiveScanning() {
    isLiveScanning = !isLiveScanning;
    var btn = document.getElementById('toggleLiveBtn');
    var statusText = document.getElementById('liveStatusText');

    if (isLiveScanning) {
        btn.innerHTML = '<i class="fa-solid fa-pause text-[10px]"></i> <span>Pause Stream</span>';
        statusText.innerText = 'Live Ingest (5s)';
        startPolling();
    } else {
        btn.innerHTML = '<i class="fa-solid fa-play text-[10px]"></i> <span>Resume Stream</span>';
        statusText.innerText = 'Paused';
    }
}

function startPolling() {
    if (scanInterval) clearInterval(scanInterval);
    scanInterval = setInterval(pollAbuseStream, 5000);
}

function pollAbuseStream() {
    if (!isLiveScanning) return;

    fetch('{{ route("api.abuse-dids.stream") }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (!data.success) return;

        if (data.stats) {
            document.getElementById('statTotalDids').innerText = data.stats.totalCount;
            document.getElementById('statTotalHits').innerText = data.stats.totalHits;
            var topText = data.stats.topDid;
            if (data.stats.topHits > 0) topText += ' (' + data.stats.topHits + ' hits)';
            document.getElementById('statTopDid').innerText = topText;
            document.getElementById('statTrunks').innerText = data.stats.uniqueTrunks;
            var tabBadge = document.getElementById('tabAbuseBadge');
            if(tabBadge) tabBadge.innerText = data.stats.totalCount;
        }

        if (data.top5) renderTop5(data.top5);

        if (data.dids) {
            allDidsData = data.dids;
            applyFilterAndPaginate();
        }
    })
    .catch(function() {});
}

function renderTop5(top5) {
    var tbody = document.getElementById('top5TableBody');
    if (!tbody) return;
    if (!top5 || top5.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">No abuse hits detected yet.</td></tr>';
        return;
    }

    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
    var html = '';

    top5.forEach(function(did, idx) {
        var phone = String(did.phone_number);
        var hits = parseInt(did.hits_count) || 1;
        var deleteUrl = '{{ url("/abuse-dids") }}/' + did.id;

        html += `
            <tr class="h-[34px] hover:bg-[var(--hover)] transition-colors" id="top5-row-${escapeHtml(phone)}">
                <td class="px-2 text-center font-bold text-[var(--ink3)]">#${idx + 1}</td>
                <td class="px-3 font-bold text-[var(--ink1)]">
                    <span class="mr-1.5">${escapeHtml(phone)}</span>
                    <button type="button" onclick="copyToClipboard('${escapeHtml(phone)}')" class="text-slate-400 hover:text-amber-500 border-none bg-transparent cursor-pointer" title="Copy Number">
                        <i class="fa-regular fa-copy text-[10px]"></i>
                    </button>
                </td>
                <td class="px-3 text-[var(--ink2)]">${escapeHtml(did.source_trunk || 'Asterisk-Inbound')}</td>
                <td class="px-3 text-center font-bold text-rose-500" id="top5-hits-${escapeHtml(phone)}">${hits}</td>
                <td class="px-3 text-center">
                    <span class="spill s-fail"><span class="sdot"></span>${escapeHtml(capitalize(did.status || 'rejected'))}</span>
                </td>
                <td class="px-3 text-[var(--ink3)] text-[11px]">${escapeHtml(did.first_hit_at || '—')}</td>
                <td class="px-3 text-[var(--ink1)] text-[11px]" id="top5-lasthit-${escapeHtml(phone)}">${escapeHtml(did.last_hit_human || did.last_hit_at || 'Just now')}</td>
                <td class="px-3 text-right">
                    <form method="POST" action="${deleteUrl}" class="m-0 inline" onsubmit="return confirm('Delete DID ${escapeHtml(phone)}?')">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn-dense btn-dense-del px-1.5" title="Delete record">
                            <i class="fa-solid fa-trash-can text-[10px]"></i>
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
    var query = (document.getElementById('tableSearch').value || '').toLowerCase().trim();

    if (!query) {
        filteredDidsData = allDidsData.slice();
    } else {
        filteredDidsData = allDidsData.filter(function(item) {
            var phone = String(item.phone_number || '').toLowerCase();
            var trunk = String(item.source_trunk || '').toLowerCase();
            return phone.includes(query) || trunk.includes(query);
        });
    }

    renderCurrentPage();
    renderPaginationControls();
}

function renderCurrentPage() {
    var tbody = document.getElementById('abuseTableBody');
    if (!tbody) return;

    if (filteredDidsData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="py-12 text-center text-xs font-mono text-[var(--ink3)]"><i class="fa-solid fa-shield-halved text-lg mb-2 block opacity-40"></i>No matching abused DIDs found.</td></tr>';
        document.getElementById('paginationInfo').innerText = 'Showing 0 to 0 of 0 detected DIDs';
        return;
    }

    var totalItems = filteredDidsData.length;
    var totalPages = Math.ceil(totalItems / pageSize) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    var startIdx = (currentPage - 1) * pageSize;
    var endIdx = Math.min(startIdx + pageSize, totalItems);
    var pageItems = filteredDidsData.slice(startIdx, endIdx);

    document.getElementById('paginationInfo').innerText =
        'Showing ' + (startIdx + 1) + ' to ' + endIdx + ' of ' + totalItems + ' detected DIDs';

    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
    var html = '';

    pageItems.forEach(function(did, idx) {
        var overallIdx = totalItems - (startIdx + idx);
        var phone = String(did.phone_number);
        var hits = parseInt(did.hits_count) || 1;
        var deleteUrl = '{{ url("/abuse-dids") }}/' + did.id;

        html += `
            <tr class="h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors">
                <td class="py-2 px-3 text-center font-mono text-[11px] text-[var(--ink3)] border-r border-slate-100 dark:border-slate-800/60">${overallIdx}</td>
                <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight font-semibold text-[var(--ink1)] truncate">
                    <span class="mr-1.5">${escapeHtml(phone)}</span>
                    <button type="button" onclick="copyToClipboard('${escapeHtml(phone)}')" class="text-slate-400 hover:text-amber-500 border-none bg-transparent cursor-pointer" title="Copy DID">
                        <i class="fa-regular fa-copy text-[10px]"></i>
                    </button>
                </td>
                <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-[var(--ink2)] truncate">${escapeHtml(did.source_trunk || 'Asterisk-Inbound')}</td>
                <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center font-bold text-rose-500">${hits}</td>
                <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center">
                    <span class="spill s-fail"><span class="sdot"></span>${escapeHtml(capitalize(did.status || 'rejected'))}</span>
                </td>
                <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-[var(--ink3)] text-[11px]">${escapeHtml(did.first_hit_at || '—')}</td>
                <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-[var(--ink1)] text-[11px]">${escapeHtml(did.last_hit_human || did.last_hit_at || '—')}</td>
                <td class="py-2 px-3 text-center">
                    <form method="POST" action="${deleteUrl}" class="m-0 inline" onsubmit="return confirm('Delete DID ${escapeHtml(phone)}?')">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn-dense btn-dense-del px-1.5" title="Delete record">
                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                        </button>
                    </form>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function renderPaginationControls() {
    var container = document.getElementById('paginationControls');
    if (!container) return;

    var totalItems = filteredDidsData.length;
    var totalPages = Math.ceil(totalItems / pageSize) || 1;

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    var html = '';
    html += `<button class="btn-dense btn-dense-ghost text-[10px]" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled style="opacity:.4;cursor:not-allowed"' : ''}>&laquo; Prev</button>`;

    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, currentPage + 2);

    for (var p = startPage; p <= endPage; p++) {
        var isCurrent = (p === currentPage);
        html += `<button class="btn-dense ${isCurrent ? 'btn-dense-primary' : 'btn-dense-ghost'} text-[10.5px] px-2" onclick="goToPage(${p})">${p}</button>`;
    }

    html += `<button class="btn-dense btn-dense-ghost text-[10px]" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled style="opacity:.4;cursor:not-allowed"' : ''}>Next &raquo;</button>`;
    container.innerHTML = html;
}

function goToPage(p) {
    var totalPages = Math.ceil(filteredDidsData.length / pageSize) || 1;
    if (p < 1 || p > totalPages) return;
    currentPage = p;
    renderCurrentPage();
    renderPaginationControls();
}

function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text);
    } else {
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function capitalize(s) {
    if (!s) return '';
    return String(s).charAt(0).toUpperCase() + String(s).slice(1);
}

document.addEventListener('DOMContentLoaded', function() {
    applyFilterAndPaginate();
    startPolling();
});

window.addEventListener('beforeunload', function() {
    if (scanInterval) clearInterval(scanInterval);
});
</script>
@endsection
