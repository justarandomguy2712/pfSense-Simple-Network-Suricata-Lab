# 11 — Email Alerting



🌐 **Language:** [Tiếng Việt](README.viever.md) | **English**



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
  <img width="600" alt="EVE JSON configuration" src="https://github.com/user-attachments/ass
