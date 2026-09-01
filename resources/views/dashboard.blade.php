@extends('layouts.app')

@section('title', 'DIDX — DID Route Manager')
@section('page-title', 'DID Route Manager')
@section('page-crumb', 'DIDX / Softswitch / DID Manager')

@section('content')
<!-- STATS BAR -->
<div class="statrow">
    <div class="stat-card sc-primary">
        <div class="stat-icon"><i class="fa-solid fa-phone-volume"></i></div>
        <div><div class="stat-lbl">Active Calls</div><div class="stat-val" id="navCalls">{{ $stats['activeCalls'] }}</div></div>
    </div>
    <div class="stat-card sc-teal">
        <div class="stat-icon"><i class="fa-solid fa-server"></i></div>
        <div><div class="stat-lbl">SIP Peers</div><div class="stat-val">{{ $stats['onlinePeers'] }}</div></div>
    </div>
    <div class="stat-card sc-violet">
        <div class="stat-icon"><i class="fa-solid fa-hashtag"></i></div>
        <div><div class="stat-lbl">Total DIDs</div><div class="stat-val">{{ $totalDids }}</div></div>
    </div>
    <div class="stat-card sc-amber">
        <div class="stat-icon"><i class="fa-solid fa-microchip"></i></div>
        <div><div class="stat-lbl">RAM Usage</div><div class="stat-val">{{ $stats['ramUsage'] }}%</div></div>
    </div>
</div>

<!-- PROVISION DID -->
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 20px;display:flex;align-items:center;gap:16px;margin-bottom:26px;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--ink1);white-space:nowrap">
        <i class="fa-solid fa-plus-circle" style="color:var(--primary)"></i>Provision DID
    </div>
    <form method="POST" action="{{ route('dashboard.provision') }}" style="display:flex;gap:10px;align-items:center;flex:1;min-width:220px">
        @csrf
        <input type="text" name="phone_number" placeholder="e.g. 44987654320" style="flex:1;min-width:160px;padding:9px 13px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:13px;outline:none" required>
        <button type="submit" class="btn-primary"><i class="fa-solid fa-bolt"></i>Deploy to Switch</button>
    </form>
</div>

