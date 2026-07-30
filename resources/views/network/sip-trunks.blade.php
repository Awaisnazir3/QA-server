@extends('layouts.app')

@section('title', 'DIDX — PJSIP Trunks')
@section('page-title', 'PJSIP Trunks Infrastructure')
@section('page-crumb', 'DIDX / Network / PJSIP Trunks')

@section('content')
<div class="slabel"><i class="fa-solid fa-server"></i>PJSIP Infrastructure</div>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-network-wired"></i>Registered PJSIP Endpoints</div>
        <div class="cbadge">{{ $onlinePeers }} Online</div>
    </div>

    @if(empty($peerList))
        <p style="font-size:13px;color:var(--ink2);padding:10px 0;">
            No PJSIP endpoints detected or Asterisk CLI permissions restricted for <code>pjsip show endpoints</code>.
        </p>
    @else
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;min-width:600px">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);color:var(--ink3);font-size:10.5px;text-transform:uppercase;font-family:var(--mono)">
                        <th style="padding:10px 14px">Endpoint / Extension</th>
                        <th style="padding:10px 14px">Host / IP Address</th>
                        <th style="padding:10px 14px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($peerList as $peer)
                        <tr style="border-bottom:1px solid var(--bordersoft)">
                            <td style="padding:12px 14px;font-weight:700;font-family:var(--mono);color:var(--ink1)">
                                <i class="fa-solid fa-plug" style="margin-right:8px;color:var(--primary)"></i>{{ htmlspecialchars($peer['name']) }}
                            </td>
                            <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink2)">
                                {{ htmlspecialchars($peer['ip']) }}
                            </td>
                            <td style="padding:12px 14px">
                                @if($peer['online'])
                                    <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;background:var(--ok-dim);color:var(--ok)">
                                        <span style="width:6px;height:6px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok)"></span>
                                        {{ htmlspecialchars($peer['status']) }}
                                    </span>
                                @else
                                    <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;background:var(--danger-dim);color:var(--danger)">
                                        <span style="width:6px;height:6px;border-radius:50%;background:var(--danger);box-shadow:0 0 6px var(--danger)"></span>
                                        {{ htmlspecialchars($peer['status']) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Statistics Summary -->
<div style="margin-top:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:11px;padding:16px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ink3);margin-bottom:8px">Total Endpoints</div>
        <div style="font-family:var(--mono);font-size:24px;font-weight:800;color:var(--ink1)">{{ count($peerList) }}</div>
    </div>
    
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:11px;padding:16px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ink3);margin-bottom:8px">Online Endpoints</div>
        <div style="font-family:var(--mono);font-size:24px;font-weight:800;color:var(--ok)">{{ $onlinePeers }}</div>
    </div>
    
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:11px;padding:16px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ink3);margin-bottom:8px">Offline Endpoints</div>
        <div style="font-family:var(--mono);font-size:24px;font-weight:800;color:var(--danger)">{{ count($peerList) - $onlinePeers }}</div>
    </div>
</div>

@endsection
