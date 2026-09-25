# Checklist dựng lại lab pfSense – Suricata – Kali – WinServer2012

> Dùng để dựng lại toàn bộ mô hình sau sự cố mất Disk 2 của GNS3 VM. Làm theo đúng thứ tự từ trên xuống, tick ✅ sau mỗi bước đã xác minh.

---

## 0. Cài lại GNS3 VM từ đầu (quyết định: bỏ VM cũ, dựng mới hoàn toàn)

- [x] Đã Remove Hard Disk 2 hỏng khỏi GNS3 VM cũ.
- [x] Quyết định: **không cố cứu VM cũ**, tải và dựng lại GNS3 VM mới hoàn toàn.

### 0.1. Xoá/gỡ VM cũ (dọn sạch trước khi làm mới)

- [ ] Trong VMware Workstation → chuột phải **GNS3 VM** cũ → **Remove from Library** (chỉ gỡ khỏi danh sách, chưa xoá file) hoặc **Delete from Disk** nếu chắc chắn không cần gì trong đó nữa.
- [ ] Trong GNS3 Desktop Client → Edit → Preferences → **GNS3 VM** → bỏ tick "Enable the GNS3 VM" tạm thời (tránh GNS3 cố kết nối tới VM không còn tồn tại trong lúc bạn cài lại).

### 0.2. Tải GNS3 VM (.ova) — đúng bản khớp với GNS3 Desktop Client đang dùng

- [ ] Vào https://gns3.com/software/download-vm → chọn mục dành cho **VMware Workstation/Player** → tải file `.zip` (giải nén ra sẽ có file `GNS3 VM.ova`).
- [ ] Kiểm tra version GNS3 VM tải về **khớp với version GNS3 Desktop Client** đang cài trên máy bạn (Help → About trong GNS3 Client) — lệch version lớn dễ gây lỗi kết nối API giữa Client và VM.
  - Nếu GNS3 Desktop Client cũng đã cũ, cân nhắc tải bản GNS3 mới nhất luôn tại https://gns3.com/software/download để đồng bộ cả 2.

### 0.3. Import vào VMware Workstation

- [ ] Mở VMware Workstation → **File → Open** → trỏ tới file `GNS3 VM.ova` vừa giải nén.
- [ ] Đặt tên VM (mặc định "GNS3 VM" là được) → chọn nơi lưu ổ đĩa (ưu tiên ổ SSD còn nhiều dung lượng trống, tối thiểu 50-100GB) → **Import**.
- [ ] Đợi quá trình import hoàn tất (vài phút tuỳ tốc độ đĩa).

### 0.4. Cấu hình phần cứng cho GNS3 VM

Chuột phải VM vừa import → **Edit virtual machine settings**:

| Thông số | Giá trị khuyến nghị |
|---|---|
| Memory | Tối thiểu 4GB, khuyến nghị **8GB** nếu máy thật đủ RAM (chạy đồng thời pfSense + WinServer2012 + Kali khá nặng) |
| Processors | 2-4 core |
| Network Adapter | **Host-only** (giữ nguyên mặc định — đây là kênh GNS3 Client giao tiếp với VM) |
| Network Adapter 2 | **NAT** (giữ nguyên — cho GNS3 VM ra Internet thật, cần cho Suricata/pfBlockerNG tải rule sau này) |
| Hard Disk | Chỉ dùng **1 ổ duy nhất** lần này — không thêm ổ 2 nữa (xem lưu ý tránh sự cố ở cuối file). Nếu dung lượng mặc định không đủ, **mở rộng ngay ổ 1 có sẵn** (Edit → Hard Disk → Expand) thay vì thêm ổ mới, để tránh lặp lại kiểu lỗi LVM 2 ổ như lần trước. |

- [ ] Nếu vừa Expand ổ đĩa: sau khi Power On, vào bên trong GNS3 VM chạy lệnh sau để mở rộng partition/filesystem theo đúng dung lượng mới:
```
sudo growpart /dev/sda 1
sudo resize2fs /dev/sda1
```
(tên `/dev/sda1` có thể khác tuỳ bản GNS3 VM — kiểm tra bằng `lsblk` trước).

### 0.5. Power On và lấy IP

- [ ] **Power on this virtual machine**.
- [ ] Đợi tới màn hình console hiện thông tin dạng:
```
Server version: x.x.x
...
Management IP: 192.168.x.x (Host-only network)
```
- [ ] Ghi lại IP đó (đây là IP để GNS3 Desktop Client kết nối tới).

- ✅ *Kiểm tra*: từ máy thật, mở CMD → `ping <IP vừa ghi>` → phải thông.

### 0.6. Kết nối GNS3 Desktop Client với GNS3 VM mới

- [ ] Mở GNS3 Desktop Client → Edit → Preferences → **GNS3 VM**.
- [ ] Tick lại **"Enable the GNS3 VM"**.
- [ ] Virtualization Engine: chọn **VMware**.
- [ ] VM: chọn đúng tên VM vừa import.
- [ ] Bấm **Apply** → **OK**.
- [ ] Tạo project mới bất kỳ (VD "test-connection") → kéo thử 1 node đơn giản (VD Cloud) vào canvas.

