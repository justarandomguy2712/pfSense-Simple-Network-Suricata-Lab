# 01 — Perimeter Defense & NAT Port Forwarding

## Mục tiêu
Chứng minh pfSense hoạt động đúng vai trò tường lửa biên: chỉ expose đúng các dịch vụ được chỉ định ra "Internet" (WAN), mọi port khác bị chặn mặc định, và toàn bộ traffic (pass/block) đều được ghi log.

## Điều kiện tiên quyết
- Lab đã dựng xong theo [../../docs/01-lab-setup.md](../../docs/01-lab-setup.md)
- Đã tạo Alias `Lab_Ports` (21, 80, 443, 445, 3389, 5985) và NAT Port Forward trỏ vào WinServer2012
- Đã tick "Log packets that are handled by this rule" trên rule WAN tương ứng

## Các bước thực hiện

Từ Kali:

```bash
# Quét các port đã forward — xác nhận port có service đang chạy hiện "open"
nmap -Pn -p 21,80,443,445,3389,5985 192.168.10.1

# Quét 1 port KHÔNG nằm trong danh sách forward — xác nhận bị chặn
nmap -Pn -p 3306 192.168.10.1
```

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/01-nmap-open-ports.png` | Kết quả nmap các port đã forward — hiện `open` |
| `screenshots/01-firewall-log-pass.png` | pfSense Firewall log — dòng Pass, đích đúng IP LAN của WinServer |
| `screenshots/01-firewall-log-block.png` | pfSense Firewall log — dòng Block cho port không nằm trong Lab_Ports |

## Kết quả mong đợi
- Các port trong `Lab_Ports` trả lời `open` khi có service thật chạy trên WinServer.
- Port ngoài danh sách (VD 3306) bị chặn, log ghi nhận **Block** bởi default-deny rule của WAN.
- Firewall log xác nhận NAT đã dịch đúng địa chỉ đích từ IP WAN sang IP LAN thật của WinServer.
