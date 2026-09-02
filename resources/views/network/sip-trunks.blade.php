@extends('layouts.app')

@section('title', 'DIDX — PJSIP Trunks')
@section('page-title', 'PJSIP Trunks Infrastructure')
@section('page-crumb', 'DIDX / Network / PJSIP Trunks')

@section('content')
<div class="flex flex-col flex-1 h-full min-h-0 overflow-hidden">
    <!-- 1. TOP TELEMETRY STRIP -->
    <div class="h-9 border-b border-[var(--border)] bg-[var(--surface2)] px-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-[var(--surface)] border border-[var(--border)] font-mono text-xs">
                <i class="fa-solid fa-server text-[10px] text-[var(--ink3)]"></i>
                <span class="text-[var(--ink3)] text-[10px]">TOTAL ENDPOINTS:</span>
                <span class="font-bold text-[var(--ink1)]">{{ count($peerList) }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 font-mono text-xs text-emerald-600 dark:text-emerald-400">
                <i class="fa-solid fa-circle-check text-[10px]"></i>
                <span class="text-[10px]">ONLINE:</span>
                <span class="font-bold">{{ $onlinePeers }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/20 font-mono text-xs text-rose-600 dark:text-rose-400">
                <i class="fa-solid fa-circle-xmark text-[10px]"></i>
                <span class="text-[10px]">OFFLINE:</span>
                <span class="font-bold">{{ count($peerList) - $onlinePeers }}</span>
            </div>
        </div>

        <div class="flex items-center gap-2 font-mono text-xs text-[var(--ink3)]">
            <span class="flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>PJSIP Core Transport: UDP 5060</span>
            </span>
        </div>
    </div>

    <!-- 2. ACTION BAR (SEARCH & CONTROLS) -->
    <div class="h-10 px-3 py-1.5 bg-[var(--surface)] border-b border-[var(--border)] flex items-center gap-2 flex-shrink-0">
        <div class="relative flex items-center flex-1 max-w-xs">
            <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
            <input type="text" id="trunkSearchInput" placeholder="Filter Trunks, IPs..." oninput="filterTrunkGrid()"
                   class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-amber-500 w-full transition-all">
        </div>

        <!-- Status Filter Pills -->
        <div class="flex items-center bg-[var(--surface2)] p-0.5 rounded border border-[var(--border)] flex-shrink-0" id="trunkStatusFilterPills">
            <button type="button" class="trunk-filter-btn active px-2 py-0.5 rounded text-[10px] font-mono font-bold" data-status="all">ALL</button>
            <button type="button" class="trunk-filter-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400" data-status="online">ONLINE</button>
            <button type="button" class="trunk-filter-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-rose-600 dark:text-rose-400" data-status="offline">OFFLINE</button>
        </div>

        <div class="flex-1"></div>

        <span class="text-[11px] font-mono text-[var(--ink3)]" id="trunkCountDisplay">
            {{ count($peerList) }} Endpoints
        </span>
    </div>

    <!-- 3. HIGH-DENSITY DATA GRID -->
    <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative bg-[var(--surface)]">
        <table class="w-full table-fixed text-xs border-collapse text-left select-text font-mono">
            <colgroup>
                <col class="w-[4%]">
                <col class="w-[32%]">
                <col class="w-[32%]">
                <col class="w-[18%]">
                <col class="w-[14%]">
            </colgroup>
            <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 shadow-xs">
                <tr class="h-8 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">#</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Endpoint / Trunk Name</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Host / IP Address</th>
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Contact Status</th>
                    <th class="py-2 px-3 text-right">Carrier Link</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="trunkTbody">
                @forelse($peerList as $pIdx => $peer)
                    @php $isOnline = $peer['online']; @endphp
                    <tr class="trunk-row h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors"
                        data-name="{{ strtolower($peer['name']) }}"
                        data-ip="{{ strtolower($peer['ip']) }}"
                        data-status="{{ $isOnline ? 'online' : 'offline' }}"
                        style="border-left:3px solid {{ $isOnline ? 'var(--ok)' : 'var(--danger)' }}">
                        <!-- Serial -->
                        <td class="py-2 px-3 text-center text-[var(--ink3)] border-r border-slate-100 dark:border-slate-800/60">
                            {{ $pIdx + 1 }}
                        </td>

                        <!-- Trunk Name -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight font-semibold text-[var(--ink1)] truncate">
                            <div class="flex items-center gap-2 truncate">
                                <i class="fa-solid fa-server text-[10px] text-amber-500 flex-shrink-0"></i>
                                <span class="truncate">{{ $peer['name'] }}</span>
                            </div>
                        </td>

                        <!-- Host / IP -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight text-[var(--ink2)] truncate">
                            {{ $peer['ip'] }}
                        </td>

                        <!-- Status Badge -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center">
                            @if($isOnline)
                                <span class="spill s-pass">
                                    <span class="sdot"></span>
                                    <span>{{ $peer['status'] }}</span>
                                </span>
                            @else
                                <span class="spill s-fail">
                                    <span class="sdot"></span>
                                    <span>{{ $peer['status'] }}</span>
                                </span>
                            @endif
                        </td>

                        <!-- Status indicator (Anchored Right) -->
                        <td class="py-2 px-3 text-right">
                            <span class="text-[10px] uppercase font-bold {{ $isOnline ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                <i class="fa-solid {{ $isOnline ? 'fa-link' : 'fa-link-slash' }} mr-1"></i>
                                {{ $isOnline ? 'Active' : 'Unreachable' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">
                            <i class="fa-solid fa-server text-lg mb-2 block opacity-40"></i>
                            No PJSIP carrier trunks detected on switch.
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
function filterTrunkGrid(){
    var searchVal = (document.getElementById('trunkSearchInput').value || '').toLowerCase().trim();
    var activeStatusBtn = document.querySelector('#trunkStatusFilterPills .trunk-filter-btn.active');
    var statusFilter = activeStatusBtn ? activeStatusBtn.getAttribute('data-status') : 'all';

    var rows = document.querySelectorAll('#trunkTbody tr.trunk-row');
    var visibleCount = 0;

    rows.forEach(function(row){
        var name = (row.getAttribute('data-name') || '').toLowerCase();
        var ip = (row.getAttribute('data-ip') || '').toLowerCase();
        var status = (row.getAttribute('data-status') || '').toLowerCase();

        var matchesSearch = !searchVal || name.includes(searchVal) || ip.includes(searchVal);
        var matchesStatus = (statusFilter === 'all') || (status === statusFilter);

        if(matchesSearch && matchesStatus){
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    var countElem = document.getElementById('trunkCountDisplay');
    if(countElem) countElem.textContent = visibleCount + ' Endpoints';
}

(function initTrunkFilter(){
    var buttons = document.querySelectorAll('#trunkStatusFilterPills .trunk-filter-btn');
    buttons.forEach(function(btn){
        btn.addEventListener('click', function(){
            buttons.forEach(function(b){
                b.classList.remove('active', 'bg-[var(--surface)]', 'text-[var(--ink1)]', 'shadow-xs');
            });
            btn.classList.add('active', 'bg-[var(--surface)]', 'shadow-xs');
            filterTrunkGrid();
        });
    });
})();
</script>
@endsection
