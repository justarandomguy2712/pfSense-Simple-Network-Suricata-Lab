# 11 — Email Alerting

🌐 **Language:** **English** | [Vietnamese](README.viever.md)


## I. Objective

Automatically send an email when Suricata detects a high-severity alert, so there is no need to watch logs manually.

## II. Prerequisites

1. **SMTP configured and tested:** System → Advanced → Notifications → SMTP.
   - Gmail settings: server `smtp.gmail.com`, **port 465**, Enable SMTP over SSL/TLS = ON, use an **App Password** (not the regular Gmail password).
   - If you get "No route to host" with the correct port: this is likely an IPv6 conflict. Disable **Allow IPv6** at System → Advanced → Networking.
2. **Suricata EVE Output Type = FILE** (not SYSLOG). Required so the `eve.json` file exists for the script to read.
3. **Cron package installed** (see section IV).

### Create a Gmail App Password

An App Password is a 16-character password Google issues for a single application (here, pfSense). It replaces the regular Gmail password. **Requirement:** 2-Step Verification must be enabled.

| Step | Action | Verify |
|------|--------|--------|
| 1 | Open https://myaccount.google.com/apppasswords | The app name field appears |
| 2 | Enter the name `pfSense-Suricata`, click **Create** | A 16-character password is shown |
| 3 | Copy it and remove all spaces (shown only once) | The string is exactly 16 characters |
| 4 | In pfSense: **System → Advanced → Notifications → SMTP**, paste into the password field, click **Save**, then **Test SMTP Settings** | A test email arrives |

> ⚠️ Never commit the App Password to GitHub. If it leaks, delete it on the App passwords page and create a new one.

## III. Configuration Steps

### 1. Configure SMTP on pfSense

Go to **System → Advanced → Notifications → SMTP E-Mail** and configure as shown.

<p align="center">
  <img width="600" alt="Gmail SMTP configuration" src="https://github.com/user-attachments/assets/92338248-e116-4acb-9647-19c0bd111c39" />
  <br>
  <em>Figure 1: Gmail SMTP configuration on pfSense for sending alert emails.</em>
</p>

<p align="center">
  <img width="600" alt="Test email" src="https://github.com/user-attachments/assets/b8201bab-af12-41f7-926f-dfdfa23adcce" />
  <br><br>
  <img width="700" alt="Test email (details)" src="https://github.com/user-attachments/assets/5f4c9e08-e221-4115-989e-6aca06632c0e" />
  <br>
  <em>Figures 2 and 3: Test email received in Gmail, confirming SMTP is configured correctly.</em>
</p>

### 2. Enable EVE JSON Log

`eve.json` is Suricata's event log. Each event is one JSON line (source/destination IP, signature, severity). The script `suricata_mailer.php` reads this file with `json_decode` to build the alert email.

#### 2.1. Configure EVE Output

1. Go to **Services → Suricata → Interfaces → Edit → EVE Output**.
2. Set **EVE Output Type = FILE**, then click **Save**.

> If you choose **SYSLOG**, `eve.json` is not created. The script has no data to read and no alert is sent.

<p align="center">
  <img width="600" alt="EVE JSON configuration" src="https://github.com/user-attachments/assets/7703ba2e-8b15-464a-9224-ebbdc7debf05" />
  <br>
  <em>Figure 4: Enabling EVE JSON output.</em>
</p>

#### 2.2. Verify the log via WebGUI

1. Go to **Diagnostics → Command Prompt**.
2. Run both commands in **Execute Shell Command**:

```sh
ls /var/log/suricata/
```

```sh
ls /var/log/suricata/suricata_em036752/
```

| Command | Purpose | Expected result |
|---------|---------|-----------------|
| `ls /var/log/suricata/` | List log directories, one per interface | A folder named `suricata_<interface><UUID>` |
| `ls /var/log/suricata/suricata_em036752/` | List log files of that interface | `eve.json` is present |

<p align="center">
  <img width="600" alt="ls Suricata log directory" src="https://github.com/user-attachments/assets/cb499258-bf49-4cfc-9874-419aaed880bd" />
  <br><br>
  <img width="600" alt="ls WAN interface directory" src="https://github.com/user-attachments/assets/1eadb704-ff20-4cd3-9e48-1ca6f68dee56" />
  <br>
  <em>Figures 5 and 6: Command output showing the WAN interface's UUID folder and the log files inside.</em>
</p>

### 3. Create the script on pfSense

1. Go to **Diagnostics → Edit File**.
2. Enter the path `/root/suricata_mailer.php` and paste the contents of [suricata_mailer.php](suricata_mailer.php).
3. Edit the `$eve_file` line to match the UUID folder name from Figure 5, then click **Save**.

#### 3.1. Script explanation

