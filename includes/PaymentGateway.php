<?php
// includes/PaymentGateway.php – Ödeme Soyut Katmanı
// Manuel (şirketsiz) + iyzico (şirketli) aynı interface üzerinden çalışır
if (file_exists(__DIR__ . '/../vendor/autoload.php')) require_once __DIR__ . '/../vendor/autoload.php';

interface PaymentGatewayInterface {
    /**
     * @param array $data [amount, site_id, user_id, due_id, description]
     * @return array [success=>bool, message=>string, gateway_ref=>?string, redirect_url=>?string, payment_id=>int]
     */
    public function createPayment(array $data): array;
    public function getName(): string;
}

// ─── Manuel Havale / Dekont ───
class ManualGateway implements PaymentGatewayInterface {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function getName(): string { return 'manual'; }

    public function createPayment(array $data): array {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO payments (site_id, user_id, due_id, subscription_id, gateway, amount, status, note, receipt_path) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['site_id'],
                $data['user_id'] ?? null,
                $data['due_id'] ?? null,
                $data['subscription_id'] ?? null,
                'manual',
                $data['amount'],
                'pending',
                $data['note'] ?? null,
                $data['receipt_path'] ?? null
            ]);
            $pid = $this->pdo->lastInsertId();
            return [
                'success' => true,
                'message' => 'Ödeme bildiriminiz alındı. Yönetici onayından sonra aidatınız ödenmiş sayılacaktır.',
                'payment_id' => (int)$pid,
                'gateway_ref' => 'MANUAL-' . $pid
            ];
        } catch (PDOException $e) {
            return ['success'=>false, 'message'=>'Ödeme kaydı oluşturulamadı: '.$e->getMessage()];
        }
    }
}

