# Softphone Dialer (63311) - Architecture Diagram

## System Architecture

```
┌────────────────────────────────────────────────────────────────────┐
│                          USER'S BROWSER                            │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  Softphone Dialer Web Interface (dialer.blade.php)          │  │
│  │                                                              │  │
│  │  ┌─────────────────────┐  ┌──────────────────────────────┐  │  │
│  │  │  Left Panel         │  │  Right Panel                 │  │  │
│  │  │                     │  │                              │  │  │
│  │  │ Extension 63311     │  │  Caller ID Selector:         │  │  │
│  │  │ Status: Online ●    │  │  ▼ Route #1 (pass)           │  │  │
│  │  │        [Offline]    │  │  ▼ Route #2 (pass)           │  │  │
│  │  │                     │  │                              │  │  │
│  │  │ Config:             │  │  Dial Number:                │  │  │
│  │  │ 165.227.88.28:5060  │  │  [+1234567890___]            │  │  │
│  │  │                     │  │                              │  │  │
│  │  │ ☑ Auto-Refresh      │  │  Dial Pad:                   │  │  │
│  │  │ [Check Now] [Info]  │  │  ┌─┬─┬─┐                     │  │  │
│  │  │                     │  │  │1│2│3│                     │  │  │
│  │  │                     │  │  ├─┼─┼─┤                     │  │  │
│  │  │                     │  │  │4│5│6│                     │  │  │
│  │  │                     │  │  ├─┼─┼─┤                     │  │  │
│  │  │                     │  │  │7│8│9│                     │  │  │
│  │  │                     │  │  ├─┼─┼─┤                     │  │  │
│  │  │                     │  │  │*│0│#│                     │  │  │
│  │  │                     │  │  └─┴─┴─┘                     │  │  │
│  │  │                     │  │                              │  │  │
│  │  │                     │  │  [CALL] [HANGUP]             │  │  │
│  │  │                     │  │                              │  │  │
│  │  │                     │  │  [← BACKSPACE] [CLEAR]       │  │  │
│  │  └─────────────────────┘  └──────────────────────────────┘  │  │
│  │                                                              │  │
│  │  ┌──────────────────────────────────────────────────────┐  │  │
│  │  │ Call History Table                                   │  │  │
│  │  │ [All] [Outbound] [Inbound]                           │  │  │
│  │  │                                                      │  │  │
│  │  │ From   │ To     │ Dir │ Status  │ Duration │ Time   │  │  │
│  │  │────────┼────────┼─────┼─────────┼──────────┼────────│  │  │
│  │  │ 4412.. │ +1415..│OUT  │✓ COMPL  │ 00:45    │ 14:32  │  │  │
│  │  │ 4412.. │ +1202..│OUT  │✗ FAIL   │ 00:00    │ 14:15  │  │  │
│  │  │ 6331.. │ +4412..│ IN  │✓ COMPL  │ 02:15    │ 13:58  │  │  │
│  │  └──────────────────────────────────────────────────────┘  │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                    │
│                        JavaScript Layer                           │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ • Auto-refresh (3s interval)                                 │  │
│  │ • Dial pad input handling                                    │  │
│  │ • AJAX requests (Fetch API)                                  │  │
│  │ • Status badge updates                                       │  │
│  │ • Call duration counter                                      │  │
│  │ • History table rendering                                    │  │
│  └──────────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────┘
                                  │
                    HTTP/HTTPS    │    AJAX Requests
                                  │
                    ┌─────────────▼──────────────┐
                    │   Laravel Application      │
                    │   (Web Server)             │
                    │                            │
                    │  DialerController:         │
                    │  • index()                 │
                    │  • makeCall()              │
                    │  • hangupCall()            │
                    │  • getHistory()            │
                    │  • getExtensionStatus()    │
                    │  • updateCallStatus()      │
                    │  • addNotes()              │
                    └─────────────┬──────────────┘
                                  │
                    ┌─────────────┴──────────────┐
                    │                            │
        ┌───────────▼──────────────┐  ┌────────▼──────────────┐
        │   Database: MySQL        │  │  Asterisk Server     │
        │   (165.227.88.28:3306)   │  │  (165.227.88.28)     │
        │                          │  │                      │
        │  Tables:                 │  │  PJSIP Endpoints:    │
        │  • call_histories        │  │  • 63311 (softphone) │
        │  • call_logs             │  │  • 7788 (trunk)      │
        │  • call_cdrs             │  │  • from-webRTC-119   │
        │  • channels              │  │                      │
        │  • admin_users           │  │  Commands:           │
        │  • etc...                │  │  $ pjsip show endpoints
        │                          │  │  $ channel originate  │
        │                          │  │  $ channel hangup     │
        └──────────────────────────┘  └──────────────────────┘
```

---

## Data Flow Diagram: Making a Call

