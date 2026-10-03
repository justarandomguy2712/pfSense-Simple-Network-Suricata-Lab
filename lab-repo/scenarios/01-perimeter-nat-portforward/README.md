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

- Đã cài Wireshark trên Windows Server 2012
- Firewall → NAT → Outbound ở chế độ Automatic outbound NAT (mặc định)



## III. Các bước cấu hình đã thực hiện

### 1. Tạo Alias `Ports_Test` 

Alias gom các nhóm ports vào một tên để dùng lại trong NAT và Firewall Rule.

- Vào Firewall → Aliases → Ports → Add (21, 80, 443, 445, 3389, 5985):


<p align="center">
  <img width="600" alt="Cấu hình SMTP Gmail" src="https://github.com/user-attachments/assets/349bf95f-abef-483f-aaef-a5f63abb82bb" />
  <br>
  <em>Hình 2: Tạo các Aliases cho phép các cổng chỉ định được phép đi qua </em>
</p>

### 2. Tạo NAT Port Forward trỏ vào WinServer 2012
- Tick "Log packets that are handled by this rule" trên rule WAN tương ứng và NAT Port Forward trỏ vào WinServer2012 như sau:

- Vào Firewall → NAT → Port Forward → Add rồi điền như ảnh dưới, sau đó thì Save -> Apply Changes


<p align="center">
  <img width="600" alt="Cấu hình SMTP Gmail" src="https://github.com/user-attachments/assets/d9435b14-77f8-4c6a-8917-7db968a81349" />
  <br>
  <em>Hình 3: Trỏ NAT Port Forward vào Win Server 2012 và các log của gói tin sẽ được Rule này giám sát</em>
</p>


### 3. Tạo Alias WinServer2012 (Host)

Alias gán một tên cho IP LAN của WinServer, dùng lại ở NAT Port Forward và Firewall Rule. Sau này đổi IP chỉ cần sửa một chỗ.

- Vào **Firewall → Aliases → IP → Add** điền như bảng sau: 

| Mục | Cần điền | Ghi chú |
|---|---------|---------|
| Name | `WinServer2012` | Chỉ gồm chữ, số và dấu `_` |
| Description | `IP LAN cua WinServer 2012` | Để dễ phân biệt và quản lý |
| Type | `Host(s)` | Alias chứa địa chỉ IP |
| IP or FQDN | `192.168.20.2` | IP LAN của WinServer |


- Bấm **Save** -> **Apply Changes**.

**Kiểm tra:** vào **Diagnostics → Tables**, chọn `WinServer2012`, thấy một dòng `192.168.20.2`.



<p align="center">
 <img width="1263" height="554" alt="Screenshot 2026-10-03 224328" src="https://github.com/user-attachments/assets/7414586d-6e5f-4f57-b782-7f2773050d2c" />
  <br>
  <em>Hình 4: Tạo Alias IP WinServer2012</em>
</p>





















### 4. Cấu hình và thực hiện Packet Capture trên WAN
#### 4.1. Thiết lập Packet Capture trên giao diện WAN
Vào Diagnostics → Packet Capture, điền như ảnh sau:

<p align="center">
  <img width="600" alt="Cấu hình SMTP Gmail" src="https://github.com/user-attachments/assets/d35d1a7c-a2ef-46e4-8717-1a05763f4a8d" />
  <br>
  <em>Hình 5: Cấu hình Packet Capture để bắt gói tin</em>
</p>

**Giải thích nhanh các mục chính trong Packet Capture**: 


| Mục | Ý Nghĩa |
|------------|---------|
| Capture Options | Chọn interface cần bắt gói tin. Ảnh đang chọn WAN (em0). |
| Promiscuous Mode | 	Bắt toàn bộ gói thấy được |
| Max number of packets to capture | Số lượng packet tối đa cần bắt |
| Name Lookup | Thực hiện phân giải tên DNS/port/MAC khi hiển thị packet |
| HOST IP ADDRESS OR SUBNET | Lọc theo IP nguồn/đích hoặc subnet. |

#### 4.2. Tạo traffic từ WinServer 2012 và thu capture 

- Trên pfSense, kéo xuống cuối trang, bấm Start.
 
- Ngay sau đó, trên WinServer (CMD), chạy câu lệnh sau:


```bash
ping -n 4 -l 100 192.168.10.10
```


<p align="center">
 <img width="677" height="340" alt="Screenshot 2026-10-03 115206" src="https://github.com/user-attachments/assets/8684edfd-b3e2-42d1-9f99-2687e9665f99" />
  <br>
  <em>Hình 6: Lệnh ping từ WinServer 2012 đến 192.168.10.10</em>
</p>

**Giải thích nhanh về câu lệnh ping**:


| Thành phần | Ý nghĩa |
|------------|---------|
| `ping` | Kiểm tra khả năng kết nối mạng tới máy đích bằng ICMP |
| `-n 4` | Gửi 4 gói tin ICMP |
| `-l 100` | Đặt kích thước dữ liệu trong mỗi gói ICMP là 100 bytes |
| `192.168.10.10` | Địa chỉ IP đích cần kiểm tra |


- Ping xong, quay lại pfSense bấm Stop (giữ dưới 15 giây), rồi Download Capture.

 
- Mở file bằng Wireshark, ô lọc nhập:

