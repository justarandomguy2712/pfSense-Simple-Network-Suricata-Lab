# pfSense-Simple-Network-Suricata-Lab

<div align="center">

  
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

## Cách dùng các thư mục template / How to use template directories

Mỗi thư mục kịch bản/mô hình đều theo cùng 1 khuôn mẫu (xem _Template-Report/README.md)


Each scenario directory follows a standard template (refer to _Template-Report/README.md).


1. Mục tiêu | Objective: Kịch bản này chứng minh điều gì? / What does this scenario demonstrate?

2. Sơ đồ mạng | Topology: Vị trí và sơ đồ thiết kế trong mạng. / Diagram and position within the network topology.

3. Cấu hình | Configuration: Các bước thiết lập chi tiết đã thực hiện. / Detailed configuration steps performed.

4. Kiểm thử | Testing: Các lệnh và công cụ được sử dụng. / Commands and tools used for testing.

5. Kết quả | Results: Kết quả thu được (kèm hình ảnh minh họa). / Obtained results (with images).

6. Khắc phục sự cố | Troubleshooting: Các vấn đề gặp phải và hướng giải quyết. / Issues encountered and their solutions.

7. Kết luận | Conclusion: Đánh giá và tổng kết. / Final evaluation and summary.



## Tech Stack sử dụng / Technology Stack

- Firewall & Routing: pfSense, Cisco IOS

- Security (IDS/IPS): Suricata, pfBlockerNG-devel

- Virtualization & Emulation: GNS3, VMware WorkstatioN

- Operating Systems: Kali Linux, Windows Server 2012
## Tác giả
Manh Ha Nguyen


🎓 Sinh viên chuyên ngành An toàn thông tin - Học viện Kỹ thuật Mật mã (KMA).

🎓 Information Security Student - Academy of Cryptography Techniques (KMA).
