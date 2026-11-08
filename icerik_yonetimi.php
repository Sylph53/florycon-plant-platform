<?php
// icerik_yonetimi.php (ID: 5200) — Admin İçerik Yönetimi
require_once 'db.php';

// Yetki Kontrolü
if(!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php?sayfa=1000");
    exit();
}

$mesaj = '';
$mesaj_tip = '';

// Silme İşlemi
if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    
    $resim_sorgu = $db->prepare("SELECT resim FROM bitkiler WHERE id = ?");
    $resim_sorgu->execute([$id]);
    $resim_adi = $resim_sorgu->fetchColumn();
    
    if($resim_adi && file_exists("uploads/".$resim_adi)) {
        unlink("uploads/".$resim_adi);
    }

    // İlişkili yorumları da sil
    $db->prepare("DELETE FROM yorumlar WHERE bitki_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM favoriler WHERE bitki_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM bitkiler WHERE id = ?")->execute([$id]);
    
    $mesaj = "✅ Bitki ve ilişkili veriler başarıyla silindi.";
    $mesaj_tip = 'basari';
}

// Yeni Bitki Ekleme
if (isset($_POST['bitki_ekle'])) {
    $ad = htmlspecialchars($_POST['ad']);
    $aciklama = htmlspecialchars($_POST['aciklama']);
    $resim = "";

    if (isset($_FILES['resim']) && $_FILES['resim']['name']) {
        $resim = time() . "_" . $_FILES['resim']['name'];
        move_uploaded_file($_FILES['resim']['tmp_name'], "uploads/" . $resim);
    }

    $ekle = $db->prepare("INSERT INTO bitkiler (ad, aciklama, resim) VALUES (?, ?, ?)");
    $ekle->execute([$ad, $aciklama, $resim]);
    
    $mesaj = "✅ '$ad' başarıyla kütüphaneye eklendi!";
    $mesaj_tip = 'basari';
}

$bitkiler = $db->query("SELECT * FROM bitkiler ORDER BY id DESC");
$tum_bitkiler = $bitkiler->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in">
    <h2 style="color:var(--text-main); margin-bottom:20px;">🌿 İçerik Yönetimi</h2>

    <?php if($mesaj): ?>
        <div class="mesaj <?php echo $mesaj_tip; ?>"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <!-- Bitki Ekleme Formu -->
    <div class="dash-card" style="margin-bottom:25px;">
        <h3 style="color:var(--text-main); margin-bottom:15px;">➕ Kütüphaneye Yeni Bitki Ekle</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="ad" placeholder="Bitki Adı (Örn: Monstera Deliciosa)" required>
            <textarea name="aciklama" placeholder="Bitki hakkında detaylı açıklama, bakım bilgileri..." rows="4"></textarea>
            
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">📷 Bitki Görseli</label>
            <input type="file" name="resim" accept="image/*">
            
            <button type="submit" name="bitki_ekle" class="btn-canli" style="margin-top:10px;">🌿 Kütüphaneye Ekle</button>
        </form>
    </div>

    <!-- Mevcut Bitkiler -->
    <h3 style="color:var(--text-main); margin-bottom:15px;">📚 Mevcut Bitkiler (<?php echo count($tum_bitkiler); ?>)</h3>
    
    <div class="bitki-grid">
        <?php foreach($tum_bitkiler as $b): ?>
            <div class="bitki-kart">
                <?php if(!empty($b['resim'])): ?>
                    <img src="uploads/<?php echo $b['resim']; ?>" alt="<?php echo htmlspecialchars($b['ad']); ?>">
                <?php else: ?>
                    <div style="height:180px; background:var(--menu-item-bg); display:flex; align-items:center; justify-content:center; font-size:3rem;">🌱</div>
                <?php endif; ?>
                
                <div class="bitki-kart-body">
                    <h3><?php echo htmlspecialchars($b['ad']); ?></h3>
                    <p><?php echo mb_substr($b['aciklama'], 0, 60); ?>...</p>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
                        <a href="index.php?sayfa=5250&id=<?php echo $b['id']; ?>" class="btn-action edit">✏️ Düzenle</a>
                        <a href="index.php?sayfa=5200&sil=<?php echo $b['id']; ?>" 
                           class="btn-action reject"
                           onclick="return confirm('Bu bitkiyi, yorumlarını ve favori kayıtlarını silmek istediğinize emin misiniz?')">
                            🗑️ Sil
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>