```bash
icmp && frame.len == 142
```

<p align="center">
<img width="1619" height="164" alt="Screenshot 2026-10-03 115612" src="https://github.com/user-attachments/assets/b4b0b0a0-83aa-4166-b683-1176d7c3e797" />
  <br>
  <em>Hình 7: Lọc gói tin ICMP có độ dài Frame 142 bytes bằng Wireshark</em>
</p>

#### 4.3. Kiểm tra bảng State, địa chỉ gốc và trạng thái kết nối

- Trên pfSense vào Diagnostics → States → States.

Điền các thông số như ảnh dưới như sau:


<p align="center">
  <img width="1085" height="572" alt="Screenshot 2026-10-03 120111" src="https://github.com/user-attachments/assets/1a356e02-3af8-4f7d-bc3d-74d97a82fc37" />
  <br>
  <em>Hình 8: Hai dòng state ICMP: trước NAT (LAN) và sau NAT (WAN).</em>
</p>





#### Giải thích bảng States (Diagnostics → States)

**Bộ lọc:** Interface `all`, Filter expression `192.168.20.2` (chỉ lấy state của WinServer). Không bấm **Kill States** vì nút này ngắt kết nối đang chạy.

| Cột | Ý nghĩa |
|-----|---------|
| Interface | Traffic bị NAT hiện 2 dòng: LAN (trước NAT) và WAN (sau NAT) |
| Source (Original Source) → Destination | Địa chỉ sau NAT, địa chỉ gốc nằm trong ngoặc |
| State | `ESTABLISHED` = đã bắt tay xong đi theo cả 2 chiều, `NO_TRAFFIC:SINGLE` = chỉ có gói 1 chiều, `0:0` = ICMP |
| Packets / Bytes | Số gói và dung lượng theo dạng `đi / về` |



---



| STT | Interface | Luồng | Phân tích |
|-----|-----------|-------|-----------|
| 1 | LAN | `tcp 192.168.20.2:49170 → 192.168.20.1:80` | WinServer mở trang quản trị pfSense, traffic nội bộ |
| 2 | LAN | `udp 192.168.20.2:137 → 192.168.20.255:137` | NetBIOS broadcast, không có trả lời (3 / 0)|
| 3 | LAN | `icmp 192.168.20.2:1 → 192.168.10.10:8` | Gói ping trước NAT |
| 4 | WAN | `icmp 192.168.10.1:14119 (192.168.20.2:1) → 192.168.10.10:8` | Gói ping sau NAT, source thành IP WAN, bên trong ngoặc là địa chỉ gốc |

**Với ICMP, số sau dấu `:` không phải cổng:** `:1` và `:14119` là ICMP ID (pfSense đổi ID để phân biệt các luồng ping), `:8` là type 8 = Echo request.








## IV. Lệnh / công cụ đã kiểm thử


### 1. Cấu hình kiểm tra, quét và xác thực trạng thái của các cổng mạng được Firewall cho phép truy cập.

#### 1.1. Từ Kali Linux, tiến hành quét cổng mạng các ports được cho phép như sau: 

Sử dụng Nmap để xác thực khả năng truy cập và trạng thái của các cổng đã được Firewall cấu hình cho phép.



```bash
nmap -Pn -p 21,80,443,445,3389,5985 192.168.10.1
```

<p align="center">
  <img width="600" alt="Quet cổng mạng bằng Nmap" src="https://github.com/user-attachments/assets/4b14cbc1-136b-4483-b1a7-ce6484958b2b" />
  <br>
  <em>Hình 8: Câu lệnh tiến hành quét cổng mạng</em>
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











## V. Kết quả thu được

### Các Port được cho phép và Port bị phát hiện ngoài Alias 

| Ảnh | Nội dung |
|---|---|
| <img width="1136" height="205" alt="Screenshot 2026-10-01 101727" src="https://github.com/user-attachments/assets/213541a2-c1d9-409c-9947-33d2d054cf0c" /> | Cấu hình Firewall trên pfSense cho phép lưu lượng từ địa chỉ IP của Kali Linux truy cập vào các cổng mạng đã được cấp phép. |
| <img width="1136" height="66" alt="Screenshot 2026-10-01 101818" src="https://github.com/user-attachments/assets/1c086e8f-3e36-4375-9bdf-18d6c3e676f7" /> | pfSense Firewall log đã block cho port không nằm trong Ports_Test |
| <img width="1141" height="167" alt="Screenshot 2026-10-01 102015" src="https://github.com/user-attachments/assets/a7efdd7c-a442-49ee-99f3-6e3d71d101ac" /> | Suricata phát hiện lưu lượng mạng bất thường qua cổng 3306 không thuộc các Alias được phép. |




## VI. Kết quả mong đợi
- Các port trong `Ports_Test` trả lời `open` khi có service thật chạy trên WinServer.
- Port ngoài danh sách (VD 3306) bị chặn, log ghi nhận **Block** bởi default-deny rule của WAN.
- Firewall log xác nhận NAT đã dịch đúng địa chỉ đích từ IP WAN sang IP LAN thật của WinServer.
- Traffic outbound chỉ đi ra qua các port được phép, source được dịch thành `192.168.10.1`.
