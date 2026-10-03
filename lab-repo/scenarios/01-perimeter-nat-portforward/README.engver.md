# 01 — Perimeter Defense & NAT Port Forwarding



🌐 **Language:** **English** | [Vietnamese](README.viever.md)



## I. Objectives

Verify that pfSense operates correctly as a perimeter firewall:

- Permit access only to explicitly authorized ports on the WAN interface (NAT Port Forward to Windows Server 2012).
- Block all remaining ports by default and log the blocked traffic.
- Validate Outbound NAT: the source IP of Windows Server 2012 is translated from 192.168.20.2 to the WAN IP 192.168.10.1, and return packets are correctly translated back to the originating host.

## II. Prerequisites

- The lab environment is fully deployed and network connectivity is established according to the topology below:

<p align="center">
  <img width="600" alt="Basic lab network topology" src="https://github.com/user-attachments/assets/b1197956-63a2-4034-b4b7-39d71079f4a1" />
  <br>
  <em>Figure 1: Basic lab network topology</em>
</p>

- Wireshark is installed on Windows Server 2012.
- Firewall → NAT → Outbound is set to Automatic outbound NAT (default).

## III. Configuration Steps

### 1. Create the `Ports_Test` Alias

An alias groups multiple ports under a single name so that it can be reused in NAT and Firewall rules.

- Navigate to Firewall → Aliases → Ports → Add (ports 21, 80, 443, 445, 3389, 5985):

<p align="center">
  <img width="600" alt="Creating the Ports_Test alias" src="https://github.com/user-attachments/assets/349bf95f-abef-483f-aaef-a5f63abb82bb" />
  <br>
  <em>Figure 2: Creating the alias that defines the ports permitted through the firewall</em>
</p>

### 2. Create the WinServer2012 (Host) Alias

This alias assigns a name to the LAN IP address of the Windows Server, to be reused in the NAT Port Forward and Firewall rules. If the IP address changes later, only this single entry needs to be updated.

- Navigate to **Firewall → Aliases → IP → Add** and fill in the fields as shown below:

| Field | Value | Notes |
|---|---------|---------|
| Name | `WinServer2012` | Letters, digits, and underscores (`_`) only |
| Description | `IP LAN cua WinServer 2012` | For easier identification and management |
| Type | `Host(s)` | Alias containing an IP address |
| IP or FQDN | `192.168.20.2` | LAN IP of the Windows Server |

- Click **Save** → **Apply Changes**.

**Verification:** Go to **Diagnostics → Tables**, select `WinServer2012`, and confirm that a single entry `192.168.20.2` is listed.

<p align="center">
  <img width="1263" height="554" alt="Creating the WinServer2012 IP alias" src="https://github.com/user-attachments/assets/7414586d-6e5f-4f57-b782-7f2773050d2c" />
  <br>
  <em>Figure 3: Creating the WinServer2012 IP alias</em>
</p>

### 3. Create the NAT Port Forward to Windows Server 2012

- Navigate to Firewall → NAT → Port Forward → Add and configure the rule as shown in the figure below, then click Save → Apply Changes.
- Enable "Log packets that are handled by this rule" on the corresponding WAN rule and on the NAT Port Forward targeting WinServer2012.

<p align="center">
  <img width="600" alt="NAT Port Forward to Windows Server 2012" src="https://github.com/user-attachments/assets/d9435b14-77f8-4c6a-8917-7db968a81349" />
  <br>
  <em>Figure 4: NAT Port Forward targeting Windows Server 2012; packets handled by this rule are logged</em>
</p>

### 4. Configure and Perform Packet Capture on the WAN Interface

#### 4.1. Set up Packet Capture on the WAN interface

Navigate to Diagnostics → Packet Capture and configure it as shown below:

<p align="center">
  <img width="600" alt="Packet Capture configuration" src="https://github.com/user-attachments/assets/d35d1a7c-a2ef-46e4-8717-1a05763f4a8d" />
  <br>
  <em>Figure 5: Packet Capture configuration</em>
</p>

**Overview of the main Packet Capture options:**

| Option | Description |
|------------|---------|
| Capture Options | Selects the interface to capture on. The WAN interface (em0) is selected in this lab. |
| Promiscuous Mode | Captures all packets visible on the interface |
| Max number of packets to capture | Maximum number of packets to capture |
| Name Lookup | Resolves DNS names, port names, and MAC addresses when displaying packets |
| HOST IP ADDRESS OR SUBNET | Filters by source/destination IP address or subnet |

#### 4.2. Generate traffic from Windows Server 2012 and collect the capture

- On pfSense, scroll to the bottom of the page and click Start.
- Immediately afterward, run the following command in CMD on the Windows Server:

```bash
ping -n 4 -l 100 192.168.10.10
```

<p align="center">
  <img width="677" height="340" alt="Ping from Windows Server 2012 to 192.168.10.10" src="https://github.com/user-attachments/assets/8684edfd-b3e2-42d1-9f99-2687e9665f99" />
  <br>
  <em>Figure 6: Ping command from Windows Server 2012 to 192.168.10.10</em>
</p>

**Ping command breakdown:**

| Component | Description |
|------------|---------|
| `ping` | Tests network connectivity to the target host using ICMP |
| `-n 4` | Sends 4 ICMP packets |
| `-l 100` | Sets the data payload of each ICMP packet to 100 bytes |
| `192.168.10.10` | Destination IP address under test |

- After the ping completes, return to pfSense and click Stop (keep the capture under 15 seconds), then click Download Capture.
- Open the file in Wireshark and enter the following display filter:

