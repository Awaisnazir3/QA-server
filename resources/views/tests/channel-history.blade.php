@extends('layouts.app')

@section('title', 'DIDX — Channel Test History')
@section('page-title', 'Channel Test Audit History')
@section('page-crumb', 'DIDX / Operations / Channel Tests')

@section('content')
<div class="slabel"><i class="fa-solid fa-signal"></i>Channel Detection Logs</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface)">
        <div class="card-title" style="font-size:13.5px"><i class="fa-solid fa-list-check"></i>Channel Diagnostic Status Logs</div>
        <div class="cbadge">{{ count($history) }} Records</div>
    </div>

    @if($history->isEmpty())
        <p style="font-size:12px;color:var(--ink3);padding:24px;text-align:center;font-family:var(--mono)">
            No channel diagnostic tests recorded yet. Execute channel tests directly from the DID Routes view.
        </p>
    @else
        <div style="overflow-x:auto">
            <table class="table-compact">
                <thead>
                    <tr>
                        <th>DID Number</th>
                        <th style="text-align:center;width:110px">Calls Fired</th>
                        <th style="text-align:center;width:130px">Channels Detected</th>
                        <th style="width:110px">Status</th>
                        <th style="width:160px">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $log)
                        <tr>
                            <td style="font-weight:700;font-family:var(--mono);color:var(--ink1);font-size:12.5px">
                                {{ $log->phone_number }}
                            </td>
                            <td style="text-align:center;font-family:var(--mono);color:var(--ink2)">
                                {{ (int)$log->calls_requested }}
                            </td>
                            <td style="text-align:center;font-family:var(--mono);font-weight:700;color:var(--violet)">
                                {{ (int)$log->channels_detected }}
                            </td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--ok);font-weight:600">
                                    <span class="status-dot status-pass"></span>
                                    {{ ucfirst(strtolower($log->status)) }}
                                </span>
                            </td>
                            <td style="font-family:var(--mono);font-size:11px;color:var(--ink3)">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
