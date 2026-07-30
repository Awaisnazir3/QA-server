@extends('layouts.app')

@section('title', 'DIDX — Softphone Dialer')
@section('page-title', 'Softphone Dialer (Extension 63311)')
@section('page-crumb', 'DIDX / Operations / Dialer')

@section('content')
<div class="slabel"><i class="fa-solid fa-phone"></i>Softphone Control Panel</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <!-- LEFT: Softphone Status & Settings -->
    <div class="card">
        <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-wifi"></i>Softphone Status</div>
        </div>

        <div style="padding:20px;display:flex;flex-direction:column;gap:16px">
            <!-- Extension 63311 Status Badge -->
            <div style="padding:16px;border-radius:8px;background:var(--surface2);border:2px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-weight:600;font-size:14px;color:var(--ink1)">Extension 63311</div>
                    <div style="font-size:12px;color:var(--ink3);margin-top:4px">Softphone: Zoiper / MicroSIP / Linphone</div>
                </div>
                <div id="softphoneStatusBadge" style="padding:12px 16px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;display:flex;align-items:center;gap:8px;background:var(--grey-dim);color:#999">
                    <span id="softphoneStatusDot" style="width:10px;height:10px;border-radius:50%;background:#999;box-shadow:0 0 8px #999"></span>
                    <span id="softphoneStatusText">Checking...</span>
                </div>
            </div>

            <!-- Configuration Info -->
            <div style="padding:12px;background:var(--surface2);border-radius:6px;border-left:4px solid var(--primary);font-size:12px;color:var(--ink2)">
                <div style="font-weight:600;color:var(--ink1);margin-bottom:8px">Configuration:</div>
                <div style="display:grid;gap:4px;font-family:var(--mono);font-size:11px">
                    <div>Server: <span style="color:var(--amber)">165.227.88.28</span></div>
                    <div>Port: <span style="color:var(--amber)">5060</span> (UDP)</div>
                    <div>Extension: <span style="color:var(--amber)">63311</span></div>
                    <div>Password: <span style="color:var(--amber)">f63311</span></div>
                </div>
            </div>

            <!-- Auto-Refresh Toggle -->
            <div style="display:flex;align-items:center;gap:12px">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;flex:1">
                    <input type="checkbox" id="autoRefresh" checked style="cursor:pointer;width:16px;height:16px">
                    <span style="color:var(--ink2);font-size:13px">Auto-refresh status (3s)</span>
                </label>
                <button type="button" onclick="checkSoftphoneStatus()" style="padding:8px 14px;background:var(--primary);color:#fff;border:none;border-radius:6px;font-weight:600;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap">
                    <i class="fa-solid fa-sync"></i>Check Now
                </button>
            </div>
        </div>
    </div>

    <!-- RIGHT: Dial Pad & Caller ID -->
    <div class="card">
        <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-keypad"></i>Dial Pad</div>
        </div>

        <div style="padding:20px;display:flex;flex-direction:column;gap:16px">
            <!-- Caller ID (Outbound Route) Selection -->
            <div>
                <label style="display:block;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--ink3);margin-bottom:8px">Caller ID (Outbound Route)</label>
                <select id="callerId" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;background:var(--surface);color:var(--ink1);font-family:var(--mono)">
                    <option value="">-- Select Route --</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->phone_number }}">{{ $route->phone_number }} ({{ $route->status }})</option>
                    @endforeach
                </select>
                <div style="font-size:11px;color:var(--ink3);margin-top:6px">Select the phone number to show as caller ID when making outbound calls</div>
            </div>

            <!-- Dial Number Input -->
            <div>
                <label style="display:block;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--ink3);margin-bottom:8px">Dial Number</label>
                <input type="tel" id="calleeNumber" placeholder="Enter phone number" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:6px;font-size:14px;background:var(--surface);color:var(--ink1);font-family:var(--mono);letter-spacing:1px" value="">
            </div>

            <!-- Dialer Keypad -->
            <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:8px">
                @foreach(['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#'] as $digit)
                    <button type="button" class="dialer-btn" data-digit="{{ $digit }}" style="padding:14px;border:1px solid var(--border);border-radius:6px;background:var(--surface2);color:var(--ink1);font-weight:600;font-size:18px;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center">
                        {{ $digit }}
                    </button>
                @endforeach
            </div>

            <!-- Call Control Buttons -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px">
                <button id="callBtn" type="button" style="padding:12px;background:var(--ok);color:#fff;border:none;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:8px" onclick="makeCall()">
                    <i class="fa-solid fa-phone"></i>Call
                </button>
                <button id="hangupBtn" type="button" style="padding:12px;background:var(--danger);color:#fff;border:none;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:8px;opacity:.5;pointer-events:none" onclick="hangupCall()">
                    <i class="fa-solid fa-phone-slash"></i>Hangup
                </button>
            </div>

            <!-- Backspace/Clear -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <button type="button" onclick="backspace()" style="padding:10px;background:var(--surface2);color:var(--ink1);border:1px solid var(--border);border-radius:6px;font-weight:600;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px">
                    <i class="fa-solid fa-delete-left"></i>Backspace
                </button>
                <button type="button" onclick="clearInput()" style="padding:10px;background:var(--surface2);color:var(--ink1);border:1px solid var(--border);border-radius:6px;font-weight:600;font-size:12px;cursor:pointer">
                    Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Call Status & Info -->
