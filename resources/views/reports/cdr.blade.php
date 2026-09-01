@extends('layouts.app')

@section('title', 'DIDX — Call Reports & CDRs')
@section('page-title', 'Call Detail Records (CDR)')
@section('page-crumb', 'DIDX / Analytics / Call Reports')

@section('content')
<div class="slabel"><i class="fa-solid fa-chart-line"></i>Call Detail Records (CDR) &amp; Reports</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface);flex-wrap:wrap;gap:10px">
        <div class="card-title" style="font-size:13.5px"><i class="fa-solid fa-file-lines"></i>CDR Logs</div>
        <div style="display:flex;align-items:center;gap:8px">
            <form method="GET" style="display:flex;gap:6px;margin:0">
                <input type="text" name="search" placeholder="Search Caller ID / DID..." value="{{ $search }}" style="padding:5px 10px;border:1px solid var(--border);border-radius:5px;background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:11.5px;outline:none;width:180px">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-search"></i> Search</button>
                @if($search)
                    <a href="{{ route('reports.cdr') }}" class="btn-sm btn-reset" style="text-decoration:none"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div style="overflow-x:auto">
        <table class="table-compact">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Caller ID</th>
                    <th>Destination</th>
                    <th style="text-align:center">Duration</th>
                    <th style="text-align:center">Billsec</th>
                    <th>Disposition</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cdrs as $cdr)
                    <tr>
                        <td style="font-family:var(--mono);font-size:11.5px;color:var(--ink2)">{{ $cdr->start_time?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td style="font-family:var(--mono);font-weight:700;color:var(--ink1);font-size:12.5px">{{ $cdr->caller_id }}</td>
                        <td style="font-family:var(--mono);color:var(--ink2)">{{ $cdr->destination }}</td>
                        <td style="text-align:center;font-family:var(--mono);color:var(--ink2)">{{ $cdr->duration }}s</td>
                        <td style="text-align:center;font-family:var(--mono);color:var(--ink2)">{{ $cdr->billsec }}s</td>
                        <td>
                            @if($cdr->disposition === 'ANSWERED')
                                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--ok);font-weight:600">
                                    <span class="status-dot status-pass"></span>
                                    Answered
                                </span>
                            @else
                                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--danger);font-weight:600">
                                    <span class="status-dot status-fail"></span>
                                    {{ ucfirst(strtolower($cdr->disposition)) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:28px;color:var(--ink3);font-family:var(--mono);font-size:12px">
                            No CDR logs found @if($search) matching "{{ $search }}" @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
