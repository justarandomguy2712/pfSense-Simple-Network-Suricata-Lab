# 09 — DNSBL: Chặn quảng cáo

## Mục tiêu
Chặn ad server ở tầng DNS — trải nghiệm mượt hơn chặn mạng xã hội vì quảng cáo là tài nguyên phụ (không gây cảnh báo TLS to như chặn cả trang chính).

## Cấu hình Group

Firewall → pfBlockerNG → DNSBL Groups → Add:
- Name: `DNSBL_Ads`
- Feed: Peter Lowe's Ad Server List
  `https://pgl.yoyo.org/adservers/serverlist.php?hostformat=hosts&showintro=0&mimetype=plaintext`
- (Tuỳ chọn mở rộng) oisd: `https://big.oisd.nl`
- Thêm domain analytics còn thiếu vào Custom List: `google-analytics.com`, `googletagmanager.com`

## Các bước kiểm tra

Công cụ test chuyên dụng:
```
https://adblock-tester.com
```
hoặc
```
https://d3ward.github.io/toolz/adblock.html
```

Test trực quan trên trang thật có nhiều ads: `weather.com`, `forbes.com`.

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/09-adblock-test-score-before.png` | Điểm số trước khi bật DNSBL_Ads |
| `screenshots/09-adblock-test-score-after.png` | Điểm số sau khi bật |
| `screenshots/09-website-ads-before-after.png` | Ảnh ghép trước/sau trên 1 trang tin tức thật |

## Kết quả mong đợi
- Điểm số công cụ test tăng rõ rệt sau khi bật group.
- Một số hạng mục (banner ảnh tĩnh, GIF quảng cáo dò theo path/keyword) **không thể chặn bằng DNSBL** — đây là giới hạn tầng domain, cần Squid/SquidGuard hoặc extension trình duyệt mới lọc được theo URL path.