| Code | Meaning |
|------|---------|
| `require_once("config.inc")`, `require_once("notices.inc")` | Load pfSense libraries, needed for `notify_via_smtp()` |
| `$eve_file` | Path to the WAN interface's `eve.json`. **Must match the UUID folder on your machine** |
| `$state_file` | Stores the last read position so old alerts are not sent again |
| `$lastpos = ... file_get_contents(...)` | Read the saved position; start from 0 if the file does not exist |
| `fopen` + `fseek($fp, $lastpos)` | Open `eve.json` and jump to the last read position |
| `while (fgets(...))` | Read line by line; each line is one JSON event |
| `json_decode($line, true)` | Convert the JSON line into a PHP array |
| `event_type == 'alert'` | Process alerts only; skip dns, http, tls, etc. |
| `$sev <= 2` | Send only high-severity alerts (severity 1 and 2; lower number = more severe). If the field is missing it defaults to 3 and is not sent |
| `$msg = ...` | Build the email body: signature, source IP → destination IP:port, time |
| `notify_via_smtp($msg)` | Send mail using the SMTP settings in System → Advanced → Notifications |
| `ftell` + `file_put_contents` | Save the end-of-file position for the next run |
| `fclose($fp)` | Close the file |

**Flow:** read saved position → read new lines in `eve.json` → filter severity ≤ 2 → send mail → save new position.

### 4. Install the Cron package for automatic alerts

#### 4.1. Install Cron

1. Go to **System → Package Manager → Available Packages**.
2. Find `Cron`, click **Install**, then **Confirm**.

**Verify:** **Services → Cron** appears in the menu.

#### 4.2. Schedule the script

1. Go to **Services → Cron → Add**.
2. Fill in the table below, then click **Save**.

| Field | Value | Meaning |
|-------|-------|---------|
| Minute | `*/2` | Run every 2 minutes |
| Hour, Day, Month, Weekday | `*` | Every hour, day, month, weekday |
| User | `root` | Root has enough permission to read logs |
| Command | `/usr/local/bin/php -f /root/suricata_mailer.php` | Run the script with PHP CLI |

<p align="center">
  <img width="600" alt="Cron configuration" src="https://github.com/user-attachments/assets/26bca455-29a0-41b3-b9af-5815b6807faf" />
  <br>
  <em>Figure 7: Cron job that sends alerts by email automatically.</em>
</p>

### 5. Generate a test alert

Run from the Kali machine (`192.168.10.50`) against the web server (`192.168.10.1`). Lab use only.

```sh
nmap -p 80 --script http-passwd 192.168.10.1
```

| Part | Meaning |
|------|---------|
| `-p 80` | Scan port 80 (HTTP) only |
| `--script http-passwd` | NSE script that tries directory traversal paths (`../../`) to read sensitive files |
| `192.168.10.1` | Target host |

Wait up to 2 minutes (Cron interval), then check Gmail for an `[SURICATA ALERT]` email.

## IV. Results

| Image | Content | Meaning |
|-------|---------|---------|
| <img width="300" alt="nmap command" src="URL_NMAP_IMAGE" /> | `nmap` command run from Kali | Source of the attack traffic |
| <img width="300" alt="Suricata Alerts tab" src="URL_ALERT_TAB_IMAGE" /> | **Services → Suricata → Alerts** shows 2 alerts (Pri 3 and Pri 1) | Suricata detected the scan |
| <img width="220" alt="Alert email" src="URL_EMAIL_IMAGE" /> | `[SURICATA ALERT]` email in Gmail | Only the **Pri 1** alert was emailed |

**Severity filter verified:** the Alerts tab shows 2 alerts, but the email contains only the Pri 1 one. This confirms the script sends only severity ≤ 2.

## V. Expected Results

- An email arrives automatically within 2 minutes of a high-severity alert, with no manual action.
- Only **severity 1 and 2** alerts are emailed. Severity 3 alerts appear only in the Suricata Alerts tab.
- Each alert is sent **once**, thanks to the saved read position in `/tmp/suricata_mail_lastpos.txt`.
- Multiple alerts within one Cron cycle are batched into **one email**.
- The email has what you need to act: signature, source → destination:port, timestamp.

### Acceptance checklist

| # | Criterion | How to verify |
|---|-----------|---------------|
| 1 | SMTP works | **Test SMTP Settings** delivers a test email |
| 2 | `eve.json` exists | `ls /var/log/suricata/<UUID folder>/` lists `eve.json` |
| 3 | Script runs without errors | `/usr/local/bin/php -f /root/suricata_mailer.php` prints no error |
| 4 | Cron schedule correct | **Services → Cron** shows a job every 2 minutes |
| 5 | Alert received | Run the `nmap` command, wait 2 minutes, Gmail has an `[SURICATA ALERT]` email |
| 6 | Severity filter works | Pri 3 alert is in the Alerts tab but not in the email |

### Known limitations

- Maximum delay equals the Cron interval (2 minutes); not real time.
- If `eve.json` rotates, delete `/tmp/suricata_mail_lastpos.txt` so the script reads from the start.
- The UUID folder in `$eve_file` must be edited manually per machine.
- `/tmp`
