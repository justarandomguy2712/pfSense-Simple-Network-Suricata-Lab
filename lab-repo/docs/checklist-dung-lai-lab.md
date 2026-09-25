# Các bước chi tiết lại lab pfSense – Suricata – Kali – WinServer2012


### 1.1. Tải GNS3 VM (.ova) — đúng bản khớp với GNS3 Desktop Client đang dùng

Vào https://gns3.com/software/download-vm → chọn mục dành cho **VMware Workstation/Player**.
Kiểm tra version GNS3 VM tải về **khớp với version GNS3 Desktop Client** đang cài trên máy.
**NOTE**: Nếu GNS3 Desktop Client cũng đã cũ, cân nhắc tải bản GNS3 mới nhất luôn tại https://gns3.com/software/download.

### 1.2. Import vào VMware Workstation

Mở VMware Workstation → **File → Open** → trỏ tới file vừa giải nén.
Đặt tên VM (nên đặt là GNS3 VM) → chọn nơi lưu ổ đĩa (ưu tiên ổ SSD còn nhiều dung lượng trống, tối thiểu 50-100GB) → **Import**.
Đợi quá trình import hoàn tất (vài phút tuỳ tốc độ đĩa).

### 1.3. Cấu hình phần cứng cho GNS3 VM

Chuột phải VM vừa import → **Edit virtual machine settings**:

| Thông số | Giá trị khuyến nghị |
|---|---|
| Memory | Tối thiểu 4GB, khuyến nghị **8GB** nếu máy thật đủ RAM (chạy đồng thời pfSense + WinServer2012 + Kali khá nặng) |
| Processors | 2-4 core |
| Network Adapter | **Host-only** (giữ nguyên mặc định — đây là kênh GNS3 Client giao tiếp với VM) |
| Network Adapter 2 | **NAT** (giữ nguyên — cho GNS3 VM ra Internet thật, cần thiết cho Suricata/pfBlockerNG tải rule sau này) |
| Hard Disk | Giữ nguyên mặc định theo VMware |

### 1.4. Power On và lấy IP

**Power on this virtual machine**.
Đợi tới màn hình console hiện thông tin dạng:
```
Server version: x.x.x
...
Management IP: 192.168.x.x (Host-only network)
```
- ✅ *Kiểm tra*: từ máy thật, mở CMD → `ping <IP của GNS3>` → phải thông.

### 1.5. Kết nối GNS3 Desktop Client với GNS3 VM mới

Mở GNS3 Desktop Client → Edit → Preferences → **GNS3 VM**.
Tick lại **"Enable the GNS3 VM"**.
Virtualization Engine: chọn **VMware**.
VM: chọn đúng tên VM vừa import.
Bấm **Apply** → **OK**.
Tạo project mới bất kỳ → kéo thử 1 node đơn giản (VD: Cloud) vào để Test thử.

- ✅ *Kiểm tra*: góc dưới bên phải GNS3 Client hiện chấm xanh "GNS3 VM (x.x.x.x)" — xác nhận kết nối thành công. Nếu chấm đỏ/không kết nối được, thử: Edit → Preferences → GNS3 VM → **Test Settings**, xem thông báo lỗi cụ thể.

### 1.6. Thêm lại các Node Template đã mất (R1, pfSense, Kali, WinServer2012)

Vì toàn bộ ổ đĩa cũ (chứa image các node) đã mất theo Disk 2, cần cấu hình lại từ đầu:

