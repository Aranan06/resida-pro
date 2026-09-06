<?php
// Blog tohum 1/2 : Aidat + Yönetici (tek seferlik, INSERT IGNORE ile güvenli)
require '/var/www/html/includes/config.php';
$posts=[];

$posts[]=['apartman-aidati-nasil-hesaplanir','aidat','Apartman Aidatı Nasıl Hesaplanır?','Apartman aidatı; ortak giderlerin dairelere paylaştırılmasıyla bulunur. Doğru hesaplama yöntemlerini ve sık yapılan hataları anlattık.',<<<'HTML'
<p>Apartman aidatı, bir binanın ortak giderlerinin kat maliklerine paylaştırılmasıyla ortaya çıkan aylık ödeme tutarıdır. Temizlik, asansör bakımı, bahçe, güvenlik, elektrik ve su gibi kalemler toplanır, yönetim planındaki pay oranlarına göre dairelere bölünür.</p>
<h2>Aidat hesaplamanın 3 adımı</h2>
<ul><li><strong>Giderleri listeleyin:</strong> Geçen yılın gerçekleşen giderleri ve bu yılın beklenen artışları ayrı ayrı yazılır.</li><li><strong>Pay oranını uygulayın:</strong> Çoğu binada aidat daire başına eşittir; bazı yönetim planlarında metrekareye göre paylaştırma yapılır. Hangi yöntemin geçerli olduğunu yönetim planınızdan öğrenin.</li><li><strong>Aylık tutarı bulun:</strong> Yıllık toplamı 12'ye bölün, küçük bir bakım payı ekleyin.</li></ul>
<h2>Sık yapılan hatalar</h2>
<ul><li>Demirbaş giderleriyle işletme giderlerini karıştırmak</li><li>Boş daireleri hesaptan düşmek (boş daire de aidat öder)</li><li>Faiz ve gecikme bedelini aidata eklememek</li></ul>
<h2>Pratik öneri</h2>
<p>Hesabı Excel'de değil, aidat takip programında tutun: giderler tek listede, borçlar daire daire görünür, kimse "benim borcum neydi?" diye sormak zorunda kalmaz.</p>
HTML
];

$posts[]=['2026-apartman-aidati-nasil-belirlenir','aidat','Apartman Aidatı Nasıl Belirlenir?','Aidat tutarı kat malikleri kurulunda belirlenir. Toplantı, çoğunluk ve karar sürecini adım adım açıkladık.',<<<'HTML'
<p>Apartman aidatını yönetici tek başına belirleyemez. Tutar, kat malikleri kurul toplantısında görüşülüp karara bağlanır. Süreç kısaca şöyledir:</p>
<h2>1. Bütçe hazırlanır</h2>
<p>Yönetici, yılın tahmini giderlerini kalem kalem yazar: personel, temizlik, asansör, sigorta, bakım ve yedek akçe. Geçen yılın fiş ve faturaları en sağlam dayanaklardır.</p>
<h2>2. Kurulda oylanır</h2>
<p>Toplantıda bütçe sunulur, aidat tutarı ve ödeme düzeni oylanır. Karar defterine yazılan tutar tüm malikler için bağlayıcı olur.</p>
<h2>3. Herkese duyurulur</h2>
<p>Karar, duyuru panosu ve mesaj gruplarıyla tüm sakinlere iletilir. Yeni tutarın hangi aydan başlayacağı net yazılmalıdır.</p>
<h2>İtirazı olan ne yapar?</h2>
<p>Karara katılmayan malik, toplantı tarihinden itibaren yasal süre içinde sulh hukuk mahkemesine başvurabilir. Ancak dava sonuçlanana kadar belirlenen aidat ödenmeye devam edilir.</p>
HTML
];

