@extends('layouts.app')

@section('title', 'DIDX — Live Connected Calls')
@section('page-title', 'Live Connected Calls')
@section('page-crumb', 'DIDX / Operations / Live Calls')

@section('content')
<div class="slabel"><i class="fa-solid fa-phone-volume"></i>Active Switch Sessions</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface);flex-wrap:wrap;gap:10px">
        <div class="card-title" style="font-size:13.5px"><i class="fa-solid fa-headset"></i>Live Calls Monitor</div>
        <div style="display:flex;align-items:center;gap:8px">
            @if(!empty($calls) && count($calls) > 0)
                <form method="POST" action="{{ route('calls.hangup-all') }}" style="margin:0" onsubmit="return confirm('Disconnect ALL active calls?')">
                    @csrf
                    <button type="submit" class="btn-hangup">
                        <i class="fa-solid fa-phone-slash"></i> Hangup All Calls
                    </button>
                </form>
            @endif
            <div class="cbadge"><span id="liveCallCount">{{ $callCount }}</span> Active</div>
        </div>
    </div>

    <div style="overflow-x:auto">
        <table class="table-compact">
            <thead>
                <tr>
                    <th>Channel Name</th>
                    <th>Context</th>
                    <th>Extension</th>
                    <th>State</th>
                    <th style="text-align:right;width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($calls as $call)
                    <tr>
                        <td style="font-family:var(--mono);color:var(--ink1);font-weight:600">{{ $call['channel'] }}</td>
                        <td style="font-family:var(--mono);color:var(--ink2)">{{ $call['context'] }}</td>
                        <td style="font-family:var(--mono);color:var(--ink2)">{{ $call['exten'] }}</td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--ok);font-weight:600">
                                <span class="status-dot status-pass"></span>
                                {{ ucfirst(strtolower($call['state'])) }}
                            </span>
                        </td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('calls.hangup-channel') }}" style="margin:0;display:inline">
                                @csrf
                                <input type="hidden" name="channel" value="{{ $call['channel'] }}">
                                <button type="submit" class="btn-sm btn-del" title="Hangup Channel">
                                    <i class="fa-solid fa-phone-slash"></i> Hangup
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:28px;color:var(--ink3);font-family:var(--mono);font-size:12px">
                            No active call channels currently online.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
