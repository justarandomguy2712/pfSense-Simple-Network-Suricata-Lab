# Dựng lab từ đầu

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
4. Đặt IP tĩnh cho Kali (qua `nmcli` hoặc `/etc/network/interfaces`, không dùng lệnh `ip addr add` tạm thời vì sẽ mất sau reboot).
5. Windows Server 2012: đặt IP tĩnh, cài IIS (Web-Server role + FTP Service), bật Remote Desktop.
6. NAT Port Forward trên pfSense: dùng Alias gộp các port cần expose (21, 80, 443, 445, 3389, 5985) trỏ vào IP LAN của WinServer.

Xem chi tiết từng bước, kèm troubleshooting thực tế đã gặp (nested-NAT DNS failure, GNS3 vmnet exhaustion, đĩa VM bị mất...), tại [checklist-dung-lai-lab.md](checklist-dung-lai-lab.md).

## Lưu ý thiết kế

- Kali đặt ở phía **WAN** (không phải 1 VLAN/segment nội bộ riêng) để mô phỏng đúng kịch bản **attacker từ Internet**, không phải lateral-movement nội bộ.
- Không dùng VLAN — dùng interface WAN/LAN đơn giản để tránh các vấn đề DHCP/trunk gặp phải khi thử nghiệm VLAN ban đầu.
