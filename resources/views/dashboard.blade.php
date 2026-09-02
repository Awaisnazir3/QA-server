@extends('layouts.app')

@section('title', 'DIDX — DID Route Manager')
@section('page-title', 'DID Route Manager')
@section('page-crumb', 'DIDX / Softswitch / DID Manager')

@section('content')
<div class="flex flex-col flex-1 h-full min-h-0 overflow-hidden" id="workspaceRoot">
    <!-- 1. INTERNAL WORKSPACE TAB BAR -->
    <div class="h-9 border-b border-[var(--border)] bg-[var(--surface2)] px-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-1" id="workspaceTabs">
            <button type="button" class="ws-tab active flex items-center gap-1.5 px-3 py-1 rounded text-xs font-semibold text-[var(--ink1)] bg-[var(--surface)] border border-[var(--border)] transition-all shadow-sm" data-tab="tabDidRoutes">
                <i class="fa-solid fa-route text-amber-500 text-[11px]"></i>
                <span>DID Routes</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-amber-500/10 text-amber-600 font-bold" id="tabDidRoutesBadge">
                    {{ $totalDids }}
                </span>
            </button>
            <button type="button" class="ws-tab flex items-center gap-1.5 px-3 py-1 rounded text-xs font-semibold text-[var(--ink3)] hover:text-[var(--ink1)] bg-transparent border border-transparent transition-all" data-tab="tabChannelTests">
                <i class="fa-solid fa-signal text-sky-500 text-[11px]"></i>
                <span>Channel Tests</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-sky-500/10 text-sky-600 font-bold">
                    {{ count($channelHistory ?? []) }}
                </span>
            </button>
            <button type="button" class="ws-tab flex items-center gap-1.5 px-3 py-1 rounded text-xs font-semibold text-[var(--ink3)] hover:text-[var(--ink1)] bg-transparent border border-transparent transition-all" data-tab="tabLiveCalls">
                <i class="fa-solid fa-phone-volume text-emerald-500 text-[11px]"></i>
                <span>Live Calls</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-emerald-500/10 text-emerald-600 font-bold" id="tabLiveCallsBadge">
                    {{ count($liveCalls ?? []) }}
                </span>
            </button>
        </div>

        <div class="flex items-center gap-2 text-[11px] font-mono text-[var(--ink3)]">
            <span class="flex items-center gap-1" title="Real-time status sync rate">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Auto-Sync: 3s</span>
            </span>
            <button type="button" onclick="updateDIDStatuses()" class="btn-dense btn-dense-ghost px-1.5 text-[10px]" title="Manual Sync Now">
                <i class="fa-solid fa-rotate text-[10px]" id="refreshSpinIcon"></i>
            </button>
        </div>
    </div>

    <!-- 2. TAB CONTENT PANES (INDEPENDENT VIEWS) -->
    <div class="flex-1 flex flex-col min-h-0 overflow-hidden relative">
        
        <!-- ==============================================
             PANEL A: DID ROUTE MANAGER (PRIMARY DATA GRID)
             ============================================== -->
        <div id="tabDidRoutes" class="tab-pane flex flex-col flex-1 h-full min-h-0 overflow-hidden">
            <!-- Flash Message Banner -->
            @if(session('success'))
                <div class="px-3 py-1.5 bg-emerald-500/10 border-b border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-mono flex items-center justify-between flex-shrink-0" id="flashSuccessMsg">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-[11px]"></i> {{ session('success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-[10px]"></i></button>
                </div>
            @elseif(session('warning'))
                <div class="px-3 py-1.5 bg-amber-500/10 border-b border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-mono flex items-center justify-between flex-shrink-0" id="flashWarningMsg">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation text-[11px]"></i> {{ session('warning') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-[10px]"></i></button>
                </div>
            @elseif(session('error'))
                <div class="px-3 py-1.5 bg-rose-500/10 border-b border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-mono flex items-center justify-between flex-shrink-0" id="flashErrorMsg">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[11px]"></i> {{ session('error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-[10px]"></i></button>
                </div>
            @endif

            <!-- ACTION BAR: SINGLE COMPACT HORIZONTAL TOOLBAR -->
            <div class="h-10 px-3 py-1.5 bg-[var(--surface)] border-b border-[var(--border)] flex items-center gap-2 flex-shrink-0 overflow-x-auto">
                <!-- Provision DID Form -->
                <form method="POST" action="{{ route('dashboard.provision') }}" class="flex items-center gap-1 m-0 flex-shrink-0" id="provisionForm">
                    @csrf
                    <div class="relative flex items-center">
                        <i class="fa-solid fa-plus text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
                        <input type="text" name="phone_number" placeholder="DID (e.g. 44987654320)" required
                               class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-amber-500 w-44 transition-all">
                    </div>
                    <button type="submit" class="btn-dense btn-dense-primary" title="Deploy DID to switch">
                        <i class="fa-solid fa-bolt text-[10px]"></i> <span>Deploy</span>
                    </button>
                </form>

                <div class="h-4 w-[1px] bg-[var(--border)] flex-shrink-0"></div>

                <!-- Instant Real-Time Filter Search -->
                <div class="relative flex items-center flex-1 min-w-[160px] max-w-xs">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2 text-[10px] pointer-events-none"></i>
                    <input type="text" id="gridSearchInput" placeholder="Filter DID, IP, User..."
                           class="h-[26px] pl-6 pr-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] placeholder-slate-400 focus:outline-none focus:border-amber-500 w-full transition-all"
                           oninput="filterDidGrid()">
                </div>

                <!-- Status Filter Pills -->
                <div class="flex items-center bg-[var(--surface2)] p-0.5 rounded border border-[var(--border)] flex-shrink-0" id="statusFilterPills">
                    <button type="button" class="status-btn active px-2 py-0.5 rounded text-[10px] font-mono font-bold" data-status="all">ALL</button>
                    <button type="button" class="status-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400" data-status="pass">PASS</button>
                    <button type="button" class="status-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-rose-600 dark:text-rose-400" data-status="fail">FAIL</button>
                    <button type="button" class="status-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-amber-600 dark:text-amber-400" data-status="route">ROUTE</button>
                    <button type="button" class="status-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-slate-500" data-status="pending">PENDING</button>
                </div>

                <div class="flex-1"></div>

                <!-- Batch Actions -->
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <form method="POST" action="{{ route('dashboard.hangup-all') }}" id="hangupForm" class="m-0" onsubmit="return handleHangupSubmit(event)">
                        @csrf
                        <button type="submit" id="hangupBtn" class="btn-dense btn-dense-del {{ ($stats['activeCalls'] ?? 0) > 0 ? 'flashing' : '' }}" title="Disconnect all active sessions">
                            <i class="fa-solid fa-phone-slash text-[10px]"></i> <span>Hangup All</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('dashboard.clear-all') }}" class="m-0" onsubmit="return confirm('Delete ALL active DID routes?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-dense btn-dense-ghost hover:text-red-500 hover:border-red-400" title="Delete all DID records">
                            <i class="fa-solid fa-trash-can text-[10px]"></i> <span>Clear All</span>
                        </button>
                    </form>

                    <span class="text-[11px] font-mono text-[var(--ink3)] ml-1 border-l border-[var(--border)] pl-2" id="gridCountDisplay">
                        {{ $totalDids }} DIDs
                    </span>
                </div>
            </div>

            <!-- HIGH-DENSITY DATA GRID (~34px ROW HEIGHT, STICKY HEADER) -->
            <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative bg-[var(--surface)]" id="didGridContainer">
                <table class="w-full table-fixed text-xs border-collapse text-left select-text" id="didGridTable">
                    <colgroup>
                        <col class="w-[4%]">
                        <col class="w-[23%]">
                        <col class="w-[23%]">
                        <col class="w-[12%]">
                        <col class="w-[18%]">
                        <col class="w-[8%]">
                        <col class="w-[12%]">
                    </colgroup>
                    <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 shadow-xs">
                        <tr class="h-8 text-[11px] font-mono font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">#</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">DID / Phone Number</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Source IP / Host</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70 text-center">Status</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70 text-center">Channel Diagnostic</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70 text-center">Channels</th>
                            <th class="py-2 px-3 text-right">Action Controls</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="didGridTbody">
                        @php $serialNumber = $totalDids; @endphp
                        @forelse($callLogs as $log)
                            @php
                                $status = !empty($log->status) ? strtolower(trim($log->status)) : 'pending';
                                if (!in_array($status, ['pass', 'fail', 'route'])) $status = 'pending';
                                $channelsDetected = $log->checked_channels !== null ? (int)$log->checked_channels : '—';
                                $sourceIp = $log->source_ip ?? '—';
                            @endphp
                            <tr data-id="{{ $log->id }}"
                                data-status="{{ $status }}"
                                data-did="{{ $log->phone_number }}"
                                data-ip="{{ $sourceIp }}"
                                class="did-row h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors"
                                style="border-left:3px solid {{ $status === 'pass' ? 'var(--ok)' : ($status === 'route' ? 'var(--accent)' : ($status === 'fail' ? 'var(--danger)' : 'transparent')) }}">
                                <!-- Serial Number -->
                                <td class="py-2 px-3 text-center font-mono text-[11px] text-[var(--ink3)] border-r border-slate-100 dark:border-slate-800/60">
                                    {{ $serialNumber-- }}
                                </td>

                                <!-- DID Number & User Tag -->
                                <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 truncate">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="font-mono tracking-tight font-semibold text-xs text-[var(--ink1)]">
                                            {{ $log->phone_number }}
                                        </span>
                                        @if($log->user)
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 flex-shrink-0" title="Provisioned by {{ $log->user->username }}">
                                                {{ $log->user->username }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Source IP Address -->
                                <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 truncate">
                                    <span class="font-mono tracking-tight font-semibold text-xs text-[var(--ink2)] source-ip-text truncate block">
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

                                <!-- Channel Diagnostic Test Form -->
                                <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-800/60 channel-test-cell">
                                    @if($status === 'pass')
                                        <form method="POST" action="{{ route('tests.test', $log->id) }}" class="m-0 inline-flex items-center justify-center gap-1" onsubmit="return startChTest(this, {{ $log->id }})">
                                            @csrf
                                            <button type="submit" class="btn-dense btn-dense-amber" title="Execute channel diagnostic test">
                                                <i class="fa-solid fa-signal text-[9.5px]"></i> <span>Test</span>
                                            </button>
                                            <input type="number" class="w-9 h-[22px] px-1 text-center font-mono text-[11px] font-bold border border-[var(--border)] rounded bg-[var(--surface2)] text-[var(--ink1)] focus:outline-none focus:border-amber-500" name="call_count" id="cc_input_{{ $log->id }}" value="5" min="1" max="100" title="Call count (1-100)">
                                        </form>
                                    @else
                                        <div class="inline-flex items-center justify-center gap-1 opacity-50">
                                            <button type="button" class="btn-dense btn-dense-ghost cursor-not-allowed text-slate-400" onclick="showErrModal('DID status must be PASS to run channel test. Current: {{ strtoupper($status) }}')">
                                                <i class="fa-solid fa-lock text-[9.5px]"></i> <span>Test</span>
                                            </button>
                                            <input type="number" class="w-9 h-[22px] px-1 text-center font-mono text-[11px] border border-[var(--border)] rounded bg-[var(--surface2)] text-slate-400" value="5" disabled>
                                        </div>
                                    @endif
                                </td>

                                <!-- Detected Channels -->
                                <td class="py-2 px-3 text-center border-r border-slate-100 dark:border-slate-800/60">
                                    <span class="font-mono tracking-tight font-semibold text-xs text-[var(--ink1)]">
                                        {{ $channelsDetected }}
                                    </span>
                                </td>

                                <!-- Action Controls -->
                                <td class="py-2 px-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        @if($status === 'pass')
                                            <button type="button"
                                                    class="btn-dense btn-dense-ok btn-route-action"
                                                    onclick="handleRouteDID({{ $log->id }}, '{{ $log->phone_number }}')"
                                                    title="Route associated DID to 7788">
                                                <i class="fa-solid fa-route text-[10px]"></i> <span>Route</span>
                                            </button>
                                        @elseif($status === 'route')
                                            <button type="button"
                                                    class="btn-dense btn-dense-ghost text-amber-600 dark:text-amber-400 border-amber-500/30 btn-route-action"
                                                    onclick="handleRouteDID({{ $log->id }}, '{{ $log->phone_number }}')"
                                                    title="Re-route associated DID to 7788">
                                                <i class="fa-solid fa-check text-[10px]"></i> <span>Routed</span>
                                            </button>
                                        @else
                                            <button type="button"
                                                    class="btn-dense btn-dense-ghost text-slate-400 opacity-60 cursor-not-allowed btn-route-action"
                                                    onclick="handleRouteDID({{ $log->id }}, '{{ $log->phone_number }}')"
                                                    title="DID status must be PASS to route on 7788">
                                                <i class="fa-solid fa-lock text-[10px]"></i> <span>Route</span>
                                            </button>
                                        @endif
                                        <form method="POST" action="{{ route('dashboard.reset', $log->id) }}" class="m-0 inline">
                                            @csrf
                                            <button type="submit" class="btn-dense btn-dense-ghost px-1.5" title="Reset status & IP">
                                                <i class="fa-solid fa-rotate-left text-[10px]"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('dashboard.destroy', $log->id) }}" class="m-0 inline" onsubmit="return confirm('Remove DID {{ $log->phone_number }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-dense btn-dense-del px-1.5" title="Delete DID record">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyGridRow">
                                <td colspan="7" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">
                                    <i class="fa-solid fa-database text-lg mb-2 block opacity-40"></i>
                                    No DID numbers provisioned yet. Use the action bar above to deploy one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==============================================
             PANEL B: CHANNEL TESTS AUDIT HISTORY
             ============================================== -->
        <div id="tabChannelTests" class="tab-pane hidden flex flex-col flex-1 h-full min-h-0 overflow-hidden bg-[var(--surface)]">
            <div class="h-10 px-3 py-1.5 bg-[var(--surface2)] border-b border-[var(--border)] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-sky-500 text-xs"></i>
                    <span class="text-xs font-bold text-[var(--ink1)] font-disp">Channel Test Audit History</span>
                </div>
                <div class="text-[11px] font-mono text-[var(--ink3)]">
                    {{ count($channelHistory ?? []) }} Diagnostic Logs
                </div>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto">
                <table class="w-full table-fixed text-xs border-collapse text-left">
                    <colgroup>
                        <col class="w-[28%]">
                        <col class="w-[16%]">
                        <col class="w-[18%]">
                        <col class="w-[16%]">
                        <col class="w-[22%]">
                    </colgroup>
                    <thead class="sticky top-0 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] font-mono font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr class="h-8">
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">DID Number</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Calls Fired</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Channels Found</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Status</th>
                            <th class="py-2 px-3 text-right">Executed At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
                        @forelse($channelHistory ?? [] as $hist)
                            <tr class="h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2 px-3 font-mono tracking-tight font-semibold text-[var(--ink1)] border-r border-slate-100 dark:border-slate-800/60 truncate">{{ $hist->phone_number }}</td>
                                <td class="py-2 px-3 text-center text-[var(--ink2)] border-r border-slate-100 dark:border-slate-800/60">{{ (int)$hist->calls_requested }}</td>
                                <td class="py-2 px-3 text-center font-bold text-violet-500 border-r border-slate-100 dark:border-slate-800/60">{{ (int)$hist->channels_detected }}</td>
                                <td class="py-2 px-3 text-center border-r border-slate-100 dark:border-slate-800/60">
                                    <span class="spill s-pass">
                                        <span class="sdot"></span>
                                        {{ ucfirst(strtolower($hist->status)) }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-right text-[var(--ink3)] text-[11px]">
                                    {{ $hist->created_at ? $hist->created_at->format('Y-m-d H:i:s') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">
                                    No channel diagnostic logs available yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==============================================
             PANEL C: LIVE ACTIVE CALLS MONITOR
             ============================================== -->
        <div id="tabLiveCalls" class="tab-pane hidden flex flex-col flex-1 h-full min-h-0 overflow-hidden bg-[var(--surface)]">
            <div class="h-10 px-3 py-1.5 bg-[var(--surface2)] border-b border-[var(--border)] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-phone-volume text-emerald-500 text-xs"></i>
                    <span class="text-xs font-bold text-[var(--ink1)] font-disp">Live Active Channels</span>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('calls.hangup-all') }}" class="m-0" onsubmit="return confirm('Disconnect all live calls?')">
                        @csrf
                        <button type="submit" class="btn-dense btn-dense-del">
                            <i class="fa-solid fa-phone-slash text-[10px]"></i> <span>Hangup All Live Calls</span>
                        </button>
                    </form>
                    <span class="text-[11px] font-mono text-[var(--ink3)]">
                        {{ count($liveCalls ?? []) }} Active
                    </span>
                </div>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto">
                <table class="w-full table-fixed text-xs border-collapse text-left font-mono">
                    <colgroup>
                        <col class="w-[36%]">
                        <col class="w-[22%]">
                        <col class="w-[18%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                    </colgroup>
                    <thead class="sticky top-0 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr class="h-8">
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Channel Name</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Context</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Extension</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">State</th>
                            <th class="py-2 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs" id="liveCallsTbody">
                        @forelse($liveCalls ?? [] as $call)
                            <tr class="h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2 px-3 font-mono tracking-tight font-semibold text-[var(--ink1)] border-r border-slate-100 dark:border-slate-800/60 truncate">{{ $call['channel'] }}</td>
                                <td class="py-2 px-3 text-[var(--ink2)] border-r border-slate-100 dark:border-slate-800/60 truncate">{{ $call['context'] }}</td>
                                <td class="py-2 px-3 text-[var(--ink2)] border-r border-slate-100 dark:border-slate-800/60">{{ $call['exten'] }}</td>
                                <td class="py-2 px-3 text-center border-r border-slate-100 dark:border-slate-800/60">
                                    <span class="spill s-pass">
                                        <span class="sdot"></span>
                                        {{ ucfirst(strtolower($call['state'])) }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-right">
                                    <form method="POST" action="{{ route('calls.hangup-channel') }}" class="m-0 inline">
                                        @csrf
                                        <input type="hidden" name="channel" value="{{ $call['channel'] }}">
                                        <button type="submit" class="btn-dense btn-dense-del px-2" title="Hangup channel">
                                            <i class="fa-solid fa-phone-slash text-[10px]"></i> Hangup
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">
                                    No active calls in session right now.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==============================================
         3. COLLAPSIBLE BOTTOM PANEL: LIVE AMI / ASTERISK LOG STREAMER
         ============================================== -->
    <div class="border-t border-[var(--border)] bg-[#070b14] flex flex-col flex-shrink-0 transition-all duration-200" id="amiLogPanel" style="height:28px">
        <!-- Docked Top Strip (Click to toggle expand/collapse) -->
        <div class="h-7 px-3 flex items-center justify-between cursor-pointer select-none text-[11px] font-mono hover:bg-white/5 transition-colors" id="amiLogHeader">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399] animate-pulse"></span>
                <span class="font-bold text-emerald-400 text-[10px] tracking-wider">LIVE AMI STREAM</span>
                <span class="text-slate-500">|</span>
                <span class="text-slate-400 text-[10.5px] truncate max-w-md hidden sm:inline" id="amiLatestSnippet">
                    [System] AMI event listener initialized and listening...
                </span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-slate-400 text-[10px]" id="amiEventCounter">0 events</span>
                <span class="text-slate-400 hover:text-white transition-colors" id="amiExpandBtn" title="Toggle Terminal Height">
                    <i class="fa-solid fa-chevron-up text-[10px]" id="amiExpandIcon"></i>
                </span>
            </div>
        </div>

        <!-- Expanded Terminal Body -->
        <div class="flex-1 min-h-0 flex flex-col overflow-hidden bg-[#04060c]" id="amiTerminalContainer" style="display:none">
            <!-- Terminal Controls Bar -->
            <div class="h-7 px-3 bg-[#0a101f] border-b border-[#16233d] flex items-center justify-between text-[10px] font-mono">
                <div class="flex items-center gap-2">
                    <span class="text-slate-300 font-semibold"><i class="fa-solid fa-terminal text-sky-400 mr-1"></i>Asterisk AMI Event Bus</span>
                    <span class="text-slate-600">|</span>
                    <select id="amiFilterSelect" class="bg-[#050812] border border-[#1d2d4d] text-slate-300 text-[10px] rounded px-1.5 py-0.5 outline-none" onchange="filterAmiLogs(this.value)">
                        <option value="all">All Events</option>
                        <option value="call">Calls & Dials</option>
                        <option value="channel">Channels & RTP</option>
                        <option value="peer">SIP Peer Status</option>
                        <option value="system">System & AMI</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="toggleLogPause()" id="pauseLogBtn" class="text-slate-400 hover:text-white px-1.5 py-0.5 rounded hover:bg-white/5 transition-colors">
                        <i class="fa-solid fa-pause mr-1"></i>Pause
                    </button>
                    <button type="button" onclick="clearAmiLogs()" class="text-slate-400 hover:text-white px-1.5 py-0.5 rounded hover:bg-white/5 transition-colors">
                        <i class="fa-solid fa-eraser mr-1"></i>Clear
                    </button>
                    <button type="button" onclick="injectSimulatedAmiEvent()" class="text-amber-400 hover:text-amber-300 px-1.5 py-0.5 rounded hover:bg-white/5 transition-colors" title="Simulate test AMI packet">
                        <i class="fa-solid fa-flask mr-1"></i>Simulate
                    </button>
                </div>
            </div>

            <!-- Terminal Output Window -->
            <div class="flex-1 overflow-y-auto p-2 font-mono text-[11px] leading-relaxed select-text space-y-0.5 text-slate-300" id="amiLogBody">
                <!-- Log entries injected here -->
            </div>
        </div>
    </div>
</div>

<!-- ==============================================
     4. SLIDE-OVER SIDE DRAWER: ROUTE CONFIGURATION
     ============================================== -->
<div id="routeDrawerBackdrop" class="fixed inset-0 bg-black/50 backdrop-blur-[2px] z-40 hidden transition-opacity" onclick="closeRouteDrawer()"></div>

<aside id="routeDrawer" class="fixed inset-y-0 right-0 z-50 w-[420px] max-w-[90vw] bg-[var(--surface)] border-l border-[var(--border)] shadow-2xl flex flex-col transform transition-transform duration-200 translate-x-full select-text">
    <!-- Drawer Header -->
    <div class="h-12 px-4 border-b border-[var(--border)] bg-[var(--surface2)] flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-xs">
                <i class="fa-solid fa-route"></i>
            </div>
            <div>
                <div class="font-disp font-bold text-xs text-[var(--ink1)]">Configure Route Destination</div>
                <div class="font-mono text-[10px] text-[var(--ink3)]">Live Route Dispatcher</div>
            </div>
        </div>
        <button type="button" onclick="closeRouteDrawer()" class="w-6 h-6 rounded flex items-center justify-center text-[var(--ink3)] hover:text-[var(--ink1)] hover:bg-[var(--hover)] transition-colors border-none bg-transparent cursor-pointer">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    <!-- Drawer Body (Form) -->
    <form id="routeDrawerForm" method="POST" action="" class="flex-1 flex flex-col min-h-0 m-0 overflow-y-auto p-4 space-y-4">
        @csrf
        <!-- DID Summary Card -->
        <div class="bg-[var(--surface2)] border border-[var(--border)] rounded-md p-3 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-mono uppercase font-bold text-[var(--ink3)]">Target DID</span>
                <span class="font-mono text-sm font-bold text-[var(--ink1)]" id="drawerDidNumber">—</span>
            </div>
            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-[var(--bordersoft)] text-[11px] font-mono">
                <div>
                    <span class="text-[var(--ink3)] text-[10px] block">Current Status</span>
                    <span id="drawerDidStatus" class="font-bold uppercase text-emerald-600">PASS</span>
                </div>
                <div>
                    <span class="text-[var(--ink3)] text-[10px] block">Source IP</span>
                    <span id="drawerSourceIp" class="text-[var(--ink2)]">—</span>
                </div>
            </div>
        </div>

        <!-- Destination Routing Type -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold text-[var(--ink1)] block">Routing Destination Type</label>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center gap-2 p-2 border border-[var(--border)] rounded-md cursor-pointer hover:border-amber-500 bg-[var(--surface)] text-xs">
                    <input type="radio" name="dest_type" value="sip_trunk" checked class="accent-amber-500">
                    <span class="font-semibold text-[var(--ink1)]">SIP Trunk</span>
                </label>
                <label class="flex items-center gap-2 p-2 border border-[var(--border)] rounded-md cursor-pointer hover:border-amber-500 bg-[var(--surface)] text-xs">
                    <input type="radio" name="dest_type" value="ip_pbx" class="accent-amber-500">
                    <span class="font-semibold text-[var(--ink1)]">IP PBX Gateway</span>
                </label>
            </div>
        </div>

        <!-- Primary Trunk / Gateway Selection -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold text-[var(--ink1)] block">Primary Trunk / Carrier Endpoint</label>
            <select name="sip_peer" id="drawerTrunkSelect" class="w-full h-8 px-2.5 bg-[var(--surface2)] border border-[var(--border)] rounded-md text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-amber-500">
                @forelse($peerList ?? [] as $peer)
                    <option value="{{ $peer['name'] }}">
                        {{ $peer['name'] }} ({{ $peer['ip'] }}) — {{ $peer['status'] }}
                    </option>
                @empty
                    <option value="VPL-Switch">VPL-Switch (104.131.49.119)</option>
                    <option value="ca.didx.net">ca.didx.net (68.183.206.46)</option>
                    <option value="eu2.didx.net">eu2.didx.net (178.62.98.165)</option>
                    <option value="sip10.didx.net">sip10.didx.net (198.211.99.232)</option>
                @endforelse
            </select>
            <p class="text-[10px] text-[var(--ink3)] font-mono">Dispatches inbound sessions via selected Asterisk PJSIP trunk.</p>
        </div>

        <!-- Custom Forward Host / Port -->
        <div class="grid grid-cols-3 gap-2">
            <div class="col-span-2 space-y-1">
                <label class="text-xs font-semibold text-[var(--ink1)] block">Direct Host / IP (Optional)</label>
                <input type="text" name="custom_host" placeholder="e.g. 192.168.1.100" class="w-full h-8 px-2 bg-[var(--surface2)] border border-[var(--border)] rounded-md text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-amber-500">
            </div>
            <div class="space-y-1">
                <label class="text-xs font-semibold text-[var(--ink1)] block">SIP Port</label>
                <input type="text" name="custom_port" value="5060" class="w-full h-8 px-2 bg-[var(--surface2)] border border-[var(--border)] rounded-md text-xs font-mono text-[var(--ink1)] text-center focus:outline-none focus:border-amber-500">
            </div>
        </div>

        <!-- Fallback Failover Route -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold text-[var(--ink1)] block">Failover / Secondary Destination</label>
            <select name="failover_peer" class="w-full h-8 px-2.5 bg-[var(--surface2)] border border-[var(--border)] rounded-md text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-amber-500">
                <option value="">None (Drop on Congestion/Timeout)</option>
                <option value="us2.didx.net">us2.didx.net (Failover Cluster)</option>
                <option value="eu3.didx.net">eu3.didx.net (Backup Node)</option>
            </select>
        </div>

        <!-- Codec Negotiations -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold text-[var(--ink1)] block">Accepted Voice Codecs</label>
            <div class="flex items-center gap-3 text-xs font-mono">
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" checked class="accent-amber-500"> G.711u (ulaw)
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" checked class="accent-amber-500"> G.711a (alaw)
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" class="accent-amber-500"> G.729
                </label>
            </div>
        </div>

        <div class="flex-1"></div>

        <!-- Drawer Footer Buttons -->
        <div class="pt-3 border-t border-[var(--border)] flex items-center justify-end gap-2">
            <button type="button" onclick="closeRouteDrawer()" class="btn-dense btn-dense-ghost h-8 px-3 text-xs">
                Cancel
            </button>
            <button type="submit" class="btn-dense btn-dense-ok h-8 px-4 text-xs font-semibold" id="drawerSubmitBtn">
                <i class="fa-solid fa-check text-[11px]"></i> <span>Confirm &amp; Set Route</span>
            </button>
        </div>
    </form>
</aside>

<!-- ==============================================
     5. MODALS: ERROR & CONFIRM
     ============================================== -->
<div id="errModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 hidden items-center justify-center">
    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-6 max-w-sm w-[90%] shadow-2xl text-center space-y-4">
        <div class="w-12 h-12 rounded-full bg-rose-500/10 text-rose-500 mx-auto flex items-center justify-center text-xl">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <div class="font-disp font-bold text-base text-[var(--ink1)]">Channel Diagnostic Blocked</div>
            <div id="errModalMsg" class="font-mono text-xs text-[var(--ink2)] mt-1.5 leading-relaxed"></div>
        </div>
        <button onclick="closeErrModal()" class="btn-dense btn-dense-primary h-8 px-6 text-xs font-bold rounded-full w-full">
            Understood
        </button>
    </div>
</div>

<div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 hidden items-center justify-center">
    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-6 max-w-sm w-[90%] shadow-2xl text-center space-y-4">
        <div class="w-12 h-12 rounded-full bg-rose-500/10 text-rose-500 mx-auto flex items-center justify-center text-xl">
            <i class="fa-solid fa-phone-slash"></i>
        </div>
        <div>
            <div id="confirmModalTitle" class="font-disp font-bold text-base text-[var(--ink1)]">Disconnect All Calls?</div>
            <div id="confirmModalMsg" class="font-mono text-xs text-[var(--ink2)] mt-1.5 leading-relaxed"></div>
        </div>
        <div class="flex items-center justify-center gap-2 pt-2">
            <button onclick="closeConfirmModal()" class="btn-dense btn-dense-ghost h-8 px-4 text-xs font-semibold">
                Cancel
            </button>
            <button onclick="confirmModalYes()" class="btn-dense btn-dense-del h-8 px-4 text-xs font-semibold">
                Yes, Disconnect
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
/**
 * Desktop Softswitch Console Manager
 */
var _confirmCallback = null;
var autoUpdateInterval = null;
var isLogPaused = false;
var totalLogEntries = 0;
var isDrawerOpen = false;

// 1. Tab Switching Engine
(function initTabs(){
    var tabs = document.querySelectorAll('.ws-tab');
    var panes = document.querySelectorAll('.tab-pane');

    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){
            var target = tab.getAttribute('data-tab');
            tabs.forEach(function(t){
                t.classList.remove('active', 'bg-[var(--surface)]', 'text-[var(--ink1)]', 'border-[var(--border)]', 'shadow-sm');
                t.classList.add('bg-transparent', 'text-[var(--ink3)]', 'border-transparent');
            });
            tab.classList.add('active', 'bg-[var(--surface)]', 'text-[var(--ink1)]', 'border-[var(--border)]', 'shadow-sm');
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

// 2. Real-Time Search & Status Filter for High-Density Grid
function filterDidGrid(){
    var searchVal = (document.getElementById('gridSearchInput').value || '').toLowerCase().trim();
    var activeStatusBtn = document.querySelector('#statusFilterPills .status-btn.active');
    var statusFilter = activeStatusBtn ? activeStatusBtn.getAttribute('data-status') : 'all';

    var rows = document.querySelectorAll('#didGridTbody tr.did-row');
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

    var countDisplay = document.getElementById('gridCountDisplay');
    if(countDisplay){
        countDisplay.textContent = visibleCount + ' DIDs';
    }
}

// Status filter pill click listeners
(function initStatusFilter(){
    var buttons = document.querySelectorAll('#statusFilterPills .status-btn');
    buttons.forEach(function(btn){
        btn.addEventListener('click', function(){
            buttons.forEach(function(b){
                b.classList.remove('active', 'bg-[var(--surface)]', 'text-[var(--ink1)]', 'shadow-xs');
            });
            btn.classList.add('active', 'bg-[var(--surface)]', 'shadow-xs');
            filterDidGrid();
        });
    });
// Strict Client-Side Duplicate Prevention on Deploy Form
(function initProvisionValidation() {
    var form = document.getElementById('provisionForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        var input = form.querySelector('input[name="phone_number"]');
        if (!input) return;
        var rawPhone = input.value.trim();
        var cleanPhone = rawPhone.replace(/[^0-9]/g, '');

        if (cleanPhone.length < 3) {
            e.preventDefault();
            showErrModal('Please enter a valid DID number (at least 3 digits).');
            return false;
        }

        // Check against existing rows in table
        var existingRow = null;
        document.querySelectorAll('#didGridTbody tr.did-row').forEach(function(row) {
            var rowDid = (row.getAttribute('data-did') || '').replace(/[^0-9]/g, '');
            if (rowDid === cleanPhone) {
                existingRow = row;
            }
        });

        if (existingRow) {
            e.preventDefault();
            var rowSt = (existingRow.getAttribute('data-status') || 'pending').toUpperCase();
            showErrModal('DID ' + rawPhone + ' is already provisioned on the switch (Status: ' + rowSt + '). Duplicate entries are not allowed.');

            existingRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            existingRow.classList.add('bg-amber-500/20');
            setTimeout(function() { existingRow.classList.remove('bg-amber-500/20'); }, 3000);
            return false;
        }
    });
})();

// 3. Direct Route DID on 7788
function handleRouteDID(didId, phoneNumber) {
    var row = document.querySelector('#didGridTbody tr.did-row[data-id="' + didId + '"]');
    var liveStatus = (row ? row.getAttribute('data-status') : '').toLowerCase().trim();
    var liveDid = (row ? row.getAttribute('data-did') : '') || phoneNumber;

    // Strict validation: Only allow routing if status of DID is pass (or already route)
    if (liveStatus !== 'pass' && liveStatus !== 'route') {
        showErrModal('DID ' + liveDid + ' cannot be routed. Status must be PASS to route on 7788. Current status: ' + (liveStatus ? liveStatus.toUpperCase() : 'PENDING') + '.');
        return false;
    }

    showConfirmModal('Route DID on 7788?', 'Route associated DID ' + liveDid + ' to 7788 now?', function() {
        var btn = row ? row.querySelector('.btn-route-action') : null;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[9.5px]"></i> <span>Routing...</span>';
        }

        fetch("{{ url('/dashboard') }}/" + didId + "/mark-route", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                sip_peer: '7788'
            })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (row) {
                    row.setAttribute('data-status', 'route');
                    row.setAttribute('data-ip', '7788');
                    row.style.borderLeftColor = 'var(--accent)';

                    var spill = row.querySelector('.spill');
                    var span = row.querySelector('.status-text');
                    if (spill && span) {
                        spill.className = 'spill s-route';
                        span.textContent = 'Route';
                    }

                    var ipElem = row.querySelector('.source-ip-text');
                    if (ipElem) ipElem.textContent = '7788';

                    if (btn) {
                        btn.disabled = false;
                        btn.className = 'btn-dense btn-dense-ghost text-amber-600 dark:text-amber-400 border-amber-500/30 btn-route-action';
                        btn.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i> <span>Routed</span>';
                        btn.title = 'Re-route associated DID to 7788';
                    }
                }

                logAmiEvent('route', 'ROUTE_7788', 'DID ' + liveDid + ' successfully routed on 7788');
                filterDidGrid();
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-route text-[10px]"></i> <span>Route</span>';
                }
                showErrModal(data.message || 'Failed to route DID on 7788.');
            }
        })
        .catch(function(err) {
            // Fallback: submit standard form if AJAX fails
            var fallbackForm = document.createElement('form');
            fallbackForm.method = 'POST';
            fallbackForm.action = "{{ url('/dashboard') }}/" + didId + "/mark-route";
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            fallbackForm.appendChild(csrfInput);
            document.body.appendChild(fallbackForm);
            fallbackForm.submit();
        });
    });
}

