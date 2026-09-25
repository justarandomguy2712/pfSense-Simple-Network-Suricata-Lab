# 03 — Web Application Attacks

## Mục tiêu
Kiểm chứng khả năng phát hiện tấn công ở tầng ứng dụng (HTTP/HTTPS) của Suricata — path traversal, SQL injection, XSS — và đối chiếu với phản hồi thực tế của IIS.

## Điều kiện tiên quyết
- IIS đã cài trên WinServer2012 (Web-Server role), có bind cả HTTP (80) và HTTPS (443, self-signed cert)
- Category Suricata đã bật: `emerging-web_server.rules`, `emerging-sql.rules`
- Đã bật "Enable HTTP Log" trên Interface Settings

## Các bước thực hiện

Từ Kali:

```bash
# 1. Web vulnerability scan tổng quát
nikto -h http://192.168.10.1

# 2. Path traversal — cố đọc /etc/passwd (chỉ cần 1 lệnh, không cần khối lượng lớn)
nmap -p 80 --script http-passwd 192.168.10.1

# 3. SQL Injection content-based
nmap -p 80 --script http-sql-injection 192.168.10.1

# 4. Reflected XSS thủ công (cần trang demo.html đã upload lên IIS — xem hướng dẫn trong docs)
firefox "https://192.168.10.1/demo.html?<script>alert('Bi Tan Cong!')</script>"

# 5. Volume-based test (nếu muốn minh hoạ khối lượng lớn request)
for i in $(seq 1 1000); do curl -s -o /dev/null "http://192.168.10.1/../../../etc/passwd"; done
```

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/03-nikto-scan-result.png` | Kết quả nikto trên terminal |
| `screenshots/03-suricata-alert-passwd-uri.png` | ET WEB_SERVER /etc/passwd Detected in URI |
| `screenshots/03-suricata-alert-nmap-nse-useragent.png` | ET SCAN Nmap Scripting Engine User-Agent Detected |
| `screenshots/03-xss-popup.png` | Ảnh chụp popup alert() hiện trên trình duyệt |
| `screenshots/03-iis-403-response.png` | Log IIS trả về 403 Forbidden cho path traversal |

## Kết quả mong đợi
- Mỗi loại tấn công chỉ cần **1 request đúng payload** đã đủ trigger alert (khác hẳn scan hành vi ở scenario 02) — do rule dựa vào nội dung request (content-based), không cần khối lượng.
- HTTPS (443): payload giống hệt HTTP nhưng **Suricata không đọc được nội dung** do mã hoá TLS — ghi chú rõ giới hạn này trong báo cáo.
