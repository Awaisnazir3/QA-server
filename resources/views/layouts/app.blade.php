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
          --bg:#f3f4f9; --surface:#ffffff; --surface2:#f8f9fd; --hover:#eef0f9;
          --border:#e6e8f2; --bordersoft:#eef0f8;
          --ink1:#171a2c; --ink2:#5c6280; --ink3:#9499b3;
          --primary:#6153f6; --primary-dk:#4d3fe0; --primary-dim:rgba(97,83,246,.08); --primary-line:rgba(97,83,246,.35);
          --teal:#0ea5a0; --teal-dim:rgba(14,165,160,.09);
          --amber:#dd8b0a; --amber-dim:rgba(221,139,10,.09);
          --violet:#8546e8; --violet-dim:rgba(133,70,232,.1);
          --ok:#0fa66a; --ok-dim:rgba(15,166,106,.09);
          --danger:#e0393f; --danger-dim:rgba(224,57,63,.09);
          --grey:#767c94; --grey-dim:rgba(118,124,148,.1);
          --sidebar:#131226; --sidebar2:#1b1a35; --sidebar-line:#2a2850; --sidebar-ink2:#8b89b8; --sidebar-ink3:#5f5d8c;
          --disp:'Sora',sans-serif; --ui:'Inter',sans-serif; --mono:'JetBrains Mono',monospace;
          --r:14px; --rs:9px;
        }
        html{color-scheme:light}
        body{font-family:var(--ui);background:var(--bg);color:var(--ink1);margin:0;min-height:100vh;transition:background .2s,color .2s}
        body.night{
          --bg:#0f0e1e; --surface:#181733; --surface2:#1d1c3d; --hover:#232149;
          --border:#2b295a; --bordersoft:#252351;
          --ink1:#f2f1fb; --ink2:#a7a4d4; --ink3:#7472ab;
        }
        body.night{color-scheme:dark}

        .shell{display:flex;min-height:100vh}

        /* SIDEBAR */
        .sidebar{width:236px;flex-shrink:0;background:linear-gradient(180deg,var(--sidebar),#0e0d1e);border-right:1px solid var(--sidebar-line);display:flex;flex-direction:column;position:sticky;top:0;height:100vh}
        .sb-brand{display:flex;align-items:center;gap:11px;padding:22px 20px 18px}
        .sb-logo{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,var(--primary),#8f6ffc);display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;box-shadow:0 6px 16px rgba(97,83,246,.35)}
        .sb-brand-name{font-family:var(--disp);font-size:16.5px;font-weight:700;color:#fff}
        .sb-brand-sub{font-size:9px;color:var(--sidebar-ink3);font-family:var(--mono);letter-spacing:1.6px;text-transform:uppercase;margin-top:1px}
        .sb-nav{flex:1;padding:8px 12px}
        .sb-section-lbl{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:1.3px;color:var(--sidebar-ink3);padding:14px 10px 8px}
        .sb-item{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:10px;color:var(--sidebar-ink2);font-size:13px;font-weight:500;margin-bottom:2px;cursor:pointer;transition:background .15s,color .15s;text-decoration:none}
        .sb-item i{width:16px;text-align:center;font-size:13px}
        .sb-item:hover{background:rgba(255,255,255,.05);color:#fff}
        .sb-item.active{background:linear-gradient(90deg,rgba(97,83,246,.22),rgba(97,83,246,.04));color:#fff;box-shadow:inset 3px 0 0 var(--primary)}
        .sb-foot{padding:16px 20px 20px;border-top:1px solid var(--sidebar-line);font-size:10px;color:var(--sidebar-ink3);font-family:var(--mono);text-align:center;letter-spacing:.5px}
        @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

        /* MAIN */
        .main{flex:1;min-width:0}
        .topbar{height:70px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 30px;position:sticky;top:0;z-index:50}
        .tb-title{font-family:var(--disp);font-size:18px;font-weight:700;color:var(--ink1)}
        .tb-crumb{font-size:11px;color:var(--ink3);font-family:var(--mono);letter-spacing:.4px;margin-top:2px}
        .tb-right{display:flex;align-items:center;gap:12px}
        .tb-live{display:flex;align-items:center;gap:8px;font-size:11.5px;color:var(--ink2);font-family:var(--mono);background:var(--surface2);border:1px solid var(--border);padding:7px 13px;border-radius:20px}
        .tb-live .dot{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 8px var(--ok);animation:blink 2s infinite}
        .vision-toggle{display:flex;background:var(--surface2);border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px}
        .vision-btn{display:flex;align-items:center;justify-content:center;gap:5px;padding:7px 12px;border-radius:16px;font-size:11px;font-weight:600;color:var(--ink3);background:transparent;transition:all .18s;border:none;cursor:pointer}
        .vision-btn i{font-size:11px}
        .vision-btn.active{background:var(--primary);color:#fff}

        .wrap{max-width:1360px;margin:0 auto;padding:28px 30px 60px}

        /* STAT CARDS */
        .statrow{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:22px}
        @media(max-width:900px){.statrow{grid-template-columns:repeat(2,1fr)}}
        .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:11px;padding:12px 14px;position:relative;overflow:hidden;display:flex;align-items:center;gap:11px}
        .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
        .stat-card.sc-primary::before{background:var(--primary)}
        .stat-card.sc-teal::before{background:var(--teal)}
        .stat-card.sc-violet::before{background:var(--violet)}
        .stat-card.sc-amber::before{background:var(--amber)}
        .stat-icon{width:30px;height:30px;flex-shrink:0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12.5px}
        .sc-primary .stat-icon{background:var(--primary-dim);color:var(--primary)}
        .sc-teal .stat-icon{background:var(--teal-dim);color:var(--teal)}
        .sc-violet .stat-icon{background:var(--violet-dim);color:var(--violet)}
        .sc-amber .stat-icon{background:var(--amber-dim);color:var(--amber)}
        .stat-lbl{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ink3);margin-bottom:2px}
        .stat-val{font-family:var(--mono);font-size:18px;font-weight:800;color:var(--ink1);line-height:1}

        .slabel{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.4px;color:var(--ink3);margin:8px 0 14px;display:flex;align-items:center;gap:9px}
        .slabel::after{content:'';flex:1;height:1px;background:var(--border)}
        .slabel i{color:var(--primary)}

        .card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:18px;margin-bottom:16px;box-shadow:0 1px 2px rgba(20,20,50,.02)}
        .card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--bordersoft)}
        .card-title{font-size:13.5px;font-weight:700;color:var(--ink1);display:flex;align-items:center;gap:8px}
        .card-title i{color:var(--primary);font-size:12.5px}
        .cbadge{font-size:11px;font-family:var(--mono);background:var(--surface2);color:var(--ink2);border:1px solid var(--border);padding:3px 8px;border-radius:4px;font-weight:600}

        .btn-hangup{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:5px;font-size:11.5px;font-weight:600;background:var(--danger-dim);color:var(--danger);border:1px solid rgba(224,57,63,.25);cursor:pointer;transition:all .15s}
        .btn-hangup:hover{background:var(--danger);color:#fff}
        .btn-hangup.flashing{animation:hflash .65s infinite}
        @keyframes hflash{0%,100%{background:var(--danger-dim);color:var(--danger)}50%{background:var(--danger);color:#fff}}

        .btn-primary{padding:6px 14px;background:var(--primary);color:#fff;border-radius:5px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap;transition:background .15s,opacity .15s;border:1px solid var(--primary);cursor:pointer}
        .btn-primary:hover{background:var(--primary-dk);border-color:var(--primary-dk);color:#fff}

        .btn-sm{display:inline-flex;align-items:center;gap:5px;padding:4px 9px;border-radius:4px;font-size:11px;font-weight:600;transition:all .15s;white-space:nowrap;border:none;cursor:pointer}
        .btn-route{background:var(--ok-dim);color:var(--ok);border:1px solid rgba(15,166,106,.25)} .btn-route:hover{background:var(--ok);color:#fff}
        .btn-reset{background:var(--surface2);color:var(--ink2);border:1px solid var(--border)} .btn-reset:hover{background:var(--hover);color:var(--ink1)}
        .btn-del{background:var(--danger-dim);color:var(--danger);border:1px solid rgba(224,57,63,.25)} .btn-del:hover{background:var(--danger);color:#fff}
        .btn-channel{background:var(--violet-dim);color:var(--violet);border:1px solid rgba(133,70,232,.25)} .btn-channel:hover{background:var(--violet);color:#fff}
        .btn-channel:disabled{opacity:.4;cursor:not-allowed}
        .btn-disabled{opacity:.4;cursor:not-allowed !important}

        .ch-input-sm{width:44px;height:26px;padding:2px 4px;border:1px solid var(--border);border-radius:4px;background:var(--surface);color:var(--ink1);font-family:var(--mono);font-size:12px;font-weight:700;text-align:center;outline:none;transition:border-color .15s}
        .ch-input-sm:focus{border-color:var(--violet);box-shadow:0 0 0 2px var(--violet-dim)}
        .ch-input-sm:disabled{opacity:.35}
        .input-disabled{opacity:.4 !important;cursor:not-allowed !important}

        .ch-res-badge{font-family:var(--mono);font-weight:700;font-size:15px;color:var(--ink1);padding:1px 6px;min-width:24px;text-align:center;display:inline-block}

        /* Clean Table Utilities */
        .table-compact{width:100%;border-collapse:collapse;font-size:12.5px;text-align:left}
        .table-compact th{padding:8px 12px;border-bottom:1px solid var(--border);color:var(--ink3);font-family:var(--mono);font-size:10.5px;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;background:var(--surface2)}
        .table-compact td{padding:8px 12px;border-bottom:1px solid var(--bordersoft);vertical-align:middle}
        .table-compact tbody tr:hover{background:var(--hover)}

        /* Status Dot Styling */
        .status-dot{width:6px;height:6px;border-radius:50%;display:inline-block;flex-shrink:0}
        .status-pass{background:var(--ok)}
        .status-fail{background:var(--danger)}
        .status-route{background:var(--amber)}
        .status-pending{background:var(--grey)}
        .status-dialing{background:var(--violet)}

        /* Section visibility control */
        .view-section{display:none}
        .view-section.active-view{display:block}

        .foot{text-align:center;padding:18px 0 0;font-size:10px;color:var(--ink3);font-family:var(--mono);border-top:1px solid var(--border);margin-top:22px;letter-spacing:.5px}

        .alert{padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:12.5px;font-weight:600}
        .alert-success{background:var(--ok-dim);color:var(--ok);border:1px solid rgba(15,166,106,.25)}
        .alert-error{background:var(--danger-dim);color:var(--danger);border:1px solid rgba(224,57,63,.25)}
        .alert-warning{background:var(--amber-dim);color:var(--amber);border:1px solid rgba(221,139,10,.25)}

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
            background: var(--amber-dim);
            color: var(--amber);
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
            padding: 5px 11px;
            font-size: 11.5px;
            border-radius: 5px;
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
            <div class="sb-brand" style="padding:16px 16px 14px;border-bottom:1px solid var(--sidebar-line);margin-bottom:6px">
                <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;text-decoration:none;gap:10px">
                    <div style="background:#ffffff;padding:6px 10px;border-radius:7px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.25)">
                        <img src="{{ asset('images/didx-logo.svg') }}" alt="DIDX" style="height:24px;width:auto;display:block">
                    </div>
                    <div style="font-size:9.5px;color:var(--sidebar-ink3);font-family:var(--mono);letter-spacing:1px;text-transform:uppercase;line-height:1.3">
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
            <div style="padding:16px 12px;border-top:1px solid var(--sidebar-line);margin-top:auto">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">
                        {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="color:#fff;font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ auth()->user()->username ?? 'User' }}
                        </div>
                        <div style="color:var(--sidebar-ink3);font-size:9.5px;text-transform:uppercase;font-family:var(--mono)">
                            {{ auth()->user()->role ?? 'Operator' }}
                        </div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin:0">
                    @csrf
                    <button type="submit" style="width:100%;padding:8px 12px;background:rgba(255,255,255,0.05);color:var(--sidebar-ink2);border:1px solid var(--sidebar-line);border-radius:8px;font-size:11.5px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all 0.2s;border:none">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                    </button>
                </form>
            </div>
            <div class="sb-foot">DIDX v2.0</div>
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
                            <span class="dot" id="globalAsteriskDot" style="background:var(--danger);box-shadow:0 0 8px var(--danger);animation:none"></span>
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
