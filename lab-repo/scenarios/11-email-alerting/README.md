# 12 — Email cảnh báo tự động

## Mục tiêu
Tự động gửi email khi Suricata phát hiện alert mức độ nghiêm trọng, không cần trực theo dõi log thủ công.

## Điều kiện để thực hiện bài lab
- System → Advanced → Notifications → SMTP đã cấu hình và test thành công
  - Gmail: SMTP server `smtp.gmail.com`, **port 465**, Enable SMTP over SSL/TLS = ON, dùng **App Password** (không dùng mật khẩu Gmail thường)
  - Nếu gặp lỗi "No route to host" dù port đã đúng: kiểm tra xung đột IPv6 — tắt "Allow IPv6" tại System → Advanced → Networking
- Suricata EVE Output Type = **FILE** (không phải SYSLOG) — bắt buộc để có file `eve.json` cho script đọc
- Cron package đã cài
### Tạo Gmail App Password

App Password là mật khẩu 16 ký tự Google cấp riêng cho pfSense, dùng thay mật khẩu Gmail thường. Yêu cầu: đã bật **Xác minh 2 bước**.

| Bước | Thao tác | Kiểm tra |
|------|----------|----------|
| 1 | Mở https://myaccount.google.com/apppasswords | Thấy ô nhập tên ứng dụng |
| 2 | Nhập tên `pfSense-Suricata`, bấm **Tạo** | Hiện mật khẩu 16 ký tự |
| 3 | Sao chép, xóa hết khoảng trắng (chỉ hiện một lần) | Chuỗi dài đúng 16 ký tự |
| 4 | pfSense: **System → Advanced → Notifications → SMTP**, dán vào ô password, **Save** rồi **Test SMTP Settings** | Nhận được email thử |




## Cài đặt

1. Xác định đúng đường dẫn eve.json: `ls /var/log/suricata/`
2. Sửa dòng `$eve_file` trong [suricata_mailer.php](suricata_mailer.php) theo đúng tên thư mục thật.
3. Copy file này lên pfSense qua Diagnostics → Edit File, lưu tại `/root/suricata_mailer.php`.
4. Services → Cron → Add: Minute `*/2`, Command `/usr/local/bin/php -f /root/suricata_mailer.php`.

## Các bước kiểm tra

```bash
# Từ Kali — tạo 1 alert nghiêm trọng
nmap -p 80 --script http-sql-injection 192.168.10.1
```

Đợi tối đa 2 phút (chu kỳ cron) — **không** chạy tay lệnh php, để xác nhận cron tự hoạt động.

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/12-smtp-test-success.png` | Test SMTP Settings thành công |
| `screenshots/12-cron-job-config.png` | Cấu hình Cron job |
| `screenshots/12-email-received.png` | Email cảnh báo nhận được trong hộp thư |

## Kết quả mong đợi
- Email tự động về trong vòng 2 phút sau khi có alert nghiêm trọng, không cần thao tác thủ công.
