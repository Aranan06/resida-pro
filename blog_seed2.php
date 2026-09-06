<?php
// Blog tohum 2/2 : Yazılım + Ödeme (tek seferlik, INSERT IGNORE ile güvenli)
require '/var/www/html/includes/config.php';
$posts=[];

$posts[]=['apartman-yonetim-programi-nedir','yazilim','Apartman Yönetim Programı Nedir?','Apartman yönetim programı; aidat, gider, sakin ve duyuruları tek panelde toplayan yazılımdır. Ne işe yaradığını anlattık.',<<<'HTML'
<p>Apartman yönetim programı, site ve apartmanların idari işlerini dijitalleştiren yazılımdır. Defter, Excel ve mesajlaşma gruplarının yerini tek panel alır.</p>
<h2>Temel özellikleri</h2>
<ul><li><strong>Aidat takibi:</strong> Dönem aidatlarını otomatik oluşturma, borç-alacak takibi</li><li><strong>Sakin kaydı:</strong> Daire, blok, iletişim ve not bilgileri</li><li><strong>Gider yönetimi:</strong> Fatura ve makbuz kayıtları, kasa raporu</li><li><strong>İletişim:</strong> Duyuru yayınlama ve hatırlatma gönderme</li></ul>
<h2>Kimler kullanmalı?</h2>
<p>20 dairelik apartmandan yüzlerce dairelik sitelere kadar aidat toplayan her yönetim kullanabilir. İş yükü daire sayısıyla katlanarak arttığı için fayda büyük sitelerde daha da belirgindir.</p>
<h2>Nereden başlanır?</h2>
<p>Önce sakin listesi aktarılır, ilk ayın aidatı oluşturulur. Kurulum çoğu sitede aynı gün tamamlanır.</p>
HTML
];

$posts[]=['site-yonetim-programi-secerken-nelere-dikkat-edilmeli','yazilim','Site Yönetim Programı Seçerken Nelere Dikkat Edilmeli?','Doğru programı seçmek için 7 kritik kriter: blok yapısı, tahsilat yöntemi, raporlama, destek ve fiyatlandırma.',<<<'HTML'
<p>Yanlış yazılım seçimi, Excel'e geri dönmekle sonuçlanır. Karar vermeden önce şu 7 soruyu sorun:</p>
<h2>Seçim kontrol listesi</h2>
<ul><li><strong>Blok yapısı:</strong> Sitenizdeki blok, kat ve daire düzenini aynen kurabiliyor musunuz?</li><li><strong>Tahsilat:</strong> Havale dekont onayı ve kartla ödeme var mı? Para kimin hesabına gidiyor?</li><li><strong>Sakin erişimi:</strong> Sakinler borcunu telefondan görebiliyor mu?</li><li><strong>Raporlar:</strong> Kasa ve tahsilat raporu tek tuşla alınıyor mu?</li><li><strong>KVKK:</strong> Veriler nerede saklanıyor, yedekleme var mı?</li><li><strong>Destek:</strong> Kurulumda yardımcı olunuyor mu?</li><li><strong>Fiyat:</strong> Daire sayınıza uygun, gizli ücretsiz paket var mı?</li></ul>
<h2>Ücretsiz denemeyi atlamayın</h2>
<p>Kendi sitenizin gerçek verileriyle 1-2 hafta deneyin. Yönetici ve birkaç sakin programa girsin; karar ondan sonra verilsin.</p>
HTML
];