// ─── iyzico Gateway (Sandbox hazır, canlı için anahtar yeterli) ───
class IyzicoGateway implements PaymentGatewayInterface {
    private $pdo;
    private $apiKey;
    private $secretKey;
    private $baseUrl;
    private $enabled;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->apiKey = $_ENV['IYZICO_API_KEY'] ?? getenv('IYZICO_API_KEY') ?: '';
        $this->secretKey = $_ENV['IYZICO_SECRET_KEY'] ?? getenv('IYZICO_SECRET_KEY') ?: '';
        $this->baseUrl = $_ENV['IYZICO_BASE_URL'] ?? 'https://sandbox-api.iyzipay.com';
        $this->enabled = !empty($this->apiKey) && !empty($this->secretKey);
    }

    public function getName(): string { return 'iyzico'; }
    public function isEnabled(): bool { return $this->enabled; }

    public function createPayment(array $data): array {
        // Placeholder anahtar kontrolü
        $isPlaceholder = ($this->apiKey === 'sandbox-api-key' || $this->secretKey === 'sandbox-secret-key');
        if (!$this->enabled || $isPlaceholder) {
            return [
                'success' => false,
                'message' => 'Kartla ödeme şu an aktif değil. Yönetici IBAN ile havale yöntemini kullanın. (iyzico için canlı anahtar gerekli – .env güncelleyin)'
            ];
        }

        try {
            // Kullanıcı ve site bilgilerini çek
            $userRow = null; $siteName='RESIDA';
            if(!empty($data['user_id'])){
                $u=$this->pdo->prepare("SELECT * FROM users WHERE id=?"); $u->execute([$data['user_id']]); $userRow=$u->fetch();
            }
            $subKey=null;
            if(!empty($data['site_id'])){
                $s=$this->pdo->prepare("SELECT name, iyzico_submerchant_key FROM sites WHERE id=?"); $s->execute([$data['site_id']]); $row=$s->fetch(); $siteName=$row['name'] ?? 'RESIDA'; $subKey=$row['iyzico_submerchant_key'] ?? null;
            }
            $dueDescRaw = trim($data['description'] ?? '');
            if($dueDescRaw === '') $dueDescRaw = trim($data['note'] ?? '');
            if($dueDescRaw === '') $dueDescRaw = 'Aidat Odemesi';
            $dueDesc = $dueDescRaw;
            if(!empty($data['due_id'])){
                $d=$this->pdo->prepare("SELECT description FROM dues WHERE id=?"); $d->execute([$data['due_id']]); $tmp=$d->fetchColumn(); if(!empty(trim($tmp))) $dueDesc=trim($tmp);
            }
            if(empty(trim($dueDesc))) $dueDesc='Aidat Odemesi';

            $conversationId = 'RESIDA-' . time() . '-' . rand(1000,9999);
            $stmt = $this->pdo->prepare("INSERT INTO payments (site_id, user_id, due_id, subscription_id, gateway, gateway_ref, amount, status, note) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['site_id'],
                $data['user_id'] ?? null,
                $data['due_id'] ?? null,
                $data['subscription_id'] ?? null,
                'iyzico',
                $conversationId,
                $data['amount'],
                'pending',
                $data['note'] ?? null
            ]);
            $pid = $this->pdo->lastInsertId();

            // iyzico checkout
            $options = new \Iyzipay\Options();
            $options->setApiKey($this->apiKey);
            $options->setSecretKey($this->secretKey);
            $options->setBaseUrl($this->baseUrl);

            $request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
            $request->setLocale(\Iyzipay\Model\Locale::TR);
            $request->setConversationId($conversationId);
            $price = number_format((float)$data['amount'], 2, '.', '');
            $request->setPrice($price);
            $request->setPaidPrice($price);
            $request->setCurrency(\Iyzipay\Model\Currency::TL);
            $request->setBasketId("BASKET-".$pid);
            $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
            $callback = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']),'/') . '/iyzico_callback.php?pid='.$pid;
            $request->setCallbackUrl($callback);
            $request->setEnabledInstallments([1,2,3,6,9]);

            // Buyer
            $buyer = new \Iyzipay\Model\Buyer();
            $buyer->setId($userRow['id'] ?? $pid);
            $buyer->setName($userRow['name'] ?? 'Sakin');
            $buyer->setSurname('Sakin');
            // iyzico ad/soyad ayırma
            $nameParts = explode(' ', $userRow['name'] ?? 'Sakin', 2);
            $buyer->setName($nameParts[0] ?? 'Sakin');
            $buyer->setSurname($nameParts[1] ?? 'Sakin');
            $buyer->setGsmNumber($userRow['phone'] ?? '+905350000000');
            $buyer->setEmail($userRow['email'] ?? 'sakin@resida.local');
            $buyer->setIdentityNumber('11111111111');
            $addr = $userRow['address'] ?? $siteName;
            $buyer->setRegistrationAddress($addr);
            $buyer->setIp($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $buyer->setCity('Istanbul'); $buyer->setCountry('Turkey');
            $request->setBuyer($buyer);

            $shipping = new \Iyzipay\Model\Address();
            $shipping->setContactName($userRow['name'] ?? 'Sakin');
            $shipping->setCity('Istanbul'); $shipping->setCountry('Turkey'); $shipping->setAddress($addr);
            $request->setShippingAddress($shipping);
            $billing = new \Iyzipay\Model\Address();
            $billing->setContactName($userRow['name'] ?? 'Sakin');
            $billing->setCity('Istanbul'); $billing->setCountry('Turkey'); $billing->setAddress($addr);
            $request->setBillingAddress($billing);

            $basket = new \Iyzipay\Model\BasketItem();
            $basket->setId("DUE-".($data['due_id']??$pid));
            $basket->setName(mb_substr(trim($dueDesc),0,100) ?: 'Aidat Odemesi');
            $basket->setCategory1(mb_substr(trim($siteName) ?: 'RESIDA',0,50));
            $basket->setItemType(\Iyzipay\Model\BasketItemType::VIRTUAL);
            $basket->setPrice($price);
            if(!empty($subKey) && strpos($subKey,'mock-')!==0 && strpos($subKey,'central-')!==0){
                $basket->setSubMerchantKey($subKey);
                $basket->setSubMerchantPrice($price);
            }
            $request->setBasketItems([$basket]);

            $result = \Iyzipay\Model\CheckoutFormInitialize::create($request, $options);
            // Debug log (sandbox)
            @file_put_contents(__DIR__.'/../iyzico_debug.log', date('Y-m-d H:i:s')." PID $pid status=".$result->getStatus()." errCode=".$result->getErrorCode()." errMsg=".$result->getErrorMessage()." raw=".json_encode($result)."\n", FILE_APPEND);
            if($result->getStatus() === 'success'){
                $token = $result->getToken();
                $this->pdo->prepare("UPDATE payments SET gateway_ref=? WHERE id=?")->execute([$token, $pid]);
                return ['success'=>true, 'redirect_url'=>$result->getPaymentPageUrl(), 'payment_id'=>(int)$pid, 'gateway_ref'=>$token];
            } else {
                $msg = $result->getErrorMessage() ?: 'iyzico hata';
                $code = $result->getErrorCode() ?: 'unknown';
                $full = "[$code] $msg";
                $this->pdo->prepare("UPDATE payments SET status='rejected', note=CONCAT(COALESCE(note,''), ' | iyzico: ', ?) WHERE id=?")->execute([$full, $pid]);
                return ['success'=>false, 'message'=>'iyzico: '.$full.' (detay iyzico_debug.log)'];
            }
        } catch (Exception $e) {
            @file_put_contents(__DIR__.'/../iyzico_debug.log', date('Y-m-d H:i:s')." EXC ".$e->getMessage()."\n", FILE_APPEND);
            return ['success'=>false, 'message'=>'iyzico hatası: '.$e->getMessage().' (detay iyzico_debug.log)'];
        }
    }
}

