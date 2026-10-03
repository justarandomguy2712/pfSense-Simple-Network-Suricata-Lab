# Transferring Software to a Virtual Machine Without Internet Access



🌐 **Language:** **English** | [Vietnamese](README.viever.md)

*Installing software on Windows Server 2012 without Internet access*

> Based on practical experience during the Lab implementation, several installation and configuration methods can be applied to reduce deployment time, minimize repetitive manual operations, and improve overall efficiency.


Since the Lab is deployed in a virtualized environment, Internet connectivity inside the virtual machines can be significantly slower. Downloading installation packages directly on Windows Server 2012 is therefore time-consuming and may also result in download failures.


| Step | Procedure | 
|------|----------|
| 1 | On the host machine, download CDBurnerXP from [Appendix-Software-Transfer/CDBurnerXP.](https://github.com/justarandomguy2712/pfSense-Simple-Network-Suricata-Lab/tree/df7634876956e55fc99f19fd6b99b5311c8f6d78/Appendix-Software-Transfer/CDBurnerXP) | 
| 2 | In CDBurnerXP, add the required files to the main compilation, then select File → Save compilation as ISO file to create an ISO image -> Image: <img width="316" height="312" alt="Screenshot 2026-10-03 234758" src="https://github.com/user-attachments/assets/05859525-f187-4467-8e6c-c661d9d87102" /> |
| 3 | In Windows Server 2012 on VMware Workstation, navigate to Edit virtual machine settings → Add CD/DVD (SATA) → Use ISO Image File → Connect at power on |
| 4 | Open File Explorer and verify that the ISO image is mounted as a CD/DVD drive, as shown below. If the drive is displayed correctly, the configuration is successful: <img width="361" height="43" alt="Screenshot 2026-10-03 235511" src="https://github.com/user-attachments/assets/b8c5c018-ff58-49dc-8d7c-00457adcf6e5" />| 
 

