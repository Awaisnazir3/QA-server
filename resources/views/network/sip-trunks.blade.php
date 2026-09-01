@extends('layouts.app')

@section('title', 'DIDX — PJSIP Trunks')
@section('page-title', 'PJSIP Trunks Infrastructure')
@section('page-crumb', 'DIDX / Network / PJSIP Trunks')

@section('content')
<div class="slabel"><i class="fa-solid fa-server"></i>PJSIP Infrastructure</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface)">
        <div class="card-title" style="font-size:13.5px"><i class="fa-solid fa-network-wired"></i>Registered PJSIP Endpoints</div>
        <div class="cbadge">{{ $onlinePeers }} Online</div>
    </div>

    @if(empty($peerList))
        <p style="font-size:12px;color:var(--ink3);padding:24px;text-align:center;font-family:var(--mono)">
            No PJSIP endpoints detected.
        </p>
    @else
        <div style="overflow-x:auto">
            <table class="table-compact">
                <thead>
                    <tr>
                        <th>Endpoint / Extension</th>
                        <th>Host / IP Address</th>
                        <th style="width:120px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($peerList as $peer)
                        <tr>
                            <td style="font-weight:700;font-family:var(--mono);color:var(--ink1);font-size:12.5px">
                                <i class="fa-solid fa-server" style="margin-right:6px;color:var(--primary);font-size:10.5px"></i>{{ $peer['name'] }}
                            </td>
                            <td style="font-family:var(--mono);color:var(--ink2)">
                                {{ $peer['ip'] }}
                            </td>
                            <td>
                                @if($peer['online'])
                                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--ok);font-weight:600">
                                        <span class="status-dot status-pass"></span>
                                        {{ $peer['status'] }}
                                    </span>
                                @else
                                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--danger);font-weight:600">
                                        <span class="status-dot status-fail"></span>
                                        {{ $peer['status'] }}
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
<div style="margin-top:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:12px 16px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ink3);margin-bottom:4px">Total Endpoints</div>
        <div style="font-family:var(--mono);font-size:18px;font-weight:700;color:var(--ink1)">{{ count($peerList) }}</div>
    </div>
    
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:12px 16px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ink3);margin-bottom:4px">Online Endpoints</div>
        <div style="font-family:var(--mono);font-size:18px;font-weight:700;color:var(--ok)">{{ $onlinePeers }}</div>
    </div>
    
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:12px 16px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ink3);margin-bottom:4px">Offline Endpoints</div>
        <div style="font-family:var(--mono);font-size:18px;font-weight:700;color:var(--danger)">{{ count($peerList) - $onlinePeers }}</div>
    </div>
</div>

@endsection