$posts[]=['excel-ile-apartman-aidati-takip-etmek-mi-yazilim-kullanmak-mi','yazilim','Excel ile Apartman Aidatı Takip Etmek mi, Yazılım Kullanmak mı?','İkisini dürüstçe karşılaştırdık: maliyet, zaman, hata riski ve şeffaflık başlıklarında kazananı açıkladık.',<<<'HTML'
<p>Birçok yönetici "Excel bana yetiyor" diye düşünür. Rakamlara bakınca tablo değişir.</p>
<h2>Zaman maliyeti</h2>
<p>50 dairelik sitede aylık aidat işleme Excel'de 2-3 saat sürer. Yazılımda aynı işlem tek tuşla dakikalara iner. Yılda 30 saatten fazla zaman kazanılır.</p>
<h2>Hata riski</h2>
<p>Tek bir yanlış formül, yanlış tahsilat demektir. Yazılımda hesaplama otomatiktir; yönetici sadece onaylar.</p>
<h2>Şeffaflık</h2>
<p>Excel dosyası yöneticinin bilgisayarındadır; sakin göremez. Yazılımda her sakin kendi borcunu görür, tartışma biter.</p>
<h2>Sonuç</h2>
<p>Excel ücretsizdir ama yönetici zamanını ve site huzurunu pahalıya mal eder. Aidat takip programı, ilk aydan kendini amorti eder.</p>
HTML
];

$posts[]=['apartman-yonetimi-icin-en-iyi-program-nasil-secilir','yazilim','Apartman Yönetimi İçin En İyi Program Nasıl Seçilir?','En iyi program, sizin sitenize uyan programdır. İhtiyaç analizi, deneme ve geçiş planı adımlarını yazdık.',<<<'HTML'
<p>"En iyi" program diye mutlak bir cevap yoktur; sitenize en uygun olan en iyisidir. Doğru seçim için şu yolu izleyin:</p>
<h2>1. İhtiyacınızı yazın</h2>
<p>Daire sayısı, blok yapısı, tahsilat yönteminiz (havale mi kart mı?), rapor ihtiyacınız ve bütçeniz. Liste netleşince seçenekler kendiliğinden elenir.</p>
<h2>2. Kısa liste yapın</h2>
<p>3 programı aynı kriterlerle puanlayın: kurulum kolaylığı, sakin paneli, destek hızı, fiyat.</p>
<h2>3. Gerçek veriyle deneyin</h2>
<p>Demo verilerle değil, kendi sakin listenizle deneyin. 10 dakikada kurulamayan programı eleyin.</p>
<h2>4. Geçişi planlayın</h2>
<p>Eski borçların nasıl taşınacağını ve sakinlere nasıl duyurulacağını önceden netleştirin.</p>
HTML
];

$posts[]=['aidat-takip-programi-ne-ise-yarar','yazilim','Aidat Takip Programı Ne İşe Yarar?','Aidat oluşturmadan tahsilat raporuna kadar tüm süreci otomatikleştirir. Günlük hayatta neleri değiştirdiğini örneklerle anlattık.',<<<'HTML'
<p>Aidat takip programı, aidatın doğduğu andan tahsil edildiği ana kadar tüm süreci yönetir.</p>
<h2>Neleri otomatikleştirir?</h2>
<ul><li>Dönem aidatlarını tüm dairelere tek tuşla işler</li><li>Vadesi geçenlere hatırlatma gönderir</li><li>Gecikme bedelini kuralla hesaplar</li><li>Tahsilat oranını ve kasayı raporlar</li></ul>
<h2>Yöneticiye kazandırdıkları</h2>
<p>Ayda saatler süren hesap işi dakikalara iner. Makbuz ve raporlar hazır çıkar. Denetim zamanı her kuruşun belgesi el altındadır.</p>
<h2>Sakine kazandırdıkları</h2>
<p>Sakin borcunu telefonundan görür, dekontunu yükler, duyuruları kaçırmaz. "Haberim yoktu" dönemi kapanır.</p>
HTML
];

