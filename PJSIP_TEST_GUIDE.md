# PJSIP Update - Quick Test Guide

## What Changed

✅ Switched from **SIP protocol** to **PJSIP protocol**  
✅ SIP Trunks page now shows **PJSIP Endpoints** instead of SIP Peers  
✅ All status indicators and colors updated  

---

## How to Test

### **Step 1: Refresh Dashboard**

1. Open: `http://localhost:8000/dashboard`
2. Press **Ctrl+F5** to force refresh (clear cache)
3. Check top stat card: Should show "SIP Peers" with count of online PJSIP endpoints

**Example:**
```
SIP Peers: 3 Online
```

### **Step 2: View PJSIP Trunks Page**

1. Click **"SIP Trunks"** in left sidebar
2. Should see table with PJSIP endpoints
3. Each row shows:
   - **Endpoint Name** (e.g., `trunk-1`, `provider-2`)
   - **IP Address** (e.g., `192.168.1.100`)
   - **Status** with color:
     - 🟢 **Green** = UP (Online)
     - 🔴 **Red** = DOWN (Offline)

**Expected table output:**
```
Endpoint Name       IP Address          Status
─────────────────────────────────────────────────
trunk-1             192.168.1.100       ✓ UP
provider-2          10.0.0.50           ✓ UP  
backup-trunk        192.168.2.200       ✗ DOWN
```

### **Step 3: Check Statistics**

At bottom of PJSIP Trunks page, should see:

```
┌─────────────────┬──────────────────┬─────────────────┐
│ Total Endpoints │ Online Endpoints │ Offline Endpoints
├─────────────────┼──────────────────┼─────────────────┤
│       3         │        2         │        1        │
└─────────────────┴──────────────────┴─────────────────┘
```

---

## Verify PJSIP Commands Work

### **On Your Asterisk Server:**

```bash
# List all PJSIP endpoints
sudo /usr/sbin/asterisk -rx 'pjsip show endpoints'

# Should show output like:
# Endpoint: trunk-1
# Transport: udp
# Status: UP
# Contact: <sip:trunk-1@192.168.1.100:5060>
```

```bash
# Get details of specific endpoint
sudo /usr/sbin/asterisk -rx 'pjsip show endpoint trunk-1'

# Should show detailed status
```

---

## Troubleshooting

### **Problem: No endpoints showing**

**Solution 1: Check PJSIP module loaded**
```bash
sudo /usr/sbin/asterisk -rx 'module show like pjsip'
# Should show multiple res_pjsip modules loaded
```

**Solution 2: Verify endpoint exists**
```bash
sudo /usr/sbin/asterisk -rx 'pjsip show endpoints | grep -i endpoint'
# Should list your endpoints
```

**Solution 3: Check permissions**
```bash
sudo -l | grep asterisk
# Should show sudo access for asterisk commands
```

### **Problem: Endpoints show but status is UNKNOWN**

**Solution: Check endpoint configuration**
```bash
# Verify endpoint is defined in pjsip.conf
sudo cat /etc/asterisk/pjsip.conf | grep -A5 'endpoint'
```

### **Problem: Page shows "No PJSIP endpoints detected"**

**Check if PJSIP CLI works:**
```bash
# Run manually
sudo /usr/sbin/asterisk -rx 'pjsip show endpoints'

# If empty, PJSIP might not be configured
# See troubleshooting above
```

---

## Expected Behavior

### **When Endpoint is UP (Online):**
- ✅ Shows in green
- ✅ Green dot with glow effect
- ✅ Status text: "UP", "REACHABLE", or "OK"

### **When Endpoint is DOWN (Offline):**
- ❌ Shows in red
- ❌ Red dot 
- ❌ Status text: "DOWN", "UNREACHABLE", or "UNAVAILABLE"

### **When Status Unknown:**
- ⚫ Shows in grey
- ⚫ Status text: "UNKNOWN"

---

## Comparison: Old vs New

| Aspect | SIP (Old) | PJSIP (New) |
|--------|-----------|------------|
| Command | `sip show peers` | `pjsip show endpoints` |
| Protocol | chan_sip | res_pjsip |
| Config file | sip.conf | pjsip.conf |
| Peer vs Endpoint | "Peers" | "Endpoints" |
| Page title | "SIP Trunks" | "PJSIP Trunks" |
| Status values | OK/REACHABLE | UP/DOWN/REACHABLE |

---

## Dashboard Changes

### **Navigation Sidebar:**
- Still shows "SIP Trunks" link (name unchanged)
- Now displays PJSIP data instead of SIP

### **Top Statistics Card:**
- Label: "SIP Peers" (unchanged for compatibility)
- Displays: Count of **online PJSIP endpoints**

### **SIP Trunks Page:**
- Title: "PJSIP Trunks Infrastructure"
- Table: Shows PJSIP endpoints, not SIP peers
- All references updated: Peers → Endpoints

---

## What's Still the Same

✅ Dashboard functionality  
✅ DID management  
✅ Channel testing  
✅ Live calls monitoring  
✅ Auto-update feature  
✅ API endpoints  

---

## Quick Checklist

- [ ] Refresh dashboard with Ctrl+F5
- [ ] Check "SIP Peers" count in stat card
- [ ] Click "SIP Trunks" page
- [ ] See PJSIP endpoints in table
- [ ] Verify endpoints show UP/DOWN status
- [ ] Check color coding (green/red)
- [ ] Run `pjsip show endpoints` on Asterisk server
- [ ] Confirm all endpoints display correctly

---

## Ready to Deploy?

Once all tests pass:

1. ✅ Clear cache one more time
2. ✅ Deploy to Ubuntu server (see DEPLOYMENT_GUIDE.md)
3. ✅ Test PJSIP page on server
4. ✅ Monitor endpoint statuses

---

## Support

If you encounter issues:

1. Check Asterisk logs:
   ```bash
   tail -f /var/log/asterisk/full
   ```

2. Check Laravel logs:
   ```bash
   tail -f /var/www/didx-laravel/storage/logs/laravel.log
   ```

3. Test PJSIP manually:
   ```bash
   sudo /usr/sbin/asterisk -rx 'pjsip show endpoints'
   ```

---

**All set! Your dashboard now uses PJSIP instead of SIP.** 🚀
