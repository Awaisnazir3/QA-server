# Softphone Dialer (63311) - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Prerequisites Checklist
- ✅ Extension 63311 already configured in Asterisk
- ✅ Password: f63311
- ✅ Server: 165.227.88.28:5060

### Step 1: Run Database Migration (2 minutes)

SSH to your Ubuntu server:
```bash
ssh admin@165.227.88.28
cd /var/www/didx-laravel
php artisan migrate
php artisan cache:clear
```

✓ **Done**: `call_histories` table is now created

### Step 2: Register Extension 63311 (1 minute)

Download a softphone app:
- **Zoiper** (best choice): https://www.zoiper.com/
- **MicroSIP**: http://www.microsip.org/
- **Linphone**: https://www.linphone.org/

Add account with these details:
```
Server: 165.227.88.28
Port: 5060
Protocol: UDP
Username: 63311
Password: f63311
```

Wait for "Registered" status in the app.

✓ **Done**: Softphone is now registered

### Step 3: Access the Dialer (1 minute)

Open your portal and click:
```
Operations > Dialer
```

Or visit:
```
http://your-portal-ip/dialer
```

### Step 4: Verify Status (1 minute)

Check the **Left Panel**:
- Should see: `Extension 63311 ● Online` (green)
- If red/offline: softphone app may not be registered

Click **[Check Now]** to refresh status manually.

### Step 5: Make a Test Call (∞ minutes)

1. Select **Caller ID** from dropdown (e.g., "441224462024")
2. Enter a **phone number** using dial pad or by typing
3. Click **[CALL]** button
4. Watch the duration counter tick up
5. Click **[HANGUP]** to end
6. Call appears in history below

✓ **Done**: You're using the softphone dialer!

---

## 🎯 Key Features

| Feature | How to Use |
|---------|-----------|
| **Check Status** | Left panel shows green (online) or red (offline) |
| **Auto-Refresh** | Toggle ☑ in left panel (checks every 3 seconds) |
| **Make Call** | Select route → dial number → click [CALL] |
| **Hang Up** | Click [HANGUP] button (enabled during call) |
| **View History** | Bottom table shows all calls with details |
| **Filter History** | Click [All], [Outbound], or [Inbound] buttons |
| **Redial** | Click [↻] button next to any history entry |

---

## 📞 What You Can Do

✅ **Make outbound calls**
- Select which phone number to show as caller ID
- Dial any number using the web interface
- Call appears immediately in history

✅ **Monitor extension status**
- See if softphone is online/offline in real time
- Auto-refresh every 3 seconds
- Manual refresh button

✅ **Track call history**
- Every call automatically logged
- Filter by outbound/inbound
- See duration and timestamp
- Redial previous calls

✅ **Use from anywhere**
- Web browser on desktop, tablet, or phone
- No special software needed (besides the registered softphone)
- Works on any internet connection

---

## 🔧 Troubleshooting

### Status shows "Checking..." indefinitely
**Fix**: Click [Check Now] manually. If still stuck, softphone may not be registered.

### Status shows "Offline" (red)
**Fix**: 
1. Open your softphone app
2. Check if it says "Registered"
3. If not, verify username (63311), password (f63311), server (165.227.88.28)
4. Refresh page

### "No active route available" error
**Fix**:
1. Go to Dashboard
2. Mark at least one DID as a route (status: "pass")
3. Return to Dialer

### Call history table empty
**Fix**: This is normal on first use. Make a test call, then history populates.

### Can't see call duration counting
**Fix**: Make sure you clicked [CALL] button and got successful response.

---

## 💡 Pro Tips

1. **Always toggle auto-refresh ON** → See real-time status updates
2. **Select caller ID before dialing** → Otherwise you'll get an error
3. **Use dial pad buttons** → Faster than typing numbers
4. **Click Redial on frequent numbers** → Saves time
5. **Check status before making important calls** → Ensure softphone is online

---

## 📊 Expected Behavior

### When Everything Works:
```
Extension 63311
● Online

After clicking [CALL]:
From: 441224462024
To: +14155552671
Duration: 00:01 → 00:02 → 00:03 (counting up)

After clicking [HANGUP]:
History table shows:
441224462024 | +14155552671 | OUT | ✓ COMPLETED | 00:45
```

### Common Scenarios:

**Scenario 1: Softphone Offline**
```
Extension 63311
● Offline  (red badge)

You can: View history, redial calls
You cannot: Make new calls (softphone won't receive)
```

**Scenario 2: Call in Progress**
```
[CALL] button disabled (grayed out)
[HANGUP] button enabled (bright red)
Duration: 00:00 → 00:01 → ...
Status: Ringing or Connected
```

**Scenario 3: Failed Call**
```
History shows:
441224462024 | +14155552671 | OUT | ✗ FAILED | 00:00

Reasons: Invalid number, network error, Asterisk issue
Check Asterisk logs for details
```

---

## 🆘 Need Help?

### Read These Docs:
1. **SOFTPHONE_DIALER_SETUP.md** - Full setup guide
2. **DIALER_UI_REFERENCE.md** - UI explanations with diagrams
3. **DIALER_CHANGES_SUMMARY.md** - Technical details
4. **ARCHITECTURE_DIAGRAM.md** - System architecture

### Check These:
- Is extension 63311 registered? (Check softphone app)
- Is Asterisk running? (SSH and check: `sudo systemctl status asterisk`)
- Can you see the dialer page? (No errors in browser console?)
- Did you run the migration? (`php artisan migrate`)

### Last Resort:
SSH to server and run:
```bash
# Check Asterisk logs
sudo tail -f /var/log/asterisk/messages

# Check if 63311 is registered
sudo /usr/sbin/asterisk -rx "pjsip show endpoints" | grep 63311

# Check Laravel logs
tail -f /var/www/didx-laravel/storage/logs/laravel.log
```

---

## ✨ Summary

The **Softphone Dialer** is your web-based phone interface for extension 63311.

**With 3 simple steps**, you can:**
1. Register softphone (1 minute)
2. Run migration (1 minute)
3. Access dialer and make calls (1 minute)

**Total setup time: ~5 minutes**

**You're ready to go! 🎉**

---

## Next Steps

1. ✅ Run migration: `php artisan migrate`
2. ✅ Register softphone with credentials
3. ✅ Visit `/dialer` and make a test call
4. ✅ Check call history to verify it was logged
5. ✅ You're all set!

---

**Questions? Check the documentation files or contact your admin!**