```
USER BROWSER                    LARAVEL SERVER                ASTERISK
│                               │                            │
├─ Select Caller ID             │                            │
├─ Dial Number                  │                            │
├─ Click [CALL] ─────────────┬─►│                            │
│                            │  │ POST /dialer/make-call     │
│                            │  │ {caller_id, callee_number} │
│                            │  │                            │
│                            │  ├─ Validate input           │
│                            │  ├─ Create CallHistory       │
│                            │  ├─ Get active route         │
│                            │  │                            │
│                            │  └─────────────────────────────►│
│                            │     channel originate           │
│                            │     (PJSIP/route@outbound)      │
│                            │                                 │
│ ◄───────────────────────────┤ Response: {call_id: 123}      │
│ Display Call Info            │                              │
│ Start Duration Timer         │                              │
│ Enable [HANGUP]              │                              │
│                              │                              │
│ GET /dialer/history ────────►│                              │
│ (every 5 seconds)            ├─ Query call_histories ─┐   │
│ ◄────────────────────────────┤                        │   │
│ Update history table         │◄ SELECT * FROM...─────┘   │
│                              │                            │
│ Duration: 00:01              │                            │
│ Duration: 00:02              │                            │
│ Duration: 00:03              │                            │
│                              │                            │
├─ Click [HANGUP] ────────────►│                            │
│                              │ POST /dialer/hangup-call    │
│                              │                            │
│                              └──────────────────────────► │
│                                 channel request hangup     │
│                                                            │
│ ◄───────────────────────────────────────────────────────── │
│ Call completed                                             │
│ Duration: 00:45                                            │
└────────────────────────────────────────────────────────────┘
```

---

## Data Flow Diagram: Checking Extension Status

```
USER BROWSER                    LARAVEL SERVER          ASTERISK
│                               │                        │
├─ Auto-Refresh Enabled         │                        │
├─ Every 3 seconds ─────────────►│                        │
│  GET /dialer/extension-status  │                        │
│  ?extension=63311              │                        │
│                                │                        │
│                                ├─ Validate extension   │
│                                │                        │
│                                ├─────────────────────► │
│                                │ sudo asterisk -rx      │
│                                │ 'pjsip show endpoints'│
│                                │                        │
│                                │◄─ Output:             │
│                                │   Endpoint: 63311 ... │
│                                │   Contact:            │
│                                │   sip:192.168.1.100...│
│                                │   Status: Avail       │
│                                │                        │
│                                ├─ Parse output         │
│                                ├─ Check for "Avail"    │
│                                │                        │
│ ◄───────────────────────────────┤                        │
│ Response:                       │                        │
│ {                               │                        │
│   status: "online",             │                        │
│   contact: "192.168.1.100:5060" │                        │
│ }                               │                        │
│                                 │                        │
├─ Update Status Badge            │                        │
│ [● ONLINE] (Green)              │                        │
│                                 │                        │
└─────────────────────────────────────────────────────────┘
```

---

## File Organization

```
Laravel Project Root
│
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── DialerController.php ✏️ UPDATED
│   │       ├── DidRouteController.php
│   │       ├── LiveCallController.php
│   │       └── ...
│   │
│   └── Models/
│       ├── CallHistory.php ✓ EXISTS
│       ├── CallLog.php
│       └── ...
│
├── resources/
│   └── views/
│       └── operations/
│           ├── dialer.blade.php ✏️ REWRITTEN
│           ├── channel-tests.blade.php
│           ├── live-calls.blade.php
│           └── ...
│
├── routes/
│   └── web.php ✓ ALREADY HAS ROUTES
│
├── database/
│   └── migrations/
│       ├── 2026_07_27_163830_create_call_histories_table.php ✓ EXISTS
│       ├── 2024_01_01_000001_create_call_logs_table.php
│       └── ...
│
├── storage/
│   └── logs/
│       └── laravel.log
│
├── SOFTPHONE_DIALER_SETUP.md ✨ NEW
├── DIALER_CHANGES_SUMMARY.md ✨ NEW
├── DIALER_UI_REFERENCE.md ✨ NEW
├── COMPLETION_STATUS.md ✨ NEW
├── ARCHITECTURE_DIAGRAM.md ✨ NEW (this file)
│
└── ... (other files)
```

---

## Extension 63311 Configuration (Remote Server)