<div class="slabel"><i class="fa-solid fa-circle-nodes"></i>DID Routing &amp; Channel Testing</div>
<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-table-list"></i>Active DID Routes</div>
        <div style="display:flex;align-items:center;gap:10px">
            <form method="POST" action="{{ route('dashboard.hangup-all') }}" style="margin:0" id="hangupForm" onsubmit="return handleHangupSubmit(event)">
                @csrf
                <button type="submit" class="btn-hangup" id="hangupBtn"><i class="fa-solid fa-phone-slash"></i>Hangup All</button>
            </form>
            <form method="POST" action="{{ route('dashboard.clear-all') }}" style="margin:0" onsubmit="return confirm('Are you sure you want to delete ALL active DID routes?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-sm btn-del" style="padding:8px 14px;font-size:11.5px;font-weight:700"><i class="fa-solid fa-trash-can"></i>Clear All DIDs</button>
            </form>
            <div class="cbadge">{{ $totalDids }} entries</div>
        </div>
    </div>
    <div style="overflow-x:auto">
        <div style="display:grid;grid-template-columns:34px minmax(150px,auto) minmax(80px,auto) 1fr minmax(210px,auto) minmax(70px,auto) 1fr minmax(210px,auto);gap:14px;padding:0 18px 11px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--ink3);min-width:880px">
            <div>#</div>
            <div>DID / Source</div>
            <div>Status</div>
            <div></div>
            <div>Channel Test</div>
            <div style="text-align:center">Channels Found</div>
            <div></div>
            <div style="text-align:center">Action</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;min-width:880px">
            @php $serialNumber = $totalDids; @endphp
            @forelse($callLogs as $log)
                @php
                    $status = !empty($log->status) ? strtolower(trim($log->status)) : 'pending';
                    if (!in_array($status, ['pass', 'fail', 'route'])) $status = 'pending';
                    $channelsDetected = $log->checked_channels !== null ? (int)$log->checked_channels : '—';
                @endphp
                <div style="display:grid;grid-template-columns:34px minmax(150px,auto) minmax(80px,auto) 1fr minmax(210px,auto) minmax(70px,auto) 1fr minmax(210px,auto);gap:14px;align-items:center;padding:13px 18px;background:var(--surface2);border:1px solid var(--border);border-left:3px solid var(--grey);border-radius:11px;transition:background .15s,border-color .15s,box-shadow .15s" style="border-left-color: {{ $status === 'pass' ? 'var(--ok)' : ($status === 'route' ? 'var(--amber)' : ($status === 'fail' ? 'var(--danger)' : 'var(--grey)')) }}" data-id="{{ $log->id }}">
                    <div style="color:var(--ink3);font-family:var(--mono);font-size:11px">{{ $serialNumber-- }}</div>

                    <div style="display:flex;flex-direction:column;gap:3px;min-width:0">
                        <div style="font-family:var(--mono);font-weight:700;color:var(--ink1);letter-spacing:.2px;font-size:13.5px">
                            {{ $log->phone_number }}
                            @if($log->user)
                                <span title="Provisioned by {{ $log->user->username }}" style="font-size:10px;font-weight:600;padding:2px 6px;background:var(--primary-dim);color:var(--primary);border-radius:4px;margin-left:6px;vertical-align:middle;font-family:var(--ui)"><i class="fa-solid fa-user" style="font-size:9px;margin-right:3px"></i>{{ $log->user->username }}</span>
                            @endif
                        </div>
                        <div style="font-family:var(--mono);font-size:11px;color:var(--ink3)">{{ $log->source_ip ?? '—' }}</div>
                    </div>

                    <div>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;background: {{ $status === 'pass' ? 'var(--ok-dim)' : ($status === 'route' ? 'var(--amber-dim)' : ($status === 'fail' ? 'var(--danger-dim)' : 'var(--grey-dim)')) }};color: {{ $status === 'pass' ? 'var(--ok)' : ($status === 'route' ? 'var(--amber)' : ($status === 'fail' ? 'var(--danger)' : 'var(--grey)')) }}">
                            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;box-shadow: {{ $status === 'pass' ? '0 0 6px var(--ok)' : ($status === 'route' ? '0 0 6px var(--amber)' : ($status === 'fail' ? '0 0 6px var(--danger)' : '')) }}"></span>
                            <span class="status-text">{{ $status }}</span>
                        </span>
                    </div>

                    <div></div>

                    <div style="min-width:0" class="channel-test-cell">
                        @if($status === 'pass')
                            <form method="POST" action="{{ route('tests.test', $log->id) }}" style="margin:0;display:inline-flex;align-items:center;gap:8px" onsubmit="return startChTest(this, {{ $log->id }})">
                                @csrf
                                <button type="submit" class="btn-sm btn-channel"><i class="fa-solid fa-signal"></i>Test Channel</button>
                                <input type="number" class="ch-input-sm" name="call_count" id="cc_input_{{ $log->id }}" value="5" min="1" max="100" title="Number of calls to test">
                            </form>
                        @else
                            <div style="display:inline-flex;align-items:center;gap:8px">
                                <button type="button" class="btn-sm btn-channel" style="opacity:.4;cursor:not-allowed" onclick="showErrModal('DID status must be PASS to run channel test. Current: {{ strtoupper($status) }}')">
                                    <i class="fa-solid fa-lock"></i>Channel
                                </button>
                                <input type="number" class="ch-input-sm" value="5" disabled style="opacity:.4">
                            </div>
                        @endif
                    </div>

                    <div style="text-align:center">
                        <span style="font-family:var(--mono);font-weight:800;font-size:19px;color:var(--ink1);padding:2px 8px;min-width:30px;display:inline-block">{{ $channelsDetected }}</span>
                    </div>

                    <div></div>

                    <div style="min-width:0;display:flex;justify-content:center;flex-wrap:wrap;gap:6px">
                        <form method="POST" action="{{ route('dashboard.mark-route', $log->id) }}" style="margin:0">
                            @csrf
                            <button type="submit" class="btn-sm btn-route"><i class="fa-solid fa-route"></i>Route</button>
                        </form>
                        <form method="POST" action="{{ route('dashboard.reset', $log->id) }}" style="margin:0">
                            @csrf
                            <button type="submit" class="btn-sm btn-reset"><i class="fa-solid fa-rotate-left"></i>Reset</button>
                        </form>
                        <form method="POST" action="{{ route('dashboard.destroy', $log->id) }}" style="margin:0" onsubmit="return confirm('Remove this DID?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-sm btn-del"><i class="fa-solid fa-trash-can"></i>Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="padding:40px 20px;text-align:center;color:var(--ink3)">
                    <i class="fa-solid fa-inbox" style="font-size:32px;margin-bottom:12px;opacity:.5;display:block"></i>
                    No DIDs provisioned yet. Add one above to get started.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- ERROR MODAL -->
