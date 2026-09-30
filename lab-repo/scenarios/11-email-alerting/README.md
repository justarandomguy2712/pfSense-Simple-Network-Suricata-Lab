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


## Các bước cấu hình đã thực hiện

1. Cấu hình SMTP Email trên pfSense để gửi email cảnh báo

<p align="center">
  <img width="600" alt="Cấu hình SMTP Gmail" src="https://github.com/user-attachments/assets/92338248-e116-4acb-9647-19c0bd111c39" />
  <br>
  <em>Hình 1: Cấu hình SMTP Gmail trên pfSense để gửi email cảnh báo.</em>
</p>

<p align="center">
  <img width="600" alt="Email cảnh báo Suricata" src="https://github.com/user-attachments/assets/b8201bab-af12-41f7-926f-dfdfa23adcce" />
  <br><br>
  <img width="700" alt="Email cảnh báo Suricata (chi tiết)" src="https://github.com/user-attachments/assets/5f4c9e08-e221-4115-989e-6aca06632c0e" />
  <br>
  <em>Hình 2 và 3: Email Test <code>suricata_mailer.php</code> được gửi về Gmail khi được cấu hình đúng.</em>
</p>
## 2. Bật EVE JSON Log

`eve.json` là log sự kiện của Suricata, mỗi sự kiện là một dòng JSON (IP nguồn/đích, signature, mức độ). Script `suricata_mailer.php` đọc file này bằng `json_decode` để gửi email cảnh báo.

### 2.1. Cấu hình EVE Output

1. Vào **Services → Suricata → Interfaces → Edit → EVE Output**.
2. Đặt **EVE Output Type = FILE**, bấm **Save**.

> Nếu chọn **SYSLOG**, file `eve.json` không được tạo, script không có dữ liệu và không gửi được cảnh báo.

<p align="center">
  <img width="600" alt="Cấu hình EVE JSON" src="https://github.com/user-attachments/assets/7703ba2e-8b15-464a-9224-ebbdc7debf05" />
  <br>
  <em>Hình 4: Cấu hình bật EVE JSON để nhận log.</em>
</p>



### 2.2. Kiểm tra log qua WebGUI
1. Vào **Diagnostics → Command Prompt**.
2. Nhập vào ô **Execute Shell Command** rồi bấm **Execute**:

```sh
ls /var/log/suricata/
```
<p align="center">
  <img width="600" alt="LS File eve" src="https://github.com/user-attachments/assets/cb499258-bf49-4cfc-9874-419aaed880bd" />
  <br>
  <em>Hình 5: Kết quả chạy command, hiển thị tên thư mục UUID của interface WAN.</em>
</p>





























4. Sửa dòng `$eve_file` trong [suricata_mailer.php](suricata_mailer.php) theo đúng tên thư mục thật.
5. Copy file này lên pfSense qua Diagnostics → Edit File, lưu tại `/root/suricata_mailer.php`.
6. Services → Cron → Add: Minute `*/2`, Command `/usr/local/bin/php -f /root/suricata_mailer.php`.

## Lệnh / công cụ đã kiểm thử

```bash
# Từ Kali — tạo 1 alert nghiêm trọng
nmap -p 80 --script http-sql-injection 192.168.10.1
```

Đợi tối đa 2 phút (chu kỳ cron) — **không** chạy tay lệnh php, để xác nhận cron tự hoạt động.

## Kết quả thu được

| File | Nội dung |
|---|---|
| `screenshots/12-smtp-test-success.png` | Test SMTP Settings thành công |
| `screenshots/12-cron-job-config.png` | Cấu hình Cron job |
| `screenshots/12-email-received.png` | Email cảnh báo nhận được trong hộp thư |

## Kết quả mong đợi
- Email tự động về trong vòng 02 phút sau khi có alert nghiêm trọng, không cần thao tác thủ công.