$posts[]=['aidat-odemeyen-kat-malikine-ne-yapilabilir','aidat','Aidat Ödemeyen Kat Malikine Ne Yapılabilir?','Borçlu malike önce yazılı hatırlatma yapılır, sonuç alınamazsa icra yoluna gidilir. Yasal süreci ve yöneticiye düşenleri yazdık.',<<<'HTML'
<p>Aidat borcunu ödemeyen kat maliki için kanunun öngördüğü yol bellidir. Önemli olan, her adımın belgeli yapılmasıdır.</p>
<h2>1. Yazılı hatırlatma</h2>
<p>Borç tutarı, dönemi ve son ödeme tarihi yazılı olarak bildirilir. WhatsApp mesajı pratiktir ama iadeli taahhütlü mektup veya noter bildirimi ispat gücü taşır.</p>
<h2>2. Gecikme bedeli işletilir</h2>
<p>Kurul kararında varsa, ödenmeyen tutara aylık gecikme bedeli eklenir. Borç her ay güncel takip edilmelidir.</p>
<h2>3. İcra takibi</h2>
<p>Ödeme yine yapılmazsa, yönetici kurul kararıyla icra takibi başlatır. Kat Mülkiyeti Kanunu'na göre aidat alacakları öncelikli alacaklardandır.</p>
<h2>Yöneticinin yapmaması gerekenler</h2>
<ul><li>Elektrik, su veya asansörü kesmek</li><li>Borçluyu site girişinde ifşa etmek</li><li>Sözlü tartışmaya girmek</li></ul>
<p>Dijital borç takibi yapan bir sistemde kimin ne kadar borcu olduğu tek ekranda görünür; ne yönetici zan altında kalır ne de malik haksızlığa uğrar.</p>
HTML
];

$posts[]=['aidat-gecikme-faizi-nasil-hesaplanir','aidat','Aidat Gecikme Faizi Nasıl Hesaplanır?','Gecikme faizi, vadesi geçen aidata kurulun belirlediği oranda işletilir. Hesap yöntemini örnekle açıkladık.',<<<'HTML'
<p>Vadesinde ödenmeyen aidata, kat malikleri kurulunun belirlediği oranda gecikme bedeli işletilebilir. Oran kararlaştırılmamışsa yasal sınırlar uygulanır.</p>
<h2>Hesap yöntemi</h2>
<p>Formül basittir: <strong>Geciken tutar × aylık oran × geciken ay sayısı</strong>. Örneğin 1.250 TL aidat, aylık %5 oranla 2 ay gecikirse faiz 125 TL olur.</p>
<h2>Hoşgörü süresi nedir?</h2>
<p>Birçok site, vade tarihinden sonra birkaç günlük hoşgörü süresi tanır. Bu süre içinde ödenen borca faiz işletilmez. Sürenin kurul kararında yazılı olması gerekir.</p>
<h2>Adil uygulama için 3 kural</h2>
<ul><li>Oran ve hoşgörü süresi herkese eşit uygulanır</li><li>Faiz, ana borca eklenerek değil ayrı kalem olarak gösterilir</li><li>Her ayın dökümü sakine açık olur</li></ul>
<p>Faizi elle hesaplamak hata doğurur; otomatik hesaplayan bir aidat takip programı hem yöneticiyi hem sakini korur.</p>
HTML
];

$posts[]=['apartman-aidat-borcu-nasil-takip-edilir','aidat','Apartman Aidat Borcu Nasıl Takip Edilir?','Daire daire borç takibi için defter yerine dijital liste kullanın. Düzenli takip rutinini ve raporlamayı anlattık.',<<<'HTML'
<p>Sağlıklı aidat takibinin sırrı düzendir: her dairenin borcu, ödemesi ve kalanı güncel ve herkes için şeffaf olmalıdır.</p>
<h2>Aylık takip rutini</h2>
<ul><li><strong>Ay başında:</strong> Yeni dönem aidatlarını tüm dairelere işleyin.</li><li><strong>Vade günü:</strong> Ödemeyenleri listeleyin, otomatik hatırlatma gönderin.</li><li><strong>Ay sonunda:</strong> Tahsilat oranını ve kasa durumunu raporlayın.</li></ul>
<h2>Hangi bilgiler kayıtlı olmalı?</h2>
<p>Daire, sakin adı, dönem, tutar, vade tarihi, ödeme tarihi, kalan borç ve varsa faiz. Bu 8 bilgi olmadan sağlıklı takip yapılamaz.</p>
<h2>Şeffaflık güven getirir</h2>
<p>Sakinler borçlarını telefonlarından görebildiğinde "benim haberim yoktu" tartışmaları biter. Tahsilat oranı ilk aylarda gözle görülür şekilde artar.</p>
HTML
];

