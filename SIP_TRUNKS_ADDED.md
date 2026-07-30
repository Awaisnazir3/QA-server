# ✅ SIP Trunks Tab Added

The missing "SIP Trunks" navigation tab has been added back to the dashboard.

## Changes Made

### 1. **New View File**
- `resources/views/network/sip-trunks.blade.php` - Displays registered SIP peers with status

### 2. **Updated Controller**
- Added `sipTrunks()` method to `DidRouteController`
- Retrieves SIP peer list and online status from `getSystemStats()`
- Passes data to the view

### 3. **New Route**
- `GET /sip-trunks` → `DidRouteController@sipTrunks`
- Route name: `sip-trunks`

### 4. **Updated Sidebar Navigation**
- Added SIP Trunks link to the layout sidebar
- Located under "Network" section
- Displays SIP trunk icon and status

## Features

The SIP Trunks page displays:

✅ **Registered SIP Peers Table**
- Peer name/extension
- Host/IP address
- Status (Online/Offline)
- Color-coded status badges
- SIP peer icon

✅ **Summary Statistics**
- Total Peers count
- Online Peers count
- Offline Peers count

## Access

Navigate to the application and click:
- **Sidebar** → Network → **SIP Trunks**
- Or direct URL: `http://localhost:8000/sip-trunks`

## Database Connection

The SIP Trunks page queries Asterisk via:
```bash
sudo /usr/sbin/asterisk -rx 'sip show peers'
```

Requires:
- Asterisk CLI access
- Sudo privileges for www-data user
- SIP peers configured in Asterisk

## Implementation Details

- Status determined from Asterisk output parsing
- Regular expressions match: OK, Unmonitored, REACHABLE status patterns
- Peers with OK/Unmonitored/REACHABLE status marked as "online"
- Real-time data from Asterisk (not cached)

## Files Modified/Created

```
✅ Created: resources/views/network/sip-trunks.blade.php
✅ Modified: app/Http/Controllers/DidRouteController.php (added sipTrunks method)
✅ Modified: routes/web.php (added SIP trunks route)
✅ Modified: resources/views/layouts/app.blade.php (added sidebar link)
```

## Testing

1. Access http://localhost:8000/sip-trunks
2. Should display list of registered SIP peers
3. Status badges show green (online) or red (offline)
4. Statistics cards show peer counts

---

The SIP Trunks tab is now fully integrated and matches the original design!
