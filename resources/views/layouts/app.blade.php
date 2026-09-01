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
    @yield('styles')
    <style>
        *,*::before,*::after{box-sizing:border-box}
        :root{
          --bg:#f4f6fa; --surface:#ffffff; --surface2:#f8fafc; --hover:#eef2f7;
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
          --r:8px; --rs:5px;
        }
        html{color-scheme:light}
        body{font-family:var(--ui);background:var(--bg);color:var(--ink1);margin:0;min-height:100vh;transition:background .2s,color .2s}
        body.night{
          --bg:#090e1a; --surface:#0f172a; --surface2:#162036; --hover:#1e293b;
          --border:#1e293b; --bordersoft:#19243a;
          --ink1:#f8fafc; --ink2:#cbd5e1; --ink3:#64748b;
          --primary:#1e5bb0; --primary-dk:#16468a; --primary-dim:rgba(30,91,176,0.18);
        }
        body.night{color-scheme:dark}

        .shell{display:flex;min-height:100vh}

        /* SIDEBAR */
        .sidebar{width:240px;flex-shrink:0;background:linear-gradient(180deg,var(--sidebar),#070d18);border-right:1px solid var(--sidebar-line);display:flex;flex-direction:column;position:sticky;top:0;height:100vh}
        .sb-brand{display:flex;align-items:center;padding:16px 16px 14px;border-bottom:1px solid var(--sidebar-line);margin-bottom:6px}
        .sb-logo-card{background:#ffffff;padding:6px 12px;border-radius:6px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.22)}
        .sb-nav{flex:1;padding:8px 12px;overflow-y:auto}
        .sb-section-lbl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1.3px;color:var(--sidebar-ink3);padding:14px 10px 6px;font-family:var(--mono)}
        .sb-item{display:flex;align-items:center;gap:11px;padding:9px 12px;border-radius:6px;color:var(--sidebar-ink2);font-size:12.5px;font-weight:500;margin-bottom:2px;cursor:pointer;transition:background .15s,color .15s;text-decoration:none}
        .sb-item i{width:16px;text-align:center;font-size:13px}
        .sb-item:hover{background:rgba(255,255,255,.05);color:#fff}
        .sb-item.active{background:linear-gradient(90deg,rgba(0,56,117,.55),rgba(0,56,117,.15));color:#fff;box-shadow:inset 3px 0 0 var(--accent)}
        .sb-foot{padding:12px 16px;border-top:1px solid var(--sidebar-line);font-size:10px;color:var(--sidebar-ink3);font-family:var(--mono);text-align:center;letter-spacing:.5px}
        @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

        /* MAIN */
        .main{flex:1;min-width:0;display:flex;flex-direction:column}
        .topbar{height:62px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:50}
        .tb-title{font-family:var(--disp);font-size:16.5px;font-weight:700;color:var(--ink1)}
        .tb-crumb{font-size:11px;color:var(--ink3);font-family:var(--mono);letter-spacing:.3px;margin-top:1px}
        .tb-right{display:flex;align-items:center;gap:10px}
        .tb-live{display:flex;align-items:center;gap:7px;font-size:11.5px;color:var(--ink2);font-family:var(--mono);background:var(--surface2);border:1px solid var(--border);padding:5px 11px;border-radius:20px}
        .tb-live .dot{width:6px;height:6px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok);animation:blink 2s infinite}
        .vision-toggle{display:flex;background:var(--surface2);border:1px solid var(--border);border-radius:20px;padding:2px;gap:2px}
        .vision-btn{display:flex;align-items:center;justify-content:center;gap:5px;padding:5px 10px;border-radius:14px;font-size:11px;font-weight:600;color:var(--ink3);background:transparent;transition:all .18s;border:none;cursor:pointer}
        .vision-btn i{font-size:10.5px}
        .vision-btn.active{background:var(--primary);color:#fff}

        .wrap{max-width:1440px;margin:0 auto;padding:22px 28px 60px;width:100%}

        /* STAT CARDS */
        .statrow{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
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

        .slabel{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--ink3);margin:8px 0 12px;display:flex;align-items:center;gap:8px}
        .slabel::after{content:'';flex:1;height:1px;background:var(--border)}
        .slabel i{color:var(--accent)}

        .card{background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:16px;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,0.02)}
        .card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--bordersoft)}
        .card-title{font-size:13px;font-weight:700;color:var(--ink1);display:flex;align-items:center;gap:8px}
        .card-title i{color:var(--primary);font-size:12px}
        .cbadge{font-size:11px;font-family:var(--mono);background:var(--surface2);color:var(--ink2);border:1px solid var(--border);padding:2px 7px;border-radius:4px;font-weight:600}

        .btn-hangup{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:4px;font-size:11.5px;font-weight:600;background:var(--danger-dim);color:var(--danger);border:1px solid rgba(220,38,38,.25);cursor:pointer;transition:all .15s}
        .btn-hangup:hover{background:var(--danger);color:#fff}
        .btn-hangup.flashing{animation:hflash .65s infinite}
        @keyframes hflash{0%,100%{background:var(--danger-dim);color:var(--danger)}50%{background:var(--danger);color:#fff}}

        .btn-primary{padding:6px 13px;background:var(--primary);color:#fff;border-radius:4px;font-size:11.5px;font-weight:600;display:inline-flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap;transition:background .15s,opacity .15s;border:1px solid var(--primary);cursor:pointer}
        .btn-primary:hover{background:var(--primary-dk);border-color:var(--primary-dk);color:#fff}

        .btn-sm{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:600;transition:all .15s;white-space:nowrap;border:none;cursor:pointer}
        .btn-route{background:var(--ok-dim);color:var(--ok);border:1px solid rgba(5,150,105,.25)} .btn-route:hover{background:var(--ok);color:#fff}
        .btn-reset{background:var(--surface2);color:var(--ink2);border:1px solid var(--border)} .btn-reset:hover{background:var(--hover);color:var(--ink1)}
        .btn-del{background:var(--danger-dim);color:var(--danger);border:1px solid rgba(220,38,38,.25)} .btn-del:hover{background:var(--danger);color:#fff}
        .btn-channel{background:var(--accent-dim);color:var(--accent);border:1px solid rgba(234,85,24,.25)} .btn-channel:hover{background:var(--accent);color:#fff}
        .btn-channel:disabled{opacity:.4;cursor:not-allowed}
        .btn-disabled{opacity:.4;cursor:not-allowed !important}

        .ch-input-sm{width:42px;height:24px;padding:2px 4px;border:1px solid var(--border);border-radius:4px;background:var(--surface);color:var(--ink1);font-family:var(--mono);font-size:11.5px;font-weight:700;text-align:center;outline:none;transition:border-color .15s}
        .ch-input-sm:focus{border-color:var(--accent);box-shadow:0 0 0 2px var(--accent-dim)}
        .ch-input-sm:disabled{opacity:.35}
        .input-disabled{opacity:.4 !important;cursor:not-allowed !important}

        .ch-res-badge{font-family:var(--mono);font-weight:700;font-size:14px;color:var(--ink1);padding:1px 6px;min-width:24px;text-align:center;display:inline-block}

        /* Clean Table Utilities */
        .table-compact{width:100%;border-collapse:collapse;font-size:12px;text-align:left}
        .table-compact th{padding:7px 12px;border-bottom:1px solid var(--border);color:var(--ink3);font-family:var(--mono);font-size:10px;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;background:var(--surface2)}
        .table-compact td{padding:7px 12px;border-bottom:1px solid var(--bordersoft);vertical-align:middle}
        .table-compact tbody tr:hover{background:var(--hover)}

        /* Status Dot Styling */
        .status-dot{width:5px;height:5px;border-radius:50%;display:inline-block;flex-shrink:0}
        .status-pass{background:var(--ok)}
        .status-fail{background:var(--danger)}
        .status-route{background:var(--accent)}
        .status-pending{background:var(--grey)}
        .status-dialing{background:var(--violet)}
        .status-rejected{background:var(--danger)}

        /* Section visibility control */
        .view-section{display:none}
        .view-section.active-view{display:block}

        .foot{text-align:center;padding:16px 0 0;font-size:10px;color:var(--ink3);font-family:var(--mono);border-top:1px solid var(--border);margin-top:20px;letter-spacing:.5px}

        .alert{padding:8px 12px;border-radius:5px;margin-bottom:12px;font-size:12px;font-weight:600}
        .alert-success{background:var(--ok-dim);color:var(--ok);border:1px solid rgba(5,150,105,.25)}
        .alert-error{background:var(--danger-dim);color:var(--danger);border:1px solid rgba(220,38,38,.25)}
        .alert-warning{background:var(--amber-dim);color:var(--amber);border:1px solid rgba(217,119,6,.25)}

        /* Clean Minimal Status pill */
        .spill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .s-pending {
            background: var(--grey-dim);
            color: var(--grey);
        }

        .s-pass {
            background: var(--ok-dim);
            color: var(--ok);
        }

        .s-route {
            background: var(--accent-dim);
            color: var(--accent);
        }

        .s-fail {
            background: var(--danger-dim);
            color: var(--danger);
        }

        .s-dialing {
            background: var(--violet-dim);
            color: var(--violet);
        }

        .spill .sdot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .btn-excel {
            background: #107c41;
            color: #fff;
            border: 1px solid #107c41;
            padding: 4px 10px;
            font-size: 11.5px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s;
        }
        .btn-excel:hover {
            background: #0d6334;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="shell">
        <!-- SIDEBAR NAVIGATION -->
        <aside class="sidebar">
            <div class="sb-brand">
                <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;text-decoration:none;gap:10px;width:100%">
                    <div class="sb-logo-card">
                        <img src="{{ asset('images/didx-logo.svg') }}" alt="DIDX" style="height:22px;width:auto;display:block">
                    </div>
                    <div style="font-size:9px;color:var(--sidebar-ink3);font-family:var(--mono);letter-spacing:1px;text-transform:uppercase;line-height:1.3">
                        Softswitch<br><span style="color:var(--sidebar-ink2);font-weight:700">Console</span>
                    </div>
                </a>
            </div>
            <nav class="sb-nav" id="sidebarNav">
                <div class="sb-section-lbl">Operations</div>
                <a href="{{ route('dashboard') }}" class="sb-item @if(Route::currentRouteName() === 'dashboard') active @endif" data-target="view-did-routes" data-title="DID Route Manager" data-crumb="DIDX / Softswitch / DID Manager">
                    <i class="fa-solid fa-route"></i>DID Routes
                </a>
                <a href="{{ route('bulk-test.index') }}" class="sb-item @if(Route::currentRouteName() === 'bulk-test.index') active @endif" data-target="view-bulk-test" data-title="Bulk DID Testing" data-crumb="DIDX / Operations / Bulk DID Test">
                    <i class="fa-solid fa-list-check"></i>Bulk DID Test
                </a>
                <a href="{{ route('tests.index') }}" class="sb-item @if(Route::currentRouteName() === 'tests.index') active @endif" data-target="view-channel-tests" data-title="Channel Tests" data-crumb="DIDX / Operations / Channel Tests">
                    <i class="fa-solid fa-signal"></i>Channel Tests
                </a>
                <a href="{{ route('calls.live') }}" class="sb-item @if(Route::currentRouteName() === 'calls.live') active @endif" data-target="view-live-calls" data-title="Live Calls Monitor" data-crumb="DIDX / Operations / Live Calls">
                    <i class="fa-solid fa-phone-volume"></i>Live Calls
                </a>
                <a href="{{ route('dialer.index') }}" class="sb-item @if(Route::currentRouteName() === 'dialer.index') active @endif" data-target="view-dialer" data-title="Dialer & Call Center" data-crumb="DIDX / Operations / Dialer">
                    <i class="fa-solid fa-phone"></i>Dialer
                </a>
                <a href="{{ route('abuse-dids.index') }}" class="sb-item @if(Route::currentRouteName() === 'abuse-dids.index') active @endif" data-target="view-abuse-dids" data-title="Abuse DIDs Detector" data-crumb="DIDX / Operations / Abuse DIDs Detector">
                    <i class="fa-solid fa-shield-virus"></i>Abuse DIDs Detector
                </a>
                <div class="sb-section-lbl">Network</div>
                <a href="{{ route('sip-trunks') }}" class="sb-item @if(Route::currentRouteName() === 'sip-trunks') active @endif" data-target="view-sip-trunks" data-title="SIP Trunks Infrastructure" data-crumb="DIDX / Network / SIP Trunks">
                    <i class="fa-solid fa-server"></i>SIP Trunks
                </a>
                <a href="{{ route('reports.cdr') }}" class="sb-item @if(Route::currentRouteName() === 'reports.cdr') active @endif" data-target="view-reports" data-title="Call Reports & CDRs" data-crumb="DIDX / Analytics / Reports">
                    <i class="fa-solid fa-chart-line"></i>Reports
                </a>
                @if(auth()->check() && strtolower(auth()->user()->username) === 'awais')
                <a href="{{ route('settings.index') }}" class="sb-item @if(Route::currentRouteName() === 'settings.index') active @endif" data-target="view-settings" data-title="System Settings" data-crumb="DIDX / System / Settings">
                    <i class="fa-solid fa-gear"></i>Settings
                </a>
                @endif
            </nav>
            <div style="padding:12px 14px;border-top:1px solid var(--sidebar-line);margin-top:auto">
                <div style="display:flex;align-items:center;gap:9px;margin-bottom:10px">
                    <div style="width:28px;height:28px;border-radius:4px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">
                        {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="color:#fff;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ auth()->user()->username ?? 'User' }}
                        </div>
                        <div style="color:var(--sidebar-ink3);font-size:9.5px;text-transform:uppercase;font-family:var(--mono)">
                            {{ auth()->user()->role ?? 'Operator' }}
                        </div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin:0">
                    @csrf
                    <button type="submit" style="width:100%;padding:6px 10px;background:rgba(255,255,255,0.05);color:var(--sidebar-ink2);border:1px solid var(--sidebar-line);border-radius:4px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all 0.2s">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                    </button>
                </form>
            </div>
            <div class="sb-foot">DIDX Softswitch v2.0</div>
        </aside>

        <!-- MAIN VIEW CONTAINER -->
        <main class="main">
            <div class="topbar">
                <div>
                    <div class="tb-title" id="pageTitle">@yield('page-title', 'Dashboard')</div>
                    <div class="tb-crumb" id="pageCrumb">@yield('page-crumb', 'DIDX / Dashboard')</div>
                </div>
                <div class="tb-right">
                    @inject('asterisk', 'App\Services\AsteriskService')
                    @php $isOnline = $asterisk->isOnline(); @endphp
                    <div class="tb-live" id="globalAsteriskStatus" style="border-color:{{ $isOnline ? 'var(--border)' : 'var(--danger-dim)' }}">
                        @if($isOnline)
                            <span class="dot" id="globalAsteriskDot"></span>
                            <span id="globalAsteriskText">Asterisk Online</span>
                        @else
                            <span class="dot" id="globalAsteriskDot" style="background:var(--danger);box-shadow:0 0 6px var(--danger);animation:none"></span>
                            <span id="globalAsteriskText" style="color:var(--danger);font-weight:600">Asterisk Offline</span>
                        @endif
                    </div>
                    <div class="vision-toggle" id="visionToggle">
                        <button type="button" class="vision-btn" data-mode="night" title="Night vision"><i class="fa-solid fa-moon"></i>Night</button>
                        <button type="button" class="vision-btn" data-mode="day" title="Day vision"><i class="fa-solid fa-sun"></i>Day</button>
                    </div>
                </div>
            </div>

            <div class="wrap">
                @if($errors->any())
                    <div class="alert alert-error">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')

                <div class="foot">DIDX &middot; VoIP Softswitch Control Panel &middot; Asterisk Engine &middot; {{ date('Y') }}</div>
            </div>
        </main>
    </div>

    <script>
        (function(){
          var toggle = document.getElementById('visionToggle');
          var btns = toggle.querySelectorAll('.vision-btn');
          function applyMode(mode){
            if(mode === 'night'){ document.body.classList.add('night'); }
            else{ document.body.classList.remove('night'); }
            btns.forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-mode') === mode); });
            try{ localStorage.setItem('didx_vision', mode); }catch(e){}
          }
          var saved = 'day';
          try{ saved = localStorage.getItem('didx_vision') || 'day'; }catch(e){}
          applyMode(saved);
          btns.forEach(function(b){
            b.addEventListener('click', function(){ applyMode(b.getAttribute('data-mode')); });
          });
        })();
    </script>

    @yield('scripts')
</body>
</html>