// 4. Route Slide-Over Side Drawer
function openRouteDrawer(didId, phoneNumber, fallbackStatus, fallbackIp, fallbackChannels){
    var drawer = document.getElementById('routeDrawer');
    var backdrop = document.getElementById('routeDrawerBackdrop');
    var form = document.getElementById('routeDrawerForm');
    if(!drawer || !backdrop || !form) return;

    // Fetch the real-time row state from DOM (reflects dynamic background polling updates)
    var row = document.querySelector('#didGridTbody tr.did-row[data-id="' + didId + '"]');
    var liveStatus = (row ? row.getAttribute('data-status') : null) || fallbackStatus || 'pending';
    var liveIp = (row ? row.getAttribute('data-ip') : null) || fallbackIp || '—';
    var liveDid = (row ? row.getAttribute('data-did') : null) || phoneNumber || '—';

    document.getElementById('drawerDidNumber').textContent = liveDid;

    // Apply accurate status text & color styling
    var statusElem = document.getElementById('drawerDidStatus');
    if (statusElem) {
        statusElem.textContent = liveStatus.toUpperCase();
        if (liveStatus === 'pass') {
            statusElem.className = 'font-bold uppercase text-emerald-600 dark:text-emerald-400';
        } else if (liveStatus === 'route') {
            statusElem.className = 'font-bold uppercase text-amber-600 dark:text-amber-400';
        } else if (liveStatus === 'fail') {
            statusElem.className = 'font-bold uppercase text-rose-600 dark:text-rose-400';
        } else {
            statusElem.className = 'font-bold uppercase text-slate-500 dark:text-slate-400';
        }
    }

    // Apply accurate source IP
    var ipElem = document.getElementById('drawerSourceIp');
    if (ipElem) {
        ipElem.textContent = (liveIp && liveIp !== '—') ? liveIp : '—';
    }

    // Reset button state
    var submitBtn = document.getElementById('drawerSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-check text-[11px]"></i> <span>Confirm &amp; Set Route</span>';
    }

    // Route form submission targets existing dashboard.mark-route endpoint
    form.action = "{{ url('/dashboard') }}/" + didId + "/mark-route";
    form.setAttribute('data-id', didId);

    backdrop.classList.remove('hidden');
    setTimeout(function(){
        drawer.classList.remove('translate-x-full');
    }, 10);
    isDrawerOpen = true;

    logAmiEvent('route', 'DRAWER_OPEN', 'DID: ' + liveDid + ' (Current: ' + liveStatus.toUpperCase() + ') awaiting route dispatch');
}

