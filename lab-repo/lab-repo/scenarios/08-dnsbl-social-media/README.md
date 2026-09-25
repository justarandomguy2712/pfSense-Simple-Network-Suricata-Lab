# 08 — DNSBL: Chặn mạng xã hội

## Mục tiêu
Kiểm soát truy cập Internet của nhân viên bằng cách chặn domain mạng xã hội ở tầng DNS, dùng feed chuyên nghiệp thay vì danh sách tự gõ tay.

## Điều kiện tiên quyết
- Enable DNSBL, DNSBL Mode = Unbound mode
- Wildcard Blocking (TLD) = Enable
- DNSBL Virtual IP: tạo 1 IP Alias **isolated, chưa dùng ở đâu** (VD `192.168.99.30/32`) trên interface LAN — **không** đặt trong dải LAN đang dùng (gây lỗi "overlaps with an existing subnet")
- Client (WinServer2012) dùng chính pfSense (192.168.20.1) làm DNS server — bắt buộc, nếu không DNSBL bị bypass hoàn toàn

## Cấu hình Group

Firewall → pfBlockerNG → DNSBL → DNSBL Groups → Add:
- Name: `DNSBL_SocialMedia`
- Thêm feed chuyên nghiệp: `https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/social/hosts`
- (Tuỳ chọn) thêm vài domain tay vào Custom List để đảm bảo có trong demo: facebook.com, instagram.com, tiktok.com...

## Các bước kiểm tra

```bash
# Kiểm tra domain có trong feed không
grep -i "linkedin" /var/db/pfblockerng/dnsbl/*.txt

# Test DNS resolve
nslookup facebook.com 192.168.20.1
```

Truy cập `facebook.com` bằng **Chrome/Firefox** (không dùng IE — IE không cho "Proceed anyway" qua cảnh báo TLS).

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/08-dnsbl-block-page-facebook.png` | Trang chặn đỏ của pfBlockerNG khi truy cập facebook.com |
| `screenshots/08-dnsbl-block-page-linkedin.png` | Domain KHÔNG có trong custom list tay nhưng vẫn bị chặn (nhờ feed) |
| `screenshots/08-grep-feed-confirm.png` | Kết quả grep xác nhận domain tới từ feed, không phải gõ tay |
| `screenshots/08-dnsbl-reports-stats.png` | Firewall → pfBlockerNG → Reports → DNSBL Stats |

## Kết quả mong đợi
- Domain chưa từng gõ tay (VD LinkedIn) vẫn bị chặn — chứng minh hiệu quả của feed toàn diện.
- HTTPS luôn kèm cảnh báo chứng chỉ (giới hạn cố hữu của DNSBL với TLS — xem ghi chú trong docs).
