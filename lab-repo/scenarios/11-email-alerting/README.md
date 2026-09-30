# 11 — Email Alerting


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

### 1. Cấu hình SMTP Email trên pfSense để gửi email cảnh báo



#### 1.1. Vào **System → Advanced → Notifications → SMTP E-Mail**, cấu hình như hình.




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





### 2. Bật EVE JSON Log

`eve.json` là log sự kiện của Suricata, mỗi sự kiện là một dòng JSON (IP nguồn/đích, signature, mức độ). Script `suricata_mailer.php` đọc file này bằng `json_decode` để gửi email cảnh báo.

#### 2.1. Cấu hình EVE Output

1. Vào **Services → Suricata → Interfaces → Edit → EVE Output**.
2. Đặt **EVE Output Type = FILE**, bấm **Save**.

> Nếu chọn **SYSLOG**, file `eve.json` không được tạo, script không có dữ liệu và không gửi được cảnh báo.

<p align="center">
  <img width="600" alt="Cấu hình EVE JSON" src="https://github.com/user-attachments/assets/7703ba2e-8b15-464a-9224-ebbdc7debf05" />
  <br>
  <em>Hình 4: Cấu hình bật EVE JSON để nhận log.</em>
</p>



#### 2.2. Kiểm tra log qua WebGUI và kiểm tra có file eve.json hay không
1. Vào **Diagnostics → Command Prompt**.
2. Nhập 2 lệnh dưới đây vào ô **Execute Shell Command** rồi bấm **Execute**:

```sh
ls /var/log/suricata/
```


```sh
ls /var/log/suricata/suricata_em036752/
```

| Lệnh | Mục đích | Kết quả đúng |
|------|---------|--------------|
| `ls /var/log/suricata/` | Liệt kê thư mục log, mỗi interface một thư mục con | Thấy thư mục dạng `suricata_<interface><UUID>` |
| `ls /var/log/suricata/suricata_em036752/` | Liệt kê file log của interface đó | Có file `eve.json` |



<p align="center">
  <img width="600" alt="ls thư mục log Suricata" src="https://github.com/user-attachments/assets/cb499258-bf49-4cfc-9874-419aaed880bd" />
  <br><br>
  <img width="600" alt="Kết quả ls thư mục interface WAN" src="https://github.com/user-attachments/assets/1eadb704-ff20-4cd3-9e48-1ca6f68dee56" />
  <br>
  <em>Hình 5 và 6: Kết quả chạy lệnh, hiển thị tên thư mục UUID của interface WAN và các file log bên trong.</em>
</p>








### 3. Sửa dòng `$eve_file` trong [suricata_mailer.php](suricata_mailer.php) theo đúng tên thư mục thật.

#### 3.1 Giải thích về đoạn script `eve.json`






| Đoạn code | Ý nghĩa |
|-----------|---------|
| `require_once("config.inc")`, `require_once("notices.inc")` | Nạp thư viện của pfSense, cần cho hàm gửi mail `notify_via_smtp()` |
| `$eve_file` | Đường dẫn file `eve.json` của interface WAN. **Phải sửa đúng tên thư mục UUID trên máy bạn** |
| `$state_file` | File lưu vị trí đã đọc đến, để lần chạy sau không đọc và gửi lại alert cũ |
| `$lastpos = ... file_get_contents(...)` | Đọc vị trí đã lưu; chưa có file thì bắt đầu từ 0 |
| `fopen` + `fseek($fp, $lastpos)` | Mở `eve.json` và nhảy tới vị trí đã đọc lần trước |
| `while (fgets(...))` | Đọc từng dòng, mỗi dòng là một sự kiện JSON |
| `json_decode($line, true)` | Chuyển dòng JSON thành mảng PHP |
| `event_type == 'alert'` | Chỉ xử lý sự kiện cảnh báo, bỏ qua dns, http, tls... |
| `$sev <= 2` | Chỉ gửi mail alert mức nghiêm trọng (Severity ID 1 và 2, số càng nhỏ càng nghiêm trọng). Điền thiếu trường này thì mặc định severity ID là 3, không gửi |
| `$msg = ...` | Soạn nội dung mail alert: signature, IP nguồn → IP đích: cổng, thời gian |
| `notify_via_smtp($msg)` | Gửi mail qua cấu hình SMTP ở System → Advanced → Notifications |
| `ftell` + `file_put_contents` | Lưu vị trí cuối file vừa đọc cho lần chạy kế tiếp |
| `fclose($fp)` | Đóng file |


**Luồng chạy:** đọc vị trí cũ → đọc dòng mới trong `eve.json` → lọc Severity ID ≤ 2 → gửi mail → lưu vị trí mới.


### 4. Cài đặt package Cron trong pfSense để log tự động.


#### 4.1. Cài package Cron

1. Vào **System → Package Manager → Available Packages**.
2. Tìm `Cron`, bấm **Install**, rồi **Confirm**.

**Kiểm tra:** mục **Services → Cron** xuất hiện trong menu.

#### 4.2. Tạo lịch chạy script
1. Diagnostics → Edit File, lưu tại `/root/suricata_mailer.php`.
2. Vào **Services → Cron → Add**.
3. Điền như bảng dưới rồi bấm **Save**.



| Ô | Giá trị | Ý nghĩa |
|---|---------|---------|
| Minute | `*/2` | Chạy mỗi 2 phút |
| Hour, Day, Month, Weekday | `*` | Mọi giờ, ngày, tháng, thứ |
| User | `root` | Chạy bằng quyền root, đủ quyền đọc log |
| Command | `/usr/local/bin/php -f /root/suricata_mailer.php` | Chạy script bằng PHP CLI |


<p align="center">
  <img width="600" alt="Cấu hình Cron" src="https://github.com/user-attachments/assets/26bca455-29a0-41b3-b9af-5815b6807faf" />
  <br>
  <em>Hình 7: Cấu hình Cron nhận log tự động qua mail.</em>
</p>



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