function closeRouteDrawer(){
    var drawer = document.getElementById('routeDrawer');
    var backdrop = document.getElementById('routeDrawerBackdrop');
    if(!drawer || !backdrop) return;

    drawer.classList.add('translate-x-full');
    setTimeout(function(){
        backdrop.classList.add('hidden');
    }, 200);
    isDrawerOpen = false;
}

// Handle Route Form submission via AJAX for immediate UI feedback
(function initRouteDrawerForm() {
    var form = document.getElementById('routeDrawerForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var submitBtn = document.getElementById('drawerSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[11px]"></i> <span>Routing...</span>';
        }

        var didId = form.getAttribute('data-id');
        var formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-check text-[11px]"></i> <span>Confirm &amp; Set Route</span>';
            }

            if (data.success) {
                // Update table row immediately
                var row = document.querySelector('#didGridTbody tr.did-row[data-id="' + didId + '"]');
                if (row) {
                    row.setAttribute('data-status', 'route');
                    var spill = row.querySelector('.spill');
                    var span = row.querySelector('.status-text');
                    if (spill && span) {
                        spill.className = 'spill s-route';
                        span.textContent = 'Route';
                    }
                    row.style.borderLeftColor = 'var(--accent)';

                    if (data.source_ip && data.source_ip !== '—') {
                        row.setAttribute('data-ip', data.source_ip);
                        var ipElem = row.querySelector('.source-ip-text');
                        if (ipElem) ipElem.textContent = data.source_ip;
                    }
                }

                logAmiEvent('route', 'ROUTE_DEPLOYED', 'DID ' + (data.phone_number || didId) + ' status updated to ROUTE (' + (data.source_ip || 'Trunk') + ')');

                closeRouteDrawer();
                filterDidGrid();
            } else {
                showErrModal(data.message || 'Failed to set route destination.');
            }
        })
        .catch(function(err) {
            // Fallback to standard form submission if AJAX fails
            form.submit();
        });
    });
})();

