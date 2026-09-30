
# 11 — Email Alerting



🌐 **Language:** [Tiếng Việt](README.viever.md) | **English**




## I. Objective

Automatically send an email when Suricata detects a high-severity alert, so there is no need to monitor logs manually.

## II. Prerequisites

- SMTP is configured and tested successfully at **System → Advanced → Notifications → SMTP**.
  - Gmail: SMTP server `smtp.gmail.com`, **port 465**, Enable SMTP over SSL/TLS = ON, use an **App Password** (not the regular Gmail password).
  - If you get "No route to host" even though the port is correct: check for an IPv6 conflict and disable "Allow IPv6" at **System → Advanced → Networking**.
- Suricata **EVE Output Type = FILE** (not SYSLOG). This is required so that the `eve.json` file exists for the script to read.
- The **Cron** package must be installed (see section III.4).

### Create a Gmail App Password

An App Password is a 16-character password issued by Google for a single application (here, pfSense). It replaces the regular Gmail password. Requirement: **2-Step Verification** must be enabled.

| Step | Action | Verify |
|------|--------|--------|
| 1 | Open https://myaccount.google.com/apppasswords | The app name field is shown |
| 2 | Enter the name `pfSense-Suricata`, click **Create** | A 16-character password is shown |
| 3 | Copy it and remove all spaces (it is shown only once) | The string is exactly 16 characters |
| 4 | In pfSense: **System → Advanced → Notifications → SMTP**, paste it into the password field, click **Save**, then **Test SMTP Settings** | A test email is received |

> ⚠️ Never commit the App Password to GitHub. If it leaks, delete it on the App passwords page and create a new one.

## III. Configuration steps

### III.1. Configure SMTP email on pfSense

Go to **System → Advanced → Notifications → SMTP E-Mail** and configure as shown.

<p align="center">
  <img width="600" alt="Gmail SMTP configuration" src="https://github.com/user-attachments/assets/92338248-e116-4acb-9647-19c0bd111c39" />
  <br>
  <em>Figure 1: Gmail SMTP configuration on pfSense for sending alert emails.</em>
</p>

<p align="center">
  <img width="600" alt="Suricata alert email" src="https://github.com/user-attachments/assets/b8201bab-af12-41f7-926f-dfdfa23adcce" />
  <br><br>
  <img width="700" alt="Suricata alert email (detail)" src="https://github.com/user-attachments/assets/5f4c9e08-e221-4115-989e-6aca06632c0e" />
  <br>
  <em>Figures 2 and 3: Test email delivered to Gmail, confirming SMTP is configured correctly.</em>
</p>

### III.2. Enable EVE JSON log

`eve.json` is Suricata's event log. Each event is one JSON line (source/destination IP, signature, severity). The script `suricata_mailer.php` reads this file with `json_decode` to build the alert email.

#### III.2.1. Configure EVE Output

1. Go to **Services → Suricata → Interfaces → Edit → EVE Output**.
2. Set **EVE Output Type = FILE**, then click **Save**.

> If **SYSLOG** is selected, `eve.json` is not created, the script has no data to read, and no alert is sent.

<p align="center">
  <img width="600" alt="EVE JSON configuration" src="https://github.com/user-attachments/assets/7703ba2e-8b15-464a-9224-ebbdc7debf05" />
  <br>
  <em>Figure 4: Enabling EVE JSON output.</em>
</p>

#### III.2.2. Check the log via WebGUI and confirm `eve.json` exists

1. Go to **Diagnostics → Command Prompt**.
2. Enter the two commands below in **Execute Shell Command** and click **Execute**:

```sh
ls /var/log/suricata/
```

```sh
ls /var/log/suricata/suricata_em036752/
```

| Command | Purpose | Expected result |
|---------|---------|-----------------|
| `ls /var/log/suricata/` | List the log directories, one per interface | A directory named `suricata_<interface><UUID>` |
| `ls /var/log/suricata/suricata_em036752/` | List the log files of that interface | The file `eve.json` is present |

<p align="center">
  <img width="600" alt="ls Suricata log directory" src="https://github.com/user-attachments/assets/cb499258-bf49-4cfc-9874-419aaed880bd" />
  <br><br>
  <img width="600" alt="ls WAN interface directory" src="https://github.com/user-attachments/assets/1eadb704-ff20-4cd3-9e48-1ca6f68dee56" />
  <br>
  <em>Figures 5 and 6: Command output showing the UUID directory of the WAN interface and the log files inside it.</em>
</p>

### III.3. Create the script on pfSense

1. Go to **Diagnostics → Edit File**.
2. Enter the path `/root/suricata_mailer.php` and paste the content of [suricata_mailer.php](suricata_mailer.php).
3. Edit the `$eve_file` line to use the UUID directory name from Figure 5, then click **Save**.

#### III.3.1. How `suricata_mailer.php` works

