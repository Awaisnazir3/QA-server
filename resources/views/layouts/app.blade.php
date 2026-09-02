<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DIDX — Softswitch Console')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            fontFamily: {
              sans: ['Inter', 'sans-serif'],
              mono: ['JetBrains Mono', 'monospace'],
              disp: ['Sora', 'sans-serif'],
            },
            colors: {
              primary: { DEFAULT: '#003875', dk: '#002754', dim: 'rgba(0,56,117,0.08)' },
              accent: { DEFAULT: '#ea5518', dk: '#cf460f', dim: 'rgba(234,85,24,0.10)' },
              navy: { 900: '#070d18', 800: '#0c1527', 700: '#111c34', 600: '#1e293b' }
            }
          }
        }
      }
    </script>
    @yield('styles')
    <style>
        *,*::before,*::after{box-sizing:border-box}
        :root{
          --bg:#f1f4f9; --surface:#ffffff; --surface2:#f8fafc; --hover:#eef2f7;
          --border:#e2e8f0; --bordersoft:#edf2f7;
          --ink1:#0f172a; --ink2:#475569; --ink3:#94a3b8;
          --primary:#003875; --primary-dk:#002754; --primary-dim:rgba(0,56,117,0.08); --primary-line:rgba(0,56,117,0.25);
          --accent:#ea5518; --accent-dim:rgba(234,85,24,0.10); --accent-dk:#cf460f;
          --teal:#0284c7; --teal-dim:rgba(2,132,199,0.09);
          --amber:#d97706; --amber-dim:rgba(217,119,6,0.09);
          --violet:#6366f1; --violet-dim:rgba(99,102,241,0.1);
          --ok:#059669; --ok-dim:rgba(5,150,105,0.09);
          --danger:#dc2626; --danger-dim:rgba(220,38,38,0.09);
          --grey:#64748b; --grey-dim:rgba(100,116,139,0.1);
          --sidebar:#0c1527; --sidebar2:#111c34; --sidebar-line:#1e293b; --sidebar-ink2:#94a3b8; --sidebar-ink3:#64748b;
          --disp:'Sora',sans-serif; --ui:'Inter',sans-serif; --mono:'JetBrains Mono',monospace;
          --r:6px; --rs:4px;
        }
        html{color-scheme:light}
        body{font-family:var(--ui);background:var(--bg);color:var(--ink1);margin:0;height:100vh;overflow:hidden;transition:background .2s,color .2s}
        body.night{
          --bg:#070b14; --surface:#0d1527; --surface2:#121d33; --hover:#18253f;
          --border:#1a2844; --bordersoft:#15223a;
          --ink1:#f8fafc; --ink2:#cbd5e1; --ink3:#64748b;
          --primary:#1e5bb0; --primary-dk:#16468a; --primary-dim:rgba(30,91,176,0.18);
        }
        body.night{color-scheme:dark}

        /* Zero-scroll Desktop Shell */
        .shell{display:flex;height:100vh;max-height:100vh;width:100vw;overflow:hidden}

        /* SIDEBAR */
        .sidebar{width:210px;flex-shrink:0;background:linear-gradient(180deg,var(--sidebar),#060a12);border-right:1px solid var(--sidebar-line);display:flex;flex-direction:column;height:100vh;transition:width .2s cubic-bezier(0.4,0,0.2,1);z-index:40;position:relative}
        .sidebar.collapsed{width:54px}
        .sidebar.collapsed .sb-label,
        .sidebar.collapsed .sb-section-lbl,
        .sidebar.collapsed .sb-brand-sub,
        .sidebar.collapsed .sb-user-meta,
        .sidebar.collapsed .sb-foot-txt{display:none}
        .sidebar.collapsed .sb-brand{padding:10px 8px;justify-content:center}
        .sidebar.collapsed .sb-item{justify-content:center;padding:7px 0;gap:0}
        .sidebar.collapsed .sb-item i{margin:0;font-size:14px}
        .sidebar.collapsed .sb-user-card{justify-content:center;padding:6px 0}
        .sidebar.collapsed .sb-logout-btn span{display:none}
        .sidebar.collapsed .sb-logout-btn{padding:6px 0;justify-content:center}

        .sb-brand{display:flex;align-items:center;padding:10px 12px;border-bottom:1px solid var(--sidebar-line);gap:8px}
        .sb-logo-card{background:#ffffff;padding:4px 8px;border-radius:5px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,0.25);flex-shrink:0}
        .sb-nav{flex:1;padding:6px 8px;overflow-y:auto;scrollbar-width:none}
        .sb-nav::-webkit-scrollbar{display:none}
        .sb-section-lbl{font-size:8.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--sidebar-ink3);padding:10px 8px 4px;font-family:var(--mono)}
        .sb-item{display:flex;align-items:center;gap:9px;padding:6px 8px;border-radius:5px;color:var(--sidebar-ink2);font-size:11.5px;font-weight:500;margin-bottom:2px;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap}
        .sb-item i{width:15px;text-align:center;font-size:12px;flex-shrink:0}
        .sb-item:hover{background:rgba(255,255,255,.06);color:#fff}
        .sb-item.active{background:linear-gradient(90deg,rgba(0,56,117,.65),rgba(0,56,117,.2));color:#fff;box-shadow:inset 3px 0 0 var(--accent)}
        .sb-foot{padding:8px 10px;border-top:1px solid var(--sidebar-line);font-size:9.5px;color:var(--sidebar-ink3);font-family:var(--mono);text-align:center;letter-spacing:.3px}

        /* TELEMETRY RIBBON (TOP BAR) */
        .telemetry-bar{height:42px;flex-shrink:0;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 14px;z-index:30}
        .t-pill{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:11px;padding:3px 9px;border-radius:4px;background:var(--surface2);border:1px solid var(--border);color:var(--ink2);line-height:1;white-space:nowrap}
        .t-pill .t-dot{width:6.5px;height:6.5px;border-radius:50%;flex-shrink:0}
        .t-dot-ok{background:var(--ok);box-shadow:0 0 7px var(--ok);animation:tPulse 2s infinite}
        .t-dot-err{background:var(--danger);box-shadow:0 0 7px var(--danger)}
        @keyframes tPulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(0.85)}}

        /* MAIN CONTENT VIEWPORT */
        .main-viewport{flex:1;min-width:0;display:flex;flex-direction:column;height:100vh;overflow:hidden;background:var(--bg)}
        .content-area{flex:1;min-height:0;display:flex;flex-direction:column;overflow:hidden}
        .content-area > :not(#workspaceRoot){overflow-y:auto;flex:1;padding:14px 18px 30px}

        /* Dense Buttons */
        .btn-dense{display:inline-flex;align-items:center;justify-content:center;gap:4px;height:24px;padding:0 8px;border-radius:4px;font-size:11px;font-weight:600;transition:all .15s;border:1px solid transparent;cursor:pointer;white-space:nowrap}
        .btn-dense-primary{background:var(--primary);color:#fff;border-color:var(--primary)}
        .btn-dense-primary:hover{background:var(--primary-dk);border-color:var(--primary-dk)}
        .btn-dense-del{background:var(--danger-dim);color:var(--danger);border-color:rgba(220,38,38,0.25)}
        .btn-dense-del:hover{background:var(--danger);color:#fff}
        .btn-dense-ok{background:var(--ok-dim);color:var(--ok);border-color:rgba(5,150,105,0.25)}
        .btn-dense-ok:hover{background:var(--ok);color:#fff}
        .btn-dense-amber{background:var(--accent-dim);color:var(--accent);border-color:rgba(234,85,24,0.25)}
        .btn-dense-amber:hover{background:var(--accent);color:#fff}
        .btn-dense-ghost{background:transparent;color:var(--ink2);border-color:var(--border)}
        .btn-dense-ghost:hover{background:var(--hover);color:var(--ink1)}

        /* Hanging Up Flashing State */
        .flashing{animation:btnFlash .7s infinite}
        @keyframes btnFlash{0%,100%{background:var(--danger-dim);color:var(--danger)}50%{background:var(--danger);color:#fff}}

        /* Status Pills */
        .spill{display:inline-flex;align-items:center;gap:4.5px;padding:2px 6px;border-radius:3px;font-size:10.5px;font-family:var(--mono);font-weight:600;text-transform:capitalize}
        .spill .sdot{width:5px;height:5px;border-radius:50%;background:currentColor}
        .s-pass{background:var(--ok-dim);color:var(--ok)}
        .s-fail{background:var(--danger-dim);color:var(--danger)}
        .s-route{background:var(--accent-dim);color:var(--accent)}
        .s-pending{background:var(--grey-dim);color:var(--grey)}
        .s-dialing{background:var(--violet-dim);color:var(--violet)}

        /* Legacy page compatibility classes */
        .statrow{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
        @media(max-width:900px){.statrow{grid-template-columns:repeat(2,1fr)}}
        .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:12px 16px;position:relative;overflow:hidden;display:flex;align-items:center;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,0.02)}
        .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
        .stat-card.sc-primary::before{background:var(--primary)}
        .stat-card.sc-teal::before{background:var(--teal)}
        .stat-card.sc-violet::before{background:var(--violet)}
        .stat-card.sc-amber::before{background:var(--accent)}
        .stat-icon{width:32px;height:32px;flex-shrink:0;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:13px}
        .sc-primary .stat-icon{background:var(--primary-dim);color:var(--primary)}
        .sc-teal .stat-icon{background:var(--teal-dim);color:var(--teal)}
        .sc-violet .stat-icon{background:var(--violet-dim);color:var(--violet)}
        .sc-amber .stat-icon{background:var(--accent-dim);color:var(--accent)}
        .stat-lbl{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--ink3);margin-bottom:2px}
        .stat-val{font-family:var(--mono);font-size:18px;font-weight:800;color:var(--ink1);line-height:1}
        .card{background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:14px;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,0.02)}
        .card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid var(--bordersoft)}
        .card-title{font-size:13px;font-weight:700;color:var(--ink1);display:flex;align-items:center;gap:8px}
        .cbadge{font-size:11px;font-family:var(--mono);background:var(--surface2);color:var(--ink2);border:1px solid var(--border);padding:2px 7px;border-radius:4px;font-weight:600}
        .slabel{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--ink3);margin:8px 0 12px;display:flex;align-items:center;gap:8px}
        .slabel::after{content:'';flex:1;height:1px;background:var(--border)}
        .table-compact{width:100%;border-collapse:collapse;font-size:12px;text-align:left}
        .table-compact th{padding:6px 10px;border-bottom:1px solid var(--border);color:var(--ink3);font-family:var(--mono);font-size:10px;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;background:var(--surface2)}
        .table-compact td{padding:6px 10px;border-bottom:1px solid var(--bordersoft);vertical-align:middle}
        .btn-sm{display:inline-flex;align-items:center;gap:4px;padding:3px 7px;border-radius:4px;font-size:11px;font-weight:600;transition:all .15s;white-space:nowrap;border:none;cursor:pointer}
        .btn-excel{background:#107c41;color:#fff;border:1px solid #107c41;padding:4px 10px;font-size:11.5px;border-radius:4px;font-weight:600;display:inline-flex;align-items:center;gap:5px;text-decoration:none;cursor:pointer}

        /* Micro Scrollbar */
        ::-webkit-scrollbar{width:5px;height:5px}
        ::-webkit-scrollbar-track{background:transparent}
        ::-webkit-scrollbar-thumb{background:rgba(100,116,139,0.3);border-radius:3px}
        ::-webkit-scrollbar-thumb:hover{background:rgba(100,116,139,0.5)}
    </style>
</head>
<body class="select-none">
    <div class="shell">
        <!-- COMPACT COLLAPSIBLE NAVY SIDEBAR -->
        <aside class="sidebar" id="appSidebar">
            <!-- Brand & Collapse Toggle -->
            <div class="sb-brand">
                <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;text-decoration:none;gap:8px;min-width:0;flex:1">
                    <div class="sb-logo-card">
                        <img src="{{ asset('images/didx-logo.png') }}" alt="DIDX" style="height:18px;width:auto;display:block">
                    </div>
                    <div class="sb-brand-sub" style="font-size:8.5px;color:var(--sidebar-ink3);font-family:var(--mono);letter-spacing:0.8px;text-transform:uppercase;line-height:1.2">
                        Softswitch<br><span style="color:#ffffff;font-weight:700">Console</span>
                    </div>
                </a>
                <button type="button" id="sidebarToggleBtn" title="Toggle Sidebar [Ctrl+B]" style="background:transparent;border:none;color:var(--sidebar-ink3);cursor:pointer;padding:4px;border-radius:4px;font-size:11px;transition:color .15s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--sidebar-ink3)'">
                    <i class="fa-solid fa-chevron-left" id="sidebarToggleIcon"></i>
                </button>
            </div>

            <!-- Nav Links -->
            <nav class="sb-nav" id="sidebarNav">
                <div class="sb-section-lbl">Routing & Ops</div>
                <a href="{{ route('dashboard') }}" class="sb-item @if(Route::currentRouteName() === 'dashboard') active @endif" title="DID Route Manager">
                    <i class="fa-solid fa-route"></i><span class="sb-label">DID Routes</span>
                </a>
                <a href="{{ route('bulk-test.index') }}" class="sb-item @if(Route::currentRouteName() === 'bulk-test.index') active @endif" title="Bulk DID Testing">
                    <i class="fa-solid fa-list-check"></i><span class="sb-label">Bulk DID Test</span>
                </a>
                <a href="{{ route('tests.index') }}" class="sb-item @if(Route::currentRouteName() === 'tests.index') active @endif" title="Channel Tests">
                    <i class="fa-solid fa-signal"></i><span class="sb-label">Channel Tests</span>
                </a>
                <a href="{{ route('calls.live') }}" class="sb-item @if(Route::currentRouteName() === 'calls.live') active @endif" title="Live Calls Monitor">
                    <i class="fa-solid fa-phone-volume"></i><span class="sb-label">Live Calls</span>
                </a>
                <a href="{{ route('dialer.index') }}" class="sb-item @if(Route::currentRouteName() === 'dialer.index') active @endif" title="Dialer & Softphone">
                    <i class="fa-solid fa-phone"></i><span class="sb-label">Dialer</span>
                </a>
                <a href="{{ route('abuse-dids.index') }}" class="sb-item @if(Route::currentRouteName() === 'abuse-dids.index') active @endif" title="Abuse DIDs Detector">
                    <i class="fa-solid fa-shield-virus"></i><span class="sb-label">Abuse DIDs</span>
                </a>

                <div class="sb-section-lbl">Telecom Network</div>
                <a href="{{ route('sip-trunks') }}" class="sb-item @if(Route::currentRouteName() === 'sip-trunks') active @endif" title="SIP Trunks Infrastructure">
                    <i class="fa-solid fa-server"></i><span class="sb-label">SIP Trunks</span>
                </a>
                <a href="{{ route('reports.cdr') }}" class="sb-item @if(Route::currentRouteName() === 'reports.cdr') active @endif" title="Call Reports & CDRs">
                    <i class="fa-solid fa-chart-line"></i><span class="sb-label">Reports &amp; CDRs</span>
                </a>

                @if(auth()->check() && strtolower(auth()->user()->username) === 'awais')
                <div class="sb-section-lbl">System Admin</div>
                <a href="{{ route('settings.index') }}" class="sb-item @if(Route::currentRouteName() === 'settings.index') active @endif" title="Switch Configuration">
                    <i class="fa-solid fa-sliders"></i><span class="sb-label">Settings</span>
                </a>
                @endif
            </nav>

            <!-- Compact User Card -->
            <div style="padding:8px 10px;border-top:1px solid var(--sidebar-line);margin-top:auto">
                <div class="sb-user-card" style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <div style="width:24px;height:24px;border-radius:4px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}
                    </div>
                    <div class="sb-user-meta" style="flex:1;min-width:0">
                        <div style="color:#fff;font-size:11px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ auth()->user()->username ?? 'User' }}
                        </div>
                        <div style="color:var(--sidebar-ink3);font-size:8.5px;text-transform:uppercase;font-family:var(--mono)">
                            {{ auth()->user()->role ?? 'Operator' }}
                        </div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin:0">
                    @csrf
                    <button type="submit" class="sb-logout-btn" title="Logout" style="width:100%;padding:4px 8px;background:rgba(255,255,255,0.04);color:var(--sidebar-ink2);border:1px solid var(--sidebar-line);border-radius:4px;font-size:10px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;transition:all 0.15s">
                        <i class="fa-solid fa-arrow-right-from-bracket text-[10px]"></i> <span>Logout</span>
                    </button>
                </form>
            </div>
            <div class="sb-foot sb-foot-txt">DIDX Softswitch v2.5</div>
        </aside>

        <!-- MAIN VIEWPORT CONTAINER (ZERO PAGE SCROLL) -->
        <main class="main-viewport">
            <!-- ULTRA-COMPACT TELEMETRY RIBBON -->
            <header class="telemetry-bar">
                <!-- Left: View Crumb & Route Title -->
                <div class="flex items-center gap-2 min-w-0">
                    <span class="font-disp font-bold text-xs text-[var(--ink1)] whitespace-nowrap">
                        @yield('page-title', 'DID Route Manager')
                    </span>
                    <span class="text-slate-400 dark:text-slate-600 text-xs">/</span>
                    <span class="font-mono text-[10.5px] text-[var(--ink3)] truncate hidden md:inline">
                        @yield('page-crumb', 'DIDX / Softswitch / Control')
                    </span>
                </div>

                <!-- Center: Telemetry Gauges Ribbon -->
                <div class="flex items-center gap-1.5 overflow-x-auto py-0.5" id="telemetryRibbon">
                    @inject('asterisk', 'App\Services\AsteriskService')
                    @php $isOnline = $asterisk->isOnline(); @endphp

                    <!-- 1. Asterisk Process Pill -->
                    <div class="t-pill" id="tbAsteriskPill" title="Asterisk Engine Status">
                        <span class="t-dot {{ $isOnline ? 't-dot-ok' : 't-dot-err' }}" id="tbAsteriskDot"></span>
                        <span class="font-bold text-[10px]" id="tbAsteriskText" style="color:{{ $isOnline ? 'var(--ok)' : 'var(--danger)' }}">
                            AST {{ $isOnline ? 'ONLINE' : 'OFFLINE' }}
                        </span>
                    </div>

                    <!-- 2. AMI Connection Pill -->
                    <div class="t-pill" id="tbAmiPill" title="Asterisk Manager Interface (AMI)">
                        <i class="fa-solid fa-bolt text-[9.5px] text-amber-500"></i>
                        <span class="text-[10px] text-[var(--ink3)]">AMI:</span>
                        <span class="font-bold text-[10.5px] text-sky-500" id="tbAmiText">Connected</span>
                    </div>

                    <!-- 3. Active Calls Counter -->
                    <div class="t-pill" id="tbCallsPill" title="Active Switch Channels">
                        <i class="fa-solid fa-phone-volume text-[9.5px] text-emerald-500"></i>
                        <span class="font-bold text-[10.5px] text-[var(--ink1)]" id="tbActiveCallsVal">
                            {{ $stats['activeCalls'] ?? 0 }}
                        </span>
                        <span class="text-[9.5px] text-[var(--ink3)]">Calls</span>
                    </div>

                    <!-- 4. Registered SIP Peers -->
                    <div class="t-pill" id="tbPeersPill" title="Online SIP Endpoints / Total">
                        <i class="fa-solid fa-server text-[9.5px] text-indigo-500"></i>
                        <span class="font-bold text-[10.5px] text-[var(--ink1)]" id="tbPeersVal">
                            {{ $stats['onlinePeers'] ?? 0 }}
                        </span>
                        <span class="text-[9.5px] text-[var(--ink3)]">Peers</span>
                    </div>

                    <!-- 5. RAM & CPU Telemetry -->
                    <div class="t-pill" title="Host Resource Utilization">
                        <span class="flex items-center gap-1">
                            <i class="fa-solid fa-microchip text-[9.5px] text-amber-500"></i>
                            <span class="font-bold text-[10px]" id="tbRamVal">{{ $stats['ramUsage'] ?? 42 }}%</span>
                        </span>
                        <span class="text-slate-300 dark:text-slate-700">|</span>
                        <span class="flex items-center gap-1">
                            <i class="fa-solid fa-gauge-high text-[9.5px] text-teal-500"></i>
                            <span class="font-bold text-[10px]" id="tbCpuVal">{{ $stats['cpuUsage'] ?? 12 }}%</span>
                        </span>
                    </div>
                </div>

                <!-- Right: Night/Day Vision & Quick Actions -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <div class="flex items-center bg-[var(--surface2)] border border-[var(--border)] rounded p-0.5" id="visionToggle">
                        <button type="button" class="vision-btn flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold text-[var(--ink3)] hover:text-[var(--ink1)] transition-all" data-mode="night" title="Night vision">
                            <i class="fa-solid fa-moon text-[9.5px]"></i> <span class="hidden sm:inline">Night</span>
                        </button>
                        <button type="button" class="vision-btn flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold text-[var(--ink3)] hover:text-[var(--ink1)] transition-all" data-mode="day" title="Day vision">
                            <i class="fa-solid fa-sun text-[9.5px]"></i> <span class="hidden sm:inline">Day</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- NOTIFICATIONS STRIP (INLINE / COMPACT) -->
            @php $hasErrors = isset($errors) && $errors->any(); @endphp
            @if(session('success') || session('error') || $hasErrors)
            <div class="px-3 py-1 flex items-center justify-between text-xs font-semibold z-20 transition-all border-b"
                 style="{{ session('success') ? 'background:var(--ok-dim);color:var(--ok);border-color:rgba(5,150,105,0.2)' : 'background:var(--danger-dim);color:var(--danger);border-color:rgba(220,38,38,0.2)' }}"
                 id="globalNoticeBanner">
                <div class="flex items-center gap-2">
                    <i class="fa-solid {{ session('success') ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                    <span>{{ session('success') ?? session('error') ?? ($hasErrors ? $errors->first() : '') }}</span>
                </div>
                <button onclick="document.getElementById('globalNoticeBanner').remove()" class="text-xs opacity-70 hover:opacity-100 bg-transparent border-none cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            @endif

            <!-- MAIN CONTENT AREA (FLEXIBLE WORKSPACE) -->
            <div class="content-area">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- SHARED DESKTOP SCRIPTS -->
    <script>
        // Vision Day/Night Toggle with Tailwind support
        (function(){
            var toggle = document.getElementById('visionToggle');
            if(!toggle) return;
            var btns = toggle.querySelectorAll('.vision-btn');
            function applyMode(mode){
                if(mode === 'night'){
                    document.body.classList.add('night');
                    document.documentElement.classList.add('dark');
                } else {
                    document.body.classList.remove('night');
                    document.documentElement.classList.remove('dark');
                }
                btns.forEach(function(b){
                    var isAct = b.getAttribute('data-mode') === mode;
                    b.style.background = isAct ? 'var(--primary)' : 'transparent';
                    b.style.color = isAct ? '#ffffff' : 'var(--ink3)';
                });
                try { localStorage.setItem('didx_vision', mode); }catch(e){}
            }
            var saved = 'day';
            try { saved = localStorage.getItem('didx_vision') || 'day'; }catch(e){}
            applyMode(saved);
            btns.forEach(function(b){
                b.addEventListener('click', function(){ applyMode(b.getAttribute('data-mode')); });
            });
        })();

        // Collapsible Sidebar Script
        (function(){
            var sidebar = document.getElementById('appSidebar');
            var toggleBtn = document.getElementById('sidebarToggleBtn');
            var toggleIcon = document.getElementById('sidebarToggleIcon');
            if(!sidebar || !toggleBtn) return;

            function setSidebarState(collapsed){
                if(collapsed){
                    sidebar.classList.add('collapsed');
                    if(toggleIcon) toggleIcon.className = 'fa-solid fa-chevron-right';
                } else {
                    sidebar.classList.remove('collapsed');
                    if(toggleIcon) toggleIcon.className = 'fa-solid fa-chevron-left';
                }
                try { localStorage.setItem('didx_sb_collapsed', collapsed ? '1' : '0'); }catch(e){}
            }

            var isCollapsed = false;
            try { isCollapsed = localStorage.getItem('didx_sb_collapsed') === '1'; }catch(e){}
            setSidebarState(isCollapsed);

            toggleBtn.addEventListener('click', function(){
                setSidebarState(!sidebar.classList.contains('collapsed'));
            });

            // Keyboard shortcut [Ctrl+B] to toggle sidebar
            window.addEventListener('keydown', function(e){
                if((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b'){
                    e.preventDefault();
                    setSidebarState(!sidebar.classList.contains('collapsed'));
                }
            });
        })();
    </script>

    @yield('scripts')
</body>
</html>
