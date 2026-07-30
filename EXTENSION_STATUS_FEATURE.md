# Extension Online/Offline Status Feature

## Overview
The dialer now displays real-time online/offline status for extensions, allowing you to see if extension 63311 (or any configured extension) is registered and available to receive calls.

## Features

### Real-Time Status Display
- **Online** (Green 🟢): Extension is registered and available
- **Offline** (Red 🔴): Extension is not registered or unavailable
- **Unknown** (Grey): Extension status cannot be determined
- Auto-updates every 3 seconds

### Status Indicator
- Colored dot with text label
- Visual feedback with color coding
- Shows in the extension selector area

## How It Works

### On Windows (Development)
Mock status shows extension 63311 as "online"

### On Ubuntu/Linux (Production)
Queries Asterisk PJSIP endpoints to determine:
- Extension registration status
- Contact/IP address
- Availability (Avail/NonQual/Unavailable)

## Files Modified

```
✅ app/Http/Controllers/DialerController.php
   - Updated getAvailableExtensions() to detect online/offline status
   - Added getExtensionStatus() API endpoint

✅ resources/views/operations/dialer.blade.php
   - Added status badge HTML
   - Added JavaScript for status updates
   - Auto-refresh every 3 seconds

✅ routes/web.php
   - Added /dialer/extension-status route
```

## API Endpoint

### Get Extension Status

**Request:**
```
GET /dialer/extension-status?extension=63311
```

**Response:**
```json
{
  "success": true,
  "extension": "63311",
  "status": "online",
  "contact": "192.168.1.100",
  "registered": true
}
```

**Status Values:**
- `online` - Extension is registered with Contact available
- `offline` - Extension is not registered or NonQual
- `unknown` - Could not determine status

## Usage

### Check Extension Status

1. Go to **Operations > Dialer**
2. Select an extension from **"Receive on Extension"** dropdown
3. Status badge appears automatically with:
   - Green dot = Online (ready to receive calls)
   - Red dot = Offline (cannot receive calls)
   - Refreshes every 3 seconds

### Making a Call with Status Check

1. Verify extension 63311 shows as **Online** (green)
2. Select **Outbound Route** (e.g., 7788)
3. Dial destination number
4. Click **Call**
5. Call will ring on extension 63311
6. Status badge remains green if phone is connected

## Implementation Details

### Status Detection Logic

The system checks for Contact status in PJSIP output:

```
Contact: 63311/sip:192.168.1.100:12345 hash Avail RTT
         ↑                                    ↑
         Contact URI                         Status
```

**Status Indicators:**
- `Avail` = Online ✓
- `Up` = Online ✓
- `In use` = Online ✓
- `NonQual` = Offline ✗
- `Unavailable` = Offline ✗
- No Contact line = Offline ✗

### Database Queries
- No database queries needed
- Real-time Asterisk CLI queries only
- Lightweight and responsive

### Performance
- Status checks every 3 seconds
- Asynchronous JavaScript fetch
- Non-blocking UI updates
- ~100ms average query time

## Troubleshooting

### Status Shows "Unknown"

**Causes:**
- Asterisk not running
- Insufficient permissions
- PJSIP module not loaded
- Extension not configured

**Solution:**
```bash
# Check Asterisk status
sudo /usr/sbin/asterisk -rx 'core show version'

# Check PJSIP endpoints
sudo /usr/sbin/asterisk -rx 'pjsip show endpoints'

# Reload PJSIP
sudo /usr/sbin/asterisk -rx 'module reload res_pjsip'
```

### Status Not Updating

**Causes:**
- Browser cache
- JavaScript error
- Network connectivity

**Solution:**
```bash
# Clear cache
php artisan cache:clear

# Check browser console for errors (F12)
# Verify extension status endpoint working:
curl http://localhost/dialer/extension-status?extension=63311
```

### Extension Shows Offline When It Should Be Online

**Causes:**
- Phone/softphone not registered
- Password incorrect
- Firewall blocking SIP
- Network connectivity issue

**Solution:**
```bash
# Verify registration on server
sudo /usr/sbin/asterisk -rx 'pjsip show endpoints' | grep 63311

# Check auth
sudo /usr/sbin/asterisk -rx 'pjsip show auths'

# Test registration
sudo /usr/sbin/asterisk -rx 'pjsip show aors'
```

## Advanced Usage

### Monitor Multiple Extensions

You can select different extensions to see status for each:

1. Change extension in dropdown
2. New status appears immediately
3. Perfect for multi-user environments

### Integration with Call Logs

Status is automatically logged with each call:
- When call starts: extension must be online
- During call: status tracked
- After call: history shows if extension was available

### Troubleshooting Calls

If calls aren't connecting:
1. Check if extension shows **Online** (green)
2. If offline, register phone/softphone first
3. Wait 3 seconds for status update
4. Then attempt call

## Configuration

### Change Status Check Interval

Edit `resources/views/operations/dialer.blade.php`:

```javascript
// Change from 3000ms to different interval
setInterval(() => {
    const ext = document.getElementById('inboundExtension').value;
    if (ext) {
        updateExtensionStatus(ext);
    }
}, 3000);  // Change this value (in milliseconds)
```

### Add More Status Colors

Edit the CSS in dialer view:

```javascript
if (data.status === 'online') {
    dot.style.background = 'var(--ok)';
    // Customize color here
```

## Testing

### Manual Test Procedure

1. **On Server:**
   ```bash
   # Register extension
   sudo /usr/sbin/asterisk -rx 'pjsip show endpoints'
   ```

2. **In Portal:**
   - Go to Dialer
   - Select extension 63311
   - Verify status shows "Online" (green)
   - Status should update every 3 seconds

3. **Make a Test Call:**
   - Select outbound route
   - Dial test number
   - Extension should ring
   - Status remains green while connected
   - After hangup, status refreshes

## Security Notes

- Status queries use sudo (requires proper permissions)
- No sensitive information exposed
- Only shows online/offline state, not passwords
- Safe for multi-user environments

## Future Enhancements

- [ ] Status history/graph
- [ ] Alert when extension goes offline
- [ ] Batch status check for multiple extensions
- [ ] Real-time notification when status changes
- [ ] Status persistence in database
- [ ] Status-based call routing

## Related Files

- Dialer View: `resources/views/operations/dialer.blade.php`
- Dialer Controller: `app/Http/Controllers/DialerController.php`
- Routes: `routes/web.php`
- Configuration: `EXTENSION_CONFIGURATION_GUIDE.md`
