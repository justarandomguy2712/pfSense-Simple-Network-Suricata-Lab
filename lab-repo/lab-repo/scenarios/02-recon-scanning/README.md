# 02 — Reconnaissance & Scan Detection

## Mục tiêu
Minh hoạ 2 cơ chế phát hiện khác nhau của Suricata: **content-based** (khớp payload cụ thể, chỉ cần 1 request) và **behavior-based** (cần đủ số lượng kết nối trong 1 khoảng thời gian mới trigger).

## Điều kiện tiên quyết
- Suricata đã cài trên pfSense, giám sát interface WAN
- Category đã bật: `emerging-scan.rules`, `emerging-info.rules`, `emerging-policy.rules`

## Các bước thực hiện

Từ Kali:

```bash
# 1. Quét cơ bản — thường KHÔNG đủ để trigger rule (chỉ 1 SYN/port, không đạt threshold)
nmap -Pn -sS -p 21,80,443,445,3389,5985 192.168.10.1

# 2. Quét toàn dải port, tốc độ cao — đủ mật độ để trigger rule hành vi (ET SCAN)
nmap -Pn -sS -p 1-65535 --min-rate 1000 192.168.10.1

# 3. Scan hành vi tập trung vào 1 dịch vụ cụ thể (RDP) bằng hping3 — mô phỏng scan/bruteforce connection rate
sudo hping3 -S -p 3389 -c 500 --fast 192.168.10.1

# 4. Banner grabbing / service detection
nmap -sV -p 21,80,443,445,3389 192.168.10.1
```

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/02-suricata-alert-scan-vnc.png` | ET SCAN Potential VNC Scan |
| `screenshots/02-suricata-alert-scan-mysql.png` | ET SCAN Suspicious inbound to mySQL port |
| `screenshots/02-suricata-alert-rdp-behavioral.png` | ET SCAN Behavioral Unusually fast Terminal Server Traffic |

## Kết quả mong đợi
- Bước 1 (quét vài port, tốc độ thường) **thường không tạo alert** — minh chứng Suricata không có bộ dò port-scan tổng quát như Snort preprocessor.
- Bước 2 và 3 (đủ mật độ/tần suất) **tạo alert rõ ràng**, xác nhận rule hành vi hoạt động đúng theo threshold.
