@extends('layouts.app')

@section('title', 'DIDX — Channel Test History')
@section('page-title', 'Channel Test Audit History')
@section('page-crumb', 'DIDX / Operations / Channel Tests')

@section('content')
<div class="slabel"><i class="fa-solid fa-signal"></i>Channel Detection Logs</div>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-list-check"></i>Channel Diagnostic Status Logs</div>
    </div>

    @if($history->isEmpty())
        <p style="font-size:13px;color:var(--ink2);padding:10px 0;">No channel diagnostic tests recorded yet. Execute channel tests directly from the DID Routes view.</p>
    @else
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;min-width:700px">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);color:var(--ink3);font-size:10.5px;text-transform:uppercase;font-family:var(--mono)">
                        <th style="padding:10px 14px">DID Number</th>
                        <th style="padding:10px 14px">Calls Fired</th>
                        <th style="padding:10px 14px">Channels Detected</th>
                        <th style="padding:10px 14px">Status</th>
                        <th style="padding:10px 14px">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $log)
                        <tr style="border-bottom:1px solid var(--bordersoft)">
                            <td style="padding:12px 14px;font-weight:700;font-family:var(--mono);color:var(--ink1)">
                                {{ htmlspecialchars($log->phone_number) }}
                            </td>
                            <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink2)">
                                {{ (int)$log->calls_requested }}
                            </td>
                            <td style="padding:12px 14px;font-family:var(--mono);font-weight:700;color:var(--violet)">
                                {{ (int)$log->channels_detected }}
                            </td>
                            <td style="padding:12px 14px">
                                <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;background:var(--ok-dim);color:var(--ok)">
                                    <span style="width:6px;height:6px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok)"></span>
                                    {{ htmlspecialchars($log->status) }}
                                </span>
                            </td>
                            <td style="padding:12px 14px;font-family:var(--mono);font-size:11px;color:var(--ink3)">
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