| Node | Cách thêm lại |
|---|---|
| **R1 (Cisco Router)** | Edit → Preferences → **Dynamips/IOS routers** → New → trỏ tới file `.bin` IOS image (Tham khảo file IMG Cisco Router tại https://github.com/hegdepavankumar/Cisco-Images-for-GNS3-and-EVE-NG) |
| **pfSense** |  Edit → Preferences → **VMware** → New → Add file ISO pfSense tại https://www.pfsense.org/download/  |
| **Kali Linux** | Edit → Preferences → **VMware** → New → Add file ISO Kali Linux  |
| **WindowsServer2012** | Edit → Preferences → **VMware** → New → Add file ISO Windows Server 2012 |

- ✅ *Kiểm tra sau khi thêm mỗi template*: kéo thử node đó vào 1 project test → Start → phải boot lên bình thường trước khi dùng cho lab thật.

Sau khi cả 4 template đã sẵn sàng và test boot OK → **chụp Snapshot VMware ngay** cho GNS3 VM — đây là điểm khôi phục quan trọng nhất, làm xong bước này gần như không còn gì để mất nếu sự cố lặp lại.

---

## 1. Dựng lại Topology trong GNS3

| Kết nối | Chi tiết |
|---|---|
| R1 (f0/0) → NAT1 | Cloud NAT có sẵn của GNS3, IP DHCP tự động (dải 192.168.42.0/24) |
| R1 (f1/0) → Switch1 | Link mới |
| Switch1 → pfSense-1 (WAN, e0) | |
| Switch1 → KaliLinux-1 (e0) | |
| pfSense-1 (LAN, e1) → WindowsServer2012-1 (e0) | Giữ nguyên như model gốc |


## Hình ảnh về mô hình lab:

<img width="1131" height="480" alt="Screenshot 2026-09-10 205445" src="https://github.com/user-attachments/assets/ac1f4067-75b3-4208-879e-bb6d9d318571" />

---

## 2. Cấu hình R1

```
enable
configure terminal 
interface FastEthernet0/0
 ip address dhcp # Nhận ip động từ DHCP
 ip nat outside  # ip này sẽ trỏ ra ngoài Internet cho quá trình NAT
 no shutdown
exit
interface FastEthernet1/0
 ip address 192.168.10.10 255.255.255.0 # Gán ip tĩnh cho Gateway của mạng LAN
 ip nat inside                          # Khai báo cổng bên trong quá trình NAT
 no shutdown
exit
access-list 1 permit 192.168.10.0 0.0.0.255
# Tạo danh sách ACL cho phép dải mạng 192.168.10.0/24 đi qua (0.0.0.255 là wildcard mask)
ip nat inside source list 1 interface FastEthernet0/0 overload
# Biên dịch các IP nội bộ (thuộc access-list 1) sang IP của cổng FastEthernet0/0 khi ra Internet. Overload cho phép nhiều máy dùng chung 1 IP public (PAT).
end
write memory
```

- ✅ *Kiểm tra quá trình config bằng câu lệnh sau*: `sh ip int bri` → Fa1/0 lên `up/up`, IP `192.168.10.10`.


## 3. Cấu hình Kali — IP tĩnh, không mất sau reboot

**Xác định cơ chế mạng đang dùng:**
```
systemctl is-active NetworkManager
```

**Nếu `active` (NetworkManager):**
```
sudo nmcli connection modify "eth0" ipv4.addresses 192.168.10.50/24
sudo nmcli connection modify "etho" ipv4.gateway 192.168.10.10
sudo nmcli connection modify "eth0" ipv4.dns "8.8.8.8"
sudo nmcli connection modify "ethh0" ipv4.method manual
sudo nmcli connection up "eth0"
```

- ✅ *Kiểm tra*: `reboot` → `ip a` và `ip route` phải tự có IP/gateway đúng, không cần gõ tay lại, và kết quả sẽ như hình sau:



---

## 4. Cấu hình pfSense — WAN

- [ ] Interfaces → WAN → Static IPv4: `192.168.10.1/24`, Upstream Gateway: `WANGW – 192.168.10.10`.
- [ ] Cuộn xuống **Reserved Networks** → **bỏ tick** cả 2 ô:
  - ☐ Block private networks and loopback addresses
  - ☐ Block bogon networks
- [ ] Save → Apply Changes.

- ✅ *Kiểm tra*: từ Kali, `ping 192.168.10.10` (R1) phải thông. Ping `192.168.10.1` (WAN pfSense) mặc định **sẽ không thông** — bình thường, do default-deny của WAN (không phải lỗi).
- ✅ *Kiểm tra qua log*: Status → System Logs → Firewall → lọc `192.168.10.50` → phải thấy dòng **block** đúng thời điểm ping — xác nhận traffic đã tới pfSense, chỉ bị chặn đúng cơ chế.

---

## 5. pfSense — Alias + NAT Port Forward

### 5.1. Alias IP đích
Firewall → Aliases → tab IP → Add:
| Name | Type | Value |
|---|---|---|
| `WinServer2012` | Host(s) | IP LAN thật của WinServer (VD `192.168.20.2`) |

### 5.2. Alias Port (mỗi port 1 dòng riêng, dùng nút "+")
Firewall → Aliases → tab Port → Add, name `Lab_Ports`:
```
21
80
443
445
3389
5985
```

### 5.3. NAT Port Forward
Firewall → NAT → Port Forward → Add:
| Trường | Giá trị |
|---|---|
| Interface | WAN |
| Protocol | TCP |
| Destination | WAN address |
| Destination port range | Lab_Ports → Lab_Ports |
| Redirect target IP | WinServer2012 |
| Redirect target port | Lab_Ports |
| Filter rule association | Add associated filter rule |

- [ ] Mở firewall rule WAN vừa tự sinh → Edit → cuộn xuống **Extra Options** → tick **Log packets that are handled by this rule** → Save → Apply.

- ✅ *Kiểm tra*: `nmap -Pn -p 21,80,443,445,3389,5985 192.168.10.1` từ Kali → port có service chạy hiện `open`.
- ✅ *Kiểm tra log*: Status → System Logs → Firewall → dòng ✔ xanh, label "NAT", Dest = IP LAN thật của WinServer + đúng port.

---

## 6. pfSense — Suricata

### 6.1. Cài đặt & bật nguồn rule
- [ ] System → Package Manager → cài `Suricata`.
- [ ] Services → Suricata → **Global Settings** → tick ☑ **ET Open Ruleset** → Save.
- [ ] Tab **Updates** → **Force Update** → xác nhận log tải thành công (không lỗi timeout/connection).

- ✅ *Kiểm tra*: "Last Update" hiện ngày giờ thật, không phải "Never".
- ⚠️ Nếu lỗi kết nối → kiểm tra R1 đã NAT overload chưa (mục 2), hoặc `df -h` trên pfSense xem đủ dung lượng trống chưa (Diagnostics → Command Prompt).

### 6.2. Thêm interface giám sát
- [ ] Services → Suricata → Interfaces → Add → chọn **WAN**.

### 6.3. Bật category cần thiết
Interface WAN → tab **Categories**, tick:
- ☑ `emerging-scan.rules`
- ☑ `emerging-web_server.rules`
- ☑ `emerging-policy.rules`
- ☑ `emerging-info.rules`
- ☑ `emerging-ftp.rules`
- ☑ `emerging-sql.rules`

Save → **Restart** Suricata trên WAN (bắt buộc sau mỗi lần đổi category).

### 6.4. Bật log chi tiết (HTTP/TLS)
Interface WAN → tab **Interface Settings**:
- ☑ Enable HTTP Log
- ☑ Enable TLS Log

### 6.5. (Mô hình 2 — IPS chặn thật)
Interface WAN → tab **Interface Settings** → mục Block Settings:
- ☑ Block Offenders = ON
- Which IP to Block: `SRC`
- ☑ Kill States

Services → Suricata → **Global Settings** → mục **Remove Blocked Hosts Interval** → đặt `1 Hour` (tránh phải xoá tay khi test lại).

- ✅ *Kiểm tra IDS*: chạy `nikto -h http://192.168.10.1` → Suricata → Alerts phải có log (VD ET_WEB_SERVER `.htaccess`, `.htpasswd`, Tilde URI).
- ✅ *Kiểm tra IPS*: quét lần 2 cùng IP sau khi đã có alert lần 1 → bị chặn (kiểm tra tab **Blocks**, thấy IP Kali).

---

## 7. pfSense — pfBlockerNG (Mô hình 2)

- [ ] System → Package Manager → cài `pfBlockerNG-devel`.
- [ ] Firewall → pfBlockerNG → tab **Wizard** → chạy, chọn interface WAN, để mặc định.
- [ ] Firewall → pfBlockerNG → **Update** → Force Update.
- [ ] Tab **IPv4** → đảm bảo các nhóm (ET_Block, Spamhaus...) State = ON, Action = Deny.
- [ ] (Nếu không có Internet thật) Phương án demo: tạo alias IP `Malicious_Demo` chứa IP Kali → thêm nhóm Custom List trong pfBlockerNG trỏ tới alias này, Action Deny.

- ✅ *Kiểm tra*: ping từ Kali tới `192.168.10.1` bị chặn ngay từ gói đầu (không cần chờ alert như Suricata) → Firewall → pfBlockerNG → **Alerts** tab thấy log tương ứng.

---

## 8. WindowsServer2012

### 8.1. Mạng
- [ ] IP tĩnh `192.168.20.x/24`, gateway `192.168.20.1`.

### 8.2. IIS + FTP
```powershell
Install-WindowsFeature -Name Web-Server -IncludeManagementTools
```
- [ ] Server Manager → Add Roles → Web Server (IIS) → Role Services → tick **FTP Server** (FTP Service + FTP Extensibility).
- [ ] IIS Manager → Add FTP Site → port 21 → Basic Auth + cho phép Anonymous (demo).
- [ ] IIS Manager → Site → Bindings → Add `https` port 443 → tạo Self-Signed Certificate.

- ✅ *Kiểm tra*: `http://localhost` ra trang chào IIS. `wf.msc` → Inbound Rules → "World Wide Web Services", "FTP Server" đều Enabled.

### 8.3. RDP
- [ ] Server Manager → Local Server → Remote Desktop → **Allow remote connections**.
- [ ] (Tuỳ demo) System Properties → Remote → tạm bỏ tick NLA nếu muốn Hydra dễ brute-force hơn.

- ✅ *Kiểm tra*: `wf.msc` → nhóm "Remote Desktop" (2-3 rule) tự Enabled.

### 8.4. Audit Policy + Log
```
secpol.msc → Advanced Audit Policy Configuration → Logon/Logoff
 → Audit Logon: tick Success + Failure
 → Audit Account Lockout: tick Success
gpupdate /force
```
- [ ] Event Viewer → Security → Properties → Maximum log size = `196608 KB`, chọn **Overwrite events as needed**.

- ✅ *Kiểm tra*: `auditpol /get /subcategory:Logon` → hiện `Success and Failure`.

---

## 9. Kịch bản test từ Kali (chạy sau khi mọi thứ ở trên xong)

| Mục tiêu | Lệnh |
|---|---|
| Port scan cơ bản | `nmap -Pn -p 21,80,443,445,3389,5985 192.168.10.1` |
| Scan hành vi bất thường (đủ ngưỡng threshold) | `sudo hping3 -S -p 3389 -c 500 --fast 192.168.10.1` |
| Web vuln scan | `nikto -h http://192.168.10.1` |
| Directory traversal (content-based, 1 lệnh đã đủ) | `nmap -p 80 --script http-passwd 192.168.10.1` |
| SQLi content-based | `nmap -p 80 --script http-sql-injection 192.168.10.1` |
| Brute-force RDP | `hydra -l administrator -P /usr/share/wordlists/rockyou.txt rdp://192.168.10.1` |
| Brute-force FTP | `hydra -l administrator -P /usr/share/wordlists/rockyou.txt ftp://192.168.10.1` |
| Volume-based HTTP (nếu cần demo khối lượng lớn) | `ab -n 1000 -c 50 "http://192.168.10.1/index.php?id=1"` |

---

## 10. Nguồn log để đối chiếu khi phân tích (báo cáo)

| Nguồn | Xem ở đâu |
|---|---|
| pfSense Firewall (NAT pass/block) | Status → System Logs → Firewall |
| Suricata Alerts | Status → Suricata → Alerts (chọn interface WAN) |
| Suricata Blocks (IPS) | Status → Suricata → Blocks |
| pfBlockerNG Alerts | Firewall → pfBlockerNG → Alerts |
| Windows Event Viewer | Security log — Event ID 4625 (fail), 4624 (success), 4740 (lockout) |
| IIS log | `C:\inetpub\logs\LogFiles` |

---

## Ghi nhớ để tránh sự cố mất dữ liệu lần sau

- Không xoá file `.vmdk`/`.qcow2` trực tiếp qua File Explorer — luôn quản lý qua GNS3 GUI hoặc VMware "Manage Disks".
- Chụp Snapshot VMware trước mỗi thay đổi lớn (thêm ổ, cấu hình LVM...).
- Xoá project GNS3 không dùng qua GUI, không để chồng chất.
- Compact định kỳ ổ đĩa qcow2 khi VM tắt: `qemu-img convert -O qcow2 old.qcow2 new.qcow2`.
- Sau khi hoàn thành mỗi node, Export OVA lưu riêng làm bản backup.
