<?php
// cron_backup.php – Otomatik yedek (DB + uploads)
// Cron: 0 3 * * * /usr/bin/php /path/to/cron_backup.php
// Tarayıcıdan: cron_backup.php?token=resida-cron-2026  (ayda 1 tetikle)
require_once __DIR__ . '/includes/config.php';
$isCli = php_sapi_name()==='cli';
$token = $_GET['token'] ?? '';
$expected = $_ENV['CRON_TOKEN'] ?? 'resida-cron-2026';
if(!$isCli && $token!==$expected){ http_response_code(403); die('Forbidden'); }

$backupDir = __DIR__ . '/backups';
if(!is_dir($backupDir)) @mkdir($backupDir,0755,true);
$date = date('Ymd_His');
$dumpFile = $backupDir . "/resida_{$date}.sql";
$zipFile  = $backupDir . "/resida_{$date}.zip";

// 1) DB dump (PDO ile - mysqldump yoksa fallback)
try{
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $out = "-- RESIDA PRO backup {$date}\nSET FOREIGN_KEY_CHECKS=0;\n";
    foreach($tables as $t){
        $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
        $out .= "\n-- Table $t\nDROP TABLE IF EXISTS `$t`;\n".$create['Create Table'].";\n";
        $rows = $pdo->query("SELECT * FROM `$t`");
        foreach($rows as $r){
            $cols = array_map(fn($v)=> $v===null ? "NULL" : $pdo->quote($v), array_values($r));
            $out .= "INSERT INTO `$t` VALUES (".implode(",",$cols).");\n";
        }
    }
    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    file_put_contents($dumpFile, $out);
    echo "DB dump OK: $dumpFile (".round(filesize($dumpFile)/1024)." KB)\n";
}catch(Exception $e){ echo "Dump hata: ".$e->getMessage()."\n"; exit(1); }

// 2) ZIP (dump + uploads/receipts)
if(class_exists('ZipArchive')){
    $zip=new ZipArchive();
    if($zip->open($zipFile, ZipArchive::CREATE)===TRUE){
        $zip->addFile($dumpFile, basename($dumpFile));
        $uploads = __DIR__.'/uploads/receipts';
        if(is_dir($uploads)){
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach($files as $f){ if($f->isFile()) $zip->addFile($f->getPathname(), 'receipts/'.$f->getFilename()); }
        }
        $zip->close();
        echo "ZIP OK: $zipFile (".round(filesize($zipFile)/1024)." KB)\n";
        @unlink($dumpFile); // sadece zip kalsın
    }
}else{ echo "ZipArchive yok, sadece .sql bırakıldı\n"; $zipFile=$dumpFile; }

// 3) Eski yedekleri temizle (30 günden eski)
foreach(glob($backupDir."/resida_*.zip") as $f){ if(time()-filemtime($f) > 30*86400) @unlink($f); }
foreach(glob($backupDir."/resida_*.sql") as $f){ if(time()-filemtime($f) > 30*86400) @unlink($f); }

file_put_contents(__DIR__.'/cron.log', "[".date('Y-m-d H:i:s')."] backup: ".basename($zipFile)."\n", FILE_APPEND);
if(!$isCli) echo "<br><a href='".basename($backupDir)."/".basename($zipFile)."' download>İndir</a>";

// Admin tek tık indirme için: admin_panel.php?export=backup
if(isset($_GET['export']) && $_GET['export']==='backup'){
    // zaten zip oluşturuldu, direkt indir
}
