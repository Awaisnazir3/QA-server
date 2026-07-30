# Dialer Feature Documentation

## Overview
The Dialer feature allows users to make and receive calls directly from the DIDX portal with full call history tracking, call status management, and caller ID routing.

## Features

### 1. **Make Calls**
- Select outbound route (Caller ID) from active DID routes
- Dial phone numbers using the interactive dialer pad (1-9, 0, *, #)
- Real-time call status display (Pending → Ringing → Connected → Completed)
- Call duration counter
- Immediate hangup capability

### 2. **Call History Tracking**
- Automatically logs all calls (outbound and inbound)
- Stores: Caller ID, Called Number, Direction, Status, Duration, Timestamps
- Filter by direction (All, Outbound, Inbound)
- Redial functionality from history
- Call notes/remarks for each call

### 3. **Call Management**
- Make/receive calls via Asterisk Originate
- Caller ID routing based on DID routes
- Call status updates (pending, ringing, connected, completed, failed)
- Hangup single calls or all active calls
- Call duration tracking

### 4. **Active Calls Monitor**
- Real-time display of active calls
- Shows caller ID, called number, and call status
- Active call count badge
- Auto-refresh every 5 seconds

## File Structure

```
app/
├── Http/Controllers/
│   └── DialerController.php          # Main controller for dialer operations
├── Models/
│   └── CallHistory.php               # Call history model
routes/
├── web.php                            # Dialer routes
resources/views/
├── operations/
│   └── dialer.blade.php              # Dialer UI template
└── layouts/
    └── app.blade.php                 # Updated sidebar with Dialer menu
database/
└── migrations/
    └── *_create_call_histories_table.php  # Call history table migration
```

## Database Schema

### call_histories Table
```
- id (PK)
- caller_id (string) - Calling party number
- callee_number (string) - Called party number
- direction (string) - 'inbound' or 'outbound'
- status (string) - pending, ringing, connected, completed, failed
- route_id (FK) - Associated DID route
- duration (integer) - Call duration in seconds
- start_time (timestamp)
- end_time (timestamp)
- recording_url (string, nullable)
- notes (text, nullable)
- timestamps (created_at, updated_at)
```

## Routes

| Method | Route | Action | Name |
|--------|-------|--------|------|
| GET | /dialer | Show dialer interface | dialer.index |
| POST | /dialer/make-call | Initiate outbound call | dialer.make-call |
| POST | /dialer/hangup-call | End active call | dialer.hangup-call |
| GET | /dialer/history | Fetch call history | dialer.history |
| POST | /dialer/call-status | Update call status | dialer.call-status |
| POST | /dialer/notes | Add call notes | dialer.notes |

## API Endpoints

### Make a Call
**Request:**
```
POST /dialer/make-call
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
  "caller_id": "+12035551234",
  "callee_number": "+16175552345",
  "route_id": 1  // optional
}
```

**Response:**
```json
{
  "success": true,
  "message": "Call initiated",
  "call_id": 42,
  "caller_id": "+12035551234",
  "callee_number": "+16175552345"
}
```

### End a Call
**Request:**
```
POST /dialer/hangup-call
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
  "call_id": 42
}
```

**Response:**
```json
{
  "success": true,
  "message": "Call ended",
  "duration": 120
}
```

### Get Call History
**Request:**
```
GET /dialer/history?direction=outbound&limit=50
```

**Response:**
```json
{
  "success": true,
  "count": 5,
  "history": [
    {
      "id": 42,
      "caller_id": "+12035551234",
      "callee_number": "+16175552345",
      "direction": "outbound",
      "status": "completed",
      "duration": 120,
      "start_time": "2026-07-27 10:30:00",
      "route_id": 1
    }
  ]
}
```

### Update Call Status
**Request:**
```
POST /dialer/call-status
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
  "call_id": 42,
  "status": "connected"
}
```

### Add Call Notes
**Request:**
```
POST /dialer/notes
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
  "call_id": 42,
  "notes": "Customer discussed account balance"
}
```

## Usage Guide

### Making a Call
1. Navigate to **Operations > Dialer**
2. Select a **Caller ID** from the dropdown (must have PASS status)
3. Enter phone number or use the **dialer pad**
4. Click **Call** to initiate
5. Monitor real-time call status
6. Click **Hangup** when done

### Viewing Call History
- All calls automatically appear in the **Call History** table
- Filter by **Direction** (All, Outbound, Inbound)
- Click **Redial** to make another call to the same number
- Call details include: From, To, Direction, Status, Duration, Time

### Active Calls Monitor
- Shows currently active calls in the right panel
- Updates automatically every 5 seconds
- Displays active call count in badge

## Asterisk Integration

### Command Format
The dialer uses Asterisk's `channel originate` command:

```
asterisk -rx 'channel originate PJSIP/{did}@outbound application bridge SIP/{caller_id}'
```

### On Windows (Development)
- Mock data is returned (no actual calls)
- Call history is still recorded
- Status updates are simulated

### On Linux/Ubuntu (Production)
- Real Asterisk commands execute
- Requires sudo permissions for asterisk user
- Real call routing based on defined routes

## Security Considerations

1. **Input Validation**
   - Phone numbers validated with regex: `^[0-9+]{1,15}$`
   - All inputs sanitized before use

2. **Authorization**
   - Only active routes (status = 'pass') can be used
   - Caller ID must match available DID routes

3. **Command Escaping**
   - All Asterisk commands use `escapeshellarg()` to prevent injection

## Troubleshooting

### Calls Not Going Through
- Verify route has `status = 'pass'`
- Check Asterisk is running: `asterisk -rx 'core show channels'`
- Verify PJSIP endpoints are online: `asterisk -rx 'pjsip show endpoints'`

### Call History Not Recording
- Check `call_histories` table exists: `php artisan migrate`
- Verify CallHistory model is correctly configured
- Check database connection in `.env`

### Call Duration Not Updating
- Ensure `start_time` is set when call connects
- Verify `end_time` updates on hangup

## Future Enhancements

1. Call recording integration
2. IVR menu system
3. Conference calling
4. Call transfer capability
5. Voicemail integration
6. CRM integration for automatic dialing
7. Real-time analytics dashboard
8. Call quality metrics (MOS, jitter, latency)

## Testing Checklist

- [x] Routes registered correctly
- [x] Controller methods exist
- [x] Database model configured
- [x] Blade template renders
- [x] Dialer menu in sidebar
- [x] Make call endpoint works
- [x] Hangup endpoint works
- [x] History endpoint works
- [x] Call status updates work
- [x] Notes recording works
- [ ] End-to-end calling (requires Asterisk)
- [ ] Call recording (if enabled)
- [ ] Multi-user concurrent calls

## Support

For issues or feature requests, contact the development team.
