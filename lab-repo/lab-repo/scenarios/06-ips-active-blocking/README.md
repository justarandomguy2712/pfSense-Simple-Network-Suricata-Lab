# 06 — IPS Active Blocking (Legacy Blocking Mode)

## Mục tiêu
Chuyển Suricata từ chế độ chỉ log (IDS) sang chủ động chặn (IPS), minh chứng bằng cặp "before/after": lần quét đầu bị log, lần quét sau (cùng nguồn) bị chặn hoàn toàn.

## Điều kiện tiên quyết
- Legacy Blocking Mode: Block Offenders = ON, Which IP to Block = SRC, Kill States = ON (Interface Settings)
- Global Settings → Remove Blocked Hosts Interval = 1 Hour (tránh phải tự tay unblock liên tục khi test)

## Đổi rule cụ thể sang action DROP (để dòng alert tô đỏ + có entry trong Blocks)

1. Services → Suricata → interface WAN → tab Rules → tìm đúng SID cần demo (VD `2024364` — ET SCAN Nmap User-Agent).
2. Đổi Action từ `alert` sang `drop` → Save → Restart Suricata.
3. (Khuyến nghị) Bật "Block Drops Only" ở Interface Settings để chỉ đúng SID đã đổi mới gây block, các SID khác chỉ log.

> Lưu ý: dòng đỏ trong Alerts chỉ xuất hiện với **Inline IPS Mode**. Ở **Legacy Mode** (đang dùng), bằng chứng "đã chặn" nằm ở tab **Blocks**, không phải màu đỏ trong Alerts.

## Các bước thực hiện

```bash
# Lần 1 — tạo alert đầu tiên (kích hoạt SID đã đổi drop)
nmap -sV -p 80 192.168.10.1

# Lần 2 — chạy lại ngay sau đó, IP đã bị đưa vào bảng block
nmap -sV -p 80 192.168.10.1
```

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/06-suricata-blocks-tab.png` | Tab Blocks — IP Kali xuất hiện sau lần quét đầu |
| `screenshots/06-nmap-second-attempt-blocked.png` | Lần quét thứ 2 bị timeout hoàn toàn |
| `screenshots/06-sid-action-drop.png` | Cấu hình rule đã đổi Action = drop |

## Kết quả mong đợi
- Sau alert đầu tiên, IP nguồn tự động vào bảng `snort2c`/Blocks.
- Lần thử thứ 2 (cùng IP) bị drop ngay từ tầng packet-filter, không tới được đích.