<div id="errModal" style="display:none;position:fixed;inset:0;background:rgba(15,15,35,.55);backdrop-filter:blur(3px);z-index:9999;align-items:center;justify-content:center">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:38px 34px;max-width:420px;width:92%;box-shadow:0 30px 80px rgba(20,20,60,.25);text-align:center">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(224,57,63,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:26px;color:#e0393f"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div style="font-size:17px;font-weight:700;margin-bottom:10px;color:var(--ink1);font-family:'Sora',sans-serif">Channel Test Blocked</div>
        <div id="errModalMsg" style="font-size:13px;color:var(--ink2);line-height:1.7;margin-bottom:26px"></div>
        <button onclick="closeErrModal()" style="padding:11px 38px;background:linear-gradient(135deg,#6153f6,#7a6bf9);color:#fff;border:none;border-radius:99px;font-size:13px;font-weight:700;cursor:pointer">OK, Got It</button>
    </div>
</div>

<!-- CONFIRM MODAL -->
<div id="confirmModal" style="display:none;position:fixed;inset:0;background:rgba(15,15,35,.55);backdrop-filter:blur(3px);z-index:9999;align-items:center;justify-content:center">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:38px 34px;max-width:420px;width:92%;box-shadow:0 30px 80px rgba(20,20,60,.25);text-align:center">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(224,57,63,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:26px;color:#e0393f"><i class="fa-solid fa-phone-slash"></i></div>
        <div id="confirmModalTitle" style="font-size:17px;font-weight:700;margin-bottom:10px;color:var(--ink1);font-family:'Sora',sans-serif">Disconnect All Calls?</div>
        <div id="confirmModalMsg" style="font-size:13px;color:var(--ink2);line-height:1.7;margin-bottom:26px"></div>
        <div style="display:flex;gap:10px;justify-content:center">
            <button onclick="closeConfirmModal()" style="padding:11px 28px;background:var(--surface2);color:var(--ink2);border:1px solid var(--border);border-radius:9px;font-size:13px;font-weight:700;cursor:pointer">Cancel</button>
            <button onclick="confirmModalYes()" style="padding:11px 28px;background:linear-gradient(135deg,#e0393f,#c93b3b);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer">Yes, Disconnect</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
var _confirmCallback = null;
var autoUpdateInterval = null;

function showConfirmModal(title, msg, onYes){
    document.getElementById('confirmModalTitle').textContent = title;
    document.getElementById('confirmModalMsg').textContent = msg;
    _confirmCallback = onYes;
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal(){
    document.getElementById('confirmModal').style.display = 'none';
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
        document.getElementById('hangupForm').submit();
    });
    return false;
}

function startChTest(form, id){
    var btn = form.querySelector('.btn-channel');
    if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Test';
        btn.disabled = true;
    }
    return true;
}