// Close drawer on Escape key
window.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && isDrawerOpen){
        closeRouteDrawer();
        closeErrModal();
        closeConfirmModal();
    }
});

// 4. Collapsible AMI Event Log Streamer
(function initAmiLogs(){
    var panel = document.getElementById('amiLogPanel');
    var header = document.getElementById('amiLogHeader');
    var container = document.getElementById('amiTerminalContainer');
    var icon = document.getElementById('amiExpandIcon');
    var isExpanded = false;

    header.addEventListener('click', function(e){
        // Don't toggle if clicking on specific buttons in header
        if(e.target.closest('#pauseLogBtn') || e.target.closest('#amiFilterSelect')) return;
        isExpanded = !isExpanded;
        if(isExpanded){
            panel.style.height = '200px';
            container.style.display = 'flex';
            icon.className = 'fa-solid fa-chevron-down text-[10px]';
        } else {
            panel.style.height = '28px';
            container.style.display = 'none';
            icon.className = 'fa-solid fa-chevron-up text-[10px]';
        }
    });

    // Add initial boot logs
    logAmiEvent('system', 'AMI_CONNECT', 'Asterisk Manager Interface bridge authenticated (127.0.0.1:5038)');
    logAmiEvent('system', 'EVENT_SUBSCRIPTION', 'Subscribed to channels, core, dial, hangup, pjsip events');
})();

