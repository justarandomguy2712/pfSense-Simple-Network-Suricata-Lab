# 10 — Traffic Shaper: Quản lý băng thông (Limiters)

## Mục tiêu
Giới hạn băng thông theo từng client, đo bằng công cụ định lượng (iperf3), so sánh baseline trước/sau.

## Điều kiện tiên quyết
- iperf3 cài trên Kali: `sudo apt install iperf3 -y`
- iperf3 cho Windows tải tại: https://github.com/ar51an/iperf3-win-builds/releases (giải nén, không cần cài đặt)
- VMware Tools đã cài trên WinServer2012 (driver NIC hiệu năng cao vmxnet3 — thiếu cái này gây tốc độ đo sai lệch nghiêm trọng)

## Cấu hình Limiter

Firewall → Traffic Shaper → Limiters → New:

| Tên | Bandwidth | Mask |
|---|---|---|
| `Upload_NhanVien` | 512 Kbit/s | Source Address /32 |
| `Download_NhanVien` | 1 Mbit/s | Destination Address /32 |

Gán vào Firewall Rule LAN → In/Out Pipe: **In = Upload, Out = Download** (đúng quy ước chính thức của pfSense).

## Các bước đo

```bash
# Trên Kali (server)
iperf3 -s
```

```
# Trên WinServer (client) — chạy 2 lần: 1 lần TRƯỚC khi gán Limiter, 1 lần SAU
iperf3.exe -c 192.168.10.50 -p 5201        # đo Download
iperf3.exe -c 192.168.10.50 -p 5201 -R     # đo Upload
```

## Bằng chứng cần thu thập

| File | Nội dung |
|---|---|
| `screenshots/10-iperf3-baseline.png` | Kết quả đo trước khi bật Limiter |
| `screenshots/10-iperf3-limited.png` | Kết quả đo sau khi bật Limiter |
| `screenshots/10-limiter-info-realtime.png` | Diagnostics → Limiter Info khi đang chạy test |

## Kết quả mong đợi

| Chỉ số | Baseline | Sau Limiter | Giới hạn đặt |
|---|---|---|---|
| Download | ... Mbit/s | ~1 Mbit/s | 1 Mbit/s |
| Upload | ... Mbit/s | ~512 Kbit/s | 512 Kbit/s |