```
┌─ Ubuntu Server: 165.227.88.28 ──────────────────────┐
│                                                      │
│  Asterisk Installation:                              │
│  /etc/asterisk/                                      │
│  ├── pjsip.conf                                      │
│  │   └── [63311]                                     │
│  │       type = endpoint                             │
│  │       auth = 63311                                │
│  │       aors = 63311                                │
│  │       ...                                         │
│  │                                                   │
│  │   [63311_auth]                                    │
│  │       type = auth                                 │
│  │       auth_type = userpass                        │
│  │       username = 63311                            │
│  │       password = f63311                           │
│  │                                                   │
│  │   [63311_aor]                                     │
│  │       type = aor                                  │
│  │       max_contacts = 1                            │
│  │                                                   │
│  ├── extensions.conf                                 │
│  ├── sip.conf                                        │
│  └── ...                                             │
│                                                      │
│  MySQL Database (165.227.88.28:3306):                │
│  Database: telecom_db                                │
│  └── call_histories table                            │
│      └── Migration: 2026_07_27_163830                │
│                                                      │
│  Laravel Project:                                    │
│  /var/www/didx-laravel/                              │
│  ├── routes/web.php                                  │
│  ├── app/Http/Controllers/DialerController.php       │
│  ├── resources/views/operations/dialer.blade.php     │
│  └── database/migrations/...                         │
│                                                      │
│  $ php artisan migrate                               │
│  $ php artisan cache:clear                           │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

## User's Softphone Setup

```
┌─ User's Device (Any Location) ──────────────────────┐
│                                                      │
│  Softphone Application:                              │
│  (Zoiper, MicroSIP, Linphone, etc.)                  │
│                                                      │
│  ┌────────────────────────────────────────────────┐ │
│  │ Account Settings                               │ │
│  ├────────────────────────────────────────────────┤ │
│  │ Display Name: 63311                            │ │
│  │ Username: 63311                                │ │
│  │ Domain/Server: 165.227.88.28                   │ │
│  │ Port: 5060                                     │ │
│  │ Password: f63311                               │ │
│  │ Protocol: UDP                                  │ │
│  │                                                │ │
│  │ [Register]                                     │ │
│  │                                                │ │
│  │ Status: ● Registered                           │ │
│  └────────────────────────────────────────────────┘ │
│                                                      │
│  Network:                                            │
│  User ◄──UDP SIP 5060──► 165.227.88.28              │
│        (Softphone)       (Asterisk Server)          │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

## Complete Request-Response Cycle

```
┌────────────────────────────────────────────────────────────────┐
│ 1. USER VISITS /dialer                                         │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ GET /dialer                                                   │
│   ↓ Router → DialerController@index                           │
│   ↓ Get routes from database                                  │
│   ↓ Get recent call history                                   │
│   ↓ Return dialer.blade.php view                              │
│                                                                │
│ ◄ Render: HTML + CSS + JavaScript                             │
│   • 63311 status badge → shows "Checking..."                  │
│   • Dial pad ready                                            │
│   • History table ready                                       │
│                                                                │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ 2. PAGE LOADS - JAVASCRIPT RUNS                                │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ addEventListener('DOMContentLoaded') triggers:                │
│   • refreshHistory() → fetches call history                   │
│   • checkSoftphoneStatus() → checks if 63311 online           │
│   • startAutoRefreshInterval() → every 3 seconds              │
│                                                                │
│ GET /dialer/history                                           │
│   ↓ Query: SELECT * FROM call_histories ORDER BY created_at  │
│   ↓ Map results to JSON                                       │
│   ◄ Response: [{id, caller_id, callee_number, ...}]          │
│     → renderHistory() updates table                           │
│                                                                │
│ GET /dialer/extension-status?extension=63311                  │
│   ↓ Parse: sudo asterisk -rx 'pjsip show endpoints'           │
│   ↓ Look for Contact line with "Avail"                        │
│   ◄ Response: {status: "online", contact: "192.168.1.100"}    │
│     → updateStatusBadge() shows green [● ONLINE]              │
│                                                                │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ 3. USER MAKES A CALL                                           │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ User clicks [CALL]                                            │
│   ↓ makeCall() validates inputs                               │
│   ↓ POST /dialer/make-call                                    │
│     {                                                         │
│       caller_id: "441224462024",                              │
│       callee_number: "+14155552671",                          │
│       extension: "63311"                                      │
│     }                                                         │
│                                                                │
│   ↓ DialerController@makeCall                                 │
│     • Create CallHistory row (status: pending)                │
│     • Execute: asterisk originate command                     │
│     • Return: {success: true, call_id: 123}                   │
│                                                                │
│ ◄ JavaScript updates:                                         │
│   • Show call info: From/To/Duration                          │
│   • Enable [HANGUP] button                                    │
│   • Start duration counter (00:00 → 00:01 → ...)             │
│   • Update history (new call visible)                         │
│                                                                │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ 4. DURING CALL                                                 │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ Every 1 second:                                               │
│   Duration counter increments: 00:00 → 00:01 → 00:02         │
│                                                                │
│ Every 5 seconds:                                              │
│   GET /dialer/history refreshes history table                 │
│   (call status may update: pending → ringing → connected)     │
│                                                                │
│ Every 3 seconds (if auto-refresh ON):                         │
│   GET /dialer/extension-status checks if 63311 still online   │
│                                                                │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ 5. USER HANGS UP                                               │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ User clicks [HANGUP]                                          │
│   ↓ hangupCall() with call_id                                 │
│   ↓ POST /dialer/hangup-call                                  │
│     {call_id: 123}                                            │
│                                                                │
│   ↓ DialerController@hangupCall                               │
│     • Update CallHistory (status: completed, duration: 45)    │
│     • Execute: asterisk hangup command                        │
│     • Return: {success: true, duration: 45}                   │
│                                                                │
│ ◄ JavaScript updates:                                         │
│   • Hide call info                                            │
│   • Disable [HANGUP] button                                   │
│   • Stop duration counter                                     │
│   • Clear dial number                                         │
│   • Refresh history (call shows 00:45 duration)               │
│                                                                │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ 6. CALL RECORDED IN HISTORY                                    │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ Database (call_histories table):                               │
│ ┌─────────────────────────────────────────────────────────┐  │
│ │ id │ caller_id      │ callee_number  │ direction │ ... │  │
│ ├────┼────────────────┼────────────────┼───────────┼─────┤  │
│ │123 │441224462024    │+14155552671    │ outbound  │ ... │  │
│ │    │ duration: 45   │ status: compl. │ end_time  │     │  │
│ └─────────────────────────────────────────────────────────┘  │
│                                                                │
│ UI (History table):                                            │
│ ┌─────────┬──────────┬─────────┬────────┬──────────┐         │
│ │From     │To        │Direction│Status  │Duration  │         │
│ ├─────────┼──────────┼─────────┼────────┼──────────┤         │
│ │4412...  │+1415...  │OUT      │✓ COMPL │00:45     │         │
│ └─────────┴──────────┴─────────┴────────┴──────────┘         │
│                                                                │
│ User can [↻] Redial this call                                │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

---

## Performance Timeline

```
Action                          Time       Network
──────────────────────────────  ─────────  ─────────────────
Page Load (/dialer)             ~500ms     HTTP GET
  - Initial HTML                ~100ms     (Laravel render)
  - Assets (CSS/JS)             ~200ms     (Browser cache)
  - DOM ready                   ~50ms      (Parse)

