# PJSIP Migration Update

## Changes Made

You've successfully updated the project from SIP to PJSIP. Here are the changes made:

### 1. **Controller Updated** (`app/Http/Controllers/DidRouteController.php`)

**Changed from:**
```bash
sip show peers
# Returns: Name/username, IP, Status
```

**Changed to:**
```bash
pjsip show endpoints
# Returns: Endpoint info with status and contact info
```

**What's improved:**
- ✅ Now uses `pjsip show endpoints` command
- ✅ Extracts PJSIP endpoint names, statuses, and IP addresses
- ✅ Parses Contact information to get IP addresses
- ✅ Detects online status: UP, DOWN, REACHABLE, UNREACHABLE
- ✅ Better error handling for PJSIP output parsing

### 2. **View Updated** (`resources/views/network/sip-trunks.blade.php`)

**UI Changes:**
- Title: "SIP Trunks" → "PJSIP Trunks Infrastructure"
- Labels: "Registered SIP Peers" → "Registered PJSIP Endpoints"
- Column headers: "Peer / Extension" → "Endpoint / Extension"
- Statistics: "Total Peers" → "Total Endpoints"
- Error message: References `pjsip show endpoints` instead of `sip show peers`

### 3. **Status Display**

**PJSIP Statuses shown:**
- 🟢 **UP** - Endpoint is online and ready
- 🟢 **REACHABLE** - Endpoint is reachable
- 🟢 **OK** - Endpoint is operational
- 🔴 **DOWN** - Endpoint is offline
- 🔴 **UNREACHABLE** - Endpoint cannot be reached
- 🔴 **UNAVAILABLE** - Endpoint is unavailable
- ⚫ **UNKNOWN** - Status cannot be determined

### 4. **Color Coding**
- 🟢 **Green** = Online/UP endpoints
- 🔴 **Red** = Offline/DOWN endpoints

---

## How It Works Now

### **Dashboard Stats Bar**
- "SIP Peers" → Shows count of online PJSIP endpoints
- Updates every time you visit the page

### **PJSIP Trunks Page**
1. Queries Asterisk: `pjsip show endpoints`
2. Parses each endpoint:
   - Endpoint name (e.g., `trunk-1`, `provider-2`)
   - Status (UP, DOWN, etc.)
   - IP address (from Contact info)
3. Marks as online if status contains: UP, REACHABLE, OK
4. Displays in table with color coding

### **Real-time Updates**
- API endpoint: `/api/status` still works for DID auto-updates
- PJSIP endpoints shown on "SIP Trunks" page
- Refreshes with page load

---

## Testing

### **Verify PJSIP is working:**

```bash
# SSH into your Asterisk server
sudo /usr/sbin/asterisk -rx 'pjsip show endpoints'

# Should show output like:
# Endpoint: trunk-1 (trunk)
# Endpoint: provider-2 (user)
# ...
```

### **Check endpoint details:**

```bash
# Get details of specific endpoint
sudo /usr/sbin/asterisk -rx 'pjsip show endpoint <endpoint-name>'

# Example:
sudo /usr/sbin/asterisk -rx 'pjsip show endpoint trunk-1'
```

---

## Dashboard Features Now Using PJSIP

✅ **Statistics:**
- Online PJSIP endpoints count
- Displays in top stat card: "SIP Peers" 

✅ **PJSIP Trunks Page:**
- Table of all registered PJSIP endpoints
- Shows endpoint name, IP, and status
- Color-coded (green = online, red = offline)
- Total endpoints count
- Online/offline breakdown

✅ **Status Indicators:**
- Green dot 🟢 for UP endpoints
- Red dot 🔴 for DOWN endpoints
- Glow effect on online endpoints

---

## Compatibility

**Before (SIP Protocol):**
- Used `sip.conf`
- Command: `sip show peers`
- Module: `chan_sip`

**After (PJSIP Protocol):**
- Uses `pjsip.conf`
- Command: `pjsip show endpoints`
- Module: `res_pjsip`

---

## Next Steps

1. ✅ Clear Laravel cache:
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```

2. ✅ Refresh dashboard in browser

3. ✅ Go to "SIP Trunks" page to see PJSIP endpoints

4. ✅ Verify endpoints show as "UP" or "DOWN"

---

## Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/DidRouteController.php` | Updated `getSystemStats()` to use PJSIP commands |
| `resources/views/network/sip-trunks.blade.php` | Updated labels and references from SIP to PJSIP |

---

## Rollback (If Needed)

To revert to SIP:
1. Restore the backup of `DidRouteController.php`
2. Restore the backup of `sip-trunks.blade.php`
3. Clear cache: `php artisan cache:clear`

---

## Support

If PJSIP endpoints don't show:

1. **Check Asterisk has PJSIP module:**
   ```bash
   sudo /usr/sbin/asterisk -rx 'module show like pjsip'
   ```

2. **Check sudo permissions:**
   ```bash
   sudo -l | grep asterisk
   ```

3. **Test PJSIP command manually:**
   ```bash
   sudo /usr/sbin/asterisk -rx 'pjsip show endpoints'
   ```

4. **Check logs:**
   ```bash
   tail -f /var/log/asterisk/full
   ```

---

## Summary

✅ PJSIP integration complete!
✅ Dashboard now shows PJSIP endpoints
✅ Status colors: Green (UP) = Online, Red (DOWN) = Offline
✅ Real-time endpoint monitoring
✅ Ready for production deployment
