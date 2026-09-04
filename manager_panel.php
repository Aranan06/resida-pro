<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
if (!isManager()) { header('Location: dashboard.php'); exit; }

$page = $_GET['page'] ?? 'dashboard';

$mySiteId = $user['site_id'];
$st=$pdo->prepare("SELECT 1 FROM site_subscriptions WHERE site_id=? AND status='active' AND current_period_end >= CURDATE() LIMIT 1"); $st->execute([$mySiteId]); $isSubscriptionActive=(bool)$st->fetchColumn();
$ms=$pdo->prepare("SELECT ss.*, p.name as plan_name FROM site_subscriptions ss JOIN subscription_plans p ON ss.plan_id=p.id WHERE ss.site_id=? AND ss.status='active' ORDER BY ss.current_period_end DESC LIMIT 1"); $ms->execute([$mySiteId]); $mySubscription=$ms->fetch();
$error = $success = '';

$siteStmt = $pdo->prepare("SELECT name, max_residents, address, bank_name, iban, iban_holder, penalty_enabled, penalty_rate, penalty_grace_days FROM sites WHERE id = ?");
$siteStmt->execute([$mySiteId]);
$siteData = $siteStmt->fetch(PDO::FETCH_ASSOC);
$siteName = $siteData['name'] ?? 'Bilinmeyen Site';
$maxResidents = (int)($siteData['max_residents'] ?? 0);
$siteAddress = $siteData['address'] ?? '';
$siteBank = $siteData['bank_name'] ?? '';
$siteIban = $siteData['iban'] ?? '';
$siteHolder = $siteData['iban_holder'] ?? '';
$penEnabled = (int)($siteData['penalty_enabled'] ?? 0);
$penRate = $siteData['penalty_rate'] ?? 5;
$penGrace = (int)($siteData['penalty_grace_days'] ?? 5);

