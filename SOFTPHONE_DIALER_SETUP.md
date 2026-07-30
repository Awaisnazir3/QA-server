# Softphone Dialer Setup Guide (Extension 63311)

## Overview

The **Softphone Dialer** is a web-based control panel for managing extension **63311**, which is a PJSIP softphone endpoint already configured on your Asterisk server.

### Key Features:
- **Status Monitor**: Shows if extension 63311 is online (registered) or offline (not registered)
- **Dial Pad**: Web-based phone keypad to enter numbers
- **Caller ID Selection**: Choose outbound routes (DIDs) to use as caller ID
- **Call History**: Track all calls made and received with status, duration, and timestamps
- **Auto-Refresh**: Status updates every 3 seconds if auto-refresh is enabled

---

## Prerequisites

### Extension 63311 Configuration (Already Done)
- Extension: **63311**
- Password: **f63311**
- Server: **165.227.88.28** (Ubuntu)
- Port: **5060** (UDP)
- Type: **PJSIP Softphone**
- Status: Already configured in `/etc/asterisk/pjsip.conf`

### Database Setup

The dialer requires a `call_histories` table. This migration needs to be run on your remote database server:

```bash
# SSH into your Ubuntu server
ssh admin@165.227.88.28

# Navigate to the Laravel project
cd /path/to/didx-laravel

# Run the migration (if you haven't already)
php artisan migrate

# Verify the table was created
mysql -u admin -p12343211 telecom_db -e "SHOW TABLES;" | grep call_histories
```

If the migration hasn't run yet, the table will be missing. To fix it manually on the remote DB:

```sql
-- Run on MySQL (165.227.88.28:3306)
CREATE TABLE IF NOT EXISTS `call_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `caller_id` varchar(255) NOT NULL,
  `callee_number` varchar(255) NOT NULL,
  `direction` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `route_id` bigint unsigned DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `recording_url` varchar(255) DEFAULT NULL,
  `notes` longtext,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## File Changes

### Updated Files:

1. **resources/views/operations/dialer.blade.php** (REWRITTEN)
   - New softphone-focused UI with two-column layout
   - Left: Extension 63311 status with auto-refresh toggle
   - Right: Dial pad, caller ID selector, call controls
   - Bottom: Call history table with filtering

2. **app/Http/Controllers/DialerController.php** (UPDATED)
   - `index()`: Now only passes routes (not extensions) to the view
   - `makeCall()`: Accepts extension parameter (defaults to '63311')
   - `getExtensionStatus()`: Checks online/offline status by parsing PJSIP endpoints
   - All other methods unchanged (getHistory, hangupCall, etc.)

3. **routes/web.php** (NO CHANGES NEEDED)
   - All dialer routes already defined:
     - `GET /dialer` → Show dialer interface
     - `POST /dialer/make-call` → Initiate outbound call
     - `POST /dialer/hangup-call` → End call
     - `GET /dialer/history` → Get call history
     - `GET /dialer/extension-status` → Check extension online/offline status

---

## How the Dialer Works

### 1. Check Softphone Status
- **Left Panel**: Shows extension 63311 status (green = Online, red = Offline)
- **Auto-Refresh**: Checks status every 3 seconds (if enabled)
- **Manual Refresh**: Click "Check Now" button anytime

The status is determined by parsing `pjsip show endpoints` output:
- **Online**: Contact line has "Avail" or "In use"
- **Offline**: No Contact line, or "NonQual"/"Unavailable"

### 2. Make an Outbound Call
1. Select a **Caller ID** (outbound route from the dropdown)
2. Enter a **phone number** using the dial pad or by typing
3. Click **Call** button
4. Monitor call duration in the status display
5. Click **Hangup** to end the call

### 3. View Call History
- Automatically refreshed every 5 seconds
- Filter by: All, Outbound, Inbound
- Shows: From/To, Direction, Status, Duration, Timestamp
- **Redial** button to quickly repeat a call

---

## Registering the Softphone (User Guide)