<div id="callStatusContainer" style="display:none;margin-bottom:20px">
    <div class="card">
        <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
            <div>
                <div style="font-size:11px;color:var(--ink3);text-transform:uppercase;font-weight:600;margin-bottom:6px">From</div>
                <div style="font-size:14px;font-weight:600;color:var(--ink1);font-family:var(--mono)" id="callFrom">—</div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--ink3);text-transform:uppercase;font-weight:600;margin-bottom:6px">To</div>
                <div style="font-size:14px;font-weight:600;color:var(--ink1);font-family:var(--mono)" id="callTo">—</div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--ink3);text-transform:uppercase;font-weight:600;margin-bottom:6px">Duration</div>
                <div style="font-size:14px;font-weight:600;color:var(--ink1);font-family:var(--mono)" id="callDuration">00:00</div>
            </div>
        </div>
    </div>
</div>

<!-- Call History -->
<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-history"></i>Call History</div>
        <div style="display:flex;gap:10px">
            <button class="filter-btn active" data-filter="all" onclick="filterHistory('all')">All</button>
            <button class="filter-btn" data-filter="outbound" onclick="filterHistory('outbound')">Outbound</button>
            <button class="filter-btn" data-filter="inbound" onclick="filterHistory('inbound')">Inbound</button>
        </div>
    </div>

    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;min-width:800px">
            <thead>
                <tr style="border-bottom:1px solid var(--border);color:var(--ink3);font-size:10.5px;text-transform:uppercase;font-family:var(--mono)">
                    <th style="padding:10px 14px">From</th>
                    <th style="padding:10px 14px">To</th>
                    <th style="padding:10px 14px">Direction</th>
                    <th style="padding:10px 14px">Status</th>
                    <th style="padding:10px 14px">Duration</th>
                    <th style="padding:10px 14px">Time</th>
                    <th style="padding:10px 14px;text-align:center">Action</th>
                </tr>
            </thead>
            <tbody id="historyBody">
                <tr>
                    <td colspan="7" style="text-align:center;padding:24px;color:var(--ink3)">Loading call history...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
    .dialer-btn {
        transition: all 0.15s;
    }
    
    .dialer-btn:hover {
        background: var(--primary);
        color: #fff;
        transform: scale(1.05);
    }
    
    .dialer-btn:active {
        transform: scale(0.95);
    }
    
    .filter-btn {
        padding: 6px 14px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--ink2);
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s;
    }
    
    .filter-btn.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    
    .filter-btn:hover {
        border-color: var(--primary);
    }
</style>

<script>
let currentCallId = null;
let callDurationInterval = null;
let autoRefreshInterval = null;

// Dialer pad - add digit to input
document.querySelectorAll('.dialer-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const digit = this.dataset.digit;
        document.getElementById('calleeNumber').value += digit;
    });
});

// Backspace
function backspace() {
    const input = document.getElementById('calleeNumber');
    input.value = input.value.slice(0, -1);
}

// Clear
function clearInput() {
    document.getElementById('calleeNumber').value = '';
}

// Make call
function makeCall() {
    const callerId = document.getElementById('callerId').value;
    const calleeNumber = document.getElementById('calleeNumber').value;

    if (!callerId) {
        alert('Please select a caller ID (outbound route)');
        return;
    }

    if (!calleeNumber || calleeNumber.length < 7) {
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
            
            // Show call status
            document.getElementById('callStatusContainer').style.display = 'block';
            document.getElementById('callFrom').textContent = callerId;
            document.getElementById('callTo').textContent = calleeNumber;
            
            // Enable hangup
            document.getElementById('hangupBtn').style.opacity = '1';
            document.getElementById('hangupBtn').style.pointerEvents = 'auto';
            
            // Start duration timer
            startDurationTimer();
            
            // Refresh history after a short delay
            setTimeout(() => refreshHistory(), 500);
        } else {
            alert('Error: ' + (data.message || 'Failed to make call'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Connection error');
    })
    .finally(() => {
        callBtn.disabled = false;
        callBtn.style.opacity = '1';
    });
}

// Hangup call
function hangupCall() {
    if (!currentCallId) {
        alert('No active call');
        return;
    }

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
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reset UI
            document.getElementById('callStatusContainer').style.display = 'none';
            document.getElementById('calleeNumber').value = '';
            currentCallId = null;
            
            // Stop timer
            if (callDurationInterval) {
                clearInterval(callDurationInterval);
            }
            
            // Disable hangup
            hangupBtn.style.opacity = '0.5';
            hangupBtn.style.pointerEvents = 'none';
            
            // Refresh history
            setTimeout(() => refreshHistory(), 500);
        }
    })
    .catch(error => console.error('Error:', error))
    .finally(() => {
        hangupBtn.disabled = false;
        hangupBtn.style.opacity = '1';
    });
}

