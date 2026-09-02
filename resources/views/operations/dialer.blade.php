@extends('layouts.app')

@section('title', 'DIDX — Softphone Dialer')
@section('page-title', 'Softphone Dialer (Extension 63311)')
@section('page-crumb', 'DIDX / Operations / Dialer')

@section('content')
<div class="flex flex-col flex-1 h-full min-h-0 overflow-hidden">
    <!-- 1. TOP TELEMETRY STRIP -->
    <div class="h-9 border-b border-[var(--border)] bg-[var(--surface2)] px-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <!-- Extension Status Pill -->
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-[var(--surface)] border border-[var(--border)] font-mono text-xs" id="softphoneStatusBadge">
                <span class="w-2 h-2 rounded-full bg-slate-400" id="softphoneStatusDot"></span>
                <span class="text-[var(--ink3)] text-[10px]">EXT 63311:</span>
                <span class="font-bold text-[var(--ink1)]" id="softphoneStatusText">Checking...</span>
            </div>

            <!-- SIP Server Config Snippet -->
            <div class="hidden md:flex items-center gap-2 px-2 py-0.5 rounded bg-[var(--surface)] border border-[var(--border)] font-mono text-[10.5px] text-[var(--ink2)]">
                <span><strong class="text-[var(--ink1)]">Host:</strong> 165.227.88.28:5060</span>
                <span class="text-slate-300 dark:text-slate-700">|</span>
                <span><strong class="text-[var(--ink1)]">Proto:</strong> PJSIP UDP</span>
            </div>
        </div>

        <div class="flex items-center gap-2 font-mono text-xs text-[var(--ink3)]">
            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                <input type="checkbox" id="autoRefresh" checked class="accent-amber-500 w-3 h-3">
                <span class="text-[11px]">Auto-Sync (3s)</span>
            </label>
            <button type="button" class="btn-dense btn-dense-ghost text-[10px] px-1.5" onclick="checkSoftphoneStatus()" title="Refresh Extension Status">
                <i class="fa-solid fa-rotate text-[9.5px]"></i>
            </button>
        </div>
    </div>

    <!-- 2. DUAL-PANE DESKTOP WORKSPACE (DIALER LEFT, HISTORY RIGHT) -->
    <div class="flex-1 flex min-h-0 overflow-hidden">
        <!-- LEFT: COMPACT KEYPAD PANEL (300px fixed width) -->
        <div class="w-[300px] border-r border-[var(--border)] bg-[var(--surface)] flex flex-col min-h-0 overflow-y-auto p-3 space-y-3 flex-shrink-0">
            <!-- Active Call Banner (Displays during live call) -->
            <div id="callStatusContainer" class="hidden p-2.5 rounded-md bg-emerald-500/10 border border-emerald-500/30 font-mono text-xs space-y-1 animate-pulse">
                <div class="flex items-center justify-between text-emerald-600 font-bold">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-phone-volume"></i> CALL IN PROGRESS</span>
                    <span id="callDuration" class="text-emerald-700 font-extrabold">00:00</span>
                </div>
                <div class="text-[10.5px] text-[var(--ink2)] truncate">From: <span id="callFrom" class="font-bold text-[var(--ink1)]"></span></div>
                <div class="text-[10.5px] text-[var(--ink2)] truncate">To: <span id="callTo" class="font-bold text-[var(--ink1)]"></span></div>
            </div>

            <!-- Outbound Caller ID Selector -->
            <div>
                <label class="block text-[10px] font-mono uppercase font-bold text-[var(--ink3)] mb-1">Outbound Caller ID (Route)</label>
                <select id="callerId" class="w-full h-8 px-2 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono text-[var(--ink1)] focus:outline-none focus:border-amber-500">
                    <option value="">-- Select Outbound DID --</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->phone_number }}">{{ $route->phone_number }} ({{ $route->status }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Destination Number Input -->
            <div>
                <label class="block text-[10px] font-mono uppercase font-bold text-[var(--ink3)] mb-1">Target Number</label>
                <div class="relative flex items-center">
                    <input type="tel" id="calleeNumber" placeholder="Enter phone number..."
                           class="w-full h-8 pl-2 pr-8 bg-[var(--surface2)] border border-[var(--border)] rounded text-xs font-mono font-bold text-[var(--ink1)] tracking-wider focus:outline-none focus:border-amber-500">
                    <button type="button" onclick="backspace()" class="absolute right-2 text-slate-400 hover:text-[var(--ink1)] border-none bg-transparent cursor-pointer" title="Backspace">
                        <i class="fa-solid fa-delete-left text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Keypad Grid -->
            <div class="grid grid-cols-3 gap-1.5 font-mono">
                @foreach(['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#'] as $digit)
                    <button type="button" class="dialer-btn h-9 rounded bg-[var(--surface2)] hover:bg-amber-500 hover:text-white border border-[var(--border)] font-bold text-sm text-[var(--ink1)] transition-colors cursor-pointer select-none" data-digit="{{ $digit }}">
                        {{ $digit }}
                    </button>
                @endforeach
            </div>

            <!-- Call & Hangup Actions -->
            <div class="grid grid-cols-2 gap-2 pt-1">
                <button id="callBtn" type="button" onclick="makeCall()" class="btn-dense btn-dense-ok h-9 text-xs font-bold w-full">
                    <i class="fa-solid fa-phone mr-1"></i> Call
                </button>
                <button id="hangupBtn" type="button" onclick="hangupCall()" class="btn-dense btn-dense-del h-9 text-xs font-bold w-full opacity-50 pointer-events-none">
                    <i class="fa-solid fa-phone-slash mr-1"></i> Hangup
                </button>
            </div>

            <div class="flex items-center justify-between text-[11px] font-mono pt-1">
                <button type="button" onclick="clearInput()" class="text-slate-400 hover:text-red-500 border-none bg-transparent cursor-pointer">
                    <i class="fa-solid fa-eraser mr-1"></i>Clear Display
                </button>
                <span class="text-[10px] text-[var(--ink3)]">SIP: 63311</span>
            </div>
        </div>

        <!-- RIGHT: CALL HISTORY HIGH-DENSITY DATA GRID -->
        <div class="flex-1 flex flex-col min-h-0 overflow-hidden bg-[var(--surface)]">
            <!-- Filter Bar -->
            <div class="h-10 px-3 py-1.5 bg-[var(--surface2)] border-b border-[var(--border)] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-history text-amber-500 text-xs"></i>
                    <span class="font-disp font-bold text-xs text-[var(--ink1)]">Dialer Call History</span>
                </div>

                <!-- Direction Filter Pills -->
                <div class="flex items-center bg-[var(--surface)] p-0.5 rounded border border-[var(--border)]">
                    <button type="button" class="filter-btn active px-2 py-0.5 rounded text-[10px] font-mono font-bold" data-filter="all" onclick="filterHistory('all')">ALL</button>
                    <button type="button" class="filter-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-amber-600" data-filter="outbound" onclick="filterHistory('outbound')">OUTBOUND</button>
                    <button type="button" class="filter-btn px-2 py-0.5 rounded text-[10px] font-mono font-bold text-emerald-600" data-filter="inbound" onclick="filterHistory('inbound')">INBOUND</button>
                </div>
            </div>

            <!-- History Table -->
            <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative">
                <table class="w-full table-fixed text-xs border-collapse text-left select-text font-mono">
                    <colgroup>
                        <col class="w-[22%]">
                        <col class="w-[22%]">
                        <col class="w-[12%]">
                        <col class="w-[14%]">
                        <col class="w-[10%]">
                        <col class="w-[12%]">
                        <col class="w-[8%]">
                    </colgroup>
                    <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 shadow-xs">
                        <tr class="h-8 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Caller ID</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Destination</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Direction</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Status</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200/70 dark:border-slate-700/70">Duration</th>
                            <th class="py-2 px-3 border-r border-slate-200/70 dark:border-slate-700/70">Timestamp</th>
                            <th class="py-2 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="historyBody">
                        <tr>
                            <td colspan="7" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">
                                Loading call history...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentCallId = null;
