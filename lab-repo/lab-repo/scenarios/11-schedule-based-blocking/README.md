# 11 — Chặn theo lịch (giờ hành chính)

## Mục tiêu
Chỉ chặn mạng xã hội trong khung giờ làm việc (Thứ 2–6, 8h–17h), tự động mở lại ngoài giờ đó.

## Lưu ý kỹ thuật quan trọng
pfBlockerNG DNSBL **không hỗ trợ schedule theo thời gian** (xác nhận qua tài liệu cộng đồng pfSense). Giải pháp: dùng tính năng Schedule **native của Firewall Rules**, kết hợp Alias IP (không dùng domain).

## Cấu hình

### 1. Tạo Schedule
Firewall → Schedules → Add: `GioHanhChinh`, Thứ 2–6, 08:00–17:00.

### 2. Tạo Alias IP mẫu
Firewall → Aliases → `SocialMedia_IP` — lấy IP qua Diagnostics → DNS Lookup cho facebook.com, instagram.com, tiktok.com.

### 3. Tạo 2 rule LAN theo đúng thứ tự

| # | Action | Destination | Schedule |
|---|---|---|---|
| 1 | Block | `SocialMedia_IP` | `GioHanhChinh` |
| 2 | Pass | `SocialMedia_IP` | (không chọn) |

## Các bước kiểm tra

```bash
ping facebook.com   # trong khung giờ → phải bị chặn; ngoài khung giờ → phải thông
```

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/11-schedule-config.png` | Cấu hình Schedule |
| `screenshots/11-rules-order.png` | Thứ tự 2 rule Block/Pass |
| `screenshots/11-blocked-in-hours.png` | Ping bị chặn trong giờ hành chính |
| `screenshots/11-allowed-outside-hours.png` | Ping thông ngoài giờ hành chính |

## Kết quả mong đợi
- Trong khung giờ: bị chặn. Ngoài khung giờ: tự động cho phép — không cần can thiệp thủ công.