function logAmiEvent(type, eventName, message){
    if(isLogPaused) return;

    var logBody = document.getElementById('amiLogBody');
    var snippet = document.getElementById('amiLatestSnippet');
    var counter = document.getElementById('amiEventCounter');
    if(!logBody) return;

    totalLogEntries++;
    if(counter) counter.textContent = totalLogEntries + ' events';

    var time = new Date().toTimeString().split(' ')[0] + '.' + String(Date.now() % 1000).padStart(3, '0');
    var colorClass = 'text-emerald-400';
    var bgBadge = 'bg-emerald-500/20 text-emerald-300';

    if(type === 'call') { colorClass = 'text-sky-400'; bgBadge = 'bg-sky-500/20 text-sky-300'; }
    else if(type === 'channel') { colorClass = 'text-violet-400'; bgBadge = 'bg-violet-500/20 text-violet-300'; }
    else if(type === 'peer') { colorClass = 'text-amber-400'; bgBadge = 'bg-amber-500/20 text-amber-300'; }
    else if(type === 'route') { colorClass = 'text-orange-400'; bgBadge = 'bg-orange-500/20 text-orange-300'; }

    var entry = document.createElement('div');
    entry.className = 'ami-entry flex items-start gap-2 py-0.5 border-b border-white/[0.02]';
    entry.setAttribute('data-type', type);
    entry.innerHTML =
        '<span class="text-slate-500 text-[10px] flex-shrink-0">' + time + '</span>' +
        '<span class="px-1.5 py-0.2 rounded text-[9.5px] font-bold ' + bgBadge + ' flex-shrink-0">[' + eventName + ']</span>' +
        '<span class="' + colorClass + ' break-all">' + escapeHtml(message) + '</span>';

    logBody.appendChild(entry);
    logBody.scrollTop = logBody.scrollHeight;

    if(snippet){
        snippet.textContent = '[' + eventName + '] ' + message;
    }
}