$posts[]=['kiraci-aidat-oder-mi','aidat','Kiracı Aidat Öder Mi?','Genel kural: işletme giderlerini kiracı, demirbaş ve yatırım giderlerini mal sahibi öder. Ayrımı örneklerle açıkladık.',<<<'HTML'
<p>Kirada oturan dairelerde en sık sorulan soru budur: aidatı kiracı mı öder, ev sahibi mi? Cevap, giderin türüne göre değişir.</p>
<h2>Kiracının ödedikleri</h2>
<p>Temizlik, asansör işletmesi, bahçe bakımı, site elektriği gibi <strong>işletme giderleri</strong> kiracıya aittir. Aylık aidatın büyük kısmı bu kalemlerden oluşur.</p>
<h2>Mal sahibinin ödedikleri</h2>
<p>Asansör yenileme, dış cephe, çatı gibi <strong>demirbaş ve yatırım giderleri</strong> mal sahibine aittir. Kiracıdan bu kalemler istenemez.</p>
<h2>Anlaşmazlığı önlemek için</h2>
<ul><li>Kira sözleşmesine aidat maddesi ekleyin</li><li>Demirbaş tahsilatlarını ayrı kalem olarak kesin</li><li>Ödemeleri makbuzla belgelendirin</li></ul>
HTML
];

$posts[]=['apartman-yoneticisinin-gorevleri-nelerdir','yonetici','Apartman Yöneticisinin Görevleri Nelerdir?','Yönetici; aidat toplar, giderleri öder, bakımı yaptırır, kurulu toplar ve siteyi temsil eder. Görev listesini çıkardık.',<<<'HTML'
<p>Apartman yöneticisi, binanın günlük işleyişinden sorumlu seçilmiş kişidir. Görevleri kanunla ve yönetim planıyla belirlenir.</p>
<h2>Temel görevler</h2>
<ul><li><strong>Aidat toplamak:</strong> Dönem aidatlarını tahakkuk ettirmek ve tahsilatı takip etmek</li><li><strong>Giderleri yönetmek:</strong> Faturaları ödemek, bakım anlaşmalarını yapmak</li><li><strong>Toplantı düzenlemek:</strong> Kat malikleri kurulunu yılda en az bir kez toplamak</li><li><strong>Kayıt tutmak:</strong> Karar defteri, gelir-gider belgeleri ve makbuzları saklamak</li><li><strong>Temsil:</strong> Siteyi üçüncü kişilere ve resmi kurumlara karşı temsil etmek</li></ul>
<h2>Yöneticinin yetkileri</h2>
<p>Yönetici, kurul kararlarını uygular; ancak bütçe dışı büyük harcamaları tek başına yapamaz. Yetki sınırları yönetim planında yazar.</p>
<h2>İşi kolaylaştıran araçlar</h2>
<p>Aidat, duyuru, gider ve sakin kayıtlarını tek panelde toplayan bir site yönetim programı, yöneticinin haftalık iş yükünü saatlerden dakikalara indirir.</p>
HTML
];

$posts[]=['site-yoneticisi-ne-yapar','yonetici','Site Yöneticisi Ne Yapar?','Site yöneticisi; bütçe hazırlar, aidat tahsilatını yönetir, personeli koordine eder ve düzeni sağlar. Günlük rutini anlattık.',<<<'HTML'
<p>Site yöneticisinin günü; tahsilat takibi, personel koordinasyonu ve sakin talepleri arasında geçer. İşte tipik bir rutin:</p>
<h2>Sabah: kontrol turu</h2>
<p>Güvenlik, temizlik ve teknik işlerin durumu gözden geçirilir. Acil arızalar aynı gün programa alınır.</p>
<h2>Öğlen: muhasebe işleri</h2>
<p>Gelen ödemeler işlenir, dekontlar onaylanır, borçlu listesi güncellenir. Düzenli kayıt, ay sonu raporunu kolaylaştırır.</p>
<h2>Haftalık: iletişim</h2>
<p>Duyurular yayınlanır, hatırlatmalar gönderilir, şikayet ve öneriler toplanır. Açık iletişim, aidat ödeme isteğini artırır.</p>
<h2>Profesyonel ipucu</h2>
<p>Tüm bu işleri kağıt ve mesajlaşmayla değil, tek panelden yöneten yöneticiler hem zamandan kazanır hem de denetimde rahat eder.</p>
HTML
];

