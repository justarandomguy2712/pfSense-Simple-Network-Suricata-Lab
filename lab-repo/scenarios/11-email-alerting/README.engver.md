# 11 — Email Alerting


🌐 **Language:** **English** | [Vietnamese](README.viever.md)



## I. Objective
Automatically send an email when Suricata detects a high-severity alert, without having to monitor logs manually.

## II. Prerequisites for the lab
- System → Advanced → Notifications → SMTP is configured and tested successfully
  - Gmail: SMTP server `smtp.gmail.com`, **port 465**, Enable SMTP over SSL/TLS = ON, use an **App Password** (do not use the regular Gmail password)
  - If you get the "No route to host" error even though the port is correct: check for an IPv6 conflict — turn off "Allow IPv6" at System → Advanced → Networking
- Suricata EVE Output Type = **FILE** (not SYSLOG) — required so the `eve.json` file exists for the script to read
- Cron package is installed
### Create a Gmail App Password

An App Password is a 16-character password that Google issues specifically for pfSense, used in place of the regular Gmail password. Requirement: **2-Step Verification** must be enabled.

| Step | Action | Verify |
|------|--------|--------|
| 1 | Open https://myaccount.google.com/apppasswords | The app name field appears |
| 2 | Enter the name `pfSense-Suricata`, click **Create** | A 16-character password is displayed |
| 3 | Copy it and remove all spaces (shown only once) | The string is exactly 16 characters long |
| 4 | In pfSense: **System → Advanced → Notifications → SMTP**, paste into the password field, **Save** then **Test SMTP Settings** | A test email is received |


## III. Configuration steps performed

### 1. Configure SMTP Email on pfSense to send alert emails



#### 1.1. Go to **System → Advanced → Notifications → SMTP E-Mail** and configure as shown.




<p align="center">
  <img width="600" alt="Gmail SMTP configuration" src="https://github.com/user-attachments/assets/92338248-e116-4acb-9647-19c0bd111c39" />
  <br>
  <em>Figure 1: Gmail SMTP configuration on pfSense to send alert emails.</em>
</p>

<p align="center">
  <img width="600" alt="Suricata alert email" src="https://github.com/user-attachments/assets/b8201bab-af12-41f7-926f-dfdfa23adcce" />
  <br><br>
  <img width="700" alt="Suricata alert email (details)" src="https://github.com/user-attachments/assets/5f4c9e08-e221-4115-989e-6aca06632c0e" />
  <br>
  <em>Figures 2 and 3: Test email from <code>suricata_mailer.php</code> delivered to Gmail when configured correctly.</em>
</p>





### 2. Enable EVE JSON Log

`eve.json` is Suricata's event log. Each event is one JSON line (source/destination IP, signature, severity). The script `suricata_mailer.php` reads this file with `json_decode` to send alert emails.

#### 2.1. Configure EVE Output

1. Go to **Services → Suricata → Interfaces → Edit → EVE Output**.
2. Set **EVE Output Type = FILE**, click **Save**.

> If you choose **SYSLOG**, the `eve.json` file is not created, the script has no data and cannot send alerts.

<p align="center">
  <img width="600" alt="EVE JSON configuration" src="https://github.com/user-attachments/assets/7703ba2e-8b15-464a-9224-ebbdc7debf05" />
  <br>
  <em>Figure 4: Enabling EVE JSON to receive logs.</em>
</p>



#### 2.2. Check the log via WebGUI and verify that the eve.json file exists
1. Go to **Diagnostics → Command Prompt**.
2. Enter the 2 commands below in the **Execute Shell Command** box and click **Execute**:

```sh
ls /var/log/suricata/
```


```sh
ls /var/log/suricata/suricata_em036752/
```

| Command | Purpose | Expected result |
|------|---------|--------------|
| `ls /var/log/suricata/` | List the log directories, one subdirectory per interface | <img width="600" alt="ls Suricata log directory" src="https://github.com/user-attachments/assets/cb499258-bf49-4cfc-9874-419aaed880bd" />|
| `ls /var/log/suricata/suricata_em036752/` | List the log files of that interface |  <img width="600" alt="ls result of the WAN interface directory" src="https://github.com/user-attachments/assets/1eadb704-ff20-4cd3-9e48-1ca6f68dee56" />|







### 3. Edit the `$eve_file` line in [suricata_mailer.php](suricata_mailer.php) to match the real folder name.

#### 3.1 Explanation of the `eve.json` script






