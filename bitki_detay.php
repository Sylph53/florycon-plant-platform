<?php
// bitki_detay.php (ID: 1100) — Favori + Beğeni + Düzenle/Sil + Kelime Filtre
require_once 'db.php';
require_once 'ai_filtre.php';

$bitki_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? null;
$user_rol = $_SESSION['rol'] ?? 'user';

// 1. Bitki bilgilerini çek
$stmt = $db->prepare("SELECT * FROM bitkiler WHERE id = ?");
$stmt->execute([$bitki_id]);
$bitki = $stmt->fetch();

if (!$bitki) {
    header("Location: index.php");
    exit();
}

// 2. Favori durumunu kontrol et
$fav_durum = false;
if ($user_id) {
    $fav_kontrol = $db->prepare("SELECT id FROM favoriler WHERE kullanici_id = ? AND bitki_id = ?");
    $fav_kontrol->execute([$user_id, $bitki_id]);
    $fav_durum = $fav_kontrol->fetch() ? true : false;
}

// 3. Yorum Ekleme İşlemi + Kelime Filtre
$yorum_hata = '';
if (isset($_POST['yorum_yap']) && $user_id) {
    $yorum = htmlspecialchars($_POST['yorum_metni']);

    // Kelime Filtre kontrolü
    $filtre = icerik_filtrele($yorum);

    if (!$filtre['temiz']) {
        $yorum_hata = "⚠️ Yorumunuzda uygunsuz ifadeler tespit edildi. Lütfen düzenleyip tekrar deneyin.";
        // Küfür skorunu düşür
        kullanici_ai_skor_guncelle($db, $user_id, $filtre['agirlik_toplam']);
    } else {
        // Skoru ile birlikte kaydet
        $ekle = $db->prepare("INSERT INTO yorumlar (bitki_id, kullanici_id, yorum_metni, ai_skor) VALUES (?, ?, ?, ?)");
        $ekle->execute([$bitki_id, $user_id, $yorum, $filtre['skor']]);

        // Aktiflik puanını güncelle
        aktiflik_puani_guncelle($db, $user_id);

        header("Location: index.php?sayfa=1100&id=$bitki_id");
        exit();
    }
}

// 4. Yorumları Çek
$yorumlar = $db->prepare("SELECT y.*, k.kullanici_ad, k.profil_resmi FROM yorumlar y JOIN kullanicilar k ON y.kullanici_id = k.id WHERE y.bitki_id = ? AND (y.gizli = 0 OR y.gizli IS NULL) ORDER BY y.id DESC");
$yorumlar->execute([$bitki_id]);
$tüm_yorumlar = $yorumlar->fetchAll();

// 5. Kullanıcının beğendiği yorumları çek
$begeni_idler = [];
if ($user_id) {
    $begeni_kontrol = $db->prepare("SELECT yorum_id FROM yorum_begeniler WHERE kullanici_id = ?");
    $begeni_kontrol->execute([$user_id]);
    $begeni_idler = $begeni_kontrol->fetchAll(PDO::FETCH_COLUMN);
}
?>

