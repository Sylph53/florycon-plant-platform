<?php
// profil_guncelle.php (ID: 2050) — Hesap Ayarları
require_once 'db.php';

if(!isset($_SESSION['user_id'])) { header("Location: index.php?sayfa=110"); exit(); }

$user_id = $_SESSION['user_id'];
$mesaj = '';
$mesaj_tip = '';

// Profil Bilgileri Güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bilgi_guncelle'])) {
    $ad_soyad = htmlspecialchars($_POST['ad_soyad']);
    $eposta = filter_var($_POST['eposta'], FILTER_SANITIZE_EMAIL);
    
    if (isset($_FILES['profil_resmi']) && $_FILES['profil_resmi']['name']) {
        $dosya_adi = time() . "_" . $_FILES['profil_resmi']['name'];
        $hedef = "uploads/" . $dosya_adi;
        $tip = strtolower(pathinfo($hedef, PATHINFO_EXTENSION));
        if(in_array($tip, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            move_uploaded_file($_FILES['profil_resmi']['tmp_name'], $hedef);
            $sorgu = $db->prepare("UPDATE kullanicilar SET profil_resmi = ? WHERE id = ?");
            $sorgu->execute([$dosya_adi, $user_id]);
            $_SESSION['profil_resmi'] = $dosya_adi;
        } else {
            $mesaj = "❌ Sadece JPG, PNG, GIF ve WEBP formatları kabul edilir.";
            $mesaj_tip = 'hata';
        }
    }

    $sorgu = $db->prepare("UPDATE kullanicilar SET ad_soyad = ?, eposta = ? WHERE id = ?");
    $sorgu->execute([$ad_soyad, $eposta, $user_id]);
    
    if (!$mesaj) {
        $mesaj = "✅ Bilgileriniz başarıyla güncellendi!";
        $mesaj_tip = 'basari';
    }
}

// Şifre Değiştirme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sifre_degistir'])) {
    $mevcut_sifre = $_POST['mevcut_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];
    
    $sifre_kontrol = $db->prepare("SELECT sifre FROM kullanicilar WHERE id = ?");
    $sifre_kontrol->execute([$user_id]);
    $kayitli_sifre = $sifre_kontrol->fetchColumn();
    
    if (!password_verify($mevcut_sifre, $kayitli_sifre)) {
        $mesaj = "❌ Mevcut şifre yanlış!";
        $mesaj_tip = 'hata';
    } elseif ($yeni_sifre !== $yeni_sifre_tekrar) {
        $mesaj = "❌ Yeni şifreler eşleşmiyor!";
        $mesaj_tip = 'hata';
    } elseif (strlen($yeni_sifre) < 6) {
        $mesaj = "❌ Yeni şifre en az 6 karakter olmalı!";
        $mesaj_tip = 'hata';
    } else {
        $hash = password_hash($yeni_sifre, PASSWORD_BCRYPT);
        $guncelle = $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?");
        $guncelle->execute([$hash, $user_id]);
        $mesaj = "✅ Şifreniz başarıyla değiştirildi!";
        $mesaj_tip = 'basari';
    }
}

// Mevcut bilgileri çek
$user = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$user->execute([$user_id]);
$current_user = $user->fetch(PDO::FETCH_ASSOC);

$yorum_sayisi = $db->prepare("SELECT COUNT(*) FROM yorumlar WHERE kullanici_id = ?");
$yorum_sayisi->execute([$user_id]);
$toplam_yorum = $yorum_sayisi->fetchColumn();

$fav_sayisi = $db->prepare("SELECT COUNT(*) FROM favoriler WHERE kullanici_id = ?");
$fav_sayisi->execute([$user_id]);
$toplam_fav = $fav_sayisi->fetchColumn();
?>

<div class="profil-container fade-in">
    <h2 style="color:var(--text-main); margin-bottom:20px;">⚙️ Hesap Ayarları</h2>
    
    <?php if($mesaj): ?>
        <div class="mesaj <?php echo $mesaj_tip; ?>"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <div class="profil-header">
        <img src="uploads/<?php echo $current_user['profil_resmi'] ?? 'default.png'; ?>" class="profil-avatar-buyuk" alt="Profil">
        <div>
            <h3 style="color:var(--text-main); margin-bottom:4px;"><?php echo htmlspecialchars($current_user['kullanici_ad']); ?></h3>
            <span style="color:var(--text-light); font-size:0.9rem;">
                <?php echo ($current_user['rol'] ?? 'user') == 'admin' ? '🛡️ Sistem Yöneticisi' : '🌿 Bitki Sever'; ?>
            </span>
        </div>
    </div>

    <div class="profil-stats">
        <div class="profil-stat-kart">
            <div class="stat-val"><?php echo number_format($current_user['aktiflik_puani'] ?? 0, 1); ?></div>
            <div class="stat-label">⭐ Aktiflik Puanı</div>
        </div>
        <div class="profil-stat-kart">
            <div class="stat-val"><?php echo $current_user['toplam_jeton'] ?? 0; ?></div>
            <div class="stat-label">🍀 Toplam Jeton</div>
        </div>
        <div class="profil-stat-kart">
            <div class="stat-val"><?php echo $toplam_yorum; ?></div>
            <div class="stat-label">💬 Yorum Sayısı</div>
        </div>
    </div>

    <div class="dash-card">
        <h3 style="color:var(--text-main); margin-bottom:15px;">📝 Kişisel Bilgiler</h3>
        <form method="POST" enctype="multipart/form-data">
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">Ad Soyad</label>
            <input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($current_user['ad_soyad'] ?? ''); ?>" placeholder="Ad Soyad">
            
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">E-posta</label>
            <input type="email" name="eposta" value="<?php echo htmlspecialchars($current_user['eposta'] ?? ''); ?>" placeholder="E-posta">
            
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">Profil Resmi Değiştir</label>
            <input type="file" name="profil_resmi" accept="image/*">
            
            <button type="submit" name="bilgi_guncelle" class="btn-canli" style="margin-top:10px;">Bilgileri Güncelle</button>
        </form>
    </div>

    <div class="dash-card" style="margin-top:20px;">
        <h3 style="color:var(--text-main); margin-bottom:15px;">🔒 Şifre Değiştir</h3>
        <form method="POST">
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">Mevcut Şifre</label>
            <input type="password" name="mevcut_sifre" placeholder="Mevcut şifreniz" required>
            
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">Yeni Şifre</label>
            <input type="password" name="yeni_sifre" placeholder="Yeni şifreniz (min. 6 karakter)" required>
            
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">Yeni Şifre (Tekrar)</label>
            <input type="password" name="yeni_sifre_tekrar" placeholder="Yeni şifrenizi tekrar girin" required>
            
            <button type="submit" name="sifre_degistir" class="btn-hayalet" style="margin-top:10px;">Şifreyi Değiştir</button>
        </form>
    </div>
</div>