| Code | Meaning |
|-----------|---------|
| `require_once("config.inc")`, `require_once("notices.inc")` | Load pfSense libraries, required for the mail function `notify_via_smtp()` |
| `$eve_file` | Path to the `eve.json` file of the WAN interface. **You must change it to the correct UUID folder name on your machine** |
| `$state_file` | File that stores the last read position, so the next run does not read and resend old alerts |
| `$lastpos = ... file_get_contents(...)` | Read the saved position; if the file does not exist, start from 0 |
| `fopen` + `fseek($fp, $lastpos)` | Open `eve.json` and jump to the position read last time |
| `while (fgets(...))` | Read line by line, each line is one JSON event |
| `json_decode($line, true)` | Convert the JSON line into a PHP array |
| `event_type == 'alert'` | Only process alert events, skip dns, http, tls... |
| `$sev <= 2` | Only send mail for high-severity alerts (Severity ID 1 and 2, the lower the number the more severe). If this field is missing, the severity ID defaults to 3 and no mail is sent |
| `$msg = ...` | Compose the alert email content: signature, source IP → destination IP: port, time |
| `notify_via_smtp($msg)` | Send mail using the SMTP configuration in System → Advanced → Notifications |
| `ftell` + `file_put_contents` | Save the end-of-file position just read for the next run |
| `fclose($fp)` | Close the file |


**Flow:** Read the old position → Read new lines in `eve.json` → Filter Severity ID ≤ 2 → Send mail → Save the new position.


### 4. Install the Cron package in pfSense to run automatically.


#### 4.1. Install the Cron package

1. Go to **System → Package Manager → Available Packages**.
2. Find `Cron`, click **Install**, then **Confirm**.

**Verify:** the **Services → Cron** entry appears in the menu.

#### 4.2. Create the script schedule
1. Diagnostics → Edit File, save at `/root/suricata_mailer.php`.
2. Go to **Services → Cron → Add**.
3. Fill in as in the table below then click **Save**.



| Field | Value | Meaning |
|---|---------|---------|
| Minute | `*/2` | Run every 2 minutes |
| Hour, Day, Month, Weekday | `*` | Every hour, day, month, weekday |
| User | `root` | Run as root, with enough permission to read logs |
| Command | `/usr/local/bin/php -f /root/suricata_mailer.php` | Run the script with PHP CLI |


<p align="center">
  <img width="600" alt="Cron configuration" src="https://github.com/user-attachments/assets/26bca455-29a0-41b3-b9af-5815b6807faf" />
  <br>
  <em>Figure 5: Cron configuration to receive logs automatically by mail.</em>
</p>



### 5. Verify the result

#### 5.1. Go to **Diagnostics → Command Prompt**, run `/usr/local/bin/php -f /root/suricata_mailer.php`.
#### 5.2 Generate a severity 1 or 2 alert (for example a web vulnerability scan with `nikto`), wait up to 2 minutes.

**Verify:** Gmail receives a `[SURICATA ALERT]` email as shown in the image below:

<p align="center">
  <img width="600" alt="Email Alerting configuration" src="https://github.com/user-attachments/assets/3a579461-a89b-44e1-ae8d-179283ac4405" />
  <br>
  <em>Figure 6: The alert email was delivered to the mailbox as configured</em>
</p>



## IV. Commands / tools tested
### 1. Attack testing with Kali Linux.
Attack simulation command: `nmap` directory traversal check:


```bash
# nmap -p 80 --script http-passwd 192.168.10.1
```

The command is run from the Kali machine (`192.168.10.50`) against the web server `192.168.10.1` to attempt a **directory traversal** attack and trigger a Suricata alert.

| Component | Meaning |
|------------|-----------|
| `nmap` | Network scanning tool |
| `-p 80` | Scan port 80 (HTTP) only |
| `--script http-passwd` | Run a Nmap Scripting Engine script that tries to read sensitive files (`/etc/passwd`, `boot.ini`) using `../../` style paths. If they can be read, the server has a directory traversal vulnerability |
| `192.168.10.1` | Target machine address |


<p align="center">
  <img width="600" alt="Kali Linux configuration" src="https://github.com/user-attachments/assets/12b66699-bb9f-4793-90ec-ad592158151b"  />
  <br>
  <em>Figure 7: Test attack on Kali Linux</em>
</p>








## V. Results obtained

| Image | Content |
|-----|----------|
|<img width="1184" height="203" alt="Screenshot 2026-09-30 165646" src="https://github.com/user-attachments/assets/e426b33c-cfde-4f08-86b0-b1bcc4390045" />| Logs in the Alerts tab of Suricata |
|<img width="1418" height="287" alt="Screenshot 2026-09-30 205535" src="https://github.com/user-attachments/assets/0d598e93-e64d-49ac-a0da-801289720583" /> | `[SURICATA ALERT]` email received in Gmail |



**Severity filter verification:** the Alerts tab has 2 alerts (Pri 3 and Pri 1), the email contains only the Pri 1 alert. This confirms the script only sends alerts with severity ≤ 2.


## VI. Expected results

- The email arrives automatically within 02 minutes after a high-severity alert, with no manual action.
- Only **severity 1 and 2** alerts are emailed. Severity 3 alerts (for example `SURICATA Applayer Mismatch protocol`) only appear in the Alerts tab and do not cause email spam.
- Each alert is sent **only once**. The script stores the read position in `/tmp/suricata_mail_lastpos.txt`, so old alerts are not resent.
- Multiple alerts occurring within the same Cron cycle are combined into **one email** (as in Figure 8, which contains 3 alerts).
- The email content is enough to decide on a response: signature name, Source IP → Destination IP: port, time.
- The system stays stable after pfSense restarts because Cron automatically runs again on the configured schedule.