$posts[]=['apartman-yoneticisi-nasil-secilir','yonetici','Apartman Yöneticisi Nasıl Seçilir?','Yönetici, kat malikleri kurulunda oy çokluğuyla seçilir. Adaylık, oylama ve görev süresi adımlarını yazdık.',<<<'HTML'
<p>Apartman yöneticisi, kat maliklerinin oylarıyla seçilir. Süreç şu adımlardan oluşur:</p>
<h2>1. Toplantı çağrısı</h2>
<p>Mevcut yönetici veya maliklerin üçte biri toplantı çağrısı yapar. Gündemde "yönetici seçimi" maddesi yer almalıdır.</p>
<h2>2. Adaylık</h2>
<p>Kat maliklerinden biri veya dışarıdan bir profesyonel aday olabilir. Adayın görevleri üstlenebileceğini beyan etmesi yeterlidir.</p>
<h2>3. Oylama</h2>
<p>Seçim, toplantıya katılanların oy çokluğuyla yapılır. Sonuç karar defterine yazılır ve tüm sakinlere duyurulur.</p>
<h2>Görev süresi ve denetim</h2>
<p>Yönetici genellikle bir yıl için seçilir. Denetçi, gelir-gideri kontrol eder ve kurula rapor sunar.</p>
HTML
];

$posts[]=['apartman-yonetiminde-gelir-gider-nasil-tutulur','yonetici','Apartman Yönetiminde Gelir Gider Nasıl Tutulur?','Her gelir makbuzla, her gider faturayla belgelenir; aylık rapor kurulla paylaşılır. Temiz muhasebe düzenini anlattık.',<<<'HTML'
<p>Site muhasebesinin altın kuralı basittir: belgesiz işlem olmaz. Her tahsilatın makbuzu, her ödemenin faturası dosyalanır.</p>
<h2>Gelir kayıtları</h2>
<ul><li>Aidat tahsilatları (daire ve dönem bazında)</li><li>Demirbaş katılım payları</li><li>Ortak alan gelirleri (reklam, kira vb.)</li></ul>
<h2>Gider kayıtları</h2>
<ul><li>Personel, temizlik, güvenlik</li><li>Bakım-onarım ve yedek parça</li><li>Faturalar: elektrik, su, doğalgaz</li></ul>
<h2>Aylık kapanış</h2>
<p>Ay sonunda gelir-gider farkı ve kasa bakiyesi raporlanır. Denetçiye ve isteyen her malike açılır. Dijital tutulan kayıtlarda rapor tek tuşla alınır, tartışma çıkmaz.</p>
HTML
];

$posts[]=['apartman-yonetim-plani-nedir','yonetici','Apartman Yönetim Planı Nedir?','Yönetim planı, binanın anayasasıdır: aidat düzeni, yönetici yetkileri ve ortak alan kurallarını belirler.',<<<'HTML'
<p>Yönetim planı, apartmanın nasıl yönetileceğini yazan temel belgedir. Kat mülkiyeti kurulurken hazırlanır ve tapuya işlenir.</p>
<h2>Plan neleri içerir?</h2>
<ul><li>Aidatların nasıl paylaştırılacağı</li><li>Yöneticinin görev ve yetkileri</li><li>Ortak alanların kullanım kuralları</li><li>Toplantı ve karar usulleri</li></ul>
<h2>Neden önemli?</h2>
<p>Anlaşmazlık çıktığında ilk bakılan belge yönetim planıdır. Aidat itirazlarından gürültü şikayetlerine kadar çoğu konu burada çözülür.</p>
<h2>Değiştirilebilir mi?</h2>
<p>Evet, ancak kat maliklerinin nitelikli çoğunluğu gerekir. Değişiklik tapuya şerh edilir.</p>
HTML
];

$ins=$pdo->prepare("INSERT IGNORE INTO blog_posts (slug,category,title,excerpt,content,meta_title,meta_desc,is_published,published_at) VALUES (?,?,?,?,?,?,?,?,CURDATE())");
$n=0; foreach($posts as $p){ $ins->execute([$p[0],$p[1],$p[2],$p[3],$p[4],$p[2],$p[3],1]); $n+=$ins->rowCount(); }
echo "seed1 inserted $n\n";