// POST İŞLEMLERİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $a = $_POST['action'];
    try {
        if ($a === 'add_resident') {
            $n=trim($_POST['name']); $fl=trim($_POST['floor']); $apt=trim($_POST['apartment_no']);
            $addr=trim($_POST['address']??''); $un=trim($_POST['username']); $pw=$_POST['password'];
            $ph=trim($_POST['phone']??''); $em=trim($_POST['email']??''); $nt=trim($_POST['notes']??'');
            $blk=!empty($_POST['block_id']) ? (int)$_POST['block_id'] : null;
            $bc=$pdo->prepare("SELECT COUNT(*) FROM blocks WHERE site_id=?"); $bc->execute([$mySiteId]); $siteHasBlocks=(int)$bc->fetchColumn()>0;
            if($blk){ $vc=$pdo->prepare("SELECT COUNT(*) FROM blocks WHERE id=? AND site_id=?"); $vc->execute([$blk,$mySiteId]); if(!(int)$vc->fetchColumn()) $blk=null; }

            if ($n&&$fl&&$apt&&$un&&$pw&&(!$siteHasBlocks||$blk)) {
                $limitStmt = $pdo->prepare("SELECT max_residents FROM sites WHERE id = ?");
                $limitStmt->execute([$mySiteId]);
                $maxResidents = (int)$limitStmt->fetchColumn();

                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE site_id = ? AND role = 'resident'");
                $countStmt->execute([$mySiteId]);
                $currentCount = (int)$countStmt->fetchColumn();

                if ($maxResidents > 0 && $currentCount >= $maxResidents) {
                    $error = 'Hata: Bu site için belirlenen maksimum kişi sınırına ('.$maxResidents.') ulaştınız. Daha fazla daire sakini ekleyemezsiniz.';
                } else {
                    $pdo->prepare("INSERT INTO users (username,password,role,name,site_id,block_id,floor,apartment_no,address,phone,email,notes) VALUES (?,?,'resident',?,?,?,?,?,?,?,?,?)")
                        ->execute([$un,password_hash($pw,PASSWORD_BCRYPT),$n,$mySiteId,$blk,$fl,$apt,$addr,$ph,$em,$nt]);
                    $success='Daire sakini eklendi.';
                }
            } else {
                $error=$siteHasBlocks&&!$blk ? 'Blok seçimi zorunlu.' : 'Zorunlu alanları doldurun.';
            }
        
        } elseif ($a === 'edit_resident') {
            $id=(int)$_POST['user_id']; $n=trim($_POST['name']); $fl=trim($_POST['floor']); $apt=trim($_POST['apartment_no']);
            $addr=trim($_POST['address']??''); $ph=trim($_POST['phone']??''); $em=trim($_POST['email']??''); $nt=trim($_POST['notes']??'');
            $blk=!empty($_POST['block_id']) ? (int)$_POST['block_id'] : null;
            if($blk){ $vc=$pdo->prepare("SELECT COUNT(*) FROM blocks WHERE id=? AND site_id=?"); $vc->execute([$blk,$mySiteId]); if(!(int)$vc->fetchColumn()) $blk=null; }
            $sql="UPDATE users SET name=?,block_id=?,floor=?,apartment_no=?,address=?,phone=?,email=?,notes=?";
            $params=[$n,$blk,$fl,$apt,$addr,$ph,$em,$nt];
            if (!empty($_POST['password'])) { $sql.=",password=?"; $params[]=password_hash($_POST['password'],PASSWORD_BCRYPT); }
            $sql.=" WHERE id=? AND site_id=?"; $params[]=$id; $params[]=$mySiteId;
            $pdo->prepare($sql)->execute($params);
            $success='Sakin bilgileri güncellendi.';
        } elseif ($a === 'delete_resident') {
            $pdo->prepare("DELETE FROM users WHERE id=? AND site_id=?")->execute([$_POST['user_id'],$mySiteId]);
            $success='Sakin silindi.';
        } elseif ($a === 'add_block') {
            $bn=trim($_POST['block_name']??'');
            if($bn){ try{ $pdo->prepare("INSERT INTO blocks (site_id,name) VALUES (?,?)")->execute([$mySiteId,$bn]); $success='Blok eklendi.'; }catch(PDOException $e){ $error='Bu blok zaten var.'; } }
            else $error='Blok adı gerekli.';
        } elseif ($a === 'save_site_settings') {
            $sn=trim($_POST['site_name']??''); $sa=trim($_POST['site_address']??'');
            $bn=trim($_POST['bank_name']??''); $ib=trim($_POST['iban']??''); $ih=trim($_POST['iban_holder']??'');
            $pe=!empty($_POST['penalty_enabled'])?1:0; $pr=(float)($_POST['penalty_rate']??5); $pg=(int)($_POST['penalty_grace_days']??5);
            if($sn){
                $pdo->prepare("UPDATE sites SET name=?,address=?,bank_name=?,iban=?,iban_holder=?,penalty_enabled=?,penalty_rate=?,penalty_grace_days=? WHERE id=?")->execute([$sn,$sa,$bn,$ib,$ih,$pe,$pr,$pg,$mySiteId]);
                $siteStmt->execute([$mySiteId]); $siteData=$siteStmt->fetch(PDO::FETCH_ASSOC);
                $siteName=$siteData['name']??$sn; $siteAddress=$siteData['address']??''; $siteBank=$siteData['bank_name']??''; $siteIban=$siteData['iban']??''; $siteHolder=$siteData['iban_holder']??'';
                $penEnabled=(int)($siteData['penalty_enabled']??0); $penRate=$siteData['penalty_rate']??5; $penGrace=(int)($siteData['penalty_grace_days']??5);
                $success='Site ayarları kaydedildi.';
            } else $error='Site adı boş olamaz.';
        } elseif ($a === 'edit_block') {
            $bid=(int)($_POST['block_id']??0); $bn=trim($_POST['block_name']??'');
            if($bid&&$bn){ try{ $pdo->prepare("UPDATE blocks SET name=? WHERE id=? AND site_id=?")->execute([$bn,$bid,$mySiteId]); $success='Blok güncellendi.'; }catch(PDOException $e){ $error='Bu blok adı zaten var.'; } }
            else $error='Blok adı gerekli.';
        } elseif ($a === 'delete_block') {
            $bid=(int)($_POST['block_id']??0);
            if($bid){ $pdo->prepare("DELETE FROM blocks WHERE id=? AND site_id=?")->execute([$bid,$mySiteId]); $success='Blok silindi. İçindeki sakinler Bloksuz listesine taşındı.'; }
        } elseif ($a === 'import_residents') {
            $importReport=['added'=>0,'skipped'=>[],'gen'=>[]];
            if(!empty($_FILES['csv_file']['tmp_name']) && empty($_FILES['csv_file']['error'])){
                $ext=strtolower(pathinfo($_FILES['csv_file']['name'],PATHINFO_EXTENSION));
                if(!in_array($ext,['csv','txt'])){ $error='Sadece CSV dosyası yükleyin.'; }
                else{
                    $raw=file_get_contents($_FILES['csv_file']['tmp_name']);
                    $raw=preg_replace('/^\xEF\xBB\xBF/','',$raw);
                    $rows=array_values(array_filter(array_map('trim',preg_split('/\r\n|\n|\r/',$raw)),fn($l)=>$l!==''));
                    if(count($rows)<2){ $error='Dosyada veri satırı yok.'; }
                    else{
                        $delim=(substr_count($rows[0],';')>=substr_count($rows[0],','))?';':',';
                        $norm=function($s){ $s=mb_strtolower(trim((string)$s),'UTF-8'); return strtr($s,['ç'=>'c','ğ'=>'g','ı'=>'i','ö'=>'o','ş'=>'s','ü'=>'u','â'=>'a','î'=>'i','û'=>'u']); };
                        $h=array_map($norm,str_getcsv(array_shift($rows),$delim));
                        $col=function($names) use($h){ foreach($names as $n){ $i=array_search($n,$h); if($i!==false) return $i; } return null; };
                        $cName=$col(['ad soyad','adsoyad','name']); $cBlock=$col(['blok','block']); $cFloor=$col(['kat','floor']); $cApt=$col(['daire no','daire','apartment_no','apartment']); $cPhone=$col(['telefon','phone']); $cMail=$col(['e-posta','eposta','email']); $cAddr=$col(['adres','address']); $cUser=$col(['kullanici adi','username']); $cPass=$col(['sifre','password']); $cNote=$col(['notlar','not','notes']);
                        if($cName===null||$cApt===null){ $error='Başlık satırı tanınmadı. Örnek şablonu kullanın.'; }
                        else{
                            $bmap=[]; $bs=$pdo->prepare("SELECT id,name FROM blocks WHERE site_id=?"); $bs->execute([$mySiteId]);
                            foreach($bs->fetchAll() as $b){ $bmap[$norm($b['name'])]=$b['id']; }
                            $hasBlocks=count($bmap)>0;
                            $limS=$pdo->prepare("SELECT max_residents FROM sites WHERE id=?"); $limS->execute([$mySiteId]); $maxR=(int)$limS->fetchColumn();
                            $cntS=$pdo->prepare("SELECT COUNT(*) FROM users WHERE site_id=? AND role='resident'"); $cntS->execute([$mySiteId]); $cur=(int)$cntS->fetchColumn();
                            $seenUsernames=[];
                            foreach($rows as $rn=>$line){
                                $f=str_getcsv($line,$delim); $ln=$rn+2;
                                $v=function($c) use($f){ return trim($f[$c]??''); };
                                $nm=$v($cName); $fl=$cFloor!==null?$v($cFloor):''; $ap=$v($cApt);
                                if(!$nm||!$ap){ $importReport['skipped'][]="Satır $ln: Ad Soyad ve Daire No zorunlu."; continue; }
                                $blk=null;
                                if($hasBlocks){ $braw=$cBlock!==null?$v($cBlock):''; $bid=$bmap[$norm($braw)]??null; if(!$bid){ $importReport['skipped'][]="Satır $ln: Blok bulunamadı ($braw)."; continue; } $blk=$bid; }
                                if($maxR>0&&$cur>=$maxR){ $importReport['skipped'][]="Satır $ln: Daire sakini sınırı doldu."; continue; }
                                $un=$cUser!==null?$v($cUser):'';
                                if(!$un){ $base=preg_replace('/[^a-z0-9]/','',$norm($nm).$ap); if(!$base) $base='sakin'.$ap; $un=$base; $sfx=1; $chk=$pdo->prepare("SELECT COUNT(*) FROM users WHERE username=?"); $chk->execute([$un]); while($chk->fetchColumn()>0||in_array($un,$seenUsernames)){ $un=$base.($sfx++); $chk->execute([$un]); } }
                                $chk=$pdo->prepare("SELECT COUNT(*) FROM users WHERE username=?"); $chk->execute([$un]);
                                if($chk->fetchColumn()>0||in_array($un,$seenUsernames)){ $importReport['skipped'][]="Satır $ln: Kullanıcı adı kullanımda ($un)."; continue; }
                                $pw=$cPass!==null?$v($cPass):'';
                                if(!$pw){ $pw=substr(str_shuffle('abcdefghjkmnpqrstuvxyz23456789'),0,8); }
                                try{
                                    $pdo->prepare("INSERT INTO users (username,password,role,name,site_id,block_id,floor,apartment_no,address,phone,email,notes) VALUES (?,?,'resident',?,?,?,?,?,?,?,?,?)")
                                        ->execute([$un,password_hash($pw,PASSWORD_BCRYPT),$nm,$mySiteId,$blk,$fl,$ap,$v($cAddr),$v($cPhone),$v($cMail),$v($cNote)]);
                                    $cur++; $seenUsernames[]=$un; $importReport['added']++;
                                    if(($cPass===null||$v($cPass)==='')&&$un) $importReport['gen'][$un]=$pw;
                                }catch(PDOException $ex){ $importReport['skipped'][]="Satır $ln: Kayıt hatası."; }
                            }
                            $success=$importReport['added'].' sakin eklendi.'.(count($importReport['skipped'])?' '.count($importReport['skipped']).' satır atlandı (detay aşağıda).':'');
                        }
                    }
                }
            } else { $error='Dosya seçilmedi.'; }
        } elseif ($a === 'add_announcement') {
            $t=trim($_POST['title']); $c=trim($_POST['content']);
            if ($t&&$c) { $pdo->prepare("INSERT INTO announcements (site_id,title,content) VALUES (?,?,?)")->execute([$mySiteId,$t,$c]); $success='Duyuru eklendi.'; }
            else $error='Başlık ve içerik gerekli.';
        } elseif ($a === 'delete_announcement') {
            $pdo->prepare("DELETE FROM announcements WHERE id=? AND site_id=?")->execute([$_POST['id'],$mySiteId]);
            $success='Duyuru silindi.';
        } elseif ($a === 'add_event') {
            $t=trim($_POST['title']); $d=trim($_POST['description']??''); $ed=$_POST['event_date'];
            if ($t&&$ed) { $pdo->prepare("INSERT INTO events (site_id,title,description,event_date) VALUES (?,?,?,?)")->execute([$mySiteId,$t,$d,$ed]); $success='Etkinlik eklendi.'; }
            else $error='Başlık ve tarih gerekli.';
        } elseif ($a === 'delete_event') {
            $pdo->prepare("DELETE FROM events WHERE id=? AND site_id=?")->execute([$_POST['id'],$mySiteId]);
            $success='Etkinlik silindi.';
        } elseif ($a === 'add_due') {
            $rid=(int)$_POST['resident_id']; $am=$_POST['amount']; $dd=$_POST['due_date']; $desc=trim($_POST['description']??'');
            if ($rid&&$am&&$dd) { $pdo->prepare("INSERT INTO dues (site_id,resident_id,amount,due_date,description) VALUES (?,?,?,?,?)")->execute([$mySiteId,$rid,$am,$dd,$desc]); $success='Aidat eklendi.'; }
            else $error='Tüm alanları doldurun.';
        } elseif ($a === 'mark_paid') {
            $pdo->prepare("UPDATE dues SET paid=1,paid_date=CURDATE() WHERE id=? AND site_id=?")->execute([$_POST['due_id'],$mySiteId]);
            $success='Aidat ödendi olarak işaretlendi.';
        } elseif ($a === 'mark_unpaid') {
            $pdo->prepare("UPDATE dues SET paid=0,paid_date=NULL WHERE id=? AND site_id=?")->execute([$_POST['due_id'],$mySiteId]);
            $success='Aidat ödenmedi olarak işaretlendi.';
        } elseif ($a === 'delete_due') {
            $pdo->prepare("DELETE FROM dues WHERE id=? AND site_id=?")->execute([$_POST['due_id'],$mySiteId]);
            $success='Aidat silindi.';
        } elseif ($a === 'save_due_setting') {
            $yr=(int)$_POST['year']; $ma=$_POST['monthly_amount'];
            if ($yr&&$ma) { saveDueSetting($pdo,$mySiteId,$yr,$ma); $success="$yr yılı aidat ücreti kaydedildi."; }
        } elseif ($a === 'bulk_create_dues') {
            $amount = (float)$_POST['amount'];
            $dueDate = trim($_POST['due_date']);
            $desc = trim($_POST['description'] ?? '');

            if ($amount > 0 && $dueDate) {
                $yr = (int)date('Y', strtotime($dueDate));
                $mo = (int)date('m', strtotime($dueDate));
                
                if (empty($desc)) {
                    $desc = "$yr / " . str_pad($mo, 2, '0', STR_PAD_LEFT) . " Dönemi Aidatı";
                }

                $residents = getResidentsBySite($pdo, $mySiteId);
                
                if (count($residents) > 0) {
                    $eklenen = 0;
                    $zaten_var = 0;
                    
                    $insertStmt = $pdo->prepare("INSERT INTO dues (site_id, resident_id, amount, due_date, description, paid) VALUES (?, ?, ?, ?, ?, 0)");
                    $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM dues WHERE resident_id=? AND YEAR(due_date)=? AND MONTH(due_date)=? AND description=?");

                    foreach ($residents as $r) {
                        $chkStmt->execute([$r['id'], $yr, $mo, $desc]);
                        
                        if ($chkStmt->fetchColumn() == 0) {
                            $insertStmt->execute([$mySiteId, $r['id'], $amount, $dueDate, $desc]);
                            $eklenen++;
                        } else {
                            $zaten_var++;
                        }
                    }
                    
                    $successMsg = "İşlem Tamamlandı! $eklenen daireye $amount ₺ yansıtıldı.";
                    if ($zaten_var > 0) {
                        $successMsg .= " ($zaten_var dairenin hesabında bu borç zaten bulunduğu için mükerrer kayıt yapılmadı.)";
                    }
                    $success = $successMsg;
                    
                } else {
                    $error = "Sitede henüz kayıtlı daire sakini bulunmuyor.";
                }
            } else {
                $error = "Lütfen geçerli bir tutar ve tarih giriniz.";
            };
        } elseif ($a === 'bulk_create_yearly') {
            $amount = (float)($_POST['amount'] ?? 0);
            $startDate = trim($_POST['start_date'] ?? '');
            $prefix = trim($_POST['description_prefix'] ?? '');
            if ($amount > 0 && $startDate) {
                $residents = getResidentsBySite($pdo, $mySiteId);
                if (count($residents) > 0) {
                    $insertStmt = $pdo->prepare("INSERT INTO dues (site_id, resident_id, amount, due_date, description, paid) VALUES (?, ?, ?, ?, ?, 0)");
                    $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM dues WHERE resident_id=? AND YEAR(due_date)=? AND MONTH(due_date)=? AND description=?");
                    $totalEklenen = 0; $totalVar = 0;
                    for ($i=0; $i<12; $i++) {
                        $dueDate = date('Y-m-d', strtotime($startDate . " +$i month"));
                        $yr = (int)date('Y', strtotime($dueDate));
                        $mo = (int)date('m', strtotime($dueDate));
                        $desc = $prefix ? "$prefix $yr/".str_pad($mo,2,'0',STR_PAD_LEFT) : "$yr / ".str_pad($mo,2,'0',STR_PAD_LEFT)." Dönemi Aidatı";
                        foreach ($residents as $r) {
                            $chkStmt->execute([$r['id'], $yr, $mo, $desc]);
                            if ($chkStmt->fetchColumn() == 0) { $insertStmt->execute([$mySiteId, $r['id'], $amount, $dueDate, $desc]); $totalEklenen++; } else { $totalVar++; }
                        }
                    }
                    $success = "1 yıllık işlem tamamlandı! Toplam $totalEklenen kayıt eklendi." . ($totalVar ? " ($totalVar mükerrer atlandı)" : "") . " — 12 ay × ".count($residents)." daire, ".money($amount)." ₺";
                } else $error="Sitede kayıtlı daire sakini yok.";
            } else $error="Tutar ve başlangıç tarihi gerekli.";
        } elseif ($a === 'add_expense') {
            $cat=$_POST['category']; $t=trim($_POST['title']); $am=$_POST['amount']; $ed=$_POST['expense_date']; $desc=trim($_POST['description']??'');
            if ($t&&$am&&$ed) { $pdo->prepare("INSERT INTO expenses (site_id,category,title,amount,expense_date,description) VALUES (?,?,?,?,?,?)")->execute([$mySiteId,$cat,$t,$am,$ed,$desc]); $success='Gider eklendi.'; }
            else $error='Zorunlu alanları doldurun.';
        } elseif ($a === 'delete_expense') {
            $pdo->prepare("DELETE FROM expenses WHERE id=? AND site_id=?")->execute([$_POST['expense_id'],$mySiteId]);
            $success='Gider silindi.';
        } elseif ($a === 'pay_subscription_manual') {
            $planId=(int)($_POST['plan_id']??0);
            $pl=$pdo->prepare("SELECT * FROM subscription_plans WHERE id=?"); $pl->execute([$planId]); $plan=$pl->fetch();
            if($plan){
                $start=date('Y-m-d'); $end=date('Y-m-d', strtotime('+1 month'));
                $pdo->prepare("INSERT INTO site_subscriptions (site_id, plan_id, status, current_period_start, current_period_end) VALUES (?,?,?,?,?)")->execute([$mySiteId,$planId,'pending',$start,$end]);
                $subId=$pdo->lastInsertId();
                $receiptPath=null;
                if(!empty($_FILES['receipt']['tmp_name'])){
                    $ext=strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
                    if(in_array($ext,['jpg','jpeg','png','pdf'])){
                        $dir="uploads/receipts"; if(!is_dir($dir)) mkdir($dir,0777,true);
                        $fn="sub_".$mySiteId."_".time().".$ext"; $dest="$dir/$fn";
                        if(move_uploaded_file($_FILES['receipt']['tmp_name'],$dest)) $receiptPath=$dest;
                    }
                }
                $pdo->prepare("INSERT INTO payments (site_id, subscription_id, amount, gateway, status, receipt_path, note, created_at) VALUES (?,?,?,?,?,?,?,NOW())")->execute([$mySiteId,$subId,$plan['price_monthly'],'manual','pending',$receiptPath,"Abonelik: ".$plan['name']]);
                $success='Havale bildiriminiz alındı, admin onayıyla aboneliğiniz aktif olacak.';
            } else $error='Paket bulunamadı.';
        } elseif ($a === 'pay_subscription_iyzico') {
            $planId=(int)($_POST['plan_id']??0);
            $pl=$pdo->prepare("SELECT * FROM subscription_plans WHERE id=?"); $pl->execute([$planId]); $plan=$pl->fetch();
            if($plan){
                try{
                    require_once 'includes/PaymentGateway.php';
                    $gw=new IyzicoGateway($pdo);
                    $start=date('Y-m-d'); $end=date('Y-m-d', strtotime('+1 month'));
                    $pdo->prepare("INSERT INTO site_subscriptions (site_id, plan_id, status, current_period_start, current_period_end) VALUES (?,?,?,?,?)")->execute([$mySiteId,$planId,'pending',$start,$end]);
                    $subId=$pdo->lastInsertId();
                    $result=$gw->createPayment([
                        'site_id'=>$mySiteId,
                        'user_id'=>$user['id'],
                        'subscription_id'=>$subId,
                        'amount'=>$plan['price_monthly'],
                        'description'=>"Abonelik: ".$plan['name'],
                        'note'=>"Abonelik: ".$plan['name']
                    ]);
                    if(!empty($result['success']) && !empty($result['redirect_url'])){
                        header("Location: ".$result['redirect_url']); exit;
                    } elseif(!empty($result['success'])){
                        $success='Ödeme oluşturuldu.';
                    } else {
                        $pdo->prepare("DELETE FROM site_subscriptions WHERE id=?")->execute([$subId]);
                        $error=$result['message'] ?? 'Ödeme oluşturulamadı. Havale yöntemini deneyin.';
                    }
                }catch(Exception $e){
                    if(isset($subId)) $pdo->prepare("DELETE FROM site_subscriptions WHERE id=?")->execute([$subId]);
                    $error='iyzico hata: '.$e->getMessage();
                }
            } else $error='Paket bulunamadı.';
        }
    } catch (PDOException $e) { $error='Hata: '.$e->getMessage(); }
}

