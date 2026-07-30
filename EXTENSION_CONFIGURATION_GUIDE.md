# Extension Configuration Guide - Extension 63311

## Overview
This guide explains how to configure extension **63311** with password **f63311** on your Asterisk server to work with the DIDX Dialer for making and receiving calls.

## Extension Details
```
Extension: 63311
Password: f63311
Server: 165.227.88.28
Protocol: PJSIP (recommended) or SIP
```

## Configuration Steps

### Step 1: SSH into Your Asterisk Server

```bash
ssh root@165.227.88.28
```

### Step 2: Edit PJSIP Configuration

On Ubuntu/Linux with Asterisk 22.x, edit the PJSIP configuration:

```bash
sudo nano /etc/asterisk/pjsip.conf
```

Or if using separate files:

```bash
sudo nano /etc/asterisk/pjsip/extensions.conf
```

### Step 3: Add Extension 63311 Configuration

Add the following PJSIP configuration block:

```ini
[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060

[63311]
type=endpoint
context=from-internal
disallow=all
allow=ulaw,alaw,gsm
auth=63311-auth
aors=63311-aor

[63311-auth]
type=auth
auth_type=userpass
username=63311
password=f63311

[63311-aor]
type=aor
max_contacts=1
contact=sip:63311@0.0.0.0
```

### Step 4: Register a SIP/PJSIP Client

You can use any SIP phone or softphone application:

**Softphone Examples:**
- Zoiper (mobile/desktop)
- Linphone (mobile/desktop)
- X-Lite (desktop)
- Bria (mobile/desktop)
- MicroSIP (Windows)

**Settings:**
```
SIP Server/Proxy: 165.227.88.28
Port: 5060
Extension/Username: 63311
Password: f63311
Display Name: Extension 63311
Protocol: PJSIP or SIP (UDP)
```

### Step 5: Reload Asterisk Configuration

```bash
sudo /usr/sbin/asterisk -rx 'module reload res_pjsip'
```

Or reload all:

```bash
sudo /usr/sbin/asterisk -rx 'core reload'
```

### Step 6: Verify Extension is Online

Check if extension is registered:

```bash
sudo /usr/sbin/asterisk -rx 'pjsip show endpoints'
```

Should show:
```
Endpoint:  63311                                                Not in use    0 of inf
Contact:   63311/sip:192.168.x.x:xxxxx              xxxxxxxx Avail         x.xxx
```

### Step 7: Create Inbound Context (if needed)

In `/etc/asterisk/extensions.conf`:

```ini
[from-internal]
exten => 63311,1,Answer()
 same => n,Playback(welcome)
 same => n,Hangup()

; Accept calls to 63311
exten => 63311,1,Ring()
 same => n,Dial(PJSIP/63311,30)
 same => n,Hangup()
```

## Using in DIDX Dialer

### Making Outbound Calls

1. Go to **Operations > Dialer**
2. Select **Outbound Route** (e.g., 7788 or any active DID)
3. Select **Receive on Extension** = 63311
4. Dial the destination number
5. Click **Call**

The call will:
- Originate from the selected outbound route
- Ring on extension 63311
- Connect when you answer

### Receiving Inbound Calls

When someone calls extension 63311:

1. Your SIP phone/softphone will ring
2. Answer the call
3. The DIDX portal will automatically log the call in history
4. Call duration and details are recorded

## Troubleshooting

### Extension Not Showing as Online

**Check:**
```bash
sudo /usr/sbin/asterisk -rx 'pjsip show endpoints'
sudo /usr/sbin/asterisk -rx 'pjsip show aors'
sudo /usr/sbin/asterisk -rx 'pjsip show auths'
```

**Common Issues:**
- Password mismatch
- Firewall blocking port 5060
- SIP phone not registering
- Incorrect server address

### Can't Make Outbound Calls

**Check:**
- Outbound route status is PASS
- Extension 63311 is registered and online
- Dialed number is valid (7-15 digits)

**Test from Asterisk CLI:**
```bash
sudo /usr/sbin/asterisk -rx 'channel originate PJSIP/7788@outbound application bridge SIP/63311'
```

### Can't Receive Calls

**Check:**
```bash
# Check if extension can be reached
sudo /usr/sbin/asterisk -rx 'channel originate PJSIP/63311 application playback welcome'

# Check dialplan
sudo /usr/sbin/asterisk -rx 'dialplan show from-internal'
```

### Call Not Recorded in History

**Check:**
- Database connection is working
- `call_histories` table exists
- PHP error logs: `tail -f /var/log/asterisk/full`

## Advanced Configuration

### Set Call Recording

In `/etc/asterisk/pjsip.conf`:

```ini
[63311]
type=endpoint
; ... other settings ...
record_on_feature=*1
record_off_feature=*0
```

### Add Call Forwarding

```ini
[63311]
type=endpoint
; ... other settings ...
incoming_call_offer_pref=preferred_codec
```

### Configure Voicemail

```bash
# Edit voicemail config
sudo nano /etc/asterisk/voicemail.conf

# Add:
[default]
63311 => 1234,Extension 63311,,|attach=yes|saycid=yes|sayduration=yes

# Enable in extensions.conf
exten => 63311,1,VoiceMail(63311@default)
```

## Monitoring Calls in Portal

Once configured, you can:

1. **See Active Calls**: Dashboard shows real-time active calls
2. **View History**: All calls logged with caller ID, duration, status
3. **Add Notes**: Add remarks to each call
4. **Redial**: Quick redial from history
5. **Filter**: Filter by inbound/outbound

## Security Notes

⚠️ **Important:**
- Change password from default `f63311`
- Use firewall to restrict SIP access
- Enable SIP authentication
- Monitor failed login attempts
- Use TLS/SRTP for encrypted calls (advanced)

## Test Procedure

1. Register extension 63311 on your softphone
2. From DIDX portal: Dial your phone number
3. Extension 63311 should ring
4. Answer the call
5. Check portal - call should show in history with duration
6. Hangup and verify call logged as completed

## Support

For issues:
- Check Asterisk logs: `tail -f /var/log/asterisk/full`
- Check PJSIP debug: `pjsip set logger on`
- Check portal logs: `tail -f storage/logs/laravel.log`

## Related Files

- PJSIP Config: `/etc/asterisk/pjsip.conf`
- Extensions: `/etc/asterisk/extensions.conf`
- Portal Config: `.env` (DB connection)
- Call History: `call_histories` table in database
