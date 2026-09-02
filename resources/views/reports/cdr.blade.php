@extends('layouts.app')

@section('title', 'DIDX — Call Reports & CDRs')
@section('page-title', 'Call Detail Records (CDR)')
@section('page-crumb', 'DIDX / Analytics / Call Reports')

@section('content')
<div class="flex flex-col flex-1 h-full min-h-0 overflow-hidden">
    <!-- 1. TOP TELEMETRY STRIP -->
    <div class="h-9 border-b border-[var(--border)] bg-[var(--surface2)] px-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-[var(--surface)] border border-[var(--border)] font-mono text-xs">
                <i class="fa-solid fa-file-lines text-[10px] text-amber-500"></i>
                <span class="text-[var(--ink3)] text-[10px]">RECORDS LOADED:</span>
                <span class="font-bold text-[var(--ink1)]">{{ count($cdrs) }}</span>
            </div>
            @if($search)
                <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-500/10 border border-blue-500/20 font-mono text-xs text-blue-600 dark:text-blue-400">
                    <i class="fa-solid fa-filter text-[10px]"></i>
                    <span>FILTER: "{{ $search }}"</span>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2 font-mono text-xs text-[var(--ink3)]">
            <span class="flex items-center gap-1">
                <i class="fa-solid fa-database text-[10px] text-emerald-500"></i>
                <span>CDR Storage: telecom_db.cdr</span>
            </span>
        </div>
    </div>

    <!-- 2. ACTION BAR -->
    <div class="h-10 px-3 py-1.5 bg-[var(--surface)] border-b border-[var(--border)] flex items-center gap-2 flex-shrink-0">
        <form method="GET" action="{{ route('reports.cdr') }}" class="flex items-center gap-1.5 m-0 flex-1 max-w-sm">
            <div class="relative flex items-center flex-1">
                <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
                <input type="text" name="search" placeholder="Search Caller ID / DID..." value="{{ $search }}"
                       class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-amber-500 w-full transition-all">
            </div>
            <button type="submit" class="btn-dense btn-dense-primary" title="Search CDR logs">
                <i class="fa-solid fa-search text-[10px]"></i> <span>Search</span>
            </button>
            @if($search)
                <a href="{{ route('reports.cdr') }}" class="btn-dense btn-dense-ghost text-xs" title="Reset Search">
                    <i class="fa-solid fa-rotate-left text-[10px]"></i>
                </a>
            @endif
        </form>

        <div class="flex-1"></div>

        <span class="text-[11px] font-mono text-[var(--ink3)]">
            {{ count($cdrs) }} Entries
        </span>
    </div>

    <!-- 3. HIGH-DENSITY DATA GRID -->
    <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative bg-[var(--surface)]">
        <table class="w-full table-fixed text-xs border-collapse text-left select-text font-mono">
            <colgroup>
                <col class="w-[4%]">
                <col class="w-[18%]">
                <col class="w-[24%]">
                <col class="w-[24%]">
                <col class="w-[10%]">
                <col class="w-[10%]">
                <col class="w-[10%]">
            </colgroup>
            <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 shadow-xs">
                <tr class="h-8 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">#</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Start Time</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Caller ID</th>
                    <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Destination</th>
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Duration</th>
                    <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Billsec</th>
                    <th class="py-2 px-3 text-right">Disposition</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($cdrs as $cIdx => $cdr)
                    @php $isAnswered = ($cdr->disposition === 'ANSWERED'); @endphp
                    <tr class="h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors"
                        style="border-left:3px solid {{ $isAnswered ? 'var(--ok)' : 'var(--danger)' }}">
                        <!-- Serial -->
                        <td class="py-2 px-3 text-center text-[var(--ink3)] border-r border-slate-100 dark:border-slate-800/60">
                            {{ $cIdx + 1 }}
                        </td>

                        <!-- Date Time -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-[var(--ink3)] text-[11px]">
                            {{ $cdr->start_time?->format('Y-m-d H:i:s') ?? '—' }}
                        </td>

                        <!-- Caller ID -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight font-semibold text-[var(--ink1)] truncate">
                            {{ $cdr->caller_id }}
                        </td>

                        <!-- Destination -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight text-[var(--ink2)] truncate">
                            {{ $cdr->destination }}
                        </td>

                        <!-- Duration -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center text-[var(--ink2)]">
                            {{ $cdr->duration }}s
                        </td>

                        <!-- Billsec -->
                        <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center font-bold text-[var(--ink1)]">
                            {{ $cdr->billsec }}s
                        </td>

                        <!-- Disposition (Anchored Right) -->
                        <td class="py-2 px-3 text-right">
                            <span class="spill {{ $isAnswered ? 's-pass' : 's-fail' }}">
                                <span class="sdot"></span>
                                {{ ucfirst(strtolower($cdr->disposition)) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">
                            <i class="fa-solid fa-file-invoice text-lg mb-2 block opacity-40"></i>
                            No CDR logs found @if($search) matching "{{ $search }}" @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