```bash
icmp && frame.len == 142
```

**Frame length of 142 bytes = 14 (Ethernet) + 20 (IP) + 8 (ICMP) + 100 (data).**

<p align="center">
  <img width="1619" height="164" alt="Wireshark filter for 142-byte ICMP frames" src="https://github.com/user-attachments/assets/b4b0b0a0-83aa-4166-b683-1176d7c3e797" />
  <br>
  <em>Figure 7: Filtering ICMP packets with a frame length of 142 bytes in Wireshark</em>
</p>

#### 4.3. Inspect the State table, original addresses, and connection status

- On pfSense, navigate to Diagnostics → States → States and apply the filter settings shown below:

<p align="center">
  <img width="1085" height="572" alt="ICMP state entries before and after NAT" src="https://github.com/user-attachments/assets/1a356e02-3af8-4f7d-bc3d-74d97a82fc37" />
  <br>
  <em>Figure 8: Two ICMP state entries: pre-NAT (LAN) and post-NAT (WAN)</em>
</p>

#### Interpreting the States table (Diagnostics → States)

**Filters:** Interface `all`, Filter expression `192.168.20.2` (shows only the Windows Server's states). Do not click **Kill States**, as it terminates active connections.

| Column | Description |
|-----|-----------|
| Interface | NAT-translated traffic appears as two entries: LAN (pre-NAT) and WAN (post-NAT) |
| Source (Original Source) → Destination | The post-NAT address is shown first; the original address appears in parentheses |
| State | `ESTABLISHED` = handshake completed, traffic flowing in both directions; `SINGLE:NO_TRAFFIC` = traffic in one direction only; `0:0` = ICMP |
| Packets / Bytes | Packet and byte counts in `outbound / inbound` format |

---

| No. | Interface | Flow | Analysis |
|-----|-----------|-------|-----------|
| 1 | LAN | `tcp 192.168.20.2:49170 → 192.168.20.1:80` | The Windows Server accessing the pfSense management interface; internal traffic |
| 2 | LAN | `udp 192.168.20.2:137 → 192.168.20.255:137` | NetBIOS broadcast with no reply (3 / 0) |
| 3 | LAN | `icmp 192.168.20.2:1 → 192.168.10.10:8` | Ping packet before NAT |
| 4 | WAN | `icmp 192.168.10.1:14119 (192.168.20.2:1) → 192.168.10.10:8` | Ping packet after NAT: the source is translated to the WAN IP; the original address is shown in parentheses |

**For ICMP, the number after the colon is not a port:** `:1` and `:14119` are ICMP IDs (pfSense rewrites the ID to distinguish between ping flows), and `:8` is type 8 = Echo Request.

## IV. Commands and Tools Used for Testing

### 1. Testing, scanning, and verifying the status of the ports permitted by the firewall

#### 1.1. Scan the permitted ports from Kali Linux

Use Nmap to verify the reachability and status of the ports that the firewall is configured to allow.

```bash
nmap -Pn -p 21,80,443,445,3389,5985 192.168.10.1
```

**Nmap command breakdown:**

| Component | Description |
|------------|---------|
| `nmap` | Port scanning and service discovery tool |
| `-Pn` | Skips the host discovery phase and treats all target IPs as online |
| `-p 21,80,443,445,3389,5985` | Scans only the 6 ports in the `Ports_Test` alias: FTP (21), HTTP (80), HTTPS (443), SMB (445), RDP (3389), WinRM (5985) |
| `192.168.10.1` | WAN IP of pfSense |

<p align="center">
  <img width="600" alt="Port scan with Nmap" src="https://github.com/user-attachments/assets/4b14cbc1-136b-4483-b1a7-ce6484958b2b" />
  <br>
  <em>Figure 9: Port scan command</em>
</p>

#### 1.2. Scan a port outside the permitted list from Kali Linux

Use Nmap to test and verify that the firewall blocks access to ports that are not permitted.

```bash
nmap -Pn -p 3306 192.168.10.1
```

`-p 3306` scans only the MySQL port. This port is not included in `Ports_Test`, so it must be blocked.

## V. Results

### Permitted ports and ports detected outside the alias

| Screenshot | Description |
|---|---|
| <img width="1136" height="205" alt="Firewall rule allowing Kali Linux" src="https://github.com/user-attachments/assets/213541a2-c1d9-409c-9947-33d2d054cf0c" /> | pfSense firewall configuration allowing traffic from the Kali Linux IP address to the authorized ports. |
| <img width="1136" height="66" alt="Firewall log blocking port 3306" src="https://github.com/user-attachments/assets/1c086e8f-3e36-4375-9bdf-18d6c3e676f7" /> | pfSense firewall log showing a block for a port not included in `Ports_Test`. |
| <img width="1141" height="167" alt="Suricata alert on port 3306" src="https://github.com/user-attachments/assets/a7efdd7c-a442-49ee-99f3-6e3d71d101ac" /> | Suricata detecting anomalous network traffic on port 3306, which is not among the permitted aliases. |

## VI. Expected Results

- Ports in `Ports_Test` respond as `open` when a real service is running on the Windows Server.
- Ports outside the list (e.g., 3306) are blocked, and the log records a **Block** by the WAN default-deny rule.
- The firewall log confirms that NAT correctly translates the destination address from the WAN IP to the actual LAN IP of the Windows Server.
- Outbound traffic (ICMP ping from the Windows Server) has its source translated to `192.168.10.1`, and return packets are translated back to `192.168.20.2`.