let callDurationInterval = null;
let autoRefreshInterval = null;

// Add digit to input
document.querySelectorAll('.dialer-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('calleeNumber').value += this.dataset.digit;
    });
});

function backspace() {
    const input = document.getElementById('calleeNumber');
    input.value = input.value.slice(0, -1);
}

function clearInput() {
    document.getElementById('calleeNumber').value = '';
}

function makeCall() {
    const callerId = document.getElementById('callerId').value;
    const calleeNumber = document.getElementById('calleeNumber').value;

    if (!callerId) {
        alert('Please select an outbound Caller ID route');
        return;
    }

    if (!calleeNumber || calleeNumber.length < 3) {
        alert('Please enter a valid phone number');
        return;
    }

    const callBtn = document.getElementById('callBtn');
    callBtn.disabled = true;
    callBtn.style.opacity = '0.5';

    fetch('./dialer/make-call', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            caller_id: callerId,
            callee_number: calleeNumber,
            extension: '63311'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentCallId = data.call_id;
            
            document.getElementById('callStatusContainer').classList.remove('hidden');
            document.getElementById('callFrom').textContent = callerId;
            document.getElementById('callTo').textContent = calleeNumber;
            
            const hangupBtn = document.getElementById('hangupBtn');
            hangupBtn.style.opacity = '1';
            hangupBtn.style.pointerEvents = 'auto';
            
            startDurationTimer();
            setTimeout(() => refreshHistory(), 500);
        } else {
            alert('Error: ' + (data.message || 'Failed to make call'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Call dispatch failed');
    })
    .finally(() => {
        callBtn.disabled = false;
        callBtn.style.opacity = '1';
    });
}

function hangupCall() {
    const hangupBtn = document.getElementById('hangupBtn');
    hangupBtn.disabled = true;
    hangupBtn.style.opacity = '0.5';

    fetch('./dialer/hangup-call', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            call_id: currentCallId,
            extension: '63311'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (callDurationInterval) clearInterval(callDurationInterval);
            document.getElementById('callStatusContainer').classList.add('hidden');
            
            hangupBtn.style.opacity = '0.5';
            hangupBtn.style.pointerEvents = 'none';
            currentCallId = null;
            setTimeout(() => refreshHistory(), 500);
        }
    })
    .catch(error => console.error('Error:', error))
    .finally(() => {
        hangupBtn.disabled = false;
    });
}