// Grafik için veri çekme sorgusu
$chartStmt = $pdo->prepare("
    SELECT 
        (SELECT SUM(amount) FROM dues WHERE site_id = ? AND paid = 1 AND MONTH(paid_date) = m.m) as gelir,
        (SELECT SUM(amount) FROM expenses WHERE site_id = ? AND MONTH(expense_date) = m.m) as gider,
        m.m as ay
    FROM (SELECT 1 as m UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 
          UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) as m
");
$chartStmt->execute([$mySiteId, $mySiteId]);
$chartData = $chartStmt->fetchAll();

// CSV Export İşlemleri
if (isset($_GET['export']) && $_GET['export'] === 'finance') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=mali_rapor_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    fputcsv($output, ['Tarih', 'İşlem Tipi', 'Kisi / Kategori', 'Açıklama', 'Gelir (TL)', 'Gider (TL)'], ';');
    
    $toplamGelir = 0; $toplamGider = 0;

    $g1 = $pdo->prepare("SELECT paid_date as t_date, 'Aidat Tahsilatı' as tip, u.name as kisi, d.description, d.amount 
                         FROM dues d JOIN users u ON d.resident_id = u.id WHERE d.site_id = ? AND d.paid = 1");
    $g1->execute([$mySiteId]); $gelirler = $g1->fetchAll(PDO::FETCH_ASSOC);

    $g2 = $pdo->prepare("SELECT expense_date as t_date, 'Gider' as tip, category as kisi, title as description, amount 
                         FROM expenses WHERE site_id = ?");
    $g2->execute([$mySiteId]); $giderler = $g2->fetchAll(PDO::FETCH_ASSOC);

    $tumIslemler = array_merge($gelirler, $giderler);
    usort($tumIslemler, function($a, $b) { return strtotime($b['t_date']) - strtotime($a['t_date']); });

    foreach($tumIslemler as $islem) {
        $gelir = $islem['tip'] === 'Aidat Tahsilatı' ? $islem['amount'] : 0;
        $gider = $islem['tip'] === 'Gider' ? $islem['amount'] : 0;
        $toplamGelir += $gelir; $toplamGider += $gider;

        fputcsv($output, [
            $islem['t_date'] ? date('d.m.Y', strtotime($islem['t_date'])) : '-',
            $islem['tip'], $islem['kisi'], $islem['description'],
            $gelir > 0 ? str_replace('.', ',', $gelir) : '-',
            $gider > 0 ? str_replace('.', ',', $gider) : '-'
        ], ';');
    }

    fputcsv($output, ['', '', '', '', '', ''], ';'); 
    fputcsv($output, ['ÖZET', '', '', 'TOPLAM GELİR:', str_replace('.', ',', $toplamGelir) . ' TL', ''], ';');
    fputcsv($output, ['ÖZET', '', '', 'TOPLAM GİDER:', '', str_replace('.', ',', $toplamGider) . ' TL'], ';');
    
    $kasa = $toplamGelir - $toplamGider;
    $kasaDurumu = $kasa > 0 ? '+ ' . str_replace('.', ',', $kasa) : str_replace('.', ',', $kasa);
    fputcsv($output, ['ÖZET', '', '', 'KASADAKİ NET TUTAR:', $kasaDurumu . ' TL', ''], ';');

    fclose($output); exit;
}
if (isset($_GET['export']) && $_GET['export'] === 'dues') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=aidatlar_listesi.csv');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['Ad Soyad', 'Daire', 'Tutar', 'Durum', 'Vade', 'Açıklama'], ';');
    
    $rows = $pdo->prepare("SELECT d.*, u.name as resident_name, u.phone FROM dues d JOIN users u ON d.resident_id = u.id WHERE d.site_id = ?");
    $rows->execute([$mySiteId]);
    
    foreach ($rows as $row) {
        fputcsv($output, [
            $row['resident_name'], $row['apartment_no'], 
            str_replace('.', ',', $row['amount']), 
            ($row['paid'] ? 'Ödendi' : 'Ödenmedi'), 
            $row['due_date'], $row['description']
        ], ';');
    }
    fclose($output); exit;
}

// Data
$residents = getResidentsBySite($pdo, $mySiteId);
$blkStmt = $pdo->prepare("SELECT * FROM blocks WHERE site_id=? ORDER BY name"); $blkStmt->execute([$mySiteId]); $siteBlocks = $blkStmt->fetchAll();
$activeBlock = $_GET['block'] ?? 'all';
$announcements = getAnnouncementsBySite($pdo, $mySiteId);
$events = getEventsBySite($pdo, $mySiteId);
$dueSummary = getDueSummary($pdo, $mySiteId);

$dueFilter = $_GET['filter'] ?? 'all';
$dueYear   = $_GET['year'] ?? null;
$dueMonth  = $_GET['month'] ?? null;
$dues = getDuesBySite($pdo, $mySiteId, $dueFilter, $dueYear, $dueMonth);

$curYear = (int)date('Y');
$dueSetting = getDueSetting($pdo, $mySiteId, $curYear);

$expenses = getExpensesBySite($pdo, $mySiteId, $curYear);
$expSummary = getExpenseSummary($pdo, $mySiteId, $curYear);
$expTotal = array_sum(array_column($expSummary, 'total'));
$allPlans = $pdo->query("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY price_monthly")->fetchAll();
$residaBankName = $_ENV['RESIDA_BANK_NAME'] ?? $_ENV['BANK_NAME'] ?? 'RESIDA PRO';
$residaIban = $_ENV['RESIDA_BANK_IBAN'] ?? $_ENV['BANK_IBAN'] ?? 'TR00 0000 0000 0000 0000 0000 00';
$residaHolder = $_ENV['RESIDA_BANK_HOLDER'] ?? $_ENV['BANK_HOLDER'] ?? 'RESIDA PRO';
$pendingSubPayments = $pdo->prepare("SELECT p.*, pl.name as plan_name FROM payments p LEFT JOIN site_subscriptions ss ON p.subscription_id=ss.id LEFT JOIN subscription_plans pl ON ss.plan_id=pl.id WHERE p.site_id=? AND p.subscription_id IS NOT NULL AND p.status='pending' ORDER BY p.created_at DESC");
$pendingSubPayments->execute([$mySiteId]); $pendingSubPayments = $pendingSubPayments->fetchAll();

$pdo->exec("ALTER TABLE expenses MODIFY category VARCHAR(100)");
$defaultCats = ['Bakım', 'Temizlik', 'Güvenlik', 'Elektrik', 'Su', 'Doğalgaz', 'Asansör', 'Sigorta', 'Diğer'];
$stmtCat = $pdo->prepare("SELECT DISTINCT category FROM expenses WHERE site_id = ?");
$stmtCat->execute([$mySiteId]);
$dbCats = $stmtCat->fetchAll(PDO::FETCH_COLUMN);
$allCategories = array_unique(array_merge($defaultCats, $dbCats));

$catColors = ['Bakım'=>'#6366f1','Temizlik'=>'#06b6d4','Güvenlik'=>'#f59e0b','Elektrik'=>'#f97316','Su'=>'#3b82f6','Doğalgaz'=>'#ef4444','Asansör'=>'#8b5cf6','Sigorta'=>'#10b981','Diğer'=>'#64748b'];

function getResidentDues($pdo, $resId) {
    $s=$pdo->prepare("SELECT * FROM dues WHERE resident_id=? ORDER BY due_date DESC LIMIT 12");
    $s->execute([$resId]); return $s->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($siteName) ?> – RESİDA PRO</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<style>
.sidebar { transition: all 0.3s ease; overflow-x: hidden; white-space: nowrap; }
body.sidebar-hidden .sidebar { width: 0 !important; min-width: 0 !important; padding-left: 0 !important; padding-right: 0 !important; opacity: 0; border: none !important; }
.main-content { transition: all 0.3s ease; flex-grow: 1; }
body.sidebar-hidden .main-content { margin-left: 0 !important; width: 100% !important; }

/* ===== KUSURSUZ PDF YAZDIRMA CSS AYARLARI ===== */
#printArea { display: none; }
@media print {
    /* Ekranda görünen her şeyi gizle */
    body > * { display: none !important; }
    .modal, .modal-backdrop { display: none !important; }
    /* Sadece yazdırılacak alanı göster */
    #printArea { 
        display: block !important; 
        position: absolute; 
        left: 0; 
        top: 0; 
        width: 100%; 
        background: white;
    }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div id="printArea"></div>

<div class="app-layout">
<aside class="sidebar">
  <div class="sidebar-brand d-flex align-items-center gap-3">
        <div class="sidebar-logo">
    <a href="index.php">
        <img src="assets/img/resida-pro-logo2.png" alt="Resida Pro" style="max-width: 125%; height: auto; display: block;">
    </a>
</div>
    <div class="sidebar-brand-text"><h3>RESİDA PRO</h3><small><?= htmlspecialchars($siteName) ?></small></div>
  </div>
  <div class="sidebar-user d-flex align-items-center gap-3">
    <div class="user-avatar"><?= avatarLetter($user['name']) ?></div>
    <div class="user-info"><div class="user-name"><?= htmlspecialchars($user['name']) ?></div><div class="user-role"><span class="badge badge-manager">Yönetici</span></div></div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Genel</div>
    <a href="?page=dashboard" class="nav-link <?= $page==='dashboard'?'active':'' ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="?page=myplan" class="nav-link <?= $page==='myplan'?'active':'' ?>"><i class="fa-solid fa-crown"></i> Paket & Abonelik</a>
    <div class="sidebar-section">Yönetim</div>
    <a href="?page=residents" class="nav-link <?= $page==='residents'?'active':'' ?>"><i class="fa-solid fa-people-roof"></i> Sakinler <span class="ms-auto badge" style="background:rgba(6,182,212,.2);color:#67e8f9;font-size:.68rem"><?= count($residents) ?></span></a>
    <a href="?page=dues" class="nav-link <?= $page==='dues'?'active':'' ?>"><i class="fa-solid fa-coins"></i> Aidatlar</a>
    <a href="?page=expenses" class="nav-link <?= $page==='expenses'?'active':'' ?>"><i class="fa-solid fa-receipt"></i> Giderler</a>
    <div class="sidebar-section">İletişim</div>
    <a href="?page=announcements" class="nav-link <?= $page==='announcements'?'active':'' ?>"><i class="fa-solid fa-bullhorn"></i> Duyurular</a>
    <a href="?page=events" class="nav-link <?= $page==='events'?'active':'' ?>"><i class="fa-solid fa-calendar-days"></i> Etkinlikler</a>
    <div class="sidebar-section">Ayarlar</div>
    <a href="?page=settings" class="nav-link <?= $page==='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> Site Ayarları</a>
  </nav>
  <div class="sidebar-footer"><a href="logout.php" class="nav-link" style="color:#f87171!important"><i class="fa-solid fa-right-from-bracket"></i> Çıkış</a></div>
</aside>

<div class="main-content">
<header class="topbar d-flex justify-content-between align-items-center">
  <div class="d-flex align-items-center">
    <button id="sidebarToggle" class="btn btn-light btn-sm border me-3 shadow-sm d-flex align-items-center gap-2" title="Menüyü Aç/Kapat">
      <i class="fa-solid fa-bars fs-6"></i> <span>Menü</span>
    </button>
    <span class="topbar-title"><i class="fa-solid fa-<?php
      $icons=['dashboard'=>'gauge-high','myplan'=>'crown','residents'=>'people-roof','dues'=>'coins','expenses'=>'receipt','announcements'=>'bullhorn','events'=>'calendar-days','settings'=>'gear'];
      echo $icons[$page]??'gauge-high';
    ?> me-2 text-accent"></i><?php
      $titles=['dashboard'=>'Dashboard','myplan'=>'Paketim','residents'=>'Sakinler','dues'=>'Aidat Yönetimi','expenses'=>'Giderler','announcements'=>'Duyurular','events'=>'Etkinlikler','settings'=>'Site Ayarları'];
      echo $titles[$page]??'Dashboard';
    ?></span>
  </div>
  <div class="topbar-right"><span style="font-size:.8rem;color:#475569"><?= date('d.m.Y') ?></span></div>
</header>

<div class="content-body fade-in">
<?php if($success): ?><div class="alert alert-success alert-dismissible fade show mb-4"><i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger alert-dismissible fade show mb-4"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<?php if(!$isSubscriptionActive && $page !== 'myplan'): ?><div class="d-flex align-items-center justify-content-center" style="min-height:60vh"><div class="card text-center p-5 shadow" style="max-width:520px;width:100%"><h4>Abonelik Gerekli</h4><p class="text-muted">Bu sitenin aboneliği aktif değil. Lütfen paket alın.</p><a href="?page=myplan" class="btn btn-primary">Paket Al</a></div></div><?php elseif($page==='dashboard'): ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1>Hoş Geldiniz, <?= htmlspecialchars($user['name']) ?> 👋</h1>
        <p><?= htmlspecialchars($siteName) ?> site yönetim paneli</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="?export=finance" class="btn btn-success shadow-sm" style="background-color: #10b981; border:none;">
            <i class="fa-solid fa-file-excel me-2"></i>Kasa Raporu (Excel)
        </a>
        <button onclick="printExpensePDF()" class="btn btn-danger shadow-sm" style="border:none;">
            <i class="fa-solid fa-file-pdf me-2"></i>Gider PDF
        </button>
    </div>
</div>
<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="fa-solid fa-people-roof"></i></div>
      <div class="stat-info">
        <div class="stat-value">
          <?= count($residents) ?>
          <?php if($maxResidents > 0): ?>
            <span style="font-size:0.65em; color:#94a3b8; font-weight: 500;">/ <?= $maxResidents ?></span>
          <?php endif; ?>
        </div>
        <div class="stat-label">Daire Sakini</div>
      </div>
    </div>
  </div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-clock"></i></div><div class="stat-info"><div class="stat-value"><?= $dueSummary['unpaid_count']??0 ?></div><div class="stat-label">Ödenmemiş Aidat</div><div class="stat-sub"><?= money($dueSummary['unpaid_amount']??0) ?> ₺</div></div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-check-double"></i></div><div class="stat-info"><div class="stat-value"><?= money($dueSummary['paid_amount']??0) ?> ₺</div><div class="stat-label">Tahsil Edilen</div></div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon amber"><i class="fa-solid fa-receipt"></i></div><div class="stat-info"><div class="stat-value"><?= money($expTotal) ?> ₺</div><div class="stat-label">Toplam Gider (<?= $curYear ?>)</div></div></div></div>
</div>

<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><span class="fw-700">Aylık Finansal Akış (Gelir vs Gider)</span></div>
      <div class="card-body">
        <canvas id="financeChart" height="80"></canvas>
      </div>
    </div>
  </div>
</div>

<?php $paidPct = ($dueSummary['total']??0) > 0 ? round(($dueSummary['paid_count']??0)/($dueSummary['total'])*100) : 0; ?>
<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header"><span class="fw-700"><i class="fa-solid fa-chart-pie me-2 text-accent"></i>Aidat Tahsilat Oranı</span></div>
      <div class="card-body d-flex flex-column justify-content-center align-items-center">
        <div style="width: 220px; height: 220px; position: relative;">
            <canvas id="duePieChart"></canvas>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.8rem; font-weight: 800; color: var(--text);">
                %<?= $paidPct ?>
            </div>
        </div>
        <div class="d-flex justify-content-center gap-5 mt-4 w-100">
            <div class="text-center">
                <div style="color: #10b981; font-weight: 800; font-size: 1.3rem;"><?= $dueSummary['paid_count'] ?? 0 ?></div>
                <small class="text-success fw-bold">Ödenen</small>
            </div>
            <div class="text-center">
                <div style="color: #ef4444; font-weight: 800; font-size: 1.3rem;"><?= $dueSummary['unpaid_count'] ?? 0 ?></div>
                <small class="text-danger fw-bold">Bekleyen</small>
            </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-700"><i class="fa-solid fa-receipt me-2 text-warning"></i>Gider Dağılımı (<?= $curYear ?>)</span><a href="?page=expenses" class="btn btn-sm btn-outline-primary">Detay</a></div>
      <div class="card-body p-0">
        <?php if($expSummary): ?>
        <table class="table mb-0"><tbody>
        <?php foreach($expSummary as $es): $cl=$catColors[$es['category']]??'#64748b'; ?>
        <tr><td><span class="cat-dot me-2" style="background:<?= $cl ?>"></span><?= $es['category'] ?></td><td class="text-end fw-700 money"><?= money($es['total']) ?> ₺</td></tr>
        <?php endforeach; ?>
        <tr style="border-top:2px solid var(--border)"><td class="fw-700">Toplam</td><td class="text-end fw-800 money text-warning"><?= money($expTotal) ?> ₺</td></tr>
        </tbody></table>
        <?php else: ?><div class="empty-state py-4"><i class="fa-solid fa-receipt"></i><p>Henüz gider kaydı yok</p></div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pieCtx = document.getElementById('duePieChart');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ödenen Aidat', 'Bekleyen Aidat'],
                datasets: [{
                    data: [<?= $dueSummary['paid_count'] ?? 0 ?>, <?= $dueSummary['unpaid_count'] ?? 0 ?>],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 0,
                    cutout: '75%',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>

<?php elseif($page==='settings'): ?>
<div class="page-header">
  <div><h1><i class="fa-solid fa-gear me-2 text-accent"></i>Site Ayarları</h1><p>Site bilgileri, tahsilat hesabı ve gecikme faizi</p></div>
</div>
<div class="card"><div class="card-body">
<form method="post">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<input type="hidden" name="action" value="save_site_settings">
<h6 class="fw-700 mb-3"><i class="fa-solid fa-building me-1"></i>Site Bilgileri</h6>
<div class="row g-3">
  <div class="col-md-6"><label class="form-label">Site Adı *</label><input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($siteName) ?>" required></div>
  <div class="col-md-6"><label class="form-label">Adres</label><input type="text" name="site_address" class="form-control" value="<?= htmlspecialchars($siteAddress) ?>"></div>
</div>
<hr class="divider my-4">
<h6 class="fw-700 mb-1"><i class="fa-solid fa-building-columns me-1"></i>Tahsilat Hesabı (IBAN)</h6>
<p class="small text-muted">Sakinler aidatlarını bu hesaba yatırır. Sakin panelinde ve makbuzlarda görünür.</p>
<div class="row g-3">
  <div class="col-md-4"><label class="form-label">Banka Adı</label><input type="text" name="bank_name" class="form-control" placeholder="Örn: Ziraat Bankası" value="<?= htmlspecialchars($siteBank) ?>"></div>
  <div class="col-md-4"><label class="form-label">IBAN</label><input type="text" name="iban" class="form-control" placeholder="TR00 0000 0000 0000 0000 0000 00" value="<?= htmlspecialchars($siteIban) ?>"></div>
  <div class="col-md-4"><label class="form-label">Hesap Sahibi</label><input type="text" name="iban_holder" class="form-control" placeholder="Site Yönetimi" value="<?= htmlspecialchars($siteHolder) ?>"></div>
</div>
<hr class="divider my-4">
<h6 class="fw-700 mb-3"><i class="fa-solid fa-percent me-1"></i>Gecikme Faizi</h6>
<div class="row g-3 align-items-end">
  <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="penalty_enabled" value="1" <?= $penEnabled?'checked':'' ?>><label class="form-check-label">Faiz uygulansın</label></div></div>
  <div class="col-md-4"><label class="form-label">Aylık Oran (%)</label><input type="number" step="0.01" min="0" name="penalty_rate" class="form-control" value="<?= htmlspecialchars($penRate) ?>"></div>
  <div class="col-md-4"><label class="form-label">Hoşgörü Günü</label><input type="number" min="0" name="penalty_grace_days" class="form-control" value="<?= (int)$penGrace ?>"></div>
</div>
<div class="mt-4"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Kaydet</button></div>
</form>
</div></div>

<?php elseif($page==='myplan'): ?>
<div class="page-header">
  <h1><i class="fa-solid fa-crown me-2 text-warning"></i>Paketim & Abonelik</h1>
  <p>RESIDA PRO aboneliğini buradan yönet, aylık ödemeni yap</p>
</div>
<?php if($mySubscription):
  $daysLeft = (int)floor((strtotime($mySubscription['current_period_end']) - time())/86400);
  $isExpired = $daysLeft < 0; $isExpiring = $daysLeft >=0 && $daysLeft <=7;
?>
<div class="card mb-4 <?= $isExpired?'border-danger':($isExpiring?'border-warning':'border-success') ?>">
  <div class="card-body d-flex justify-content-between flex-wrap gap-3 align-items-center">
    <div>
      <h5 class="fw-700 mb-1"><i class="fa-solid fa-crown me-2 text-warning"></i><?= htmlspecialchars($mySubscription['plan_name']) ?> Paket</h5>
      <div class="small text-muted">Dönem: <?= date_tr($mySubscription['current_period_start']) ?> → <?= date_tr($mySubscription['current_period_end']) ?> · Durum: <span class="badge <?= $mySubscription['status']==='active'?'bg-success':'bg-warning text-dark' ?>"><?= htmlspecialchars($mySubscription['status']) ?></span></div>
      <div class="mt-2"><?php if($isExpired): ?><span class="badge bg-danger">Süresi doldu</span><?php elseif($isExpiring): ?><span class="badge bg-warning text-dark"><?= $daysLeft ?> gün kaldı</span><?php else: ?><span class="badge bg-success"><?= $daysLeft ?> gün kaldı</span><?php endif; ?> <span class="ms-2 small">Aylık: <?= money($mySubscription['price_monthly'] ?? 0) ?> ₺</span></div>
    </div>
  </div>
</div>
<?php else: ?><div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Henüz aktif aboneliğin yok. Aşağıdan bir paket seçip ödemeni yap.</div><?php endif; ?>
<?php if($pendingSubPayments): ?>
<div class="card mb-4 border-warning"><div class="card-header bg-warning bg-opacity-10"><span class="fw-700"><i class="fa-solid fa-clock me-2"></i>Bekleyen Ödemen</span></div><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Paket</th><th>Tutar</th><th>Yöntem</th><th>Tarih</th></tr></thead><tbody><?php foreach($pendingSubPayments as $ps): ?><tr><td><?= htmlspecialchars($ps['plan_name'] ?? '-') ?></td><td class="fw-700"><?= money($ps['amount']) ?> ₺</td><td><?= htmlspecialchars($ps['gateway']) ?></td><td class="small"><?= datetime_tr($ps['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php endif; ?>
<div class="row g-4">
<?php foreach($allPlans as $pl): $isCurrent=$mySubscription && $mySubscription['plan_id']==$pl['id']; ?>
<div class="col-md-4"><div class="card h-100 <?= $isCurrent?'border-success':'' ?>"><div class="card-body"><h5 class="fw-700"><?= htmlspecialchars($pl['name']) ?> <?php if($isCurrent): ?><span class="badge bg-success">Mevcut</span><?php endif; ?></h5><div class="my-2"><span class="fs-4 fw-bold"><?= money($pl['price_monthly']) ?> ₺</span><span class="text-muted">/ay</span></div><button class="btn <?= $isCurrent?'btn-outline-success':'btn-primary' ?> w-100 mt-2" data-bs-toggle="modal" data-bs-target="#buyPlan<?= $pl['id'] ?>"><i class="fa-solid fa-cart-shopping me-1"></i><?= $isCurrent?'Yenile':'Satın Al' ?></button></div></div></div>
<div class="modal fade" id="buyPlan<?= $pl['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><?= htmlspecialchars($pl['name']) ?> — <?= money($pl['price_monthly']) ?> ₺/ay</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-info small"><strong>RESIDA Hesabı</strong><br><?= htmlspecialchars($residaBankName) ?> — <?= htmlspecialchars($residaHolder) ?><br>IBAN: <code><?= htmlspecialchars($residaIban) ?></code><br>Tutar: <strong><?= money($pl['price_monthly']) ?> ₺</strong></div><ul class="nav nav-tabs mb-3" role="tablist"><li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#havale<?= $pl['id'] ?>">Havale / EFT</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#kart<?= $pl['id'] ?>">Kart (iyzico)</a></li></ul><div class="tab-content"><div class="tab-pane fade show active" id="havale<?= $pl['id'] ?>"><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="pay_subscription_manual"><input type="hidden" name="plan_id" value="<?= $pl['id'] ?>"><div class="mb-3"><label class="form-label">Dekont Yükle (JPG/PNG/PDF)</label><input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf"></div><button type="submit" class="btn btn-primary w-100">Havale Bildir — Onay bekleniyor</button></form></div><div class="tab-pane fade" id="kart<?= $pl['id'] ?>"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="pay_subscription_iyzico"><input type="hidden" name="plan_id" value="<?= $pl['id'] ?>"><button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-credit-card me-1"></i>Kartla Öde — <?= money($pl['price_monthly']) ?> ₺</button></form></div></div></div></div></div></div>
<?php endforeach; ?></div>

<?php elseif($page==='residents'): ?>
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
  <div><h1><i class="fa-solid fa-people-roof me-2 text-cyan"></i>Daire Sakinleri</h1><p><?= count($residents) ?> kayıtlı sakin<?= $siteBlocks ? ' · '.count($siteBlocks).' blok' : '' ?></p></div>
  <div class="d-flex gap-2 flex-wrap">
    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addBlockModal"><i class="fa-solid fa-layer-group me-2"></i>Blok Ekle</button>
    <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#importResidentsModal"><i class="fa-solid fa-file-import me-2"></i>Toplu İçe Aktar</button>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResidentModal"><i class="fa-solid fa-user-plus me-2"></i>Yeni Sakin</button>
  </div>
</div>
<?php if(!empty($importReport??[])): ?>
<div class="card mb-4 border-<?= ($importReport['added']??0)?'success':'warning' ?>"><div class="card-body">
<div class="fw-700 mb-2"><i class="fa-solid fa-file-import me-1"></i><?= (int)($importReport['added']??0) ?> sakin eklendi, <?= count($importReport['skipped']??[]) ?> satır atlandı.</div>
<?php if(!empty($importReport['gen'])): ?><div class="alert alert-info"><b>Otomatik oluşturulan giriş bilgileri (bir kez gösterilir, not alın):</b><br><?php foreach($importReport['gen'] as $u=>$p): ?><span class="badge bg-dark me-1"><?= htmlspecialchars($u) ?> / <?= htmlspecialchars($p) ?></span><?php endforeach; ?></div><?php endif; ?>
<?php if(!empty($importReport['skipped'])): ?><ul class="small text-muted mb-0"><?php foreach(array_slice($importReport['skipped'],0,30) as $s): ?><li><?= htmlspecialchars($s) ?></li><?php endforeach; ?></ul><?php endif; ?>
</div></div>
<?php endif; ?>
<?php if($siteBlocks): ?>
<div class="filter-tabs mb-4">
  <a href="?page=residents&block=all" class="filter-tab <?= $activeBlock==='all'?'active':'' ?>">Tümü (<?= count($residents) ?>)</a>
  <?php foreach($siteBlocks as $b): $bcnt=count(array_filter($residents,fn($x)=>($x['block_id']??null)==$b['id'])); ?>
  <a href="?page=residents&block=<?=$b['id']?>" class="filter-tab <?= (string)$activeBlock===(string)$b['id']?'active':'' ?>"><?= htmlspecialchars($b['name']) ?> (<?= $bcnt ?>)</a>
  <?php endforeach; ?>
  <?php $ncnt=count(array_filter($residents,fn($x)=>empty($x['block_id']))); if($ncnt): ?>
  <a href="?page=residents&block=none" class="filter-tab <?= $activeBlock==='none'?'active':'' ?>">Bloksuz (<?= $ncnt ?>)</a>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php $activeBlockRow=null; foreach($siteBlocks as $bb){ if((string)$activeBlock===(string)$bb['id']){ $activeBlockRow=$bb; break; } } ?>
<?php if($activeBlockRow): ?>
<div class="d-flex gap-2 align-items-center mb-4 flex-wrap">
  <span class="fw-700"><i class="fa-solid fa-layer-group me-1"></i><?= htmlspecialchars($activeBlockRow['name']) ?> bloğu:</span>
  <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editBlockModal"><i class="fa-solid fa-pen me-1"></i>Yeniden Adlandır</button>
  <form method="post" style="display:inline" onsubmit="return confirm('<?= htmlspecialchars($activeBlockRow['name']) ?> silinsin mi? İçindeki sakinler Bloksuz listesine taşınır.')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="delete_block"><input type="hidden" name="block_id" value="<?= $activeBlockRow['id'] ?>"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash me-1"></i>Bloku Sil</button></form>
</div>
<div class="modal fade" id="editBlockModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="edit_block"><input type="hidden" name="block_id" value="<?= $activeBlockRow['id'] ?>">
<div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Blok Adını Değiştir</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="mb-3"><label class="form-label">Blok Adı *</label><input type="text" name="block_name" class="form-control" value="<?= htmlspecialchars($activeBlockRow['name']) ?>" required></div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-warning">Güncelle</button></div>
</form></div></div></div>
<?php endif; ?>
<?php
$shownResidents=$residents;
if($siteBlocks&&$activeBlock!=='all'){ $shownResidents=array_filter($residents,fn($x)=> $activeBlock==='none' ? empty($x['block_id']) : (string)($x['block_id']??'')===(string)$activeBlock); }
?>
<?php if($shownResidents): ?>
<div class="residents-grid">
<?php $ri=0; foreach($shownResidents as $r): $i=$ri++;
$colors=['c1','c2','c3','c4','c5']; $cc=$colors[$i%5];
$rDues=getResidentDues($pdo,$r['id']);
$hasDebt=count(array_filter($rDues,fn($d)=>!$d['paid']))>0; ?>
<div class="resident-card" data-bs-toggle="modal" data-bs-target="#residentModal<?= $r['id'] ?>">
  <div class="resident-status <?= $hasDebt?'has-debt':'clear' ?>"></div>
  <div class="resident-avatar <?= $cc ?>"><?= avatarLetter($r['name']) ?></div>
  <div class="resident-name"><?= htmlspecialchars($r['name']) ?></div>
  <div class="resident-apt"><?php if(!empty($r['block_name'])): ?><?= htmlspecialchars($r['block_name']) ?> · <?php endif; ?><?= $r['floor'] ?>. Kat · Daire <?= $r['apartment_no'] ?></div>
  <div class="resident-meta">
    <?php if($r['phone']): ?><span class="resident-tag"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($r['phone']) ?></span><?php endif; ?>
    <span class="resident-tag"><?= $hasDebt?'<i class="fa-solid fa-triangle-exclamation me-1" style="color:var(--danger)"></i>Borçlu':'<i class="fa-solid fa-check me-1" style="color:var(--success)"></i>Temiz' ?></span>
  </div>
</div>

<div class="modal fade" id="residentModal<?= $r['id'] ?>" tabindex="-1">
<div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header">
  <h5 class="modal-title"><i class="fa-solid fa-user me-2 text-cyan"></i><?= htmlspecialchars($r['name']) ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
  <ul class="nav nav-tabs mb-3" role="tablist" style="border-color:var(--border)">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#info<?= $r['id'] ?>" style="color:var(--text2)">Bilgiler</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#rdues<?= $r['id'] ?>" style="color:var(--text2)">Aidatlar</a></li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="info<?= $r['id'] ?>">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="edit_resident">
        <input type="hidden" name="user_id" value="<?= $r['id'] ?>">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Ad Soyad</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($r['name']) ?>" required></div>
          <div class="col-md-3"><label class="form-label">Kat</label><input type="text" name="floor" class="form-control" value="<?= htmlspecialchars($r['floor']) ?>" required></div>
          <div class="col-md-3"><label class="form-label">Daire No</label><input type="text" name="apartment_no" class="form-control" value="<?= htmlspecialchars($r['apartment_no']) ?>" required></div>
          <?php if($siteBlocks): ?><div class="col-md-6"><label class="form-label">Blok</label><select name="block_id" class="form-control"><option value="">Bloksuz</option><?php foreach($siteBlocks as $b): ?><option value="<?=$b['id']?>" <?=($r['block_id']??null)==$b['id']?'selected':''?>><?=htmlspecialchars($b['name'])?></option><?php endforeach; ?></select></div><?php endif; ?>
          <div class="col-md-6"><label class="form-label">Telefon</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($r['phone']??'') ?>"></div>
          <div class="col-md-6"><label class="form-label">E-posta</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($r['email']??'') ?>"></div>
          <div class="col-12"><label class="form-label">Adres</label><input type="text" name="address" class="form-control" value="<?= htmlspecialchars($r['address']??'') ?>"></div>
          <div class="col-12"><label class="form-label">Notlar</label><textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($r['notes']??'') ?></textarea></div>
          <div class="col-md-6"><label class="form-label">Yeni Şifre <small class="text-subtle">(boş = değişmez)</small></label><input type="password" name="password" class="form-control"></div>
        </div>
        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Kaydet</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        </div>
      </form>
      <hr class="divider my-3">
      <form method="post" onsubmit="return confirm('Bu sakini silmek istediğinize emin misiniz?')">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="delete_resident"><input type="hidden" name="user_id" value="<?= $r['id'] ?>">
        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash me-1"></i>Sakini Sil</button>
      </form>
    </div>
  <div class="tab-pane fade" id="rdues<?= $r['id'] ?>">
      <?php
      $unpaidDues = array_filter($rDues, fn($d)=>!$d['paid']);
      $toplamBorc = array_sum(array_column($unpaidDues, 'amount'));
      $waPhone = preg_replace('/[^0-9]/', '', $r['phone'] ?? '');
      if (str_starts_with($waPhone, '0')) $waPhone = '9' . $waPhone;
      elseif (strlen($waPhone) == 10) $waPhone = '90' . $waPhone;
      $waMesaj = urlencode("Merhaba " . $r['name'] . ",\n" . $siteName . " yönetimi olarak hatırlatmak isteriz. Sistemimizde toplam *" . money($toplamBorc) . " TL* ödenmemiş aidat borcunuz bulunmaktadır. Lütfen en kısa sürede ödemenizi gerçekleştiriniz. İyi günler dileriz.");
      ?>
      <?php if($toplamBorc > 0): ?>
          <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
              <div><i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Toplam Borç:</strong> <span class="fs-5 text-danger fw-bold"><?= money($toplamBorc) ?> ₺</span></div>
              <?php if($waPhone): ?>
                  <a href="https://wa.me/<?= $waPhone ?>?text=<?= $waMesaj ?>" target="_blank" class="btn btn-success btn-sm" style="background-color: #25D366; border-color: #25D366; color: white;">
                      <i class="fa-brands fa-whatsapp fs-6 me-1"></i> WhatsApp'tan Bildir
                  </a>
              <?php else: ?>
                  <small class="text-muted"><i class="fa-solid fa-phone-slash me-1"></i>Tel. Kayıtlı Değil</small>
              <?php endif; ?>
          </div>
      <?php endif; ?>
      <?php if($rDues): ?>
      <div class="table-responsive">
          <table class="table"><thead><tr><th>Tutar</th><th>Vade</th><th>Durum</th><th>Ödeme</th></tr></thead><tbody>
          <?php foreach($rDues as $d): ?>
          <tr><td class="fw-700 money"><?= money($d['amount']) ?> ₺</td><td><?= date_tr($d['due_date']) ?></td>
          <td><span class="badge <?= $d['paid']?'badge-paid':'badge-unpaid' ?>"><?= $d['paid']?'Ödendi':'Ödenmedi' ?></span></td>
          <td><?= $d['paid']?date_tr($d['paid_date']):'-' ?></td></tr>
          <?php endforeach; ?></tbody></table>
      </div>
      <?php else: ?><p class="text-subtle text-center py-3">Aidat kaydı yok</p><?php endif; ?>
    </div>
  </div>
</div>
</div></div></div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state"><i class="fa-solid fa-people-roof"></i><h4>Henüz sakin eklenmedi</h4><p>Yukarıdaki butonla ilk sakini ekleyin.</p></div>
<?php endif; ?>

<?php elseif($page==='dues'): ?>
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
  <div><h1><i class="fa-solid fa-coins me-2 text-warning"></i>Aidat Yönetimi</h1></div>
<div class="d-flex gap-2 flex-wrap">
  <a href="manager_panel.php?export=dues" class="btn btn-success btn-sm"><i class="fa-solid fa-file-excel me-1"></i> Excel'e Aktar</a>
  <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkWaModal" style="background-color: #25D366; border-color: #25D366; color: white;"><i class="fa-brands fa-whatsapp me-1"></i>Toplu Hatırlat</button>
  <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#freeReceiptModal"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Serbest Makbuz</button>
  <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#dueSettingModal"><i class="fa-solid fa-gear me-1"></i>Yıllık Ücret</button>
  <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkDueModal"><i class="fa-solid fa-layer-group me-1"></i>Toplu Oluştur</button>
  <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#yearlyDueModal" style="background:#f59e0b;border-color:#f59e0b;color:white"><i class="fa-solid fa-calendar-check me-1"></i>1 Yıllık Otomatik</button>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDueModal"><i class="fa-solid fa-plus me-1"></i>Aidat Ekle</button>
</div>
</div>

<?php if($dueSetting): ?>
<div class="alert alert-info mb-3"><i class="fa-solid fa-info-circle me-2"></i><?= $curYear ?> yılı aylık aidat ücreti: <strong><?= money($dueSetting['monthly_amount']) ?> ₺</strong></div>
<?php endif; ?>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
  <div class="filter-tabs">
    <a href="?page=dues&filter=all" class="filter-tab <?= $dueFilter==='all'?'active':'' ?>">Tümü</a>
    <a href="?page=dues&filter=paid" class="filter-tab <?= $dueFilter==='paid'?'active':'' ?>">Ödenen</a>
    <a href="?page=dues&filter=unpaid" class="filter-tab <?= $dueFilter==='unpaid'?'active':'' ?>">Ödenmeyen</a>
  </div>
  <form class="d-flex gap-2" method="get">
    <input type="hidden" name="page" value="dues">
    <input type="hidden" name="filter" value="<?= $dueFilter ?>">
    <select name="year" class="form-select form-select-sm" style="width:auto"><option value="">Yıl</option><?php for($y=$curYear;$y>=$curYear-3;$y--): ?><option value="<?=$y?>" <?=$dueYear==$y?'selected':''?>><?=$y?></option><?php endfor; ?></select>
    <select name="month" class="form-select form-select-sm" style="width:auto"><option value="">Ay</option><?php $aylar=['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık']; for($m=1;$m<=12;$m++): ?><option value="<?=$m?>" <?=$dueMonth==$m?'selected':''?>><?=$aylar[$m-1]?></option><?php endfor; ?></select>
    <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-filter"></i></button>
  </form>
</div>

<div class="card"><div class="card-body p-0">
<div class="table-responsive"> 
<table class="table mb-0"><thead><tr><th>Sakin</th><th>Daire</th><th>Tutar</th><th>Vade</th><th>Durum</th><th class="text-end">İşlem</th></tr></thead><tbody>
<?php if($dues): foreach($dues as $d): ?>
<tr>
  <td class="fw-700"><?= htmlspecialchars($d['resident_name']) ?></td>
  <td class="text-muted"><?= $d['floor'] ?>. Kat / <?= $d['apartment_no'] ?></td>
  <td class="fw-700 money"><?= money($d['amount']) ?> ₺</td>
  <td><?= date_tr($d['due_date']) ?></td>
  <td><span class="badge <?= $d['paid']?'badge-paid':'badge-unpaid' ?>"><?= $d['paid']?'Ödendi':'Ödenmedi' ?></span><?php if($d['paid']&&$d['paid_date']): ?><br><small class="text-subtle"><?= date_tr($d['paid_date']) ?></small><?php endif; ?></td>
<td class="text-end" style="white-space: nowrap;">
    <?php 
    $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$d['resident_id']]);
    $resPhone = $stmt->fetchColumn(); 
    $waPhone = preg_replace('/[^0-9]/', '', $resPhone ?? '');
    if (str_starts_with($waPhone, '0')) $waPhone = '9' . $waPhone;
    elseif (strlen($waPhone) == 10) $waPhone = '90' . $waPhone;
    $waMesaj = urlencode("Sayın " . $d['resident_name'] . ", " . date_tr($d['due_date']) . " vadeli " . money($d['amount']) . " TL aidat borcunuz bulunmaktadır.");
    ?>
    <?php if(!$d['paid']): ?>
        <?php if(!empty($waPhone)): ?>
            <a href="https://wa.me/<?= $waPhone ?>?text=<?= $waMesaj ?>" target="_blank" class="btn btn-sm btn-success btn-icon me-1" title="WhatsApp Mesaj Gönder" style="background-color: #25D366 !important; border:none;">
                <i class="fa-brands fa-whatsapp"></i>
            </a>
        <?php else: ?>
            <button class="btn btn-sm btn-secondary btn-icon me-1" disabled title="Tel. Kayıtlı Değil"><i class="fa-solid fa-phone-slash"></i></button>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-success btn-icon btn-open-receipt"
            data-due-id="<?=$d['id']?>"
            data-resident-name="<?=htmlspecialchars($d['resident_name'])?>"
            data-amount="<?=$d['amount']?>"
            data-due-date="<?=$d['due_date']?>"
            data-apartment="<?=$d['apartment_no']?>"
            data-floor="<?=$d['floor']?>"
            data-phone="<?=$waPhone?>"
            title="Ödendi İşaretle ve Makbuz Gönder">
            <i class="fa-solid fa-check"></i>
        </button>
    <?php else: ?>
        <form method="post" style="display:inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="mark_unpaid"><input type="hidden" name="due_id" value="<?=$d['id']?>"><button class="btn btn-sm btn-warning btn-icon" title="Geri Al"><i class="fa-solid fa-undo"></i></button></form>
    <?php endif; ?>
    <form method="post" style="display:inline" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="delete_due"><input type="hidden" name="due_id" value="<?=$d['id']?>"><button class="btn btn-sm btn-danger btn-icon"><i class="fa-solid fa-trash"></i></button></form>
</td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="6"><div class="empty-state py-4"><i class="fa-solid fa-coins"></i><h4>Aidat kaydı bulunamadı</h4></div></td></tr>
<?php endif; ?></tbody></table></div></div></div>

<?php elseif($page==='expenses'): ?>
<div class="page-header d-flex justify-content-between align-items-start">
  <div><h1><i class="fa-solid fa-receipt me-2 text-warning"></i>Giderler (<?= $curYear ?>)</h1><p>Toplam: <strong class="text-warning"><?= money($expTotal) ?> ₺</strong></p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal"><i class="fa-solid fa-plus me-2"></i>Gider Ekle</button>
</div>
<?php if($expSummary): ?>
<div class="row g-3 mb-4"><?php foreach($expSummary as $es): $cl=$catColors[$es['category']]??'#64748b'; ?>
<div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:<?= $cl ?>22;color:<?= $cl ?>"><i class="fa-solid fa-tag"></i></div><div class="stat-info"><div class="stat-value" style="font-size:1.1rem"><?= money($es['total']) ?> ₺</div><div class="stat-label"><?= $es['category'] ?></div></div></div></div>
<?php endforeach; ?></div><?php endif; ?>
<div class="card"><div class="card-body p-0">
<div class="table-responsive"> 
<table class="table mb-0"><thead><tr><th>Kategori</th><th>Başlık</th><th>Tutar</th><th>Tarih</th><th>Açıklama</th><th class="text-end">İşlem</th></tr></thead><tbody>
<?php if($expenses): foreach($expenses as $ex): $cl=$catColors[$ex['category']]??'#64748b'; ?>
<tr><td><span class="cat-dot me-2" style="background:<?=$cl?>"></span><?=$ex['category']?></td><td class="fw-700"><?=htmlspecialchars($ex['title'])?></td><td class="fw-700 money"><?=money($ex['amount'])?> ₺</td><td><?=date_tr($ex['expense_date'])?></td><td class="text-muted"><?=htmlspecialchars($ex['description']??'-')?></td>
<td class="text-end"><form method="post" style="display:inline" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="delete_expense"><input type="hidden" name="expense_id" value="<?=$ex['id']?>"><button class="btn btn-sm btn-danger btn-icon"><i class="fa-solid fa-trash"></i></button></form></td></tr>
<?php endforeach; else: ?><tr><td colspan="6"><div class="empty-state py-4"><i class="fa-solid fa-receipt"></i><h4>Gider kaydı yok</h4></div></td></tr><?php endif; ?>
</tbody></table></div></div></div>

<?php elseif($page==='announcements'): ?>
<div class="page-header d-flex justify-content-between align-items-start">
  <div><h1><i class="fa-solid fa-bullhorn me-2 text-accent"></i>Duyurular</h1></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnModal"><i class="fa-solid fa-plus me-2"></i>Yeni Duyuru</button>
</div>
<?php if($announcements): foreach($announcements as $ann): ?>
<div class="ann-card" data-bs-toggle="modal" data-bs-target="#annModal<?=$ann['id']?>">
  <div class="d-flex justify-content-between align-items-start">
  <div><div class="ann-card-title"><?= htmlspecialchars($ann['title']) ?></div><div class="ann-card-date mt-1"><i class="fa-solid fa-clock me-1"></i><?= datetime_tr($ann['created_at']) ?></div></div>
  <form method="post" onclick="event.stopPropagation()" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="delete_announcement"><input type="hidden" name="id" value="<?=$ann['id']?>"><button class="btn btn-sm btn-danger btn-icon"><i class="fa-solid fa-trash"></i></button></form>
</div>
<div class="ann-card-body mt-2"><?= nl2br(htmlspecialchars(mb_substr($ann['content'], 0, 100))) ?><?= mb_strlen($ann['content']) > 100 ? ' <strong class="text-accent">Devamını Oku...</strong>' : '' ?></div>
</div>

<div class="modal fade" id="annModal<?=$ann['id']?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" onclick="event.stopPropagation()">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-bullhorn me-2 text-accent"></i><?= htmlspecialchars($ann['title']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-muted mb-3" style="font-size:.85rem"><i class="fa-solid fa-clock me-1"></i>Tarih: <?= datetime_tr($ann['created_at']) ?></div>
        <div style="font-size:.95rem; line-height:1.6; color:var(--text); white-space: pre-wrap; word-break: break-word;"><?= htmlspecialchars($ann['content']) ?></div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; else: ?><div class="empty-state"><i class="fa-solid fa-bullhorn"></i><h4>Duyuru yok</h4></div><?php endif; ?>

<?php elseif($page==='events'): ?>
<div class="page-header d-flex justify-content-between align-items-start">
  <div><h1><i class="fa-solid fa-calendar-days me-2 text-cyan"></i>Etkinlikler</h1></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal"><i class="fa-solid fa-plus me-2"></i>Yeni Etkinlik</button>
</div>
<?php if($events): foreach($events as $ev): ?>
<div class="event-card"><div class="d-flex justify-content-between align-items-start">
  <div><div class="event-date"><i class="fa-solid fa-calendar me-1"></i><?= datetime_tr($ev['event_date']) ?></div><div class="fw-700 mt-1"><?= htmlspecialchars($ev['title']) ?></div><?php if($ev['description']): ?><div class="text-muted mt-1" style="font-size:.85rem"><?= nl2br(htmlspecialchars($ev['description'])) ?></div><?php endif; ?></div>
  <form method="post" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="delete_event"><input type="hidden" name="id" value="<?=$ev['id']?>"><button class="btn btn-sm btn-danger btn-icon"><i class="fa-solid fa-trash"></i></button></form>
</div></div>
<?php endforeach; else: ?><div class="empty-state"><i class="fa-solid fa-calendar-days"></i><h4>Etkinlik yok</h4></div><?php endif; ?>
<?php endif; ?>
</div></div></div>

<div class="modal fade" id="addResidentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="add_resident">
<div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-user-plus me-2 text-accent"></i>Yeni Sakin</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
  <div class="col-12"><label class="form-label">Ad Soyad *</label><input type="text" name="name" class="form-control" required></div>
  <?php if($siteBlocks): ?><div class="col-md-6"><label class="form-label">Blok *</label><select name="block_id" class="form-control" required><option value="">Seçin</option><?php foreach($siteBlocks as $b): ?><option value="<?=$b['id']?>"><?=htmlspecialchars($b['name'])?></option><?php endforeach; ?></select></div><?php endif; ?>
  <div class="col-4"><label class="form-label">Kat *</label><input type="text" name="floor" class="form-control" required></div>
  <div class="col-4"><label class="form-label">Daire No *</label><input type="text" name="apartment_no" class="form-control" required></div>
  <div class="col-4"><label class="form-label">Telefon</label><input type="text" name="phone" class="form-control"></div>
  <div class="col-md-6"><label class="form-label">E-posta</label><input type="email" name="email" class="form-control"></div>
  <div class="col-md-6"><label class="form-label">Adres</label><input type="text" name="address" class="form-control"></div>
  <div class="col-md-6"><label class="form-label">Kullanıcı Adı *</label><input type="text" name="username" class="form-control" required></div>
  <div class="col-md-6"><label class="form-label">Şifre *</label><input type="password" name="password" class="form-control" required></div>
  <div class="col-12"><label class="form-label">Notlar</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-primary">Kaydet</button></div>
</form></div></div></div>

<div class="modal fade" id="addBlockModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="add_block">
<div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-layer-group me-2"></i>Blok Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="mb-3"><label class="form-label">Blok Adı *</label><input type="text" name="block_name" class="form-control" placeholder="Örn: D Blok" required></div>
<?php if($siteBlocks): ?><div class="small text-muted">Mevcut: <?= htmlspecialchars(implode(', ',array_column($siteBlocks,'name'))) ?></div><?php endif; ?></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-primary">Ekle</button></div>
</form></div></div></div>

<div class="modal fade" id="importResidentsModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="import_residents">
<div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-file-import me-2 text-info"></i>Toplu Sakin Aktar (Excel/CSV)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="alert alert-info small"><i class="fa-solid fa-download me-1"></i> Önce <a href="sakin-sablon.csv" download class="fw-700">örnek şablonu indirin</a>, bilgileri doldurun, dosyayı seçip aktarın. Blok adları sitedeki bloklarla birebir aynı olmalı. Şifresi boş bırakılanlara otomatik şifre üretilir.</div>
<div class="mb-3"><label class="form-label">CSV Dosyası *</label><input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-info text-white"><i class="fa-solid fa-upload me-1"></i>Aktar</button></div>
</form></div></div></div>

<div class="modal fade" id="addDueModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="add_due">
<div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-coins me-2 text-warning"></i>Aidat Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
  <div class="col-12"><label class="form-label">Sakin *</label><select name="resident_id" class="form-select" required><option value="">Seçin</option><?php foreach($residents as $r): ?><option value="<?=$r['id']?>"><?=htmlspecialchars($r['name'])?> (<?= !empty($r['block_name'])?htmlspecialchars($r['block_name']).' / ':'' ?>Daire <?=$r['apartment_no']?>)</option><?php endforeach; ?></select></div>
  <div class="col-md-6"><label class="form-label">Tutar (₺) *</label><input type="number" step="0.01" name="amount" class="form-control" value="<?=$dueSetting['monthly_amount']??''?>" required></div>
  <div class="col-md-6"><label class="form-label">Vade Tarihi *</label><input type="date" name="due_date" class="form-control" required></div>
  <div class="col-12"><label class="form-label">Açıklama</label><input type="text" name="description" class="form-control"></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-primary">Ekle</button></div>
</form></div></div></div>

<div class="modal fade" id="dueSettingModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="save_due_setting">
<div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-gear me-2"></i>Yıllık Aidat Ücreti</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
  <div class="col-md-6"><label class="form-label">Yıl</label><select name="year" class="form-select"><?php for($y=$curYear+1;$y>=$curYear-1;$y--): ?><option value="<?=$y?>" <?=$y==$curYear?'selected':''?>><?=$y?></option><?php endfor; ?></select></div>
  <div class="col-md-6"><label class="form-label">Aylık Tutar (₺)</label><input type="number" step="0.01" name="monthly_amount" class="form-control" value="<?=$dueSetting['monthly_amount']??''?>" required></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-primary">Kaydet</button></div>
</form></div></div></div>

<div class="modal fade" id="bulkDueModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="bulk_create_dues">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-layer-group me-2 text-warning"></i>Toplu Aidat / Demirbaş Yansıt</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info" style="font-size:0.85rem;">
            <i class="fa-solid fa-shield-halved me-1"></i> <b>Akıllı Koruma Aktif:</b> Aynı kişiye, aynı ay için aynı isimde ikinci bir borç (mükerrer kayıt) kesinlikle yansıtılmaz.
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tutar (₺) *</label>
              <input type="number" step="0.01" name="amount" class="form-control" value="<?= $dueSetting['monthly_amount'] ?? '' ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Son Ödeme Tarihi *</label>
              <input type="date" name="due_date" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Açıklama</label>
              <input type="text" name="description" class="form-control" placeholder="Örn: 2026/05 Aidatı veya Çatı Onarımı">
              <small class="text-muted" style="font-size: 0.75rem;">Boş bırakırsanız seçtiğiniz tarihe göre otomatik isimlendirilir.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check-double me-1"></i>Tüm Sakinlere Yansıt</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="yearlyDueModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="bulk_create_yearly">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <div class="modal-header" style="background:#fffbeb;border-bottom:1px solid #fde68a">
          <h5 class="modal-title"><i class="fa-solid fa-calendar-check me-2" style="color:#f59e0b"></i>1 Yıllık Otomatik Aidat</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning py-2 small"><i class="fa-solid fa-bolt me-1"></i> <b>12 ay tek tık!</b> Seçtiğin ay + sonraki 11 ay için her daireye otomatik aidat oluşturur. Mükerrer kayıt atlanır.</div>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Aylık Tutar (₺) *</label><input type="number" step="0.01" name="amount" class="form-control" value="<?= $dueSetting['monthly_amount'] ?? '' ?>" required></div>
            <div class="col-md-6"><label class="form-label">Başlangıç Tarihi *</label><input type="date" name="start_date" class="form-control" value="<?= date('Y-m-10') ?>" required><small class="text-muted" style="font-size:.72rem">Her ayın aynı günü vade yapılır</small></div>
            <div class="col-12"><label class="form-label">Açıklama öneki (opsiyonel)</label><input type="text" name="description_prefix" class="form-control" placeholder="Boş = 2026/05 Dönemi Aidatı"><small class="text-muted" style="font-size:.72rem">Örn: Aidat → Aidat 2026/05, boş bırakırsan otomatik</small></div>
          </div>
          <div class="mt-3 p-2 rounded small" style="background:#f8fafc;border:1px solid #e2e8f0"><i class="fa-solid fa-circle-info me-1 text-primary"></i> <?= count($residents) ?> daire × 12 ay = <b><?= count($residents)*12 ?> kayıt</b> denenecek, olanlar atlanacak.</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-warning" style="background:#f59e0b;border-color:#f59e0b;color:white"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>12 Ayı Oluştur</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addExpenseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="add_expense">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-receipt me-2 text-warning"></i>Gider Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Kategori *</label>
              <input list="catList" name="category" class="form-control" placeholder="Seçin veya yeni yazın..." required>
              <datalist id="catList">
                  <?php foreach($allCategories as $cat): ?>
                      <option value="<?= htmlspecialchars($cat) ?>">
                  <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-md-6"><label class="form-label">Tutar (₺) *</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Başlık *</label><input type="text" name="title" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Tarih *</label><input type="date" name="expense_date" class="form-control" value="<?=date('Y-m-d')?>" required></div>
            <div class="col-md-6"><label class="form-label">Açıklama</label><input type="text" name="description" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-primary">Ekle</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="bulkWaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-brands fa-whatsapp me-2 text-success"></i>Toplu Hatırlatma Listesi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" style="font-size:0.85rem;">
                    <i class="fa-solid fa-circle-info me-1"></i> Bu liste, sakinlerin <b>toplam ödenmemiş borçlarını</b> hesaplar. Birden fazla aidat borcu olanlara tek tek değil, toplam borç tutarını içeren tek bir özet mesajı atmanızı sağlar.
                </div>
                
                <?php
                $bulkWaStmt = $pdo->prepare("
                    SELECT u.name, u.phone, u.apartment_no, u.floor, 
                           SUM(d.amount) as total_debt, 
                           COUNT(d.id) as unp_count
                    FROM dues d
                    JOIN users u ON d.resident_id = u.id
                    WHERE d.site_id = ? AND d.paid = 0
                    GROUP BY u.id
                    ORDER BY u.apartment_no ASC
                ");
                $bulkWaStmt->execute([$mySiteId]);
                $debtors = $bulkWaStmt->fetchAll();
                ?>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Daire</th>
                                <th>Sakin</th>
                                <th>Gecikme</th>
                                <th>Toplam Borç</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($debtors): foreach($debtors as $db):
                                $tel = preg_replace('/[^0-9]/', '', $db['phone'] ?? '');
                                $waNum = (strlen($tel) == 10) ? '90' . $tel : ((strlen($tel) == 11 && str_starts_with($tel, '0')) ? '90' . substr($tel, 1) : $tel);
                                $msg = urlencode("Sayın " . $db['name'] . ", site yönetiminden hatırlatmak isteriz. Toplam " . $db['unp_count'] . " adet ödenmemiş aidatınıza ait *" . money($db['total_debt']) . " TL* borcunuz bulunmaktadır. En kısa sürede ödemenizi rica ederiz.");
                            ?>
                            <tr>
                                <td class="text-muted"><?= $db['floor'] ?>. Kat / <?= $db['apartment_no'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($db['name']) ?></td>
                                <td><span class="badge bg-danger"><?= $db['unp_count'] ?> Ay</span></td>
                                <td class="fw-bold text-danger"><?= money($db['total_debt']) ?> ₺</td>
                                <td class="text-end">
                                    <?php if(!empty($tel)): ?>
                                        <a href="https://wa.me/<?= $waNum ?>?text=<?= $msg ?>" target="_blank" class="btn btn-sm btn-success" style="background-color: #25D366; border:none;">
                                            <i class="fa-brands fa-whatsapp me-1"></i> Gönder
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled><i class="fa-solid fa-phone-slash"></i> Yok</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-success">
                                    <i class="fa-solid fa-face-smile fs-1 mb-2"></i><br>
                                    Harika! Sitede ödenmemiş borç bulunmuyor.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAnnModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="add_announcement">
<div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-bullhorn me-2 text-accent"></i>Yeni Duyuru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="mb-3"><label class="form-label">Başlık *</label><input type="text" name="title" class="form-control" required></div><div class="mb-3"><label class="form-label">İçerik *</label><textarea name="content" rows="4" class="form-control" required></textarea></div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-primary">Yayınla</button></div>
</form></div></div></div>

<div class="modal fade" id="addEventModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="action" value="add_event">
<div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-calendar-days me-2 text-cyan"></i>Yeni Etkinlik</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="mb-3"><label class="form-label">Başlık *</label><input type="text" name="title" class="form-control" required></div><div class="mb-3"><label class="form-label">Tarih *</label><input type="datetime-local" name="event_date" class="form-control" required></div><div class="mb-3"><label class="form-label">Açıklama</label><textarea name="description" rows="2" class="form-control"></textarea></div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-primary">Ekle</button></div>
</form></div></div></div>

<div class="modal fade" id="receiptModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background: var(--card-bg);">
        <h5 class="modal-title"><i class="fa-solid fa-file-invoice me-2 text-success"></i>Makbuz İşlemleri</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <div id="receiptCard" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; font-family: monospace;">
          <div style="text-align: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 12px; margin-bottom: 12px;">
            <div style="font-size: 1.2rem; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($siteName) ?></div>
            <div style="font-size: 0.8rem; color: #64748b;">RESİDA PRO – Aidat Tahsilatı</div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
    <span style="color: #475569;">Tarih:</span>
    <strong id="rcDate" style="color: #000000;">-</strong>
</div>
<div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
    <span style="color: #475569;">Sakin:</span>
    <strong id="rcName" style="color: #000000;">-</strong>
</div>
<div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
    <span style="color: #475569;">Daire:</span>
    <strong id="rcApt" style="color: #000000;">-</strong>
</div>
<div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
    <span style="color: #475569;">Dönem:</span>
    <strong id="rcPeriod" style="color: #000000;">-</strong>
</div>
          <div style="display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 10px;">
            <span style="font-weight: 700; color: #0f172a;">Ödenen Tutar:</span>
            <strong id="rcAmount" style="font-size: 1.2rem; color: #10b981;">-</strong>
          </div>
          <div style="text-align: center; margin-top: 12px; font-size: 0.75rem; color: #94a3b8;">Makbuz No: <span id="rcNo">-</span></div>
        </div>

        <div class="d-grid gap-2 mt-3">
          <a href="#" id="rcWaLink" target="_blank" class="btn btn-success" style="background-color: #25D366; border: none;">
            <i class="fa-brands fa-whatsapp me-2"></i> WhatsApp'tan Bilgi Gönder
          </a>
          <button type="button" onclick="executePrint('rc')" class="btn btn-outline-primary">
            <i class="fa-solid fa-print me-2"></i> Şık Makbuzu Yazdır / PDF İndir
          </button>
        </div>

        <form method="post" id="receiptMarkPaidForm" class="mt-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="action" value="mark_paid">
          <input type="hidden" name="due_id" id="rcDueId">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fa-solid fa-check-double me-2"></i> Ödendi Olarak İşaretle
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="freeReceiptModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Serbest Makbuz Kes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Sakin</label>
            <select id="freeRecResident" class="form-select">
              <option value="">Dışarıdan Biri / Manuel</option>
              <?php foreach($residents as $r): 
                $telFree = preg_replace('/[^0-9]/', '', $r['phone'] ?? '');
                if (str_starts_with($telFree, '0')) $telFree = '9' . $telFree;
                elseif (strlen($telFree) == 10) $telFree = '90' . $telFree;
              ?>
              <option value="<?=$r['id']?>" data-name="<?=htmlspecialchars($r['name'])?>" data-apt="<?=$r['apartment_no']?>" data-floor="<?=$r['floor']?>" data-phone="<?=$telFree?>">
                <?=htmlspecialchars($r['name'])?> (Daire <?=$r['apartment_no']?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12"><label class="form-label">İsim (Eğer üstten seçilmediyse)</label><input type="text" id="freeRecManualName" class="form-control" placeholder="Örn: Ahmet Usta"></div>
          <div class="col-md-6"><label class="form-label">Tutar (₺)</label><input type="number" step="0.01" id="freeRecAmount" class="form-control" placeholder="0.00"></div>
          <div class="col-md-6"><label class="form-label">Tarih</label><input type="date" id="freeRecDate" class="form-control" value="<?=date('Y-m-d')?>"></div>
          <div class="col-12"><label class="form-label">Açıklama</label><input type="text" id="freeRecDesc" class="form-control" placeholder="Örn: Asansör Bakımı"></div>
        </div>
        <div class="d-grid gap-2 mt-3">
          <button type="button" onclick="executePrint('free')" class="btn btn-outline-primary">
            <i class="fa-solid fa-print me-2"></i> Şık Makbuzu Yazdır / PDF İndir
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    
    // MENÜ KONTROLÜ
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', function(e) {
        e.stopPropagation(); 
        if (window.innerWidth <= 768) { document.body.classList.toggle('sidebar-open'); } 
        else { document.body.classList.toggle('sidebar-hidden'); }
      });
    }

    // GRAFİK KONTROLÜ
    const chartCanvas = document.getElementById('financeChart');
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        const gelirData = [<?= isset($chartData) ? implode(',', array_column($chartData, 'gelir')) : '' ?>];
        new Chart(ctx, { 
            type: 'bar', 
            data: { 
                labels: ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'], 
                datasets: [{ label: 'Aylık Tahsilat (₺)', data: gelirData, backgroundColor: '#10b981', borderRadius: 4 }, 
                           { label: 'Gider', data: [<?= isset($chartData) ? implode(',', array_column($chartData, 'gider')) : '' ?>], backgroundColor: '#ef4444', borderRadius: 4 }] 
            }, options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // AIDAT TABLOSUNDAN MAKBUZ AÇMA
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-open-receipt');
        if (!btn) return;
        e.preventDefault();
        
        const dueId    = btn.dataset.dueId;
        const name     = btn.dataset.residentName;
        const amount   = parseFloat(btn.dataset.amount).toFixed(2);
        const dueDate  = btn.dataset.dueDate;
        const apt      = btn.dataset.apartment;
        const floor    = btn.dataset.floor;
        const phone    = btn.dataset.phone;
        
        document.getElementById('rcDate').textContent   = new Date().toLocaleDateString('tr-TR');
        document.getElementById('rcName').textContent   = name;
        document.getElementById('rcApt').textContent    = floor + '. Kat / Daire ' + apt;
        document.getElementById('rcPeriod').textContent = dueDate;
        document.getElementById('rcAmount').textContent = amount.replace('.', ',') + ' ₺';
        document.getElementById('rcNo').textContent     = 'RCP-' + dueId;
        document.getElementById('rcDueId').value        = dueId;
        
const siteName = <?= json_encode($siteName) ?>;
        // Dinamik olarak tam site adresini (domain) alır
        const fullReceiptUrl = window.location.origin + '/RESIDA/receipt.php?id=' + dueId; 
        
        const msg = encodeURIComponent(
            '🏢 *' + siteName + '*\n' +
            '📄 *Tahsilat Makbuzu*\n\n' +
            '👤 Sakin: ' + name + '\n' +
            '🚪 Daire: ' + floor + '. Kat / ' + apt + '\n' +
            '💰 Odenen Tutar: ' + amount.replace('.', ',') + ' TL\n\n' +
            '📎 *Makbuzunuzu PDF olarak goruntulemek veya indirmek icin asagidaki guvenli baglantiya tiklayabilirsiniz:*\n' +
            fullReceiptUrl
        );
        document.getElementById('rcWaLink').href = 'https://wa.me/' + phone + '?text=' + msg;
        
        new bootstrap.Modal(document.getElementById('receiptModal')).show();
    });
  });

  // KUSURSUZ GİZLİ YAZDIRMA FONKSİYONU
  function executePrint(type) {
      let rDate, rName, rApt, rPeriod, rAmount, rNo;
      let siteName = <?= json_encode($siteName) ?>;
      
      if (type === 'rc') {
          rDate = document.getElementById('rcDate').innerText;
          rName = document.getElementById('rcName').innerText;
          rApt = document.getElementById('rcApt').innerText;
          rPeriod = "Dönem: " + document.getElementById('rcPeriod').innerText;
          rAmount = document.getElementById('rcAmount').innerText;
          rNo = document.getElementById('rcNo').innerText;
      } else {
          rDate = document.getElementById('freeRecDate').value;
          const sel = document.getElementById('freeRecResident');
          const opt = sel.options[sel.selectedIndex];
          
          if (sel.value) {
              rName = opt.dataset.name;
              rApt = opt.dataset.floor + '. Kat / Daire ' + opt.dataset.apt;
          } else {
              rName = document.getElementById('freeRecManualName').value || 'Belirtilmedi';
              rApt = '-';
          }
          
          rPeriod = document.getElementById('freeRecDesc').value || 'Tahsilat';
          let amnt = parseFloat(document.getElementById('freeRecAmount').value || 0).toFixed(2);
          rAmount = amnt.replace('.', ',') + ' ₺';
          rNo = 'FR-' + Math.floor(Math.random() * 90000 + 10000);
      }

      // O harika PDF tasarımını oluşturup gizli div'e yerleştiriyoruz
      const htmlTemplate = `
        <div style="font-family: 'Helvetica Neue', Arial, sans-serif; color: #333; margin: 0; padding: 20px; font-size: 11pt; line-height: 1.5; background:white;">
            <div style="width: 100%; border: 2px solid #cbd5e1; padding: 15mm; position: relative; border-radius: 8px;">
                <div style="position: absolute; right: 25mm; bottom: 45mm; border: 4px double #10b981; color: #10b981; font-size: 18pt; font-weight: bold; padding: 4px 12px; transform: rotate(-15deg); border-radius: 6px; letter-spacing: 2px; opacity: 0.85;">ÖDENDİ</div>
                
                <table style="width: 100%; margin-bottom: 12mm; border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align: middle;">
                            <h2 style="margin: 0; color: #0f172a; font-size: 22pt; font-weight: bold;">${siteName}</h2>
                            <p style="margin: 3px 0 0 0; color: #64748b; font-size: 9.5pt; text-transform: uppercase; letter-spacing: 1px;">RESİDA PRO Site Yönetimi</p>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <h1 style="margin: 0; color: #1e3a8a; font-size: 20pt; font-weight: 800; letter-spacing: 1px;">TAHSİLAT MAKBUZU</h1>
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 8mm; background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <tr>
                        <td style="padding: 4mm 5mm; font-size: 10pt; color: #475569; width: 25%; border-right: 1px solid #e2e8f0;">Makbuz No:<br><strong style="color: #0f172a; display: block; margin-top: 1mm; font-size: 11pt;">${rNo}</strong></td>
                        <td style="padding: 4mm 5mm; font-size: 10pt; color: #475569; width: 25%; border-right: 1px solid #e2e8f0;">Tarih:<br><strong style="color: #0f172a; display: block; margin-top: 1mm; font-size: 11pt;">${rDate}</strong></td>
                        <td style="padding: 4mm 5mm; font-size: 10pt; color: #475569; width: 25%; border-right: 1px solid #e2e8f0;">Ödeme Türü:<br><strong style="color: #0f172a; display: block; margin-top: 1mm; font-size: 11pt;">Nakit / Havale</strong></td>
                        <td style="padding: 4mm 5mm; font-size: 10pt; color: #475569; width: 25%;">Durum:<br><strong style="color: #10b981; display: block; margin-top: 1mm; font-size: 11pt;">Tahsil Edildi</strong></td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 10mm;">
                    <thead>
                        <tr>
                            <th style="background-color: #1e3a8a; color: #ffffff; text-align: left; padding: 3.5mm 5mm; font-size: 11pt; border: 1px solid #1e3a8a; width:45%;">Açıklama Detayı</th>
                            <th style="background-color: #1e3a8a; color: #ffffff; text-align: left; padding: 3.5mm 5mm; font-size: 11pt; border: 1px solid #1e3a8a; width:55%;">Ödeme Yapan Bilgileri</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 6mm 5mm; border-bottom: 1px solid #cbd5e1; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; font-size: 11pt; font-weight: bold; color: #1e3a8a; line-height: 1.6;">${rPeriod}</td>
                            <td style="padding: 6mm 5mm; border-bottom: 1px solid #cbd5e1; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; font-size: 11pt;">
                                <div style="margin-bottom: 4px;"><span style="color:#64748b">Adı Soyadı:</span> <strong>${rName}</strong></div>
                                <div><span style="color:#64748b">Daire Bilgisi:</span> <strong>${rApt}</strong></div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="background-color: #f0fdf4; border: 1px dashed #10b981; padding: 6mm; text-align: center; margin-bottom: 15mm; border-radius: 4px;">
                    <div style="font-size: 10pt; color: #166534; font-weight: bold; letter-spacing: 1px;">Tahsil Edilen Toplam Tutar</div>
                    <div style="font-size: 26pt; font-weight: 800; color: #15803d; margin-top: 1mm;">${rAmount}</div>
                </div>

                <table style="width: 100%; margin-top: 5mm; text-align: center; border-collapse:collapse;">
                    <tr>
                        <td style="width: 50%; padding-top: 15mm; color: #1e293b;">
                            <div style="border-top: 1px solid #94a3b8; width: 55%; margin: 0 auto 3mm auto;"></div>
                            <strong>Teslim Eden (Ödeyen)</strong><br><span style="font-size: 9pt; color: #64748b;">İmza</span>
                        </td>
                        <td style="width: 50%; padding-top: 15mm; color: #1e293b;">
                            <div style="border-top: 1px solid #94a3b8; width: 55%; margin: 0 auto 3mm auto;"></div>
                            <strong>Tahsil Eden (Yönetim)</strong><br><span style="font-size: 9pt; color: #64748b;">Kaşe / İmza</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
      `;

      // HTML'i gizli alana yerleştir
      document.getElementById('printArea').innerHTML = htmlTemplate;

      // Tarayıcının kendi yazdırma/PDF ekranını çağır
      window.print();
  }
  function printExpensePDF(){
    const rows = `<?php foreach($expSummary as $es){ echo "<tr><td>".htmlspecialchars($es['category'])."</td><td style=\"text-align:right\">".money($es['total'])." ₺</td></tr>"; } ?>`;
    const total = `<?= money($expTotal) ?>`;
    const site = `<?= htmlspecialchars($siteName) ?>`;
    const year = `<?= $curYear ?>`;
    const html = `
      <div style="font-family:Inter,sans-serif;padding:20px;color:#0f172a">
        <div style="text-align:center;border-bottom:2px solid #e2e8f0;padding-bottom:12px;margin-bottom:16px">
          <h2 style="margin:0">${site} — Gider Raporu ${year}</h2>
          <div style="color:#64748b;font-size:10pt">${new Date().toLocaleDateString('tr-TR')} • RESIDA PRO</div>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:10pt">
          <thead><tr style="background:#f1f5f9"><th style="text-align:left;padding:8px;border:1px solid #e2e8f0">Kategori</th><th style="text-align:right;padding:8px;border:1px solid #e2e8f0">Tutar</th></tr></thead>
          <tbody>${rows}<tr style="font-weight:800;background:#f8fafc"><td style="padding:8px;border:1px solid #e2e8f0">Toplam</td><td style="text-align:right;padding:8px;border:1px solid #e2e8f0">${total} ₺</td></tr></tbody>
        </table>
        <div style="margin-top:18px;text-align:center;color:#64748b;font-size:9pt">Bu belge RESIDA PRO tarafından oluşturulmuştur.</div>
      </div>`;
    document.getElementById('printArea').innerHTML = html;
    window.print();
  }
</script>
</body>
</html>