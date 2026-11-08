<?php
// bitki_duzenle.php (ID: 5250) — Bitki Düzenleme
require_once 'db.php';

// Yetki ve ID Kontrolü
if(!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin' || !isset($_GET['id'])) {
    header("Location: index.php?sayfa=1000");
    exit();
}

$id = (int)$_GET['id'];
$mesaj = '';
$mesaj_tip = '';

// Mevcut veriyi çek
$sorgu = $db->prepare("SELECT * FROM bitkiler WHERE id = ?");
$sorgu->execute([$id]);
$bitki = $sorgu->fetch();

if (!$bitki) {
    header("Location: index.php?sayfa=5200");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad = htmlspecialchars($_POST['ad']);
    $aciklama = htmlspecialchars($_POST['aciklama']);
    $yeni_resim = $bitki['resim'];

    // Yeni resim yüklendiyse
    if (isset($_FILES['resim']) && $_FILES['resim']['name']) {
        $dosya_adi = time() . "_" . $_FILES['resim']['name'];
        if(move_uploaded_file($_FILES['resim']['tmp_name'], "uploads/" . $dosya_adi)) {
            // Eski resmi sil
            if($bitki['resim'] && file_exists("uploads/".$bitki['resim'])) {
                unlink("uploads/".$bitki['resim']);
            }
            $yeni_resim = $dosya_adi;
        }
    }

    // Güncelleme
    $guncelle = $db->prepare("UPDATE bitkiler SET ad = ?, aciklama = ?, resim = ? WHERE id = ?");
    $guncelle->execute([$ad, $aciklama, $yeni_resim, $id]);
    
    // Güncel veriyi tekrar çek
    $sorgu->execute([$id]);
    $bitki = $sorgu->fetch();
    
    $mesaj = "✅ Bitki başarıyla güncellendi!";
    $mesaj_tip = 'basari';
}
?>

<div class="fade-in" style="max-width:700px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 style="color:var(--text-main);">✏️ Bitki Düzenle</h2>
        <a href="index.php?sayfa=5200" class="btn-hayalet" style="padding:8px 18px; font-size:0.85rem;">← İçerik Yönetimine Dön</a>
    </div>

    <?php if($mesaj): ?>
        <div class="mesaj <?php echo $mesaj_tip; ?>"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <div class="dash-card">
        <div style="display:flex; gap:20px; align-items:flex-start; margin-bottom:20px; flex-wrap:wrap;">
            <?php if(!empty($bitki['resim'])): ?>
                <img src="uploads/<?php echo $bitki['resim']; ?>" 
                     style="width:200px; height:150px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid var(--border);" 
                     alt="<?php echo htmlspecialchars($bitki['ad']); ?>">
            <?php endif; ?>
            <div>
                <h3 style="color:var(--text-main);"><?php echo htmlspecialchars($bitki['ad']); ?></h3>
                <p style="color:var(--text-light); font-size:0.9rem;">ID: #<?php echo $bitki['id']; ?></p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">Bitki Adı</label>
            <input type="text" name="ad" value="<?php echo htmlspecialchars($bitki['ad']); ?>" required>
            
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">Açıklama</label>
            <textarea name="aciklama" rows="6"><?php echo htmlspecialchars($bitki['aciklama']); ?></textarea>
            
            <label style="font-weight:600; color:var(--text-main); display:block; margin-bottom:5px;">Resmi Değiştir (İsteğe bağlı)</label>
            <input type="file" name="resim" accept="image/*">
            
            <div style="display:flex; gap:10px; margin-top:15px;">
                <button type="submit" class="btn-canli">💾 Kaydet</button>
                <a href="index.php?sayfa=5200" class="btn-hayalet">İptal</a>
            </div>
        </form>
    </div>
</div>