@extends('layouts.app')

@section('title', 'DIDX — Live Connected Calls')
@section('page-title', 'Live Connected Calls')
@section('page-crumb', 'DIDX / Operations / Live Calls')

@section('content')
<div class="flex flex-col flex-1 h-full min-h-0 overflow-hidden">
    <!-- 1. TOP TELEMETRY STRIP -->
    <div class="h-9 border-b border-[var(--border)] bg-[var(--surface2)] px-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-[var(--surface)] border border-[var(--border)] font-mono text-xs">
                <i class="fa-solid fa-phone-volume text-[10px] text-emerald-500"></i>
                <span class="text-[var(--ink3)] text-[10px]">ACTIVE CHANNELS:</span>
                <span class="font-bold text-[var(--ink1)]" id="liveCallCount">{{ $callCount }}</span>
            </div>
        </div>

        <div class="flex items-center gap-2 font-mono text-xs text-[var(--ink3)]">
            <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981] animate-pulse"></span>
                <span>Real-Time Switch Polling</span>
            </span>
        </div>
    </div>

    <!-- 2. ACTION BAR -->
    <div class="h-10 px-3 py-1.5 bg-[var(--surface)] border-b border-[var(--border)] flex items-center gap-2 flex-shrink-0">
        <!-- Search Filter -->
        <div class="relative flex items-center flex-1 max-w-xs">
            <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
            <input type="text" id="liveCallSearch" placeholder="Filter Channel, Exten, Context..." oninput="filterLiveCalls()"
                   class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-emerald-500 w-full transition-all">
        </div>

        <div class="flex-1"></div>

        <!-- Hangup All -->
        @if(!empty($calls) && count($calls) > 0)
            <form method="POST" action="{{ route('calls.hangup-all') }}" class="m-0" onsubmit="return confirm('Disconnect ALL active calls?')">
                @csrf
                <button type="submit" class="btn-dense btn-dense-del flashing" title="Hangup all active sessions">
                    <i class="fa-solid fa-phone-slash text-[10px]"></i> <span>Hangup All Calls</span>
                </button>
            </form>
        @endif

        <span class="text-[11px] font-mono text-[var(--ink3)] ml-1" id="liveCallDisplayCount">
            {{ $callCount }} Active
        </span>
    </div>

    <!-- 3. HIGH-DENSITY DATA GRID -->
    <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative bg-[var(--surface)]">
        <table class="w-full table-fixed text-xs border-collapse text-left select-text font-mono">
            <colgroup>
                <col class="w-[4%]">
                <col class="w-[36%]">
                <col class="w-[22%]">
                <col class="w-[16%]">
                <col class="w-[12%]">
                <col class="w-[10%]">
            </colgroup>
            <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 shadow-xs">
                <tr class="h-8 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">#</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Channel Identifier</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Context</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Target Extension</th>
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">State</th>
                    <th class="py-2 px-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="liveCallsGridTbody">
                @forelse($calls as $cIdx => $call)
                    <tr class="live-call-row h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors"
                        data-channel="{{ strtolower($call['channel']) }}"
                        data-context="{{ strtolower($call['context']) }}"
                        data-exten="{{ strtolower($call['exten']) }}"
                        style="border-left:3px solid var(--ok)">
                        <!-- Serial -->
                        <td class="py-2 px-3 text-center text-[var(--ink3)] border-r border-slate-100 dark:border-slate-800/60">
                            {{ $cIdx + 1 }}
                        </td>

                        <!-- Channel -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight font-semibold text-[var(--ink1)] truncate">
                            <div class="flex items-center gap-1.5 truncate">
                                <i class="fa-solid fa-phone-volume text-[10px] text-emerald-500 flex-shrink-0"></i>
                                <span class="truncate">{{ $call['channel'] }}</span>
                            </div>
                        </td>

                        <!-- Context -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-[var(--ink2)] truncate">
                            {{ $call['context'] }}
                        </td>

                        <!-- Extension -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-[var(--ink1)] font-semibold">
                            {{ $call['exten'] }}
                        </td>

                        <!-- State -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center">
                            <span class="spill s-pass">
                                <span class="sdot"></span>
                                {{ ucfirst(strtolower($call['state'])) }}
                            </span>
                        </td>

                        <!-- Action (Anchored Right) -->
                        <td class="py-2.5 px-3 text-right">
                            <form method="POST" action="{{ route('calls.hangup-channel') }}" class="m-0 inline">
                                @csrf
                                <input type="hidden" name="channel" value="{{ $call['channel'] }}">
                                <button type="submit" class="btn-dense btn-dense-del px-2" title="Hangup Channel">
                                    <i class="fa-solid fa-phone-slash text-[10px]"></i> Hangup
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">
                            <i class="fa-solid fa-headset text-lg mb-2 block opacity-40"></i>
                            No active call channels currently online.
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
function filterLiveCalls(){
    var searchVal = (document.getElementById('liveCallSearch').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#liveCallsGridTbody tr.live-call-row');
    var visibleCount = 0;

    rows.forEach(function(row){
        var ch = (row.getAttribute('data-channel') || '').toLowerCase();
        var ctx = (row.getAttribute('data-context') || '').toLowerCase();
        var ext = (row.getAttribute('data-exten') || '').toLowerCase();

        var matches = !searchVal || ch.includes(searchVal) || ctx.includes(searchVal) || ext.includes(searchVal);
        if(matches){
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    var countElem = document.getElementById('liveCallDisplayCount');
    if(countElem) countElem.textContent = visibleCount + ' Active';
}
</script>
@endsection
