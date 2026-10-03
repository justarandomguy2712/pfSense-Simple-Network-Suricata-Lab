# Đưa phần mềm vào máy ảo khi không có mạng

🌐 **Ngôn Ngữ:** **Tiếng Việt** | [English](README.engver.md)


*Cài phần mềm vào Windows Server 2012 khi không có Internet*

> Trong quá trình thực hiện Lab, từ kinh nghiệm thực tế, có thể áp dụng một số phương pháp cài đặt và cấu hình nhằm rút ngắn thời gian triển khai, giảm các thao tác thủ công và nâng cao hiệu quả thực hiện.

Do lab chạy trên môi trường ảo hóa nên tốc độ Internet trong các máy ảo giảm rất nhiều, tải trực tiếp bộ cài trên Windows Server 2012 vừa chậm vừa dễ lỗi.


| Bước | Thao tác | 
|------|----------|
| 1 | Trên máy host, tải CDBurnerXP tại [Appendix-Software-Transfer/CDBurnerXP.](https://github.com/justarandomguy2712/pfSense-Simple-Network-Suricata-Lab/tree/df7634876956e55fc99f19fd6b99b5311c8f6d78/Appendix-Software-Transfer/CDBurnerXP) | 
| 2 | Trong CDBurnerXP: Thao tác add file vào menu chính xong từ File -> Save compilation as ISO file -> Hình ảnh minh họa: <img width="316" height="312" alt="Screenshot 2026-10-03 234758" src="https://github.com/user-attachments/assets/05859525-f187-4467-8e6c-c661d9d87102" /> |
| 3 | Trên Windows Server 2012 (VMWare Workstation), thao tác Edit virtual machine settings -> Add CD/DVD (SATA) -> Use ISO Image File -> Connect at power on |
| 4 | Vào kiểm tra trên File Explorer, thấy ổ đĩa ISO như ảnh là đạt yêu cầu: <img width="361" height="43" alt="Screenshot 2026-10-03 235511" src="https://github.com/user-attachments/assets/b8c5c018-ff58-49dc-8d7c-00457adcf6e5" />| 
 
