@extends('layouts.app')

@section('title', 'DIDX — Abuse DIDs Detector')
@section('page-title', 'Abuse DIDs Detector')
@section('page-crumb', 'DIDX / Operations / Abuse DIDs Detector')

@section('content')
<style>
    .hit-badge {
        font-family: var(--mono);
        font-weight: 800;
        font-size: 13px;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    .hit-low {
        background: var(--primary-dim);
        color: var(--primary);
        border: 1px solid var(--primary-line);
    }
    .hit-mid {
        background: var(--amber-dim);
        color: var(--amber);
        border: 1px solid rgba(221,139,10,0.35);
    }
    .hit-high {
        background: var(--danger-dim);
        color: var(--danger);
        border: 1px solid rgba(224,57,63,0.4);
        animation: pulse-danger 1.5s infinite;
    }
    @keyframes pulse-danger {
        0%, 100% { box-shadow: 0 0 0 0 rgba(224,57,63,0.3); }
        50% { box-shadow: 0 0 0 6px rgba(224,57,63,0); }
    }
    .row-hit-flash {
        animation: rowFlash 1.2s ease-out;
    }
    @keyframes rowFlash {
        0% { background-color: rgba(224, 57, 63, 0.25) !important; transform: scale(1.01); }
        100% { background-color: transparent; transform: scale(1); }
    }
    .trunk-pill {
        font-family: var(--mono);
        font-size: 11px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 6px;
        background: var(--surface2);
        border: 1px solid var(--border);
        color: var(--ink2);
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .live-pulse-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: var(--mono);
        font-size: 11px;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 20px;
        background: var(--ok-dim);
        color: var(--ok);
        border: 1px solid rgba(15,166,106,0.3);
    }
    .live-pulse-indicator .pdot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--ok);
        box-shadow: 0 0 8px var(--ok);
        animation: blink 1.5s infinite;
    }
</style>

