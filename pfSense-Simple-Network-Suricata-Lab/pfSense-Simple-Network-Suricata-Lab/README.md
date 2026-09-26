# pfSense-Simple-Network-Suricata-Lab

![status](https://img.shields.io/badge/status-active-brightgreen) ![platform](https://img.shields.io/badge/platform-GNS3%20%2F%20VMware-blue) ![stack](https://img.shields.io/badge/stack-pfSense%20%7C%20Suricata%20%7C%20pfBlockerNG-red)

Dự án cá nhân xây dựng lab mô phỏng pfSense NGFW cho môi trường SME (doanh nghiệp vừa và nhỏ), triển khai trên nền tảng ảo hóa GNS3 + VMware.

Vietnamese Version: Đây là dự án cá nhân xây dựng một lab cơ bản mô phỏng cách hoạt động của IPS/IDS trong hệ thống doanh nghiệp vừa và nhỏ, được xây dựng hoàn toàn trên hệ thống ảo hóa (VMware + GNS3).

English Version: This is a personal project to build a basic lab that simulates how an IPS/IDS operates in a small- to medium-sized enterprise (SME) environment. The entire lab is built using a virtualized infrastructure with VMware and GNS3.

---

## Cấu trúc thư mục

```
├── 00-lab-setup/                     → Dựng lab: topology, GNS3 VM, cấu hình mạng nền
├── 01-mo-hinh-1-perimeter-nat/       → Mô hình 1: NAT Port Forwarding, firewall biên
├── 02-mo-hinh-2-ids-ips/             → Mô hình 2: Suricata IDS/IPS + pfBlockerNG
│   ├── scenario-01-port-scan/
│   ├── scenario-02-web-attack/
│   ├── scenario-03-brute-force/
│   ├── scenario-04-dos/
│   └── scenario-05-ips-blocking/
├── 03-mo-hinh-3-internet-control/    → Mô hình 3: Kiểm soát truy cập Internet nhân viên
│   ├── dnsbl-social-media/
│   ├── dnsbl-ads/
│   ├── traffic-shaper/
│   └── scheduled-blocking/
├── 04-email-alerting/                → Cảnh báo qua Email khi Suricata phát hiện tấn công
├── _template-kich-ban-moi/           → Thư mục mẫu — copy để tạo kịch bản mới
├── scripts/                          → Script dùng chung
└── reports/                          → Mẫu báo cáo tiến độ
```

## Cách dùng các thư mục template

Mỗi thư mục kịch bản/mô hình đều theo cùng 1 khuôn mẫu (xem `_template-kich-ban-moi/README.md`):

1. **Mục tiêu** — kịch bản này chứng minh điều gì
2. **Sơ đồ / vị trí trong topology**
3. **Các bước cấu hình đã thực hiện**
4. **Lệnh / công cụ sử dụng để kiểm thử**
5. **Kết quả thu được** (kèm ảnh trong thư mục `screenshots/` con)
6. **Vấn đề gặp phải & cách khắc phục**
7. **Kết luận / đánh giá**

Muốn thêm 1 kịch bản mới: copy nguyên thư mục `_template-kich-ban-moi/`, đổi tên thư mục và sửa tiêu đề trong README.md.

## Stack sử dụng

`pfSense` · `Suricata` (IDS/IPS) · `pfBlockerNG-devel` · `GNS3` · `VMware Workstation` · `Kali Linux` · `Windows Server 2012` · `Cisco IOS`

## Tác giả

Mr. Hà — Sinh viên Học viện Kỹ thuật Mật mã (KMA), chuyên ngành An toàn thông tin.