function filterAmiLogs(val){
    var entries = document.querySelectorAll('#amiLogBody .ami-entry');
    entries.forEach(function(e){
        if(val === 'all' || e.getAttribute('data-type') === val){
            e.style.display = 'flex';
        } else {
            e.style.display = 'none';
        }
    });
}

function clearAmiLogs(){
    var logBody = document.getElementById('amiLogBody');
    if(logBody) logBody.innerHTML = '';
    totalLogEntries = 0;
    document.getElementById('amiEventCounter').textContent = '0 events';
    logAmiEvent('system', 'LOG_CLEARED', 'Terminal buffer cleared by operator');
}

function toggleLogPause(){
    isLogPaused = !isLogPaused;
    var btn = document.getElementById('pauseLogBtn');
    if(btn){
        btn.innerHTML = isLogPaused ?
            '<i class="fa-solid fa-play mr-1 text-emerald-400"></i>Resume' :
            '<i class="fa-solid fa-pause mr-1"></i>Pause';
    }
}

function injectSimulatedAmiEvent(){
    var sampleEvents = [
        { type: 'call', name: 'Newchannel', msg: 'Channel: PJSIP/trunk-000004a2 State: Ringing CallerID: 4471239845 Context: didx-inbound' },
        { type: 'channel', name: 'VarSet', msg: 'Channel: PJSIP/trunk-000004a2 Variable: DIDX_ROUTE_MATCH Value: 44987654320' },
        { type: 'peer', name: 'PeerStatus', msg: 'Endpoint: VPL-Switch Address: 104.131.49.119:5080 Status: Reachable RTT: 6.71ms' },
        { type: 'call', name: 'Hangup', msg: 'Channel: PJSIP/trunk-000004a2 Cause: 16 (Normal Clearing) Duration: 00:01:24' }
    ];
    var randomEvent = sampleEvents[Math.floor(Math.random() * sampleEvents.length)];
    logAmiEvent(randomEvent.type, randomEvent.name, randomEvent.msg);
}