// ─── Fabrika ───
function getPaymentGateway($pdo, $type = 'manual'): PaymentGatewayInterface {
    if ($type === 'iyzico') return new IyzicoGateway($pdo);
    return new ManualGateway($pdo);
}

// ─── Yardımcı Fonksiyonlar ───
function approvePayment($pdo, $paymentId, $approverId) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? FOR UPDATE");
        $stmt->execute([$paymentId]);
        $pay = $stmt->fetch();
        if (!$pay || $pay['status'] !== 'pending') throw new Exception('Ödeme bulunamadı veya zaten işlenmiş.');

        $pdo->prepare("UPDATE payments SET status='approved', approved_at=NOW(), approved_by=? WHERE id=?")->execute([$approverId, $paymentId]);
        if (!empty($pay['due_id'])) {
            $pdo->prepare("UPDATE dues SET paid=1, paid_date=CURDATE() WHERE id=?")->execute([$pay['due_id']]);
        }
        if (!empty($pay['subscription_id'])) {
            $pdo->prepare("UPDATE site_subscriptions SET status='active', current_period_start=CURDATE(), current_period_end=DATE_ADD(CURDATE(), INTERVAL 1 MONTH), updated_at=NOW() WHERE id=?")->execute([$pay['subscription_id']]);
            // Eski aktif abonelikleri past_due yap (aynı site için)
            $pdo->prepare("UPDATE site_subscriptions SET status='expired' WHERE site_id=? AND id!=? AND status='active'")->execute([$pay['site_id'],$pay['subscription_id']]);
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function rejectPayment($pdo, $paymentId, $approverId, $reason = '') {
    $pdo->prepare("UPDATE payments SET status='rejected', approved_by=?, note=CONCAT(COALESCE(note,''), ' | Red: ', ?) WHERE id=?")->execute([$approverId, $reason, $paymentId]);
}

function getPendingPaymentsBySite($pdo, $siteId) {
    $stmt = $pdo->prepare("SELECT p.*, u.name as user_name, u.apartment_no, d.description as due_desc, d.amount as due_amount
                           FROM payments p
                           LEFT JOIN users u ON p.user_id = u.id
                           LEFT JOIN dues d ON p.due_id = d.id
                           WHERE p.site_id = ? AND p.status='pending' ORDER BY p.created_at DESC");
    $stmt->execute([$siteId]);
    return $stmt->fetchAll();
}

// ─── iyzico Pazaryeri: Alt Üye (site) oluştur ───
function ensureIyzicoSubMerchant($pdo, $siteId){
    $s=$pdo->prepare("SELECT s.*, u.email as mgr_email, u.phone as mgr_phone, u.name as mgr_name FROM sites s LEFT JOIN users u ON u.site_id=s.id AND u.role='manager' WHERE s.id=? LIMIT 1");
    $s->execute([$siteId]); $site=$s->fetch();
    if(!$site) throw new Exception("Site bulunamadı");
    if(!empty($site['iyzico_submerchant_key'])) return $site['iyzico_submerchant_key'];
    if(empty($site['iban'])) throw new Exception("Önce site IBAN'ını kaydetmelisiniz");
    $apiKey = $_ENV['IYZICO_API_KEY'] ?? getenv('IYZICO_API_KEY') ?: '';
    $secret = $_ENV['IYZICO_SECRET_KEY'] ?? getenv('IYZICO_SECRET_KEY') ?: '';
    $baseUrl = $_ENV['IYZICO_BASE_URL'] ?? 'https://sandbox-api.iyzipay.com';
    $isPlaceholder = ($apiKey==='sandbox-api-key' || $secret==='sandbox-secret-key' || !$apiKey);
    if($isPlaceholder){
        // Mock mod: rastgele key üret, DB'ye yaz
        $mockKey = 'mock-sub-'.substr(md5($siteId.$site['iban'].time()),0,16);
        $pdo->prepare("UPDATE sites SET iyzico_submerchant_key=? WHERE id=?")->execute([$mockKey,$siteId]);
        return $mockKey;
    }
    // Gerçek iyzico alt üye oluştur
    $options=new \Iyzipay\Options(); $options->setApiKey($apiKey); $options->setSecretKey($secret); $options->setBaseUrl($baseUrl);
    $req=new \Iyzipay\Request\CreateSubMerchantRequest();
    $req->setLocale(\Iyzipay\Model\Locale::TR);
    $req->setConversationId('SUB-'.$siteId.'-'.time());
    $req->setSubMerchantExternalId('site_'.$siteId);
    $req->setSubMerchantType(\Iyzipay\Model\SubMerchantType::PERSONAL);
    $mgrName = $site['mgr_name'] ?? $site['name'];
    $parts=explode(' ', trim($mgrName),2);
    $req->setContactName($parts[0] ?? $site['name']);
    $req->setContactSurname($parts[1] ?? 'Yonetim');
    $req->setAddress($site['address'] ?: 'Site Adresi, Istanbul');
    $req->setGsmNumber($site['mgr_phone'] ?: '+905350000000');
    // iyzico sandbox email validasyonu katı - .local kabul etmiyor
    $email = $site['mgr_email'] ?? '';
    if(!filter_var($email, FILTER_VALIDATE_EMAIL) || strpos($email,'.local')!==false) $email = 'info@residapro.com';
    $req->setEmail($email);
    $req->setName($site['name']);
    $req->setIban(str_replace(' ','',$site['iban']));
    $req->setCurrency(\Iyzipay\Model\Currency::TL);
    $result=\Iyzipay\Model\SubMerchant::create($req,$options);
    if($result->getStatus()==='success'){
        $key=$result->getSubMerchantKey();
        $pdo->prepare("UPDATE sites SET iyzico_submerchant_key=? WHERE id=?")->execute([$key,$siteId]);
        return $key;
    } else {
        $code=$result->getErrorCode(); $msg=$result->getErrorMessage();
        // Pazaryeri değilse: merkezi tahsilat modunda kal, mock key ile devam et
        if(strpos($msg,'pazaryeri')!==false || $code==5){
            $mockKey='central-'.$siteId;
            $pdo->prepare("UPDATE sites SET iyzico_submerchant_key=? WHERE id=?")->execute([$mockKey,$siteId]);
            throw new Exception("iyzico hesabınız Pazaryeri değil — kartlı ödemeler merkezi hesaba düşecek, siz site IBAN'ına manuel aktaracaksınız. (Pazaryeri başvurusu: iyzico panel → Pazaryeri) [".$code."] ".$msg);
        }
        throw new Exception("iyzico alt üye hata [".$code."] ".$msg);
    }
}
