# 🔄 Real-Time DID Status Updates

## Overview

The dashboard now automatically updates DID statuses, active calls count, and UI elements **every 3 seconds** without requiring a page reload.

## Features

### ✅ Auto-Updating Elements

1. **DID Status Badges** (pending, pass, fail, route)
   - Color-coded status indicators
   - Border colors change with status
   - Status text updates in real-time

2. **Active Calls Counter**
   - Top-left stat card shows live count
   - Updates every 3 seconds
   - Hangup button blinks when calls are active

3. **Channel Test Button**
   - Automatically enables when DID reaches "pass" status
   - Automatically disables when status changes away from "pass"
   - Lock icon shows when unavailable

4. **Hangup Button**
   - Blinks/flashes when active calls exist
   - Stops blinking when no active calls

## How It Works

### JavaScript Polling (Frontend)

```javascript
// Fetches from /api/status every 3 seconds
fetch('./api/status', { cache: "no-store" })
    .then(response => response.json())
    .then(data => {
        // Updates each DID row if status changed
        // Updates active calls count
        // Updates button states
    });
```

### API Endpoint (Backend)

**Endpoint**: `GET /api/status`

**Response**: JSON with all DID statuses
```json
{
    "1": "pending",
    "2": "pass",
    "3": "fail",
    "_active_calls": 2
}
```

**Source Code**: `app/Http/Controllers/Api/StatusController.php`

## Update Interval

- **Default**: 3 seconds (configurable)
- **Location**: `resources/views/dashboard.blade.php` line ~120
- **To Change**: Edit `setInterval(updateDIDStatuses, 3000)` (value in milliseconds)

### Adjusting Interval

```javascript
// Faster updates (1 second)
setInterval(updateDIDStatuses, 1000);

// Slower updates (5 seconds)
setInterval(updateDIDStatuses, 5000);

// Very slow (10 seconds)
setInterval(updateDIDStatuses, 10000);
```

## Technical Implementation

### Files Involved

1. **Frontend**: `resources/views/dashboard.blade.php`
   - JavaScript polling logic
   - DOM updates for status changes
   - Event listeners

2. **Backend**: `app/Http/Controllers/Api/StatusController.php`
   - Fetches current DID statuses from database
   - Queries Asterisk for active calls count
   - Returns JSON response

3. **Routes**: `routes/api.php`
   - Defines `/api/status` endpoint

### Data Flow

```
Dashboard Page (3-second interval)
    ↓
fetch('/api/status')
    ↓
StatusController@index
    ↓
CallLog::all() + Asterisk query
    ↓
JSON Response {id: status, _active_calls: count}
    ↓
JavaScript updates DOM
    ↓
User sees live updates
```

## Visual Indicators

### Status Badge Colors

| Status | Color | Meaning |
|--------|-------|---------|
| **pending** | Gray | Waiting to be processed |
| **pass** | Green | Ready for channel testing |
| **fail** | Red | Failed validation |
| **route** | Orange | Routed successfully |

### Button States

| Condition | State |
|-----------|-------|
| DID = "pass" | ✅ Enabled |
| DID = other | 🔒 Disabled |
| Active calls > 0 | 💥 Flashing |
| Active calls = 0 | 🛑 Normal |

## Performance Optimization

### What Updates

✅ Only updates if status actually changed
✅ No page reload needed
✅ No database writes during updates
✅ Uses efficient AJAX polling

### What Doesn't Update

❌ Page title (requires reload)
❌ Form data (use page refresh if needed)
❌ New DIDs (requires reload to see new row)

## Browser Compatibility

Works in all modern browsers:
- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Mobile browsers

## Testing

### Manual Testing

1. Open dashboard at `http://localhost:8000/dashboard`
2. Provision a test DID
3. Change status manually (via database or admin panel)
4. Watch status update in 3 seconds without page reload
5. Execute channel test and watch "pass" status enable/disable button

### Test DID Status Changes

```bash
# Via MySQL
mysql -h 165.227.88.28 -u admin -p

# Change a DID status
UPDATE call_logs SET status = 'pass' WHERE id = 1;
UPDATE call_logs SET status = 'fail' WHERE id = 1;
UPDATE call_logs SET status = 'pending' WHERE id = 1;
```

The dashboard will automatically reflect changes within 3 seconds.

## Troubleshooting

### Updates Not Working

**Problem**: Status doesn't update after 3 seconds

**Solutions**:
1. Check browser console for JavaScript errors (F12)
2. Verify `/api/status` endpoint works:
   ```
   http://localhost:8000/api/status
   ```
   Should return JSON with DID statuses
3. Check if Asterisk is running (for active calls count)
4. Clear browser cache (Ctrl+Shift+Delete)

### Excessive API Calls

**Problem**: Too many requests to server

**Solution**: Increase interval in dashboard.blade.php
```javascript
// Change 3000 to 5000 or 10000
setInterval(updateDIDStatuses, 5000);
```

### Status Not Reflecting Database Changes

**Problem**: UI shows old status

**Solutions**:
1. Wait 3 seconds for next update
2. Check database value:
   ```sql
   SELECT id, phone_number, status FROM call_logs;
   ```
3. Manually refresh page if needed

## Advanced Configuration

### Disable Auto-Update

Edit `resources/views/dashboard.blade.php` and remove:
```javascript
document.addEventListener('DOMContentLoaded', function() {
    updateDIDStatuses();
    autoUpdateInterval = setInterval(updateDIDStatuses, 3000);
});
```

### Custom Update Logic

Modify the `updateDIDStatuses()` function in dashboard view to:
- Add sound notifications
- Send desktop alerts
- Log status changes
- Custom styling changes

### API Caching

Disable caching with `cache: "no-store"` in fetch options (already implemented)

## Future Enhancements

Possible improvements:
- [ ] WebSocket for real-time updates (faster than polling)
- [ ] Sound notifications on status changes
- [ ] Desktop alerts for critical statuses
- [ ] Email notifications on failures
- [ ] Customizable update intervals per user
- [ ] History log of status changes
- [ ] Export status change reports

## Performance Impact

### Server Load

- 3-second polling = 20 requests/minute per user
- Lightweight JSON response (~1KB)
- No database writes during polling
- Minimal CPU impact

### Recommended Settings

- **Development**: 3 seconds (default)
- **Production (few users)**: 3-5 seconds
- **Production (many users)**: 10-15 seconds
- **High traffic**: 30+ seconds or use WebSockets

## Monitoring

Check API endpoint health:
```bash
# Monitor status endpoint response time
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8000/api/status

# Watch for errors in Laravel logs
tail -f storage/logs/laravel.log
```

---

**Status**: ✅ Implemented and Working  
**Update Interval**: 3 seconds (configurable)  
**Last Updated**: Today
