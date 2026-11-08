<?php
// talep_formu.php (ID: 2200) — Bitki Yayın Talebi
require_once 'db.php';

if(!isset($_SESSION['user_id'])) { 
    header("Location: index.php?sayfa=110&donus=2200"); 
    exit(); 
}

$mesaj = '';
$mesaj_tip = '';

// Silme İşlemi
if (isset($_GET['sil_id'])) {
    $sil_id = (int)$_GET['sil_id'];
    $sil = $db->prepare("DELETE FROM talepler WHERE id = ? AND kullanici_id = ? AND durum = 'beklemede'");
    $sil->execute([$sil_id, $_SESSION['user_id']]);
    header("Location: index.php?sayfa=2200");
    exit();
}

// Talep Ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['talep_gonder'])) {
    $bitki_adi = htmlspecialchars($_POST['bitki_adi']);
    $icerik = htmlspecialchars($_POST['icerik']);
    
    $ekle = $db->prepare("INSERT INTO talepler (kullanici_id, bitki_adi, icerik) VALUES (?, ?, ?)");
    $ekle->execute([$_SESSION['user_id'], $bitki_adi, $icerik]);
    $mesaj = "✅ Talebin başarıyla admine gönderildi!";
    $mesaj_tip = 'basari';
}

// Kullanıcının kendi taleplerini listeleme
$taleplerim = $db->prepare("SELECT * FROM talepler WHERE kullanici_id = ? ORDER BY tarih DESC");
$taleplerim->execute([$_SESSION['user_id']]);
$tum_talepler = $taleplerim->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in" style="max-width:800px;">
    <h2 style="color:var(--text-main); margin-bottom:20px;">📩 Yeni Bitki Yayınlama Talebi</h2>
    <p style="color:var(--text-light); margin-bottom:20px;">
        Kütüphanede görmek istediğin bitkiyi öner! Admin onayladığında otomatik olarak kütüphaneye eklenir.
    </p>

    <?php if($mesaj): ?>
        <div class="mesaj <?php echo $mesaj_tip; ?>"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <!-- Talep Formu -->
    <div class="dash-card" style="margin-bottom:25px;">
        <h4 style="color:var(--text-main); margin-bottom:15px;">🌱 Yeni Talep Gönder</h4>
        <form method="POST">
            <input type="text" name="bitki_adi" placeholder="Bitki Adı (Örn: Aloe Vera)" required>
            <textarea name="icerik" placeholder="Bitki hakkında kısa bilgi ve bakım önerileri..." rows="4" required></textarea>
            <button type="submit" name="talep_gonder" class="btn-canli">📩 Talep Gönder</button>
        </form>
    </div>

    <!-- Gönderdiğim Talepler -->
    <h3 style="color:var(--text-main); margin-bottom:15px;">📋 Gönderdiğim Talepler (<?php echo count($tum_talepler); ?>)</h3>
    
    <?php if(count($tum_talepler) > 0): ?>
        <div class="dash-card" style="padding:0; overflow:hidden;">
            <table>
                <tr>
                    <th>Bitki Adı</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>İşlem</th>
                </tr>
                <?php foreach($tum_talepler as $t): ?>
                <tr>
                    <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($t['bitki_adi']); ?></strong></td>
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
                            <a href="index.php?sayfa=2200&sil_id=<?php echo $t['id']; ?>" 
                               onclick="return confirm('İsteği silmek istediğine emin misin?')"
                               class="btn-action reject">🗑️ Sil</a>
                        <?php else: ?>
                            <span style="color:var(--text-light); font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php else: ?>
        <div class="dash-card" style="text-align:center; padding:40px;">
            <p style="color:var(--text-light);">📭 Henüz talep göndermemişsin.</p>
        </div>
    <?php endif; ?>
</div>