<div class="detay-sayfasi">
    <div style="background: var(--bg-card); padding: 25px; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; border: 1px solid var(--border);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:15px; margin-bottom:15px;">
            <h1 style="color: var(--text-main); font-size:1.6rem;"><?php echo htmlspecialchars($bitki['ad']); ?></h1>

            <?php if ($user_id): ?>
                <a href="favori_isle.php?bitki_id=<?php echo $bitki_id; ?>&donus=<?php echo urlencode('index.php?sayfa=1100&id=' . $bitki_id); ?>"
                   class="fav-btn <?php echo $fav_durum ? 'cikar' : 'ekle'; ?>">
                    <?php echo $fav_durum ? '❤️ Favorilerimde' : '🤍 Favorilere Ekle'; ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if(!empty($bitki['resim'])): ?>
            <img src="uploads/<?php echo $bitki['resim']; ?>"
                style="width: 100%; max-height: 400px; object-fit: cover; border-radius: var(--radius-sm); margin-bottom: 15px;">
        <?php endif; ?>
        <p style="font-size: 1.05rem; line-height: 1.7; color: var(--text-light);"><?php echo nl2br(htmlspecialchars($bitki['aciklama'])); ?></p>
    </div>

    <h3 style="margin-bottom: 15px; color:var(--text-main);">💬 Yorumlar (<?php echo count($tüm_yorumlar); ?>)</h3>

    <?php if ($user_id): ?>
        <?php if($yorum_hata): ?>
            <div class="mesaj hata"><?php echo $yorum_hata; ?></div>
        <?php endif; ?>

        <form method="POST" style="margin-bottom: 30px;">
            <textarea name="yorum_metni" placeholder="Bu bitki hakkında ne düşünüyorsun?"
                style="width: 100%; padding: 15px; border: 2px solid var(--border); border-radius: var(--radius-sm); background:var(--bg-input); color:var(--text-main); font-family:'Poppins',sans-serif;" rows="3" required><?php echo $_POST['yorum_metni'] ?? ''; ?></textarea>
            <button name="yorum_yap" class="btn-canli"
                style="margin-top: 10px;">Yorum Gönder</button>
        </form>
    <?php endif; ?>

    <div class="yorum-listesi-container" style="position: relative;">

        <div class="<?php echo !$user_id ? 'blurred-content' : ''; ?>">
            <?php if (count($tüm_yorumlar) > 0): ?>
                <?php foreach ($tüm_yorumlar as $y):
                    // Beğeni sayısını çek
                    $begeni_sayi_stmt = $db->prepare("SELECT COUNT(*) FROM yorum_begeniler WHERE yorum_id = ?");
                    $begeni_sayi_stmt->execute([$y['id']]);
                    $begeni_sayisi = $begeni_sayi_stmt->fetchColumn();
                    $kullanici_begenmis = in_array($y['id'], $begeni_idler);
                    $kendi_yorumu = ($user_id && $y['kullanici_id'] == $user_id);
                    $admin_mi = ($user_rol === 'admin');
                ?>
                    <div class="yorum-kart" id="yorum-<?php echo $y['id']; ?>">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                            <img src="uploads/<?php echo $y['profil_resmi'] ?? 'default.png'; ?>"
                                 style="width:32px; height:32px; border-radius:50%; object-fit:cover; border:2px solid var(--border);" alt="avatar">
                            <strong style="color: var(--primary-dark);"><?php echo htmlspecialchars($y['kullanici_ad']); ?></strong>
                            <?php if(isset($y['tarih'])): ?>
                                <span style="font-size:0.75rem; color:var(--text-light);"><?php echo date('d.m.Y H:i', strtotime($y['tarih'])); ?></span>
                            <?php endif; ?>

                            <!-- Düzenle / Sil Butonları -->
                            <?php if ($user_id && ($kendi_yorumu || $admin_mi)): ?>
                                <div style="margin-left:auto; display:flex; gap:8px;">
                                    <button onclick="yorumDuzenleBasla(<?php echo $y['id']; ?>)"
                                        class="yorum-aksiyon-btn duzenle" title="Düzenle">✏️</button>
                                    <button onclick="yorumSil(<?php echo $y['id']; ?>)"
                                        class="yorum-aksiyon-btn sil" title="Sil">🗑️</button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Normal görünüm -->
                        <div id="yorum-metin-<?php echo $y['id']; ?>">
                            <p style="margin-top: 5px; color: var(--text-light); line-height:1.6;"><?php echo nl2br(htmlspecialchars($y['yorum_metni'])); ?></p>
                        </div>

                        <!-- Düzenleme formu (gizli) -->
                        <div id="yorum-duzenle-<?php echo $y['id']; ?>" style="display:none; margin-top:8px;">
                            <textarea id="yorum-textarea-<?php echo $y['id']; ?>"
                                style="width:100%; padding:10px; border:2px solid var(--primary); border-radius:var(--radius-sm); background:var(--bg-input); color:var(--text-main); font-family:'Poppins',sans-serif; resize:vertical;"
                                rows="3"><?php echo htmlspecialchars($y['yorum_metni']); ?></textarea>
                            <div style="display:flex; gap:8px; margin-top:8px;">
                                <button onclick="yorumDuzenleKaydet(<?php echo $y['id']; ?>)" class="btn-sm" style="background:var(--primary); font-size:0.8rem;">💾 Kaydet</button>
                                <button onclick="yorumDuzenleIptal(<?php echo $y['id']; ?>)" class="btn-sm" style="background:var(--text-light); font-size:0.8rem;">❌ İptal</button>
                            </div>
                            <div id="yorum-duzenle-hata-<?php echo $y['id']; ?>" class="mesaj hata" style="display:none; margin-top:8px; font-size:0.85rem;"></div>
                        </div>

                        <!-- Beğeni Butonu -->
                        <?php if ($user_id): ?>
                            <div style="margin-top:8px;">
                                <button onclick="yorumBegeniToggle(<?php echo $y['id']; ?>, this)"
                                    class="forum-begeni-btn <?php echo $kullanici_begenmis ? 'begenilmis' : ''; ?>"
                                    style="background:none; border:none; cursor:pointer; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; transition:all 0.3s ease;
                                       color:<?php echo $kullanici_begenmis ? 'var(--primary)' : 'var(--text-light)'; ?>;
                                       background:<?php echo $kullanici_begenmis ? 'var(--primary-light)' : 'transparent'; ?>;">
                                    <span class="begeni-ikon">👍</span>
                                    <span class="begeni-sayi"><?php echo $begeni_sayisi; ?></span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:30px; color:var(--text-light);">
                    <div style="font-size:2rem; margin-bottom:10px;">💬</div>
                    <p>Henüz yorum yapılmamış. İlk yorumu sen yap!</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$user_id): ?>
            <div class="login-overlay">
                <div class="login-overlay-box">
                    <h4>🔒 Yorumları Görmek İçin</h4>
                    <p>Topluluğumuza katıl ve tartışmalara dahil ol.</p>
                    <a href="index.php?sayfa=110&donus=1100&id=<?php echo $bitki_id; ?>"
                        class="btn-canli" style="padding:10px 24px;">Giriş Yap</a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Yorum Beğeni / Düzenle / Sil JavaScript -->