// Start duration timer
function startDurationTimer() {
    let seconds = 0;
    if (callDurationInterval) clearInterval(callDurationInterval);
    
    callDurationInterval = setInterval(() => {
        seconds++;
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        const formatted = (hours > 0 ? hours + ':' : '') + 
                         (minutes.toString().padStart(2, '0')) + ':' +
                         (secs.toString().padStart(2, '0'));
        
        document.getElementById('callDuration').textContent = formatted;
    }, 1000);
}

// Check softphone (63311) status
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

// Update status badge
function updateStatusBadge(status) {
    const badge = document.getElementById('softphoneStatusBadge');
    const dot = document.getElementById('softphoneStatusDot');
    const text = document.getElementById('softphoneStatusText');
    
    if (status === 'online') {
        dot.style.background = 'var(--ok)';
        dot.style.boxShadow = '0 0 8px var(--ok)';
        text.textContent = 'Online';
        badge.style.background = 'var(--ok-dim)';
        badge.style.color = 'var(--ok)';
    } else if (status === 'offline') {
        dot.style.background = 'var(--danger)';
        dot.style.boxShadow = '0 0 8px var(--danger)';
        text.textContent = 'Offline';
        badge.style.background = 'var(--danger-dim)';
        badge.style.color = 'var(--danger)';
    } else {
        dot.style.background = '#999';
        dot.style.boxShadow = '0 0 8px #999';
        text.textContent = 'Checking...';
        badge.style.background = 'var(--grey-dim)';
        badge.style.color = '#999';
    }
}

// Filter history
function filterHistory(direction) {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    refreshHistory();
}

// Refresh call history
function refreshHistory() {
    const filter = document.querySelector('.filter-btn.active').dataset.filter;
    
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

// Render history table
function renderHistory(history) {
    const tbody = document.getElementById('historyBody');
    
    if (history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--ink3)">No call history yet</td></tr>';
        return;
    }
    
    tbody.innerHTML = history.map(call => {
        const statusColor = call.status === 'completed' ? 'var(--ok)' : 
                           call.status === 'failed' ? 'var(--danger)' : 'var(--amber)';
        const statusBg = call.status === 'completed' ? 'var(--ok-dim)' : 
                        call.status === 'failed' ? 'var(--danger-dim)' : 'var(--amber-dim)';
        
        const durationSecs = call.duration || 0;
        const hours = Math.floor(durationSecs / 3600);
        const minutes = Math.floor((durationSecs % 3600) / 60);
        const secs = durationSecs % 60;
        const durationStr = (hours > 0 ? hours + ':' : '') + 
                           (minutes.toString().padStart(2, '0')) + ':' +
                           (secs.toString().padStart(2, '0'));
        
        return `<tr style="border-bottom:1px solid var(--bordersoft)">
            <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink1)">${call.caller_id}</td>
            <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink2)">${call.callee_number}</td>
            <td style="padding:12px 14px">
                ${call.direction === 'outbound' ? 
                    '<span style="display:inline-flex;align-items:center;gap:4px;color:var(--amber)"><i class="fa-solid fa-arrow-up-right"></i>Outbound</span>' :
                    '<span style="display:inline-flex;align-items:center;gap:4px;color:var(--ok)"><i class="fa-solid fa-arrow-down-left"></i>Inbound</span>'}
            </td>
            <td style="padding:12px 14px">
                <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;background:${statusBg};color:${statusColor}">
                    <span style="width:6px;height:6px;border-radius:50%;background:${statusColor}"></span>
                    ${call.status}
                </span>
            </td>
            <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink2)">${durationStr}</td>
            <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink3);font-size:11px">${call.start_time || '—'}</td>
            <td style="padding:12px 14px;text-align:center">
                <button class="btn-sm" onclick="redialCall('${call.caller_id}', '${call.callee_number}')" style="padding:6px 10px;background:var(--primary);color:#fff;border:none;border-radius:4px;font-size:11px;cursor:pointer">
                    <i class="fa-solid fa-redo"></i>Redial
                </button>
            </td>
        </tr>`;
    }).join('');
}

// Redial a call
function redialCall(callerId, calleeNumber) {
    document.getElementById('calleeNumber').value = calleeNumber;
    document.getElementById('callerId').value = callerId;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Load initial history
    refreshHistory();
    
    // Auto-refresh history every 5 seconds
    setInterval(refreshHistory, 5000);
    
    // Check softphone status immediately
    checkSoftphoneStatus();
    
    // Auto-refresh softphone status every 3 seconds if enabled
    autoRefreshInterval = setInterval(() => {
        if (document.getElementById('autoRefresh').checked) {
            checkSoftphoneStatus();
        }
    }, 3000);
});

// Handle auto-refresh toggle
document.getElementById('autoRefresh').addEventListener('change', function() {
    if (this.checked) {
        autoRefreshInterval = setInterval(() => {
            checkSoftphoneStatus();
        }, 3000);
    } else if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});
</script>

@endsection