function startDurationTimer() {
    let seconds = 0;
    if (callDurationInterval) clearInterval(callDurationInterval);
    
    callDurationInterval = setInterval(() => {
        seconds++;
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        const formatted = (minutes.toString().padStart(2, '0')) + ':' + (secs.toString().padStart(2, '0'));
        document.getElementById('callDuration').textContent = formatted;
    }, 1000);
}

function checkSoftphoneStatus() {
    fetch('./dialer/extension-status?extension=63311', { cache: 'no-store' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatusBadge(data.status);
            }
        })
        .catch(error => console.error('Error:', error));
}

function updateStatusBadge(status) {
    const dot = document.getElementById('softphoneStatusDot');
    const text = document.getElementById('softphoneStatusText');
    if (!dot || !text) return;
    
    if (status === 'online') {
        dot.style.background = 'var(--ok)';
        dot.style.boxShadow = '0 0 8px var(--ok)';
        text.textContent = 'Online';
        text.style.color = 'var(--ok)';
    } else if (status === 'offline') {
        dot.style.background = 'var(--danger)';
        dot.style.boxShadow = 'none';
        text.textContent = 'Offline';
        text.style.color = 'var(--danger)';
    } else {
        dot.style.background = '#999';
        dot.style.boxShadow = 'none';
        text.textContent = 'Checking...';
        text.style.color = 'var(--ink3)';
    }
}

function filterHistory(direction) {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-[var(--surface2)]');
    });
    const target = document.querySelector(`.filter-btn[data-filter="${direction}"]`);
    if (target) target.classList.add('active', 'bg-[var(--surface2)]');
    
    refreshHistory();
}

function refreshHistory() {
    const activeFilterBtn = document.querySelector('.filter-btn.active');
    const filter = activeFilterBtn ? activeFilterBtn.dataset.filter : 'all';
    
    const url = new URL('./dialer/history', window.location);
    if (filter !== 'all') {
        url.searchParams.append('direction', filter);
    }
    
    fetch(url, { cache: 'no-store' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderHistory(data.history);
            }
        })
        .catch(error => console.error('Error:', error));
}

function renderHistory(history) {
    const tbody = document.getElementById('historyBody');
    if (!tbody) return;
    
    if (!history || history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-12 text-center text-xs font-mono text-[var(--ink3)]">No call history recorded yet</td></tr>';
        return;
    }
    
    tbody.innerHTML = history.map(call => {
        const isComp = call.status === 'completed';
        const isFail = call.status === 'failed';
        const statusClass = isComp ? 's-pass' : (isFail ? 's-fail' : 's-route');
        
        const durationSecs = call.duration || 0;
        const minutes = Math.floor(durationSecs / 60);
        const secs = durationSecs % 60;
        const durationStr = (minutes.toString().padStart(2, '0')) + ':' + (secs.toString().padStart(2, '0'));
        
        return `<tr class="h-[34px] border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors">
            <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight font-semibold text-[var(--ink1)] truncate">${call.caller_id}</td>
            <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 font-mono tracking-tight text-[var(--ink2)] truncate">${call.callee_number}</td>
            <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center">
                ${call.direction === 'outbound' ? 
                    '<span class="text-amber-600 font-semibold"><i class="fa-solid fa-arrow-up-right mr-1 text-[10px]"></i>Out</span>' :
                    '<span class="text-emerald-600 font-semibold"><i class="fa-solid fa-arrow-down-left mr-1 text-[10px]"></i>In</span>'}
            </td>
            <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center">
                <span class="spill ${statusClass}"><span class="sdot"></span>${call.status}</span>
            </td>
            <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-center text-[var(--ink2)]">${durationStr}</td>
            <td class="py-2 px-3 border-r border-slate-100 dark:border-slate-800/60 text-[var(--ink3)] text-[11px]">${call.start_time || '—'}</td>
            <td class="py-2 px-3 text-right">
                <button type="button" class="btn-dense btn-dense-ghost text-[10px]" onclick="redialCall('${call.caller_id}', '${call.callee_number}')" title="Redial">
                    <i class="fa-solid fa-redo text-[9.5px]"></i> Redial
                </button>
            </td>
        </tr>`;
    }).join('');
}

function redialCall(callerId, calleeNumber) {
    document.getElementById('calleeNumber').value = calleeNumber;
    document.getElementById('callerId').value = callerId;
}

document.addEventListener('DOMContentLoaded', () => {
    refreshHistory();
    setInterval(refreshHistory, 5000);
    checkSoftphoneStatus();
    
    autoRefreshInterval = setInterval(() => {
        if (document.getElementById('autoRefresh').checked) {
            checkSoftphoneStatus();
        }
    }, 3000);
});

document.getElementById('autoRefresh').addEventListener('change', function() {
    if (this.checked) {
        autoRefreshInterval = setInterval(checkSoftphoneStatus, 3000);
    } else if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});
</script>
@endsection