### On Ubuntu Server (Verify Configuration):
```bash
# SSH to server
ssh admin@165.227.88.28

# Check if extension 63311 is configured
sudo /usr/sbin/asterisk -rx "pjsip show endpoints" | grep 63311

# Expected output (if registered):
# Endpoint / Extension    Host / IP                 Port  Status / Status
# 63311                  192.168.1.100:12345        5060  Avail
```

### On Your Softphone (Zoiper, MicroSIP, Linphone):

**Account Settings:**
- **Display Name**: 63311
- **Username**: 63311
- **Domain / Server**: 165.227.88.28
- **Port**: 5060
- **Password**: f63311
- **Protocol**: UDP (or TLS/TCP if required)

**After registration**, the web portal will show:
```
Extension 63311
● Online
```

---

## JavaScript Functions (in dialer.blade.php)

| Function | Purpose |
|----------|---------|
| `makeCall()` | Initiate outbound call with caller ID and phone number |
| `hangupCall()` | Terminate active call |
| `checkSoftphoneStatus()` | Poll Asterisk for extension 63311 status |
| `updateStatusBadge(status)` | Update green/red indicator |
| `refreshHistory()` | Fetch and display recent calls |
| `filterHistory(direction)` | Filter by Inbound/Outbound/All |
| `redialCall(callerId, calleeNumber)` | Re-dial a previous call |
| `startDurationTimer()` | Count call duration in real time |

---

## Troubleshooting

### Issue: "Checking..." Status Never Updates
**Cause**: Asterisk command failed or extension not registered  
**Fix**: 
- Verify extension 63311 is registered on the server
- Run: `sudo /usr/sbin/asterisk -rx "pjsip show endpoints"`
- Check softphone is connected and logged in

### Issue: Call History Table Empty
**Cause**: `call_histories` table doesn't exist in database  
**Fix**: 
- Run migration: `php artisan migrate`
- Or manually create the table (SQL above)

### Issue: "No active route available" When Making Call
**Cause**: No outbound routes (DIDs) marked as "pass" status  
**Fix**:
- Go to Dashboard
- Mark at least one DID route as active
- Return to Dialer

### Issue: Status Updates Lag (5+ seconds)
**Cause**: Auto-refresh interval set too high or query is slow  
**Fix**: 
- Ensure MySQL server (165.227.88.28) is responsive
- Check network latency
- Status checks every 3s if auto-refresh is ON

---

## API Endpoints

All dialer operations use these endpoints:

### GET /dialer
Display the dialer interface

### POST /dialer/make-call
```json
{
  "caller_id": "441224462024",
  "callee_number": "+1234567890",
  "extension": "63311"
}
```
**Response**: `{ "success": true, "call_id": 1 }`

### POST /dialer/hangup-call
```json
{
  "call_id": 1
}
```
**Response**: `{ "success": true, "duration": 45 }`

### GET /dialer/history?direction=outbound
Filter call history by direction (outbound/inbound/all)

### GET /dialer/extension-status?extension=63311
Check if extension is online or offline
**Response**: `{ "success": true, "status": "online", "contact": "192.168.1.100" }`

---

## Next Steps

1. **SSH to Ubuntu server** and run: `php artisan migrate`
2. **Clear Laravel cache**: `php artisan cache:clear`
3. **Register softphone** (63311) on your phone/app using credentials above
4. **Go to Operations > Dialer** in the web portal
5. **Test**: Check if 63311 shows Online (green), then try making a call

---

## Files Modified

```
c:\xampp\htdocs\didx-laravel\
├── resources\
│   └── views\
│       └── operations\
│           └── dialer.blade.php (REWRITTEN)
├── app\
│   └── Http\
│       └── Controllers\
│           └── DialerController.php (UPDATED - index() method)
└── database\
    └── migrations\
        └── 2026_07_27_163830_create_call_histories_table.php (ALREADY EXISTS)
```

---

## Summary

✅ **Softphone Dialer is ready to use!**

The UI now focuses on extension 63311 as a softphone. To complete setup:
1. Run the migration on remote DB
2. Register extension 63311 on your softphone app
3. Access the dialer via Operations > Dialer menu

All code is production-ready and handles both Windows (dev) and Linux (prod) environments correctly.
