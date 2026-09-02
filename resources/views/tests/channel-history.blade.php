@extends('layouts.app')

@section('title', 'DIDX — Channel Test History')
@section('page-title', 'Channel Test Audit History')
@section('page-crumb', 'DIDX / Operations / Channel Tests')

@section('content')
<div class="flex flex-col flex-1 h-full min-h-0 overflow-hidden">
    <!-- 1. TOP TELEMETRY STRIP -->
    <div class="h-9 border-b border-[var(--border)] bg-[var(--surface2)] px-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-[var(--surface)] border border-[var(--border)] font-mono text-xs">
                <i class="fa-solid fa-signal text-[10px] text-sky-500"></i>
                <span class="text-[var(--ink3)] text-[10px]">DIAGNOSTIC LOGS:</span>
                <span class="font-bold text-[var(--ink1)]">{{ count($history) }}</span>
            </div>
        </div>

        <div class="flex items-center gap-2 font-mono text-xs text-[var(--ink3)]">
            <span class="flex items-center gap-1">
                <i class="fa-solid fa-circle-info text-sky-500 text-[10px]"></i>
                <span>Channel tests are triggered from the DID Routes console</span>
            </span>
        </div>
    </div>

    <!-- 2. ACTION BAR -->
    <div class="h-10 px-3 py-1.5 bg-[var(--surface)] border-b border-[var(--border)] flex items-center gap-2 flex-shrink-0">
        <!-- Search Filter -->
        <div class="relative flex items-center flex-1 max-w-xs">
            <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
            <input type="text" id="chHistSearch" placeholder="Filter DID Number..." oninput="filterChHist()"
                   class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-sky-500 w-full transition-all">
        </div>

        <div class="flex-1"></div>

        <a href="{{ route('dashboard') }}" class="btn-dense btn-dense-ghost text-xs" title="Go to DID Routes">
            <i class="fa-solid fa-route text-[10px]"></i> <span>DID Routes Manager</span>
        </a>

        <span class="text-[11px] font-mono text-[var(--ink3)] ml-1 border-l border-[var(--border)] pl-2" id="chHistDisplayCount">
            {{ count($history) }} Records
        </span>
    </div>

    <!-- 3. HIGH-DENSITY DATA GRID -->
    <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative bg-[var(--surface)]">
        <table class="w-full table-fixed text-xs border-collapse text-left select-text font-mono">
            <colgroup>
                <col class="w-[4%]">
                <col class="w-[28%]">
                <col class="w-[16%]">
                <col class="w-[18%]">
                <col class="w-[14%]">
                <col class="w-[20%]">
            </colgroup>
            <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 shadow-xs">
                <tr class="h-8 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">#</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">DID / Target Number</th>
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Calls Fired</th>
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Channels Detected</th>
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Status</th>
                    <th class="py-2 px-3 text-right">Execution Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="chHistGridTbody">
                @forelse($history as $hIdx => $log)
                    <tr class="ch-hist-row h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors"
                        data-phone="{{ strtolower($log->phone_number) }}">
                        <!-- Serial -->
                        <td class="py-2 px-3 text-center text-[var(--ink3)] border-r border-slate-100 dark:border-slate-800/60">
                            {{ $hIdx + 1 }}
                        </td>

                        <!-- DID Number -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight font-semibold text-[var(--ink1)] truncate">
                            <div class="flex items-center gap-1.5 truncate">
                                <i class="fa-solid fa-signal text-[10px] text-sky-500 flex-shrink-0"></i>
                                <span class="truncate">{{ $log->phone_number }}</span>
                            </div>
                        </td>

                        <!-- Calls Fired -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center text-[var(--ink2)]">
                            {{ (int)$log->calls_requested }}
                        </td>

                        <!-- Channels Detected -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center font-bold text-violet-600 dark:text-violet-400">
                            {{ (int)$log->channels_detected }}
                        </td>

                        <!-- Status Badge -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center">
                            <span class="spill s-pass">
                                <span class="sdot"></span>
                                {{ ucfirst(strtolower($log->status)) }}
                            </span>
                        </td>

                        <!-- Timestamp (Anchored Right) -->
                        <td class="py-2 px-3 text-right text-[11px] text-[var(--ink3)]">
                            {{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-xs text-[var(--ink3)]">
                            <i class="fa-solid fa-list-check text-lg mb-2 block opacity-40"></i>
                            No channel diagnostic tests recorded yet. Execute channel tests directly from the DID Routes view.
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
function filterChHist(){
    var searchVal = (document.getElementById('chHistSearch').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#chHistGridTbody tr.ch-hist-row');
    var visibleCount = 0;

    rows.forEach(function(row){
        var phone = (row.getAttribute('data-phone') || '').toLowerCase();
        if(!searchVal || phone.includes(searchVal)){
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    var countElem = document.getElementById('chHistDisplayCount');
    if(countElem) countElem.textContent = visibleCount + ' Records';
}
</script>
@endsection