function escapeHtml(text){
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// 5. Modals Handling
function showConfirmModal(title, msg, onYes){
    document.getElementById('confirmModalTitle').textContent = title;
    document.getElementById('confirmModalMsg').textContent = msg;
    _confirmCallback = onYes;
    var modal = document.getElementById('confirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeConfirmModal(){
    var modal = document.getElementById('confirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    _confirmCallback = null;
}

function confirmModalYes(){
    var cb = _confirmCallback;
    closeConfirmModal();
    if (cb) cb();
}

function handleHangupSubmit(e){
    e.preventDefault();
    showConfirmModal('Disconnect All Calls?', 'Disconnect ALL active calls on the switch now?', function(){
        logAmiEvent('call', 'HANGUP_ALL_REQUEST', 'Operator issued Hangup All Active Calls command');
        document.getElementById('hangupForm').submit();
    });
    return false;
}

function startChTest(form, id){
    var btn = form.querySelector('.btn-dense');
    if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[9.5px]"></i> Testing...';
        btn.disabled = true;
    }
    logAmiEvent('channel', 'CHANNEL_TEST_START', 'Initiating channel diagnostic test on DID ID ' + id);
    return true;
}

function showErrModal(msg){
    document.getElementById('errModalMsg').textContent = msg;
    var modal = document.getElementById('errModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeErrModal(){
    var modal = document.getElementById('errModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// 6. Real-Time Telemetry & Status Polling (Every 3 Seconds)
function updateDIDStatuses() {
    var spinIcon = document.getElementById('refreshSpinIcon');
    if(spinIcon) spinIcon.classList.add('fa-spin');

    fetch("{{ route('api.status') }}", { cache: "no-store" })
        .then(function(response) { 
            if (!response.ok) throw new Error('API Error');
            return response.json(); 
        })
        .then(function(data) {
            if(spinIcon) setTimeout(function(){ spinIcon.classList.remove('fa-spin'); }, 500);
            if (!data) return;

            // Update Top Telemetry Ribbon Gauges
            if (data.hasOwnProperty('_asterisk_online')) {
                var astPill = document.getElementById('tbAsteriskPill');
                var astDot = document.getElementById('tbAsteriskDot');
                var astText = document.getElementById('tbAsteriskText');
                if (data['_asterisk_online']) {
                    if(astDot) astDot.className = 't-dot t-dot-ok';
                    if(astText) { astText.textContent = 'AST ONLINE'; astText.style.color = 'var(--ok)'; }
                } else {
                    if(astDot) astDot.className = 't-dot t-dot-err';
                    if(astText) { astText.textContent = 'AST OFFLINE'; astText.style.color = 'var(--danger)'; }
                }
            }

            if(data.hasOwnProperty('_ami_status') && document.getElementById('tbAmiText')){
                document.getElementById('tbAmiText').textContent = data['_ami_status'];
            }

            var activeCalls = data['_active_calls'] || 0;
            if(document.getElementById('tbActiveCallsVal')){
                document.getElementById('tbActiveCallsVal').textContent = activeCalls;
            }
            if(document.getElementById('tabLiveCallsBadge')){
                document.getElementById('tabLiveCallsBadge').textContent = activeCalls;
            }

            if(data.hasOwnProperty('_online_peers') && document.getElementById('tbPeersVal')){
                document.getElementById('tbPeersVal').textContent = data['_online_peers'];
            }
            if(data.hasOwnProperty('_ram_usage') && document.getElementById('tbRamVal')){
                document.getElementById('tbRamVal').textContent = data['_ram_usage'] + '%';
            }
            if(data.hasOwnProperty('_cpu_usage') && document.getElementById('tbCpuVal')){
                document.getElementById('tbCpuVal').textContent = data['_cpu_usage'] + '%';
            }

            // Hangup All button blinking state
            var hangupBtn = document.getElementById('hangupBtn');
            if (hangupBtn) {
                if (activeCalls > 0) hangupBtn.classList.add('flashing');
                else hangupBtn.classList.remove('flashing');
            }

            // Update DID rows
            var visibleCount = 0;
            document.querySelectorAll('#didGridTbody tr.did-row').forEach(function(row) {
                var didId = row.getAttribute('data-id');
                var didData = data[didId];
                if (!didData) return;

                var newStatus = String(didData.status || 'pending').toLowerCase().trim();
                var newSourceIp = String(didData.source_ip || '—').trim();
                if (!['pass', 'fail', 'route'].includes(newStatus)) newStatus = 'pending';

                var oldStatus = row.getAttribute('data-status');
                if(oldStatus !== newStatus){
                    row.setAttribute('data-status', newStatus);
                    var spill = row.querySelector('.spill');
                    var span = row.querySelector('.status-text');
                    if(spill && span){
                        spill.className = 'spill s-' + newStatus;
                        span.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    }

                    // Left border indicator
                    var bColor = newStatus === 'pass' ? 'var(--ok)' : (newStatus === 'route' ? 'var(--accent)' : (newStatus === 'fail' ? 'var(--danger)' : 'transparent'));
                    row.style.borderLeftColor = bColor;

                    // Channel test form state update
                    var chCell = row.querySelector('.channel-test-cell');
                    if(chCell){
                        if(newStatus === 'pass'){
                            chCell.innerHTML =
                                '<form method="POST" action="{{ url("/tests") }}/' + didId + '" class="m-0 inline-flex items-center gap-1" onsubmit="return startChTest(this, ' + didId + ')">' +
                                '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                                '<button type="submit" class="btn-dense btn-dense-amber" title="Execute channel diagnostic test"><i class="fa-solid fa-signal text-[9.5px]"></i> <span>Test</span></button>' +
                                '<input type="number" class="w-9 h-[22px] px-1 text-center font-mono text-[11px] font-bold border border-[var(--border)] rounded bg-[var(--surface2)] text-[var(--ink1)] focus:outline-none focus:border-amber-500" name="call_count" id="cc_input_' + didId + '" value="5" min="1" max="100">' +
                                '</form>';
                        } else {
                            chCell.innerHTML =
                                '<div class="inline-flex items-center gap-1 opacity-50">' +
                                '<button type="button" class="btn-dense btn-dense-ghost cursor-not-allowed text-slate-400" onclick="showErrModal(\'DID status must be PASS to run channel test. Current: ' + newStatus.toUpperCase() + '\')"><i class="fa-solid fa-lock text-[9.5px]"></i> <span>Test</span></button>' +
                                '<input type="number" class="w-9 h-[22px] px-1 text-center font-mono text-[11px] border border-[var(--border)] rounded bg-[var(--surface2)] text-slate-400" value="5" disabled>' +
                                '</div>';
                        }
                    }

                    // Route button state update
                    var routeBtn = row.querySelector('.btn-route-action');
                    if (routeBtn) {
                        if (newStatus === 'pass') {
                            routeBtn.className = 'btn-dense btn-dense-ok btn-route-action';
                            routeBtn.title = 'Route associated DID to 7788';
                            routeBtn.innerHTML = '<i class="fa-solid fa-route text-[10px]"></i> <span>Route</span>';
                        } else if (newStatus === 'route') {
                            routeBtn.className = 'btn-dense btn-dense-ghost text-amber-600 dark:text-amber-400 border-amber-500/30 btn-route-action';
                            routeBtn.title = 'Re-route associated DID to 7788';
                            routeBtn.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i> <span>Routed</span>';
                        } else {
                            routeBtn.className = 'btn-dense btn-dense-ghost text-slate-400 opacity-60 cursor-not-allowed btn-route-action';
                            routeBtn.title = 'DID status must be PASS to route on 7788';
                            routeBtn.innerHTML = '<i class="fa-solid fa-lock text-[10px]"></i> <span>Route</span>';
                        }
                    }

                    logAmiEvent('call', 'STATUS_CHANGE', 'DID ' + row.getAttribute('data-did') + ' transitioned to ' + newStatus.toUpperCase());
                }

                // Update Source IP
                var ipElem = row.querySelector('.source-ip-text');
                if(ipElem && ipElem.textContent.trim() !== newSourceIp){
                    ipElem.textContent = newSourceIp;
                    row.setAttribute('data-ip', newSourceIp);
                    logAmiEvent('channel', 'IP_UPDATE', 'DID ' + row.getAttribute('data-did') + ' mapped to IP: ' + newSourceIp);
                }
            });
        })
        .catch(function(err) {
            if(spinIcon) spinIcon.classList.remove('fa-spin');
            console.error("Softswitch polling error:", err);
        });
}

// 7. Auto-Update Interval Lifecycle
document.addEventListener('DOMContentLoaded', function() {
    updateDIDStatuses();
    autoUpdateInterval = setInterval(updateDIDStatuses, 3000);
});

window.addEventListener('beforeunload', function() {
    if (autoUpdateInterval) clearInterval(autoUpdateInterval);
});
</script>
@endsection
