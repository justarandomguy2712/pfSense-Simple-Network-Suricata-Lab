# 11 — Email Alerting


## I. Mục tiêu
Tự động gửi email khi Suricata phát hiện alert mức độ nghiêm trọng, không cần trực theo dõi log thủ công.

## II. Điều kiện để thực hiện bài lab
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


## III. Các bước cấu hình đã thực hiện

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



### 5. Kiểm tra kết quả

#### 5.1. Vào **Diagnostics → Command Prompt**, chạy `/usr/local/bin/php -f /root/suricata_mailer.php`.
#### 5.2 Tạo một alert severity 1 hoặc 2 (ví dụ quét lỗ hổng web bằng `nikto`), chờ tối đa 2 phút.

**Kiểm tra:** Gmail nhận được email `[SURICATA ALERT]`như ảnh sau:

<p align="center">
  <img width="600" alt="Cấu hình Email Alearting" src="https://github.com/user-attachments/assets/3a579461-a89b-44e1-ae8d-179283ac4405" />
  <br>
  <em>Hình 8: Email Alerting đã được gửi về mail theo đúng cấu hình</em>
</p>



## IV. Lệnh / công cụ đã kiểm thử
### 1. Kiểm thử tấn công bằng Kali Linux. 
Lệnh mô phỏng tấn công: `nmap` kiểm tra directory traversal:


```bash
# nmap -p 80 --script http-passwd 192.168.10.1
```

Lệnh chạy từ máy Kali (`192.168.10.50`) tới web server `192.168.10.1` để thử tấn công **directory traversal** và kích hoạt cảnh báo của Suricata.

| Thành phần | Ý nghĩa |
|------------|---------|
| `nmap` | Công cụ quét mạng |
| `-p 80` | Chỉ quét cổng 80 (HTTP) |
| `--script http-passwd` | Chạy script Nmap Scripting Engine thử đọc file nhạy cảm (`/etc/passwd`, `boot.ini`) bằng các đường dẫn dạng `../../`. Nếu đọc được, server bị lỗi directory traversal |
| `192.168.10.1` | Địa chỉ máy đích |


<p align="center">
  <img width="600" alt="Cấu hình Kali Linux" src="https://github.com/user-attachments/assets/12b66699-bb9f-4793-90ec-ad592158151b"  />
  <br>
  <em>Hình 9: Tấn công kiểm thử trên Kali Linux</em>
</p>








## V. Kết quả thu được

| Ảnh | Nội dung |
|-----|----------|
|<img width="1184" height="203" alt="Screenshot 2026-09-30 165646" src="https://github.com/user-attachments/assets/e426b33c-cfde-4f08-86b0-b1bcc4390045" />| Logs ở tab alerts trên Suricata |
|<img width="1418" height="287" alt="Screenshot 2026-09-30 205535" src="https://github.com/user-attachments/assets/0d598e93-e64d-49ac-a0da-801289720583" /> | Email `[SURICATA ALERT]` nhận được trong Gmail |



**Kiểm chứng bộ lọc severity:** tab Alerts có 2 alert (Pri 3 và Pri 1), email chỉ chứa alert Pri 1. Điều này xác nhận script chỉ gửi alert có severity ≤ 2.


## VI. Kết quả mong đợi

- Email tự động về trong vòng 02 phút sau khi có alert nghiêm trọng, không cần thao tác thủ công.
- Chỉ alert **severity 1 và 2** được gửi mail. Alert severity 3 (ví dụ `SURICATA Applayer Mismatch protocol`) chỉ hiện ở tab Alerts, không gây ra tình trạng spam email.
- Mỗi alert chỉ gửi **một lần**. Script lưu vị trí đã đọc trong `/tmp/suricata_mail_lastpos.txt` nên không gửi lại alert cũ.
- Nhiều alert xảy ra trong cùng một chu kỳ Cron được gộp vào **một email** (như Hình 8, gồm 3 alert).
- Nội dung email đủ để đưa ra biện pháp xử lý: tên signature, IP Source → IP Des: cổng, thời gian.
- Hệ thống chạy ổn định sau khi khởi động lại pfSense vì Cron tự chạy lại theo lịch đã config.

