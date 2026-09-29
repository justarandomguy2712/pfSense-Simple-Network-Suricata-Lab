<?php
require_once("config.inc");
require_once("notices.inc");

$eve_file = "/var/log/suricata/suricata_em036752/eve.json"; // sửa đúng tên thư mục thật trên máy bạn
$state_file = "/tmp/suricata_mail_lastpos.txt";

$lastpos = file_exists($state_file) ? (int)file_get_contents($state_file) : 0;

$fp = fopen($eve_file, "r");
fseek($fp, $lastpos);

while (($line = fgets($fp)) !== false) {
    $event = json_decode($line, true);
    if ($event && isset($event['event_type']) && $event['event_type'] == 'alert') {
        $sev = $event['alert']['severity'] ?? 3;
        if ($sev <= 2) { // chỉ mail alert mức nghiêm trọng (1-2)
            $msg = "[SURICATA ALERT]\n" .
                   "Signature: " . $event['alert']['signature'] . "\n" .
                   "Src: " . $event['src_ip'] . " -> Dst: " . $event['dest_ip'] . ":" . ($event['dest_port'] ?? '') . "\n" .
                   "Time: " . $event['timestamp'];
            notify_via_smtp($msg);
        }
    }
}

$newpos = ftell($fp);
file_put_contents($state_file, $newpos);
fclose($fp);
