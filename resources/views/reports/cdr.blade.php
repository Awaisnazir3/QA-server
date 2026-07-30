@extends('layouts.app')

@section('title', 'DIDX — Call Reports & CDRs')
@section('page-title', 'Call Detail Records (CDR)')
@section('page-crumb', 'DIDX / Analytics / Call Reports')

@section('content')
<div class="slabel"><i class="fa-solid fa-chart-line"></i>Call Detail Records (CDR) &amp; Reports</div>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-file-lines"></i>CDR Logs</div>
        <div style="display:flex;align-items:center;gap:10px">
            <form method="GET" style="display:flex;gap:8px;margin:0">
                <input type="text" name="search" placeholder="Search Caller ID / DID..." value="{{ $search }}" style="padding:9px 13px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:13px;outline:none;flex:1;min-width:200px">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-search"></i>Search</button>
                @if($search)
                    <a href="{{ route('reports.cdr') }}" class="btn-sm btn-reset" style="text-decoration:none"><i class="fa-solid fa-rotate-left"></i>Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;margin-top:15px;font-family:var(--mono);font-size:13px;text-align:left;min-width:700px">
            <thead>
                <tr style="border-bottom:1px solid var(--border)">
                    <th style="padding:12px;border-bottom:1px solid var(--border);background:#f8f9fd;color:#9499b3;font-size:11px;text-transform:uppercase">Date & Time</th>
                    <th style="padding:12px;border-bottom:1px solid var(--border);background:#f8f9fd;color:#9499b3;font-size:11px;text-transform:uppercase">Caller ID</th>
                    <th style="padding:12px;border-bottom:1px solid var(--border);background:#f8f9fd;color:#9499b3;font-size:11px;text-transform:uppercase">Destination</th>
                    <th style="padding:12px;border-bottom:1px solid var(--border);background:#f8f9fd;color:#9499b3;font-size:11px;text-transform:uppercase">Duration</th>
                    <th style="padding:12px;border-bottom:1px solid var(--border);background:#f8f9fd;color:#9499b3;font-size:11px;text-transform:uppercase">Billsec</th>
                    <th style="padding:12px;border-bottom:1px solid var(--border);background:#f8f9fd;color:#9499b3;font-size:11px;text-transform:uppercase">Disposition</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cdrs as $cdr)
                    <tr style="border-bottom:1px solid var(--bordersoft)">
                        <td style="padding:12px">{{ $cdr->start_time?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td style="padding:12px"><strong>{{ htmlspecialchars($cdr->caller_id) }}</strong></td>
                        <td style="padding:12px">{{ htmlspecialchars($cdr->destination) }}</td>
                        <td style="padding:12px">{{ $cdr->duration }}s</td>
                        <td style="padding:12px">{{ $cdr->billsec }}s</td>
                        <td style="padding:12px">
                            <strong style="color:{{ $cdr->disposition === 'ANSWERED' ? '#0fa66a' : '#e0393f' }}">{{ $cdr->disposition }}</strong>
                        </td>
                    </tr>
                @empty
                    <tr style="border-bottom:1px solid var(--bordersoft)">
                        <td colspan="6" style="text-align:center;padding:24px;color:#9499b3">No CDR logs found @if($search) matching "{{ htmlspecialchars($search) }}" @endif</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