- ✅ *Kiểm tra*: góc dưới bên phải GNS3 Client hiện chấm xanh "GNS3 VM (x.x.x.x)" — xác nhận kết nối thành công. Nếu chấm đỏ/không kết nối được, thử: Edit → Preferences → GNS3 VM → **Test Settings**, xem thông báo lỗi cụ thể.

### 0.7. Thêm lại các Node Template đã mất (R1, pfSense, Kali, WinServer2012)

Vì toàn bộ ổ đĩa cũ (chứa image các node) đã mất theo Disk 2, cần cấu hình lại từ đầu:

| Node | Cách thêm lại |
|---|---|
| **R1 (Cisco Router)** | Edit → Preferences → **Dynamips/IOS routers** → New → trỏ tới file `.bin` IOS image (nếu bạn còn lưu file này ở nơi khác ngoài GNS3 VM, VD ổ D máy thật — nếu mất luôn thì cần tìm lại nguồn IOS image cũ) |
| **pfSense** | Edit → Preferences → **QEMU VMs** → New → hoặc dùng GNS3 Marketplace: File → Import appliance → tìm "pfSense" → làm theo wizard (cần file ISO pfSense — tải lại tại https://www.pfsense.org/download nếu không còn file cũ) |
| **Kali Linux** | File → Import appliance → tìm "Kali Linux" trong Marketplace → GNS3 có thể tự tải ISO hoặc yêu cầu bạn cung cấp file đã tải sẵn |
| **WindowsServer2012** | Edit → Preferences → **QEMU VMs** → New → cần file ISO Windows Server 2012 (tìm lại từ nguồn bạn dùng ban đầu, VD Microsoft Evaluation Center nếu dùng bản dùng thử) |

- ✅ *Kiểm tra sau khi thêm mỗi template*: kéo thử node đó vào 1 project test → Start → phải boot lên bình thường trước khi dùng cho lab thật.

- [ ] Sau khi cả 4 template đã sẵn sàng và test boot OK → **chụp Snapshot VMware ngay** cho GNS3 VM (đặt tên rõ, VD "GNS3VM-templates-ready") — đây là điểm khôi phục quan trọng nhất, làm xong bước này gần như không còn gì để mất nếu sự cố lặp lại.

---

## 1. Dựng lại Topology trong GNS3

| Kết nối | Chi tiết |
|---|---|
| R1 (f0/0) → NAT1 | Cloud NAT có sẵn của GNS3, IP DHCP tự động (dải 192.168.42.0/24) |
| R1 (f1/0) → Switch1 | Link mới |
| Switch1 → pfSense-1 (WAN, e0) | |
| Switch1 → KaliLinux-1 (e0) | |
| pfSense-1 (LAN, e1) → WindowsServer2012-1 (e0) | Giữ nguyên như model gốc |

- [ ] Không dùng OPT1/VLAN10/VLAN20 — mô hình cuối cùng chỉ có WAN + LAN trên pfSense.
- [ ] Không dùng R2/NAT2 (đã bỏ từ đầu do tách biệt Kali khỏi WinServer).

---

## 2. Cấu hình R1

```
enable
configure terminal
interface fa1/0
 ip address 192.168.10.10 255.255.255.0
 no shutdown
end
write memory
```

- ✅ *Kiểm tra*: `sh ip int bri` → Fa1/0 lên `up/up`, IP `192.168.10.10`.

### Dọn route thừa (nếu import lại config cũ có dính)
```
conf t
no ip route 192.168.20.0 255.255.255.0 192.168.10.1
no ip route 192.168.30.0 255.255.255.0 192.168.10.1
no ip route 192.168.40.0 255.255.255.0 192.168.10.1
end
```
- ✅ *Kiểm tra*: `show ip route static` → không còn dòng nào liên quan 20/30/40.0/24.

### (Tuỳ chọn) Cho pfSense ra Internet thật — cần cho Suricata tải ET Open + pfBlockerNG tải feed
```
conf t
access-list 1 permit 192.168.10.0 0.0.0.255
interface fa0/0
 ip nat outside
interface fa1/0
 ip nat inside
exit
ip nat inside source list 1 interface fa0/0 overload
end
write memory
```
- ✅ *Kiểm tra*: từ pfSense (Diagnostics → Ping) → ping `8.8.8.8` → phải thông.

---

## 3. Cấu hình Kali — IP tĩnh, không mất sau reboot

**Xác định cơ chế mạng đang dùng:**
```
systemctl is-active NetworkManager
```

**Nếu `active` (NetworkManager):**
```
sudo nmcli connection modify "Wired connection 1" ipv4.addresses 192.168.10.50/24
sudo nmcli connection modify "Wired connection 1" ipv4.gateway 192.168.10.10
sudo nmcli connection modify "Wired connection 1" ipv4.dns "8.8.8.8"
sudo nmcli connection modify "Wired connection 1" ipv4.method manual
sudo nmcli connection up "Wired connection 1"
```

**Nếu dùng ifupdown (`/etc/network/interfaces`):**
```
auto eth0
iface eth0 inet static
    address 192.168.10.50
    netmask 255.255.255.0
    gateway 192.168.10.10
    dns-nameservers 8.8.8.8
```
Sau đó: `sudo systemctl restart networking`

- ✅ *Kiểm tra*: `reboot` → `ip a` và `ip route` phải tự có IP/gateway đúng, không cần gõ tay lại.

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
