<div align="center">

<img src="images/pfsense-logo.png" alt="pfSense logo" width="180">

<h1>pfSense Suricata Lab</h1>

Vietnamese Version: Đây là dự án cá nhân xây dựng một lab cơ bản mô phỏng cách hoạt động của IPS/IDS trong hệ thống doanh nghiệp vừa và nhỏ được xây dựng hoàn toàn trên hệ thống ảo hóa (VMWare + GNS3).

English Version: This is a personal project to build a basic lab that simulates how an IPS/IDS operates in a small- to medium-sized enterprise (SME) environment. The entire lab is built using a virtualized infrastructure with VMware and GNS3.

<p>
  <img src="https://img.shields.io/badge/status-active-brightgreen">
  <img src="https://img.shields.io/badge/platform-GNS3%20%2F%20VMware-blue">
  <img src="https://img.shields.io/badge/stack-pfSense%20%7C%20Suricata%20%7C%20pfBlockerNG%20%7C%20Cron-red">
</p>

</div>

---


## Cấu trúc thư mục / Directory Structure

```
lab-repo/
├── docs/
└── scenarios/
    ├── 01-perimeter-nat-portforward/
    ├── 02-recon-scanning/
    ├── 03-web-application-attacks/
    ├── 04-credential-bruteforce/
    ├── 05-denial-of-service/
    ├── 06-ips-active-blocking/
    ├── 07-pfblockerng-threat-intel/
    ├── 08-dnsbl-social-media/
    ├── 09-dnsbl-ads-blocking/
    ├── 10-schedule-based-blocking/
    ├── 11-Script/
    ├── 12-email-alerting/
    ├── 13-Template-Report/
    └── README.md      
```

## Cách dùng các thư mục template | How to Use Template Directories

Mỗi thư mục kịch bản/mô hình đều tuân theo cùng một khuôn mẫu  
(xem `_Template-Report/README.md`).

Each scenario directory follows a standard template  
(refer to `_Template-Report/README.md`).

| # | Nội dung | Contents |
|---|---|---|
| 1 | **Mục tiêu** | **Objective** — Kịch bản này chứng minh điều gì?<br>*What does this scenario demonstrate?* |
| 2 | **Xây dựng kịch bản** | **Scenario Development** — Mô phỏng các tình huống thực tế để kiểm tra và đánh giá hệ thống.<br>*Simulate real-world scenarios to test and evaluate system performance.* |
| 3 | **Cấu hình** | **Configuration** — Các bước thiết lập chi tiết đã thực hiện.<br>*Detailed configuration steps performed.* |
| 4 | **Kiểm thử** | **Testing** — Các lệnh và công cụ được sử dụng.<br>*Commands and tools used for testing.* |
| 5 | **Kết quả** | **Results** — Kết quả thu được, kèm hình ảnh minh họa.<br>*Obtained results with supporting images.* |
| 6 | **Kết quả mong đợi** | **Expected Results** — Thu thập đầy đủ dữ liệu cần thiết và đưa ra đánh giá, kết luận.<br>*Collect the required data, followed by evaluation and conclusions.* |



## Tech Stack sử dụng / Technology Stack

- Network Emulation: GNS3.

- Firewall & Routing: pfSense, Cisco IOS.

  
- Switching: GNS3 Ethernet Switch


- Security (IDS/IPS): Suricata, pfBlockerNG-devel


- Virtualization: VMware Workstation


- Operating Systems: Kali Linux, Windows Server 2012
## Tác giả / Author
Mạnh Hà Nguyễn


🎓 Sinh viên chuyên ngành An toàn thông tin - Học viện Kỹ thuật Mật mã (KMA).

🎓 Information Security Student - Academy of Cryptography Techniques (KMA).