| Code | Meaning |
|------|---------|
| `require_once("config.inc")`, `require_once("notices.inc")` | Load pfSense libraries, needed for the `notify_via_smtp()` mail function |
| `$eve_file` | Path to the `eve.json` file of the WAN interface. **Must be edited to match the UUID directory on your machine** |
| `$state_file` | Stores the last read position so old alerts are not read and sent again |
| `$lastpos = ... file_get_contents(...)` | Read the saved position; start from 0 if the file does not exist |
| `fopen` + `fseek($fp, $lastpos)` | Open `eve.json` and jump to the position of the previous run |
| `while (fgets(...))` | Read line by line; each line is one JSON event |
| `json_decode($line, true)` | Convert the JSON line into a PHP array |
| `event_type == 'alert'` | Handle alert events only; ignore dns, http, tls, etc. |
| `$sev <= 2` | Send mail only for severity 1 and 2 (the lower the number, the more severe). If the field is missing it defaults to 3 and no mail is sent |
| `$msg = ...` | Build the mail body: signature, source IP → destination IP:port, timestamp |
| `notify_via_smtp($msg)` | Send mail using the SMTP settings from System → Advanced → Notifications |
| `ftell` + `file_put_contents` | Save the end-of-file position for the next run |
| `fclose($fp)` | Close the file |

**Flow:** read the old position → read new lines in `eve.json` → filter severity ≤ 2 → send mail → save the new position.

### III.4. Install the Cron package to run the script automatically

The script runs only once when called manually. The **Cron** package schedules it to run periodically so alert emails are sent automatically.

#### III.4.1. Install Cron

1. Go to **System → Package Manager → Available Packages**.
2. Find `Cron`, click **Install**, then **Confirm**.

**Verify:** the **Services → Cron** menu item appears.

#### III.4.2. Schedule the script

1. Go to **Services → Cron → Add**.
2. Fill in the table below, then click **Save**.

| Field | Value | Meaning |
|-------|-------|---------|
| Minute | `*/2` | Run every 2 minutes |
| Hour, Day, Month, Weekday | `*` | Every hour, day, month, weekday |
| User | `root` | Run as root, with enough permission to read logs |
| Command | `/usr/local/bin/php -f /root/suricata_mailer.php` | Run the script with PHP CLI |

<p align="center">
  <img width="600" alt="Cron configuration" src="https://github.com/user-attachments/assets/26bca455-29a0-41b3-b9af-5815b6807faf" />
  <br>
  <em>Figure 7: Cron configuration for automatic alert emails.</em>
</p>

### III.5. Simulate an attack and verify

Run from the Kali machine (`192.168.10.50`) against the web server `192.168.10.1`:

```sh
nmap -p 80 --script http-passwd 192.168.10.1
```

| Part | Meaning |
|------|---------|
| `nmap` | Network scanning tool |
| `-p 80` | Scan port 80 (HTTP) only |
| `--script http-passwd` | NSE script that tries to read sensitive files (`/etc/passwd`, `boot.ini`) using `../../` style paths (directory traversal test) |
| `192.168.10.1` | Target host |

**Verify:** wait up to 2 minutes (Cron interval). Gmail receives an email starting with `[SURICATA ALERT]`.

> ⚠️ Run this only against machines in your own lab.

#### Results

| Image | Content | Meaning |
|-------|---------|---------|
| <img width="300" alt="nmap command" src="URL_NMAP_IMAGE" /> | `nmap -p 80 --script http-passwd 192.168.10.1` run from Kali | Source of the attack traffic that triggers Suricata |
| <img width="300" alt="Suricata Alerts tab" src="URL_ALERT_TAB_IMAGE" /> | **Services → Suricata → Alerts** tab shows 2 alerts (Pri 3 and Pri 1) | Suricata detects the scan traffic |
| <img width="220" alt="Alert email" src="URL_EMAIL_IMAGE" /> | `[SURICATA ALERT]` email received in Gmail | Only the **Pri 1** alert (`ET SCAN Nmap Scripting Engine`, SID 2009358) was emailed |

**Severity filter check:** the Alerts tab shows 2 alerts (Pri 3 and Pri 1), but the email contains only the Pri 1 alert. This confirms the script sends only alerts with severity ≤ 2.

## IV. Expected results

- An email arrives automatically within 2 minutes of a high-severity alert, with no manual action.
- Only alerts with **severity 1 and 2** are emailed. Severity 3 alerts (for example `SURICATA Applayer Mismatch protocol`) appear only in **Services → Suricata → Alerts**, so no spam email.
- Each alert is sent **once**. The script stores the read position in `/tmp/suricata_mail_lastpos.txt`, so old alerts are not resent.
- Multiple alerts in the same Cron cycle are merged into **one email**.
- The email has what is needed to act: signature, source IP → destination IP:port, timestamp.
- The setup keeps working after pfSense restarts because Cron runs on schedule again.

### Acceptance criteria

| # | Criterion | How to check |
|---|-----------|--------------|
| 1 | SMTP works | **Test SMTP Settings** delivers a test email |
| 2 | `eve.json` exists | `ls /var/log/suricata/<UUID directory>/` shows `eve.json` |
| 3 | Script runs without errors | `/usr/local/bin/php -f /root/suricata_mailer.php` prints no error |
| 4 | Cron schedule is correct | **Services → Cron** has an entry running every 2 minutes |
| 5 | Alert is received | Run `nmap -p 80 --script http-passwd 192.168.10.1`, wait 2 minutes, Gmail has a `[SURICATA ALERT]` email |
| 6 | Severity filter works | A Pri 3 alert appears in the Alerts tab but not in the email |

### Known limitations

- Maximum delay equals the Cron interval (2 minutes); it is not real time.
- If `eve.json` is rotated, delete `/tmp/suricata_mail_lastpos.txt` so the script reads from the beginning.
- The UUID directory in `$eve_file` must be edited manually on each machine.
- `/tmp/suricata_mail_lastpos.txt` is lost on reboot, so after a reboot the script reads from the start and may resend old alerts once. To avoid this, change `$state_file` to `/root/suricata_mail_lastpos.txt`.
