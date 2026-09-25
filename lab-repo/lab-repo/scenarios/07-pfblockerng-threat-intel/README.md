# 07 — pfBlockerNG: Reputation / GeoIP Threat Blocking

## Mục tiêu
Bổ sung lớp phòng thủ thứ 2 song song với Suricata: chặn theo danh tiếng IP đã biết trước (reputation-based), khác với Suricata vốn chặn theo hành vi/signature.

## Điều kiện tiên quyết
- pfBlockerNG-devel đã cài
- (Nếu không có Internet thật ổn định cho pfSense) dùng Custom List thay cho feed online

## Cấu hình

### Phương án A — dùng feed thật (ET_Block, Spamhaus DROP...)
Firewall → pfBlockerNG → IPv4 tab → bật các nhóm feed sẵn có, Action = Deny → Update → Force Update.

### Phương án B — Custom List (an toàn cho lab kín)
1. Firewall → Aliases → tạo alias IP `Malicious_Demo` chứa IP Kali (192.168.10.50).
2. Firewall → pfBlockerNG → IPv4 → Add nhóm mới, Source = Custom List, dán alias trên, Action = Deny.

## Các bước thực hiện

```bash
ping 192.168.10.1   # từ Kali, sau khi pfBlockerNG đã Reload
```

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/07-pfblockerng-ipv4-config.png` | Cấu hình nhóm IPv4 |
| `screenshots/07-ping-blocked-immediately.png` | Ping bị chặn ngay gói đầu tiên |
| `screenshots/07-pfblockerng-alerts.png` | pfBlockerNG → Alerts — log ghi nhận IP bị chặn |

## Kết quả mong đợi
- Khác với Suricata (cần ít nhất 1 alert trước mới chặn), pfBlockerNG chặn **ngay từ gói đầu tiên** vì dựa vào danh sách đã biết trước, không cần chờ phát hiện hành vi.
