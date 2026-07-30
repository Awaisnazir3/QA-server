# Softphone Dialer UI Reference Guide

## Layout Overview

```
┌─────────────────────────────────────────────────────────────┐
│   DIDX — Softphone Dialer (Extension 63311)                 │
├─────────────────────────────────────────────────────────────┤
│
│  [LEFT PANEL]                    [RIGHT PANEL]
│  ├─ Softphone Status             ├─ Dial Pad
│  └─ Configuration                └─ Call Controls
│
│  [BOTTOM]
│  └─ Call History Table (All/Outbound/Inbound)
│
└─────────────────────────────────────────────────────────────┘
```

---

## LEFT PANEL: Softphone Status

### Layout:
```
┌─────────────────────────────────┐
│ 📡 Softphone Status             │
├─────────────────────────────────┤
│                                 │
│  Extension 63311                │  
│  Softphone: Zoiper / MicroSIP   │
│            [● ONLINE]           │  ← GREEN when registered
│                                 │
├─────────────────────────────────┤
│  Configuration:                 │
│  Server: 165.227.88.28          │
│  Port: 5060 (UDP)               │
│  Extension: 63311               │
│  Password: f63311               │
├─────────────────────────────────┤
│                                 │
│  ☑ Auto-refresh status (3s)     │
│                                 │
│  [SYNC] [INFO]                  │
│                                 │
└─────────────────────────────────┘
```

### Status Indicators:

```
● ONLINE  (Green)
  ✓ Extension 63311 is registered
  ✓ Softphone is connected and available
  ✓ Can receive incoming calls

● OFFLINE (Red)
  ✗ Extension 63311 is not registered
  ✗ Softphone is disconnected or offline
  ✗ Cannot receive incoming calls

⚪ CHECKING (Gray)
  ⟳ System is querying Asterisk
  ⟳ Status update in progress
```

---

## RIGHT PANEL: Dial Pad & Caller ID

### Layout:
```
┌─────────────────────────────────┐
│ 📞 Dial Pad                     │
├─────────────────────────────────┤
│                                 │
│  Caller ID (Outbound Route):    │
│  [▼ 441224462024 (pass)]        │  ← Select route for caller ID
│  Select phone number to show    │
│  as caller ID for outbound      │
│                                 │
│  Dial Number:                   │
│  [+1234567890_____]             │  ← Number to dial
│                                 │
│  ┌─────┬─────┬─────┐            │
│  │  1  │  2  │  3  │            │
│  ├─────┼─────┼─────┤            │
│  │  4  │  5  │  6  │            │  ← Touchpad
│  ├─────┼─────┼─────┤            │
│  │  7  │  8  │  9  │            │
│  ├─────┼─────┼─────┤            │
│  │  *  │  0  │  #  │            │
│  └─────┴─────┴─────┘            │
│                                 │
│  ┌──────────┬──────────┐        │
│  │   CALL   │  HANGUP  │        │  ← Call control
│  └──────────┴──────────┘        │
│                                 │
│  [← BACKSPACE] [CLEAR]          │  ← Input control
│                                 │
└─────────────────────────────────┘
```

### Workflow: Making a Call

```
Step 1: Select Caller ID
  └─ Click dropdown
  └─ Choose outbound route (e.g., "441224462024")
  └─ This phone number will show as your caller ID

Step 2: Enter Dial Number
  └─ Type manually OR
  └─ Click dial pad buttons to add digits
  └─ Example: +14155552671

Step 3: Initiate Call
  └─ Click [CALL] button
  └─ Call history receives "pending" status
  └─ Duration timer starts counting

Step 4: During Call
  └─ Status shows call is "ringing" then "connected"
  └─ Duration updates in real time: 00:01, 00:02, etc.
  └─ Can still see call in history while active

Step 5: End Call
  └─ Click [HANGUP] button
  └─ Call status changes to "completed"
  └─ Final duration recorded in history
  └─ Example: 00:45 (45 seconds)
```

---

## BOTTOM: Call History Table

### Layout:
```
┌────────────────────────────────────────────────────────────┐
│ 📞 Call History     [All] [Outbound] [Inbound]             │
├─────┬────────┬────────┬─────────┬──────────┬────────┬─────┤
│From │   To   │ Direction│ Status │Duration │ Time   │Redial│
├─────┼────────┼────────┼─────────┼──────────┼────────┼─────┤
│4412 │ +1415  │ OUT    │✓ COMPL  │ 00:45    │14:32   │ ↻   │
│     │ 552671 │        │         │          │        │     │
├─────┼────────┼────────┼─────────┼──────────┼────────┼─────┤
│4412 │ +1202  │ OUT    │✗ FAIL   │ 00:00    │14:15   │ ↻   │
│     │ 123456 │        │         │          │        │     │
├─────┼────────┼────────┼─────────┼──────────┼────────┼─────┤
│6331 │ +4412  │ IN     │✓ COMPL  │ 02:15    │13:58   │ ↻   │
│1    │ 456789 │        │         │          │        │     │
└─────┴────────┴────────┴─────────┴──────────┴────────┴─────┘
```

### Column Descriptions:

| Column | Meaning | Example |
|--------|---------|---------|
| **From** | Caller ID (who initiated) | 441224462024 |
| **To** | Number dialed (destination) | +14155552671 |
| **Direction** | OUT = outbound, IN = inbound | OUT / IN |
| **Status** | Call outcome (colored badge) | ✓ COMPLETED / ✗ FAILED |
| **Duration** | Call length in HH:MM:SS format | 00:45 (45 seconds) |
| **Time** | When call occurred | 14:32 (2:32 PM) |
| **Action** | Redial button | ↻ (repeats call) |

