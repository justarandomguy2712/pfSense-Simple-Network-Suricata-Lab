## Các commands pfSense trong quá trình làm lab

Chạy trong **Diagnostics->Command Prompt** của pfSense (trên WebGUI)

### Nhóm 1: Suricata (IDS/IPS)

| # | Lệnh | Ý nghĩa | Kiểm tra kết quả |
|---|------|---------|------------------|
| 1 | `/usr/local/bin/php -f /root/suricata_mailer.php` | Chạy script PHP tự viết `suricata_mailer.php` bằng PHP CLI của pfSense (`-f` = chạy file). Script dùng để gửi email cảnh báo khi Suricata phát hiện sự kiện. | Hộp thư nhận được email; script không in lỗi. |
| 2 | `ls /var/log/suricata/` | Liệt kê thư mục log của Suricata. Mỗi interface được bảo vệ có một thư mục con riêng. | Thấy thư mục dạng `suricata_<interface><số>`. |
| 3 | `ls /var/log/suricata/suricata_em036752/` | Xem file log của instance Suricata gắn với interface `em0`. Thường có `alerts.log`, `eve.json`, `stats.log`, `http.log`. | Thấy các file log, thời gian sửa đổi gần đây. |

### Nhóm 2: pfBlockerNG (DNSBL)

| # | Lệnh | Ý nghĩa | Kiểm tra kết quả |
|---|------|---------|------------------|
| 4 | `grep -i "doubleclick" /var/db/pfblockerng/dnsbl/*.txt` | Tìm không phân biệt hoa/thường (`-i`) chuỗi `doubleclick` trong các danh sách chặn DNSBL. Dùng để biết tên miền quảng cáo đã nằm trong blocklist chưa. | Có dòng kết quả = tên miền đã bị chặn. Không có kết quả = chưa nằm trong list. |

### Nhóm 3: Sửa lỗi cập nhật gói / nâng cấp pfSense

Thực hiện theo đúng thứ tự.

| Bước | Lệnh | Ý nghĩa | Kiểm tra kết quả |
|------|------|---------|------------------|
| 1 | `pkg clean -ay` | Xóa cache gói đã tải (`-a` = tất cả, `-y` = tự trả lời yes). Giải phóng dung lượng, loại bỏ gói lỗi. | `df -h` thấy dung lượng trống tăng. |
| 2 | `pkg update -f` | Ép (`-f`) tải lại danh mục repository. | Báo `... repository is up to date` hoặc cập nhật thành công. |
| 3 | `ps aux \| grep -i pfSense-upgrade` | Kiểm tra tiến trình nâng cấp có đang chạy không. | Chỉ còn dòng của chính lệnh `grep` là không có upgrade đang chạy. |
| 4 | `rm -f /var/run/pfSense-upgrade.pid` | Xóa file PID cũ còn sót của lần upgrade bị treo. | `ls /var/run/pfSense-upgrade.pid` báo `No such file`. |

> ⚠️ **Cảnh báo:** Chỉ chạy bước 4 và 5 khi bước 3 xác nhận **không có** tiến trình upgrade đang chạy. Xóa lock/PID khi upgrade đang chạy có thể làm hỏng hệ thống.