DOMContentLoaded triggers       ~50ms      (Local JS)

First History Fetch             ~150ms     AJAX GET
First Status Check              ~200ms     AJAX GET
  - Asterisk query overhead     ~100ms     (Server CLI)

Status Update (Auto-refresh)    ~200ms     Every 3s
History Refresh                 ~150ms     Every 5s

Make Call                       ~300ms     AJAX POST
  - DB insert                   ~50ms      (MySQL)
  - Asterisk originate          ~200ms     (Server CLI)
  - Response                    ~50ms      (JSON)

Duration Counter                ~1ms       Local timer

Hangup Call                     ~250ms     AJAX POST
  - DB update                   ~50ms      (MySQL)
  - Asterisk hangup             ~150ms     (Server CLI)

────────────────────────────────────────────────────────────
Total Time for Complete Call:   ~30s + actual call duration
(Page load + status check + call setup + 45s call + hangup)
```

---

## Security & Error Handling

```
┌─ Input Validation ──────────────────────────────────┐
│ ✓ Phone number regex: /^[0-9+]{1,15}$/              │
│ ✓ Extension regex: /^[0-9]{3,}$/                    │
│ ✓ CSRF token on all POST requests                   │
│ ✓ Sanitize shell commands with escapeshellarg()     │
└─────────────────────────────────────────────────────┘

┌─ Error Handling ────────────────────────────────────┐
│ ✓ Try-catch on Asterisk shell_exec()                │
│ ✓ Null-safe operators (?->) for safe property access│
│ ✓ Validation errors returned as JSON                │
│ ✓ User-friendly error messages                      │
│ ✓ Silent failure for system errors (Windows)        │
└─────────────────────────────────────────────────────┘

┌─ Database Security ─────────────────────────────────┐
│ ✓ Laravel parameterized queries (ORM)               │
│ ✓ No raw SQL except in migrations                   │
│ ✓ Foreign key constraints (optional)                │
│ ✓ Timestamps auto-managed                          │
└─────────────────────────────────────────────────────┘
```

---

## Summary

This architecture diagram shows:

1. **Frontend**: Web-based dial pad and status monitor
2. **Backend**: Laravel controller handling business logic
3. **Data Layer**: MySQL storing call history
4. **External System**: Asterisk handling actual PJSIP calls
5. **Integration**: Real-time status polling and event handling

The system is designed for:
- ✅ Real-time status monitoring
- ✅ Web-based calling without specialized software
- ✅ Complete call history tracking
- ✅ Easy softphone registration
- ✅ Scalable and maintainable architecture

