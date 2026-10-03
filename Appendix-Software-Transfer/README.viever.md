# Đưa phần mềm vào máy ảo lab ảo hóa lồng nhau

*Cài phần mềm vào Windows Server 2012 khi không có Internet*

> Tài liệu bổ trợ, nằm ngoài các kịch bản lab và không bắt buộc cho lộ trình lab chính.

Do lab chạy ảo hóa lồng nhau nên tốc độ Internet trong các máy ảo giảm rất nhiều, tải trực tiếp bộ cài trên Windows Server 2012 vừa chậm vừa dễ lỗi.


| Bước | Thao tác | 
|------|----------|
| 1 | Trên máy host, tải CDBurnerXP tại . | 
| 2 | Trong VMware: **VM → Settings → Options → Shared Folders** → chọn **Always enabled** → **Add** → chọn `C:\Lab_Installers` → tick **Enable this share** → **Finish** → **OK**. | Thư mục chia sẻ hiện trong danh sách với trạng thái *Enabled*. |
| 3 | Trên Windows Server 2012, mở File Explorer và truy cập `\\vmware-host\Shared Folders\Lab_Installers`. | Thấy file bộ cài. Trong CMD: `dir "\\vmware-host\Shared Folders\Lab_Installers"` liệt kê được file. |
| 4 | Sao chép bộ cài sang ổ cục bộ, ví dụ `C:\Temp`, rồi chạy cài đặt. | Chạy `certutil -hashfile C:\Temp\<bo_cai>.exe SHA256` và so sánh với SHA-256 do nhà phát hành công bố. |