function showErrModal(msg){
    document.getElementById('errModalMsg').textContent = msg;
    document.getElementById('errModal').style.display = 'flex';
}

function closeErrModal(){
    document.getElementById('errModal').style.display = 'none';
}

/**
 * Auto-refresh DID statuses and stats every 3 seconds
 */
function updateDIDStatuses() {
    fetch('./api/status', { cache: "no-store" })
        .then(function(response) { 
            if (!response.ok) throw new Error('API Error');
            return response.json(); 
        })
        .then(function(data) {
            if (!data) return;

            // Update global Asterisk status
            var astStatus = document.getElementById('globalAsteriskStatus');
            var astDot = document.getElementById('globalAsteriskDot');
            var astText = document.getElementById('globalAsteriskText');
            if (astStatus && astDot && astText && data.hasOwnProperty('_asterisk_online')) {
                if (data['_asterisk_online']) {
                    astStatus.style.borderColor = 'var(--border)';
                    astDot.style.background = 'var(--ok)';
                    astDot.style.boxShadow = '0 0 8px var(--ok)';
                    astDot.style.animation = 'blink 2s infinite';
                    astText.textContent = 'Asterisk Online';
                    astText.style.color = '';
                    astText.style.fontWeight = '';
                } else {
                    astStatus.style.borderColor = 'var(--danger-dim)';
                    astDot.style.background = 'var(--danger)';
                    astDot.style.boxShadow = '0 0 8px var(--danger)';
                    astDot.style.animation = 'none';
                    astText.textContent = 'Asterisk Offline';
                    astText.style.color = 'var(--danger)';
                    astText.style.fontWeight = '600';
                }
            }

            // Update active calls display
            var navCalls = document.getElementById('navCalls');
            var activeCalls = data['_active_calls'] || 0;
            if (navCalls) {
                navCalls.textContent = activeCalls;
            }

            // Update hangup button blinking
            var hangupBtn = document.getElementById('hangupBtn');
            if (hangupBtn) {
                if (activeCalls > 0) {
                    hangupBtn.classList.add('flashing');
                } else {
                    hangupBtn.classList.remove('flashing');
                }
            }

            // Update each DID row status
            document.querySelectorAll('[data-id]').forEach(function(row) {
                var didId = row.getAttribute('data-id');
                var didData = data[didId];
                
                if (!didData) return;
                
                var newStatus = String(didData.status || 'pending').toLowerCase().trim();
                var newSourceIp = String(didData.source_ip || '—').trim();

                // Validate status
                if (!['pass', 'fail', 'route'].includes(newStatus)) {
                    newStatus = 'pending';
                }

                // Find current status in the row
                var statusSpan = row.querySelector('.status-text');
                var currentStatus = statusSpan ? statusSpan.textContent.trim().toLowerCase() : '';

                // Update status badge if changed
                if (currentStatus !== newStatus) {
                    var statusSpill = row.querySelector('.spill');
                    if (statusSpill && statusSpan) {
                        // Remove old status classes
                        statusSpill.classList.remove('s-pending', 's-pass', 's-fail', 's-route');
                        // Add new status class
                        statusSpill.classList.add('s-' + newStatus);
                        statusSpan.textContent = newStatus;
                        
                        // Update inline styles for status badge
                        var bgColor = 'var(--grey-dim)';
                        var textColor = 'var(--grey)';
                        var boxShadow = '';
                        
                        if (newStatus === 'pass') {
                            bgColor = 'var(--ok-dim)';
                            textColor = 'var(--ok)';
                            boxShadow = '0 0 6px var(--ok)';
                        } else if (newStatus === 'route') {
                            bgColor = 'var(--amber-dim)';
                            textColor = 'var(--amber)';
                            boxShadow = '0 0 6px var(--amber)';
                        } else if (newStatus === 'fail') {
                            bgColor = 'var(--danger-dim)';
                            textColor = 'var(--danger)';
                            boxShadow = '0 0 6px var(--danger)';
                        }
                        
                        statusSpill.style.background = bgColor;
                        statusSpill.style.color = textColor;
                        
                        // Update the dot shadow
                        var dotSpan = statusSpill.querySelector('span:first-child');
                        if (dotSpan) {
                            dotSpan.style.boxShadow = boxShadow;
                        }
                    }

                    // Update border color
                    var borderColor = 'var(--grey)';
                    if (newStatus === 'pass') borderColor = 'var(--ok)';
                    else if (newStatus === 'route') borderColor = 'var(--amber)';
                    else if (newStatus === 'fail') borderColor = 'var(--danger)';
                    row.style.borderLeftColor = borderColor;
                }

                // Always update source IP (check every time, not just on status change)
                var idCell = row.querySelector('div > div:nth-child(2)');
                if (idCell) {
                    var divsInCell = idCell.querySelectorAll('div');
                    if (divsInCell.length >= 2) {
                        var sourceIpSpan = divsInCell[1];
                        var currentSourceIp = sourceIpSpan ? sourceIpSpan.textContent.trim() : '';
                        if (currentSourceIp !== newSourceIp) {
                            sourceIpSpan.textContent = newSourceIp;
                        }
                    }
                }

                // Always update channel test button state (every update, not just on status change)
                var channelTestCell = row.querySelector('.channel-test-cell');
                
                if (channelTestCell) {
                    if (newStatus === 'pass') {
                        // Show enabled form for PASS status with BLUE button
                        channelTestCell.innerHTML =
                            '<form method="POST" action="/tests/' + didId + '" style="margin:0;display:inline-flex;align-items:center;gap:8px" onsubmit="return startChTest(this, ' + didId + ')">' +
                            '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                            '<button type="submit" class="btn-sm btn-channel" style="background:var(--primary-dim);color:var(--primary);border:1px solid rgba(97,83,246,.25)"><i class="fa-solid fa-signal"></i>Test Channel</button>' +
                            '<input type="number" class="ch-input-sm" name="call_count" id="cc_input_' + didId + '" value="5" min="1" max="100" title="Number of calls to test">' +
                            '</form>';
                    } else {
                        // Show disabled button for non-PASS status
                        channelTestCell.innerHTML =
                            '<div style="display:inline-flex;align-items:center;gap:8px">' +
                            '<button type="button" class="btn-sm btn-channel" style="opacity:.4;cursor:not-allowed" onclick="showErrModal(\'DID status must be PASS to run channel test. Current: ' + newStatus.toUpperCase() + '\')">' +
                            '<i class="fa-solid fa-lock"></i>Channel' +
                            '</button>' +
                            '<input type="number" class="ch-input-sm" value="5" disabled style="opacity:.4">' +
                            '</div>';
                    }
                }
            });
        })
        .catch(function(err) { 
            console.error("Status update error:", err);
        });
}

// Start auto-update when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize row attributes for auto-update
    document.querySelectorAll('[data-id]').forEach(function(row) {
        // Ensure the status span has 'spill' class
        var statusSpan = row.querySelector('.status-text');
        if (statusSpan) {
            var statusParent = statusSpan.parentElement;
            if (statusParent && !statusParent.classList.contains('spill')) {
                statusParent.classList.add('spill');
                // Add status-specific class
                var currentStatus = statusSpan.textContent.trim().toLowerCase();
                if (!['pass', 'fail', 'route'].includes(currentStatus)) {
                    currentStatus = 'pending';
                }
                statusParent.classList.add('s-' + currentStatus);
            }
        }
    });
    
    // Initial update
    updateDIDStatuses();
    
    // Update every 3 seconds
    autoUpdateInterval = setInterval(updateDIDStatuses, 3000);
});

// Clean up when leaving page
window.addEventListener('beforeunload', function() {
    if (autoUpdateInterval) {
        clearInterval(autoUpdateInterval);
    }
});
</script>
@endsection
