# Các Bước Dựng Lab

Toàn bộ hệ thống được dựng trên GNS3 (chạy trong VMware Workstation), gồm 4 node: R1 (Cisco router, giả lập ISP), pfSense (NGFW), Kali Linux (attacker), Windows Server 2012 (target server).

## Sơ đồ topology

```
NAT1 (Internet giả lập)
   |
  R1 (f0/0 = NAT1, f1/0 = 192.168.10.10/24)
   |
Switch1 (192.168.10.0/24)
   |         |
pfSense    Kali Linux
(WAN)      (192.168.10.50/24)
   |
LAN (192.168.20.0/24)
   |
WindowsServer2012
```
## Hình ảnh minh họa bài lab: 

<img width="1131" height="480" alt="Screenshot 2026-09-10 205445" src="https://github.com/user-attachments/assets/14183846-9f5f-499c-9309-b537b28f2b6a" />

## Bảng địa chỉ IP

| Thiết bị | Interface | IP |
|---|---|---|
| R1 | Fa1/0 | 192.168.10.10/24 |
| pfSense | WAN (em0) | 192.168.10.1/24 |
| pfSense | LAN (em1) | 192.168.20.1/24 |
| Kali Linux | eth0 | 192.168.10.50/24 |
| Windows Server 2012 | eth0 | 192.168.20.2/24 |



## Các bước dựng lab

1. Tạo topology GNS3 theo đúng sơ đồ trên (R1 → Switch1 → pfSense WAN + Kali; pfSense LAN → WinServer).
2. Cấu hình R1: đặt IP tĩnh Fa1/0, NAT overload ra NAT1 (nếu cần Internet thật cho pfSense).
3. Cấu hình pfSense WAN: IP tĩnh 192.168.10.1/24, **bỏ tick** "Block private networks" và "Block bogon networks" (bắt buộc vì mạng lab dùng dải private làm WAN).
4. Đặt IP tĩnh cho Kali.
5. Windows Server 2012: đặt IP tĩnh, cài IIS (Web-Server role + FTP Service), bật Remote Desktop.
6. NAT Port Forward trên pfSense: dùng Alias gộp các port cần expose (21, 80, 443, 445, 3389, 5985) trỏ vào IP LAN của WinServer.