$posts[]=['apartman-yonetim-programi-fiyatlari-neye-gore-degisir','yazilim','Apartman Yönetim Programı Fiyatları Neye Göre Değişir?','Fiyatlar genelde daire sayısına ve özelliklere göre belirlenir. Adil fiyatın formülünü ve gizli maliyetleri yazdık.',<<<'HTML'
<p>Apartman yönetim programı fiyatları; daire sayısı, özellik seti ve destek seviyesine göre değişir.</p>
<h2>Fiyatı belirleyen 3 unsur</h2>
<ul><li><strong>Daire sayısı:</strong> 20 dairelik apartmanla 500 dairelik site aynı pakette olmaz; kademeli fiyat normaldir.</li><li><strong>Özellikler:</strong> Kartla ödeme, mobil panel, gelişmiş rapor gibi modüller fiyatı etkiler.</li><li><strong>Destek:</strong> Kurulum yardımı ve hızlı destek, fiyata dahil olmalıdır.</li></ul>
<h2>Gizli maliyetlere dikkat</h2>
<p>Kurulum ücreti, kullanıcı başına ek ücret, komisyon kesintisi gibi kalemleri sözleşmeden önce sorun. Aylık net tutarı yazılı alın.</p>
<h2>Doğru bütçe</h2>
<p>Daire başına aylık birkaç liralık maliyet, yöneticinin kazandığı saatler yanında her zaman kârlıdır.</p>
HTML
];

$posts[]=['apartman-aidati-kredi-kartiyla-odenebilir-mi','odeme','Apartman Aidatı Kredi Kartıyla Ödenebilir Mi?','Evet. Kartla aidat ödemesi hem yasal hem pratiktir; para doğrudan site hesabına gider. Nasıl çalıştığını anlattık.',<<<'HTML'
<p>Evet, apartman aidatı kredi kartıyla ödenebilir. Modern tahsilat sistemlerinde sakin kart bilgilerini girer, ödeme anında site hesabına yönlendirilir.</p>
<h2>Sakin için avantajları</h2>
<ul><li>Bankaya gitmeden, 7/24 ödeme</li><li>Taksit imkanı (anlaşmaya göre)</li><li>Ödeme kaydı otomatik oluşur, dekont aranmaz</li></ul>
<h2>Yönetici için avantajları</h2>
<ul><li>Tahsilat hızlanır, "havale yapmayı unuttum" biter</li><li>Mutabakat otomatik olur</li><li>Para doğrudan site hesabına gider, aracıda beklemez</li></ul>
<h2>Komisyonu kim öder?</h2>
<p>Kart komisyonunun kime yansıtılacağı baştan netleştirilmelidir. Birçok site, tahsilat hızındaki artış komisyonu karşıladığı için maliyeti üstlenir.</p>
HTML
];

$posts[]=['online-aidat-odeme-sistemi-nasil-calisir','odeme','Online Aidat Ödeme Sistemi Nasıl Çalışır?','Sakin paneline girişten paranın site hesabına geçişine kadar 4 adımlı akışı ve güvenlik önlemlerini anlattık.',<<<'HTML'
<p>Online aidat ödeme sistemi, sakinle site hesabı arasındaki güvenli köprüdür. Akış 4 adımdır:</p>
<h2>1. Sakin borcunu görür</h2>
<p>Telefon veya bilgisayardan panele giren sakin, dönem borcunu ve varsa gecikme bedelini görür.</p>
<h2>2. Ödeme yöntemini seçer</h2>
<p>Kartla ödeme veya havale seçeneklerinden birini tercih eder.</p>
<h2>3. Ödeme gerçekleşir</h2>
<p>Kart ödemesinde tutar anında site hesabına yönlendirilir. Havalede sakin dekontunu yükler, yönetici tek dokunuşla onaylar.</p>
<h2>4. Kayıt kapanır</h2>
<p>Borç otomatik kapanır, makbuz oluşur, kasa raporuna yansır. Tüm adımlar kayıt altındadır.</p>
<h2>Güvenlik</h2>
<p>Kart bilgileri sitede saklanmaz; ödeme, lisanslı ödeme kuruluşu üzerinden gerçekleşir.</p>
HTML
];