<script>
function yorumBegeniToggle(yorumId, buton) {
    fetch('yorum_isle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'islem=begeni&yorum_id=' + yorumId
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.basarili) {
            var sayiEl = buton.querySelector('.begeni-sayi');
            sayiEl.textContent = data.toplam_begeni;

            if (data.durum === 'begenildi') {
                buton.style.color = 'var(--primary)';
                buton.style.background = 'var(--primary-light)';
                buton.classList.add('begenilmis');
            } else {
                buton.style.color = 'var(--text-light)';
                buton.style.background = 'transparent';
                buton.classList.remove('begenilmis');
            }

            buton.style.transform = 'scale(1.2)';
            setTimeout(function() { buton.style.transform = 'scale(1)'; }, 200);
        }
    })
    .catch(function(err) { console.log('Begeni hatasi:', err); });
}

function yorumSil(yorumId) {
    if (!confirm('Bu yorumu silmek istediğinize emin misiniz?')) return;

    fetch('yorum_isle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'islem=sil&yorum_id=' + yorumId
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.basarili) {
            var kart = document.getElementById('yorum-' + yorumId);
            if (kart) {
                kart.style.transform = 'scale(0.9)';
                kart.style.opacity = '0';
                setTimeout(function() { kart.remove(); }, 300);
            }
        } else {
            alert(data.mesaj || 'Silme işlemi başarısız');
        }
    })
    .catch(function(err) { console.log('Silme hatasi:', err); });
}

function yorumDuzenleBasla(yorumId) {
    document.getElementById('yorum-metin-' + yorumId).style.display = 'none';
    document.getElementById('yorum-duzenle-' + yorumId).style.display = 'block';
}

function yorumDuzenleIptal(yorumId) {
    document.getElementById('yorum-metin-' + yorumId).style.display = 'block';
    document.getElementById('yorum-duzenle-' + yorumId).style.display = 'none';
    // Hata mesajını temizle
    var hataEl = document.getElementById('yorum-duzenle-hata-' + yorumId);
    if (hataEl) hataEl.style.display = 'none';
}

function yorumDuzenleKaydet(yorumId) {
    var textarea = document.getElementById('yorum-textarea-' + yorumId);
    var yeniMetin = textarea.value.trim();

    if (!yeniMetin) {
        alert('Yorum boş olamaz!');
        return;
    }

    fetch('yorum_isle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'islem=duzenle&yorum_id=' + yorumId + '&yeni_metin=' + encodeURIComponent(yeniMetin)
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.basarili) {
            // Orijinal metni güncelle
            var metinEl = document.getElementById('yorum-metin-' + yorumId);
            metinEl.innerHTML = '<p style="margin-top: 5px; color: var(--text-light); line-height:1.6;">' +
                data.yeni_metin.replace(/\n/g, '<br>') +
                ' <span style="font-size:0.7rem; color:var(--accent);">(düzenlendi)</span></p>';

            // Formu gizle
            yorumDuzenleIptal(yorumId);

            // Başarı animasyonu
            var kart = document.getElementById('yorum-' + yorumId);
            kart.style.boxShadow = '0 0 15px rgba(16, 185, 129, 0.4)';
            setTimeout(function() { kart.style.boxShadow = ''; }, 1500);
        } else {
            var hataEl = document.getElementById('yorum-duzenle-hata-' + yorumId);
            hataEl.textContent = data.mesaj;
            hataEl.style.display = 'block';
        }
    })
    .catch(function(err) { console.log('Duzenleme hatasi:', err); });
}
</script>