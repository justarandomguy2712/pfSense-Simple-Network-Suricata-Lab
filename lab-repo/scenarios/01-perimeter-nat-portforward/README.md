# 01 — Perimeter Defense & NAT Port Forwarding

## I. Mục tiêu

 
Xác minh pfSense hoạt động đúng vai trò Firewall biên: chỉ expose các dịch vụ được cấp phép trên WAN, chặn mặc định các port còn lại và ghi log toàn bộ traffic. Đồng thời kiểm tra cơ chế NAT Outbound, chuyển IP nguồn của Windows Server 2012 từ `192.168.20.2` sang WAN `192.168.10.1` và dịch ngược gói tin trả về đúng máy đích.

## II. Điều kiện để thực hiện bài lab
- Lab đã dựng xong và mạng đã thông theo mô hình như ảnh:


<p align="center">
  <img width="600" alt="Cấu hình SMTP Gmail" src="https://github.com/user-attachments/assets/b1197956-63a2-4034-b4b7-39d71079f4a1" />
  <br>
  <em>Hình 1: Mô hình mạng lab cơ bản</em>
</p>

- Đã tạo Alias `Ports_Test` (21, 80, 443, 445, 3389, 5985):


<p align="center">
  <img width="600" alt="Cấu hình SMTP Gmail" src="https://github.com/user-attachments/assets/349bf95f-abef-483f-aaef-a5f63abb82bb" />
  <br>
  <em>Hình 2: Tạo các Aliases cho phép các cổng chỉ định được phép đi qua </em>
</p>




- Đã tick "Log packets that are handled by this rule" trên rule WAN tương ứng và NAT Port Forward trỏ vào WinServer2012 như sau:



**Hướng dẫn cách add Rule**: Firewall → NAT → Port Forward → Add rồi điền như ảnh dưới, sau đó thì Save -> Apply Changes


<p align="center">
  <img width="600" alt="Cấu hình SMTP Gmail" src="https://github.com/user-attachments/assets/d9435b14-77f8-4c6a-8917-7db968a81349" />
  <br>
  <em>Hình 3: Trỏ NAT Port Forward vào Win Server 2012 và các log của gói tin sẽ được Rule này giám sát</em>
</p>

- Đã cài Wireshark trên Windows Server 2012
- Firewall → NAT → Outbound ở chế độ Automatic outbound NAT (mặc định)



## III. Các bước cấu hình đã thực hiện







## IV. Lệnh / công cụ đã kiểm thử


### 1. Cấu hình kiểm tra, quét và xác thực trạng thái của các cổng mạng được Firewall cho phép truy cập.

#### 1.1. Từ Kali Linux, tiến hành quét cổng mạng các ports được cho phép như sau: 

Sử dụng Nmap để xác thực khả năng truy cập và trạng thái của các cổng đã được Firewall cấu hình cho phép.



```bash
nmap -Pn -p 21,80,443,445,3389,5985 192.168.10.1
```


<p align="center">
  <img width="600" alt="Cấu hình SMTP Gmail" src="https://github.com/user-attachments/assets/4b14cbc1-136b-4483-b1a7-ce6484958b2b" />
  <br>
  <em>Hình 4: Câu lệnh tiến hành quét cổng mạng</em>
</p>

#### 1.2. Từ Kali Linux, tiến hành quét cổng mạng port không nằm trong ports được cho phép: 

Sử dụng Nmap để kiểm thử và xác thực khả năng chặn truy cập đối với các cổng mạng không được Firewall cho phép

```bash
nmap -Pn -p 3306 192.168.10.1
```


| Thành phần | Ý nghĩa |
|------------|---------|
| `nmap` | Công cụ quét cổng và phát hiện dịch vụ |
| `-Pn` | -Pn là lệnh dùng để bỏ qua giai đoạn thăm dò máy chủ (Host Discovery) và mặc định coi tất cả các IP mục tiêu đều đang hoạt động. |
| `-p 21,80,443,445,3389,5985` | Chỉ quét 6 cổng trong alias `Ports_Test`: FTP (21), HTTP (80), HTTPS (443), SMB (445), RDP (3389), WinRM (5985) |
| `192.168.10.1` | IP WAN của pfSense |









<img width="1141" height="167" alt="Screenshot 2026-10-01 102015" src="https://github.com/user-attachments/assets/a7efdd7c-a442-49ee-99f3-6e3d71d101ac" />



## V. Kết quả thu được


| Ảnh | Nội dung |
|---|---|
| <img width="1136" height="205" alt="Screenshot 2026-10-01 101727" src="https://github.com/user-attachments/assets/213541a2-c1d9-409c-9947-33d2d054cf0c" /> | Cấu hình Firewall trên pfSense cho phép lưu lượng từ địa chỉ IP của Kali Linux truy cập vào các cổng mạng đã được cấp phép. |
| <img width="1136" height="66" alt="Screenshot 2026-10-01 101818" src="https://github.com/user-attachments/assets/1c086e8f-3e36-4375-9bdf-18d6c3e676f7" /> | pfSense Firewall log đã block cho port không nằm trong Ports_Test |
| <img width="1141" height="167" alt="Screenshot 2026-10-01 102015" src="https://github.com/user-attachments/assets/a7efdd7c-a442-49ee-99f3-6e3d71d101ac" /> | Suricata phát hiện lưu lượng mạng bất thường qua cổng 3306 không thuộc các Alias được phép. |



## VI. Kết quả mong đợi
- Các port trong `Lab_Ports` trả lời `open` khi có service thật chạy trên WinServer.
- Port ngoài danh sách (VD 3306) bị chặn, log ghi nhận **Block** bởi default-deny rule của WAN.
- Firewall log xác nhận NAT đã dịch đúng địa chỉ đích từ IP WAN sang IP LAN thật của WinServer.
