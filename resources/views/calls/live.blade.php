@extends('layouts.app')

@section('title', 'DIDX — Live Connected Calls')
@section('page-title', 'Live Connected Calls')
@section('page-crumb', 'DIDX / Operations / Live Calls')

@section('content')
<div class="slabel"><i class="fa-solid fa-phone-volume"></i>Active Switch Sessions</div>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-headset"></i>Live Calls Monitor</div>
        <div class="cbadge"><span id="liveCallCount">{{ $callCount }}</span> Active Call(s)</div>
    </div>

    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;min-width:700px">
            <thead>
                <tr style="border-bottom:1px solid var(--border);color:var(--ink3);font-size:10.5px;text-transform:uppercase;font-family:var(--mono)">
                    <th style="padding:10px 14px">Channel Name</th>
                    <th style="padding:10px 14px">Context</th>
                    <th style="padding:10px 14px">Extension</th>
                    <th style="padding:10px 14px">State</th>
                    <th style="padding:10px 14px;text-align:center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($calls as $call)
                    <tr style="border-bottom:1px solid var(--bordersoft)">
                        <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink1)">{{ htmlspecialchars($call['channel']) }}</td>
                        <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink2)">{{ htmlspecialchars($call['context']) }}</td>
                        <td style="padding:12px 14px;font-family:var(--mono);color:var(--ink2)">{{ htmlspecialchars($call['exten']) }}</td>
                        <td style="padding:12px 14px">
                            <span style="color:#0fa66a;font-weight:700">{{ htmlspecialchars($call['state']) }}</span>
                        </td>
                        <td style="padding:12px 14px;text-align:center">
                            <form method="POST" action="{{ route('calls.hangup-channel') }}" style="margin:0;display:inline">
                                @csrf
                                <input type="hidden" name="channel" value="{{ htmlspecialchars($call['channel']) }}">
                                <button type="submit" class="btn-sm btn-del"><i class="fa-solid fa-xmark"></i> Hangup</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr style="border-bottom:1px solid var(--bordersoft)">
                        <td colspan="5" style="text-align:center;padding:24px;color:#9499b3">No active call channels currently online.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(!empty($calls))
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);display:flex;gap:10px">
            <form method="POST" action="{{ route('calls.hangup-all') }}" style="margin:0">
                @csrf
                <button type="submit" class="btn-hangup" onclick="return confirm('Disconnect ALL active calls?')">
                    <i class="fa-solid fa-phone-slash"></i>Hangup All Calls
                </button>
            </form>
        </div>
    @endif
</div>

@endsection