### Status Colors:

```
✓ COMPLETED (Green)
  → Call connected and ended normally
  → Duration shows how long the call lasted

✗ FAILED (Red)
  → Call didn't connect or was rejected
  → Duration usually 0

⏳ PENDING (Yellow/Amber)
  → Call is being initiated
  → Waiting for connection

🔄 RINGING (Yellow/Amber)
  → Call is ringing on the other end
  → Not yet connected

📞 CONNECTED (Green)
  → Call is active right now
  → Duration counting
```

### Filtering:

```
[All]      → Show all calls (inbound + outbound)
[Outbound] → Show only calls I made (OUT direction)
[Inbound]  → Show only incoming calls (IN direction)
```

Click a filter button to update the history table immediately.

---

## Real-Time Updates

### Automatic Refresh:
- **Softphone Status**: Every 3 seconds (if auto-refresh enabled)
- **Call History**: Every 5 seconds
- **Call Duration**: Every 1 second (during active call)

### Manual Refresh:
- Click **[Check Now]** button to check status immediately
- Click filter buttons to refresh history

---

## Keyboard Shortcuts (in Call)

When the dial number input is focused:

| Input | Action |
|-------|--------|
| 0-9 | Add digit to dial pad |
| `*` | Add asterisk |
| `#` | Add hash |
| Backspace | Delete last digit |
| Enter | (Future: could trigger Call) |

---

## Color Scheme

```
Primary Colors:
  ✓ Green (#2ecc71)  → Online, Connected, Completed
  ✗ Red (#e74c3c)    → Offline, Failed, Hangup
  ⏳ Yellow (#f39c12) → Pending, Ringing, In progress

Neutral:
  Gray (#95a5a6)     → Checking, Unknown status
  White              → Text on colored backgrounds
```

---

## Examples

### Example 1: Checking Softphone Status

```
User goes to Dialer page
  ↓
JavaScript runs: checkSoftphoneStatus()
  ↓
Queries: GET /dialer/extension-status?extension=63311
  ↓
Server runs: sudo asterisk -rx 'pjsip show endpoints'
  ↓
Parses response and checks Contact line for "Avail"
  ↓
Returns: { status: "online" }
  ↓
UI updates: [● ONLINE] badge displays (green)
```

### Example 2: Making an Outbound Call

```
User selects caller ID: "441224462024"
User dials: "+14155552671"
User clicks [CALL]
  ↓
JavaScript submits: POST /dialer/make-call
  {
    caller_id: "441224462024",
    callee_number: "+14155552671",
    extension: "63311"
  }
  ↓
Server creates CallHistory record:
  caller_id: 441224462024
  callee_number: +14155552671
  direction: outbound
  status: pending
  start_time: now()
  ↓
Server runs Asterisk command to originate call
  ↓
Returns: { success: true, call_id: 123 }
  ↓
UI updates:
  - Shows call info (From/To/Duration)
  - Enables [HANGUP] button
  - Starts duration counter
  - Refreshes history (shows new call)
```

### Example 3: Redial Previous Call

```
User sees call in history: 441224462024 → +14155552671
User clicks [↻] (Redial) button
  ↓
JavaScript function: redialCall(callerId, calleeNumber)
  ↓
Fills dial pad:
  - Caller ID: 441224462024
  - Dial Number: +14155552671
  ↓
User clicks [CALL] to execute redial
  ↓
Same flow as Example 2 (new call created)
```

---

## API Responses

### Successful Call Initiation:
```json
{
  "success": true,
  "call_id": 42,
  "caller_id": "441224462024",
  "callee_number": "+14155552671",
  "message": "Call initiated"
}
```

### Extension Status Check:
```json
{
  "success": true,
  "extension": "63311",
  "status": "online",
  "contact": "192.168.1.100:51234",
  "registered": true
}
```

### Call History Fetch:
```json
{
  "success": true,
  "count": 15,
  "history": [
    {
      "id": 1,
      "caller_id": "441224462024",
      "callee_number": "+14155552671",
      "direction": "outbound",
      "status": "completed",
      "duration": 45,
      "start_time": "2026-07-24 14:32:00"
    },
    ...
  ]
}
```

---

## User Tips

### ✅ Do:
- Keep auto-refresh enabled to see real-time status
- Check softphone is registered before making calls
- Select the correct caller ID before dialing
- Use redial for frequently called numbers
- Filter history to find specific calls quickly

### ❌ Don't:
- Disable auto-refresh unless troubleshooting
- Make calls without selecting a caller ID
- Forget to click [HANGUP] (though system auto-ends after timeout)
- Assume offline status without checking network
- Refresh the page during an active call (data lost)

---

## Troubleshooting Quick Reference

| Problem | Check | Fix |
|---------|-------|-----|
| Status shows "Checking..." for 5+ seconds | Is Asterisk running? | SSH to server: `sudo systemctl status asterisk` |
| Status is Offline but softphone is open | Is softphone registered? | Check softphone app settings, re-register |
| No routes in caller ID dropdown | Are routes configured? | Go to Dashboard, mark DIDs as routes |
| Call doesn't dial | Is caller ID selected? | Choose a route from dropdown first |
| Call history empty | Has database migrated? | Run: `php artisan migrate` on server |
| Duration not counting | Is call actually connected? | Check Asterisk logs for errors |

---

## Summary

The Softphone Dialer provides a **focused, easy-to-use interface** for controlling extension 63311:

- **Left**: Know if your softphone is online/offline at a glance
- **Right**: Make calls with a familiar dial pad interface
- **Bottom**: Review all calls with filtering and redial options
- **Auto-refresh**: See updates without page reloads

Perfect for desk phone replacement, remote calling, and call tracking!