<!-- STATS OVERVIEW -->
<div class="statrow">
    <div class="stat-card sc-primary">
        <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
            <div class="stat-lbl">Abused DIDs Detected</div>
            <div class="stat-val" id="statTotalDids">{{ $stats['totalCount'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-amber">
        <div class="stat-icon"><i class="fa-solid fa-arrows-to-circle"></i></div>
        <div>
            <div class="stat-lbl">Total Abuse Hits</div>
            <div class="stat-val" id="statTotalHits" style="color:var(--amber)">{{ $stats['totalHits'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-violet">
        <div class="stat-icon"><i class="fa-solid fa-fire"></i></div>
        <div>
            <div class="stat-lbl">Top Offender DID</div>
            <div class="stat-val" id="statTopDid" style="font-size:15px;color:var(--danger)">
                {{ $stats['topDid'] }} 
                @if($stats['topHits'] > 0)
                    <span style="font-size:11px;color:var(--ink2)">({{ $stats['topHits'] }} hits)</span>
                @endif
            </div>
        </div>
    </div>
    <div class="stat-card sc-teal">
        <div class="stat-icon"><i class="fa-solid fa-network-wired"></i></div>
        <div>
            <div class="stat-lbl">Active Inbound Trunks</div>
            <div class="stat-val" id="statTrunks" style="color:var(--teal)">{{ $stats['uniqueTrunks'] }}</div>
        </div>
    </div>
</div>

<!-- SECTION HEADER & MAIN TABLE -->
<div class="slabel"><i class="fa-solid fa-shield-virus"></i>Live Abuse DIDs Detection Monitor</div>

<div class="card">
    <div class="card-head">
        <div style="display:flex;align-items:center;gap:12px">
            <div class="card-title"><i class="fa-solid fa-list-ol"></i>Detected Abused Numbers</div>
            <div class="live-pulse-indicator" id="liveIndicator">
                <span class="pdot"></span>
                <span id="liveStatusText">Live Scanning Active (2.5s)</span>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <!-- Search & Filter -->
            <input type="text" id="tableSearch" placeholder="Filter DID / Trunk..." onkeyup="filterAbuseTable()" style="padding:7px 12px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:12px;outline:none;width:170px">
            
            <!-- Pause/Resume Live Toggle -->
            <button type="button" class="btn-sm btn-reset" id="toggleLiveBtn" onclick="toggleLiveScanning()">
                <i class="fa-solid fa-pause"></i> Pause Feed
            </button>

            <!-- Excel Export -->
            <a href="{{ route('abuse-dids.export') }}" class="btn-primary btn-excel" style="padding:7px 13px;font-size:11.5px;text-decoration:none">
                <i class="fa-solid fa-file-excel"></i> Export CSV
            </a>

            <!-- Clear All -->
            <form method="POST" action="{{ route('abuse-dids.clear-all') }}" style="margin:0" onsubmit="return confirm('Are you sure you want to clear ALL detected abuse DIDs?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-sm btn-del" style="padding:7px 13px;font-size:11.5px"><i class="fa-solid fa-trash-can"></i> Clear All</button>
            </form>
        </div>
    </div>

    <!-- MAIN ABUSE TABLE -->
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left">
            <thead>
                <tr style="border-bottom:1px solid var(--border);color:var(--ink3);font-family:var(--mono);font-size:11px;text-transform:uppercase;letter-spacing:0.7px">
                    <th style="padding:10px 14px">#</th>
                    <th style="padding:10px 14px">DID / Phone Number</th>
                    <th style="padding:10px 14px">Source Trunk</th>
                    <th style="padding:10px 14px;text-align:center">Total Hits</th>
                    <th style="padding:10px 14px">Status</th>
                    <th style="padding:10px 14px">First Detected</th>
                    <th style="padding:10px 14px">Last Hit</th>
                    <th style="padding:10px 14px;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody id="abuseTableBody">
                @forelse($dids as $idx => $did)
                    @php
                        $hitClass = 'hit-low';
                        if ($did->hits_count >= 5) {
                            $hitClass = 'hit-high';
                        } elseif ($did->hits_count >= 2) {
                            $hitClass = 'hit-mid';
                        }
                    @endphp
                    <tr id="row-{{ $did->phone_number }}" data-phone="{{ $did->phone_number }}" data-trunk="{{ $did->source_trunk }}" style="border-bottom:1px solid var(--bordersoft);transition:background 0.3s">
                        <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink3);font-size:12px">{{ $idx + 1 }}</td>
                        <td style="padding:12px 14px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="font-family:var(--mono);font-weight:700;color:var(--ink1);font-size:14px">{{ $did->phone_number }}</span>
                                <button type="button" onclick="copyToClipboard('{{ $did->phone_number }}')" title="Copy DID" style="background:transparent;border:none;color:var(--ink3);cursor:pointer;font-size:11px;padding:2px 4px">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td style="padding:12px 14px">
                            <span class="trunk-pill">
                                <i class="fa-solid fa-server" style="color:var(--primary);font-size:10px"></i>
                                {{ $did->source_trunk ?: 'Asterisk-Inbound' }}
                            </span>
                        </td>
                        <td style="padding:12px 14px;text-align:center">
                            <span class="hit-badge {{ $hitClass }}" id="hits-{{ $did->phone_number }}">
                                <i class="fa-solid fa-bolt"></i>
                                <span>{{ $did->hits_count }}</span> {{ Str::plural('hit', $did->hits_count) }}
                            </span>
                        </td>
                        <td style="padding:12px 14px">
                            <span class="spill s-fail">
                                <span class="sdot"></span>
                                {{ strtoupper($did->status ?: 'REJECTED') }}
                            </span>
                        </td>
                        <td style="padding:12px 14px;font-family:var(--mono);font-size:11.5px;color:var(--ink2)">
                            {{ $did->first_hit_at ? $did->first_hit_at->format('M d, H:i:s') : '—' }}
                        </td>
                        <td style="padding:12px 14px;font-family:var(--mono);font-size:11.5px;color:var(--ink1)">
                            <span id="lasthit-{{ $did->phone_number }}">
                                {{ $did->last_hit_at ? $did->last_hit_at->diffForHumans() : '—' }}
                            </span>
                        </td>
                        <td style="padding:12px 14px;text-align:right">
                            <div style="display:inline-flex;gap:6px">
                                <!-- Delete Button -->
                                <form method="POST" action="{{ route('abuse-dids.destroy', $did->id) }}" style="margin:0" onsubmit="return confirm('Delete DID {{ $did->phone_number }} from abuse list?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm btn-del" title="Delete this DID">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="emptyRow">
                        <td colspan="8" style="text-align:center;padding:36px;color:var(--ink3);font-family:var(--mono)">
                            <i class="fa-solid fa-shield-halved" style="font-size:28px;color:var(--primary);margin-bottom:10px;display:block"></i>
                            No abusive DID hits detected yet. Incoming hits from Asterisk will appear here automatically!
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
    var isLiveScanning = true;
    var scanInterval = null;
    var lastKnownHits = {};

    // Initialize last known hits
    @foreach($dids as $did)
        lastKnownHits['{{ $did->phone_number }}'] = {{ (int)$did->hits_count }};
    @endforeach

    function toggleLiveScanning() {
        isLiveScanning = !isLiveScanning;
        var btn = document.getElementById('toggleLiveBtn');
        var indicator = document.getElementById('liveIndicator');
        var statusText = document.getElementById('liveStatusText');

        if (isLiveScanning) {
            btn.innerHTML = '<i class="fa-solid fa-pause"></i> Pause Feed';
            indicator.style.background = 'var(--ok-dim)';
            indicator.style.color = 'var(--ok)';
            indicator.style.borderColor = 'rgba(15,166,106,0.3)';
            statusText.innerText = 'Live Scanning Active (2.5s)';
            startPolling();
        } else {
            btn.innerHTML = '<i class="fa-solid fa-play"></i> Resume Feed';
            indicator.style.background = 'var(--amber-dim)';
            indicator.style.color = 'var(--amber)';
            indicator.style.borderColor = 'rgba(221,139,10,0.3)';
            statusText.innerText = 'Feed Paused';
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

            // Update Stats
            if (data.stats) {
                document.getElementById('statTotalDids').innerText = data.stats.totalCount;
                document.getElementById('statTotalHits').innerText = data.stats.totalHits;
                var topText = data.stats.topDid;
                if (data.stats.topHits > 0) {
                    topText += ' <span style="font-size:11px;color:var(--ink2)">(' + data.stats.topHits + ' hits)</span>';
                }
                document.getElementById('statTopDid').innerHTML = topText;
                document.getElementById('statTrunks').innerText = data.stats.uniqueTrunks;
            }

            // Render/Update Table Rows
            if (data.dids) {
                renderTableRows(data.dids);
            }
        })
        .catch(function(err) {
            console.warn('Abuse stream polling error:', err);
        });
    }

    function renderTableRows(dids) {
        var tbody = document.getElementById('abuseTableBody');
        var query = document.getElementById('tableSearch').value.toLowerCase().trim();

        if (!dids || dids.length === 0) {
            tbody.innerHTML = '<tr id="emptyRow"><td colspan="8" style="text-align:center;padding:36px;color:var(--ink3);font-family:var(--mono)"><i class="fa-solid fa-shield-halved" style="font-size:28px;color:var(--primary);margin-bottom:10px;display:block"></i>No abusive DID hits detected yet. Incoming hits from Asterisk will appear here automatically!</td></tr>';
            return;
        }

        var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
        var html = '';

        dids.forEach(function(did, idx) {
            var phone = String(did.phone_number);
            var hits = parseInt(did.hits_count) || 1;
            var prevHits = lastKnownHits[phone] || 0;
            var isNewHit = (hits > prevHits && prevHits > 0);
            var isBrandNew = (prevHits === 0 && Object.keys(lastKnownHits).length > 0);

            lastKnownHits[phone] = hits;

            var hitClass = 'hit-low';
            if (hits >= 5) hitClass = 'hit-high';
            else if (hits >= 2) hitClass = 'hit-mid';

            var flashClass = (isNewHit || isBrandNew) ? 'row-hit-flash' : '';
            var isHidden = false;
            if (query) {
                var trunk = (did.source_trunk || '').toLowerCase();
                if (phone.toLowerCase().indexOf(query) === -1 && trunk.indexOf(query) === -1) {
                    isHidden = true;
                }
            }

            var displayStyle = isHidden ? 'display:none;' : '';
            var deleteUrl = '/abuse-dids/' + did.id;

            html += `
                <tr id="row-${escapeHtml(phone)}" data-phone="${escapeHtml(phone)}" data-trunk="${escapeHtml(did.source_trunk || '')}" class="${flashClass}" style="border-bottom:1px solid var(--bordersoft);transition:background 0.3s;${displayStyle}">
                    <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink3);font-size:12px">${idx + 1}</td>
                    <td style="padding:12px 14px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-family:var(--mono);font-weight:700;color:var(--ink1);font-size:14px">${escapeHtml(phone)}</span>
                            <button type="button" onclick="copyToClipboard('${escapeHtml(phone)}')" title="Copy DID" style="background:transparent;border:none;color:var(--ink3);cursor:pointer;font-size:11px;padding:2px 4px">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </td>
                    <td style="padding:12px 14px">
                        <span class="trunk-pill">
                            <i class="fa-solid fa-server" style="color:var(--primary);font-size:10px"></i>
                            ${escapeHtml(did.source_trunk || 'Asterisk-Inbound')}
                        </span>
                    </td>
                    <td style="padding:12px 14px;text-align:center">
                        <span class="hit-badge ${hitClass}" id="hits-${escapeHtml(phone)}">
                            <i class="fa-solid fa-bolt"></i>
                            <span>${hits}</span> ${hits === 1 ? 'hit' : 'hits'}
                        </span>
                    </td>
                    <td style="padding:12px 14px">
                        <span class="spill s-fail">
                            <span class="sdot"></span>
                            ${escapeHtml((did.status || 'REJECTED').toUpperCase())}
                        </span>
                    </td>
                    <td style="padding:12px 14px;font-family:var(--mono);font-size:11.5px;color:var(--ink2)">
                        ${escapeHtml(did.first_hit_at || '—')}
                    </td>
                    <td style="padding:12px 14px;font-family:var(--mono);font-size:11.5px;color:var(--ink1)">
                        <span id="lasthit-${escapeHtml(phone)}">
                            ${escapeHtml(did.last_hit_human || did.last_hit_at || 'Just now')}
                        </span>
                    </td>
                    <td style="padding:12px 14px;text-align:right">
                        <div style="display:inline-flex;gap:6px">
                            <form method="POST" action="${deleteUrl}" style="margin:0" onsubmit="return confirm('Delete DID ${escapeHtml(phone)} from abuse list?')">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn-sm btn-del" title="Delete this DID">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function filterAbuseTable() {
        var query = document.getElementById('tableSearch').value.toLowerCase().trim();
        var rows = document.querySelectorAll('#abuseTableBody tr');

        rows.forEach(function(row) {
            if (row.id === 'emptyRow') return;
            var phone = (row.getAttribute('data-phone') || '').toLowerCase();
            var trunk = (row.getAttribute('data-trunk') || '').toLowerCase();
            if (phone.indexOf(query) !== -1 || trunk.indexOf(query) !== -1) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
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
        alert('Copied DID ' + text + ' to clipboard!');
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

    // Start auto scanning on load
    startPolling();
</script>
@endsection