$posts[]=['site-aidati-internetten-nasil-odenir','odeme','Site Aidatı İnternetten Nasıl Ödenir?','İnternetten aidat ödemek 2 dakikada biter: panele gir, borcu gör, kartla öde veya dekont yükle. Adım adım rehber.',<<<'HTML'
<p>İnternetten aidat ödemek için bankaya gitmenize gerek yok. İşte 2 dakikalık yol:</p>
<h2>Adım 1: Panele giriş</h2>
<p>Yöneticinizin verdiği kullanıcı adı ve şifreyle sakin paneline girin.</p>
<h2>Adım 2: Borcu kontrol edin</h2>
<p>Dönem borcunuzu, varsa gecikme bedelini ve son ödeme tarihini görün.</p>
<h2>Adım 3a: Kartla ödeyin</h2>
<p>Kart bilgilerinizi girip onaylayın. Ödeme anında site hesabına geçer, borcunuz kapanır.</p>
<h2>Adım 3b: Havale yapın</h2>
<p>Site IBAN'ına havale yapıp dekont fotoğrafını panele yükleyin. Yönetici onaylayınca borcunuz kapanır.</p>
<h2>Sorun yaşarsanız</h2>
<p>Ödeme görünmüyorsa önce sayfayı yenileyin, sonra yöneticinize dekontunuzla birlikte yazın.</p>
HTML
];

$posts[]=['apartman-aidat-tahsilati-nasil-kolaylastirilir','odeme','Apartman Aidat Tahsilatı Nasıl Kolaylaştırılır?','Tahsilat oranını artıran 6 pratik yöntem: erken hatırlatma, kolay ödeme, şeffaflık ve düzenli takip.',<<<'HTML'
<p>Tahsilat sorunu çoğunlukla kötü niyetten değil, sürtünmeden doğar. Sürtünmeyi azaltan her adım oranı artırır.</p>
<h2>6 pratik yöntem</h2>
<ul><li><strong>Vadeden önce hatırlatın:</strong> Son güne 2-3 gün kala kısa mesaj gönderin.</li><li><strong>Ödemeyi kolaylaştırın:</strong> Kartla ödeme ve havale seçeneklerini birlikte sunun.</li><li><strong>Şeffaf olun:</strong> Her sakin borcunu görebilsin.</li><li><strong>Hızlı onaylayın:</strong> Yüklenen dekont aynı gün onaylansın.</li><li><strong>Düzenli takip edin:</strong> Her ay aynı gün borç listesini çıkarın.</li><li><strong>Adil olun:</strong> Faiz ve kurallar herkese eşit uygulansın.</li></ul>
<h2>Sonuçları ölçün</h2>
<p>Aylık tahsilat oranını takip edin. %90'ın altı, süreçte sürtünme olduğunu gösterir.</p>
HTML
];

$posts[]=['havale-ile-aidat-odeme-dekont-takibi','odeme','Havale ile Aidat Ödeme ve Dekont Takibi','Havale + dekont yönteminde kaybolmamak için: doğru açıklama, hızlı yükleme ve tek dokunuşla onay düzeni.',<<<'HTML'
<p>Havale, aidat tahsilatının en yaygın yoludur. Düzenli dekont takibiyle sorunsuz işler.</p>
<h2>Sakin ne yapmalı?</h2>
<ul><li>Açıklamaya daire no ve dönemi yazın (örn: "B Blok D7 Mart aidatı")</li><li>Dekont fotoğrafını hemen panele yükleyin</li><li>Fotoğrafın okunaklı olduğunu kontrol edin</li></ul>
<h2>Yönetici ne yapmalı?</h2>
<ul><li>Yüklenen dekontları her gün kontrol edin</li><li>Tutar ve dönemi doğrulayıp tek dokunuşla onaylayın</li><li>Eksik dekontu aynı gün sakine bildirin</li></ul>
<h2>Anlaşmazlık çıkarsa</h2>
<p>Banka kayıt tarihi esas alınır. Dekont ve banka ekstresi yan yana konur, konu dakikalar içinde kapanır.</p>
HTML
];

$ins=$pdo->prepare("INSERT IGNORE INTO blog_posts (slug,category,title,excerpt,content,meta_title,meta_desc,is_published,published_at) VALUES (?,?,?,?,?,?,?,?,CURDATE())");
$n=0; foreach($posts as $p){ $ins->execute([$p[0],$p[1],$p[2],$p[3],$p[4],$p[2],$p[3],1]); $n+=$ins->rowCount(); }
echo "seed2 inserted $n\n";
