<?php
// istek_yonetimi.php (ID: 5100) — Admin Talep Yönetimi
require_once 'db.php';

// Yetki Kontrolü
if(!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php?sayfa=1000");
    exit();
}

$mesaj = '';
$mesaj_tip = '';

// ONAYLA veya REDDET İşlemi
if (isset($_GET['islem']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $yeni_durum = ($_GET['islem'] == 'onayla') ? 'onaylandi' : 'reddedildi';
    
    $guncelle = $db->prepare("UPDATE talepler SET durum = ? WHERE id = ?");
    $guncelle->execute([$yeni_durum, $id]);
    
    // Eğer onaylandıysa, bitkiyi otomatik olarak bitkiler tablosuna ekle
    if ($yeni_durum == 'onaylandi') {
        $talep = $db->prepare("SELECT * FROM talepler WHERE id = ?");
        $talep->execute([$id]);
        $talep_veri = $talep->fetch(PDO::FETCH_ASSOC);
        
        if ($talep_veri) {
            // Aynı isimde bitki var mı kontrol et
            $kontrol = $db->prepare("SELECT id FROM bitkiler WHERE ad = ?");
            $kontrol->execute([$talep_veri['bitki_adi']]);
            
            if (!$kontrol->fetch()) {
                $ekle = $db->prepare("INSERT INTO bitkiler (ad, aciklama, resim) VALUES (?, ?, '')");
                $ekle->execute([$talep_veri['bitki_adi'], $talep_veri['icerik']]);
                $mesaj = "✅ Talep onaylandı ve bitki kütüphaneye eklendi!";
            } else {
                $mesaj = "✅ Talep onaylandı. (Bitki zaten kütüphanede mevcut)";
            }
            $mesaj_tip = 'basari';
        }
    } else {
        $mesaj = "❌ Talep reddedildi.";
        $mesaj_tip = 'uyari';
    }
}

// Tüm talepleri kullanıcı isimleriyle birlikte çek
$talepler = $db->query("SELECT t.*, k.kullanici_ad, k.profil_resmi FROM talepler t 
                        JOIN kullanicilar k ON t.kullanici_id = k.id 
                        ORDER BY 
                            CASE WHEN t.durum = 'beklemede' THEN 0 ELSE 1 END,
                            t.tarih DESC");
$tum_talepler = $talepler->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in">
    <h2 style="color:var(--text-main); margin-bottom:20px;">📩 Gelen Bitki Yayın Talepleri</h2>
    <p style="color:var(--text-light); margin-bottom:20px;">
        Kullanıcıların gönderdiği bitki içeriklerini inceleyip onaylayabilirsiniz. Onaylanan bitkiler otomatik olarak kütüphaneye eklenir.
    </p>

    <?php if($mesaj): ?>
        <div class="mesaj <?php echo $mesaj_tip; ?>"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <div class="dash-card" style="padding:0; overflow:hidden;">
        <table>
            <tr>
                <th>Gönderen</th>
                <th>Bitki Adı</th>
                <th>İçerik</th>
                <th>Durum</th>
                <th>Tarih</th>
                <th>İşlemler</th>
            </tr>
            <?php if(count($tum_talepler) > 0): ?>
                <?php foreach($tum_talepler as $t): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <img src="uploads/<?php echo $t['profil_resmi'] ?? 'default.png'; ?>" class="uye-avatar" alt="avatar">
                            <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($t['kullanici_ad']); ?></strong>
                        </div>
                    </td>
                    <td><strong style="color:var(--primary);"><?php echo htmlspecialchars($t['bitki_adi']); ?></strong></td>
                    <td style="max-width:250px; font-size:0.85rem; color:var(--text-light);">
                        <?php echo mb_substr($t['icerik'], 0, 100); ?>...
                    </td>
                    <td>
                        <?php 
                        if($t['durum'] == 'beklemede') echo "<span class='badge' style='background:#fef3c7; color:#92400e;'>⏳ Beklemede</span>";
                        elseif($t['durum'] == 'onaylandi') echo "<span class='badge' style='background:#d1fae5; color:#065f46;'>✅ Onaylandı</span>";
                        else echo "<span class='badge danger'>❌ Reddedildi</span>";
                        ?>
                    </td>
                    <td style="font-size:0.85rem; color:var(--text-light);">
                        <?php echo date('d.m.Y', strtotime($t['tarih'])); ?>
                    </td>
                    <td>
                        <?php if($t['durum'] == 'beklemede'): ?>
                            <a href="index.php?sayfa=5100&islem=onayla&id=<?php echo $t['id']; ?>" 
                               class="btn-action approve" onclick="return confirm('Bu talebi onaylayıp bitkiyi kütüphaneye eklemek istiyor musunuz?')">
                                ✅ Onayla
                            </a>
                            <a href="index.php?sayfa=5100&islem=reddet&id=<?php echo $t['id']; ?>" 
                               class="btn-action reject" style="margin-left:5px;"
                               onclick="return confirm('Bu talebi reddetmek istediğinize emin misiniz?')">
                                ❌ Reddet
                            </a>
                        <?php else: ?>
                            <span style="color:var(--text-light); font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:40px; color:var(--text-light);">
                        📭 Henüz talep yok.
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>