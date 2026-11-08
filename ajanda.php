<?php
// ajanda.php (ID: 2000) — Bitki Ajandam
require_once 'db.php';

if(!isset($_SESSION['user_id'])) { header("Location: index.php?sayfa=110"); exit(); }

$user_id = $_SESSION['user_id'];

// Not Ekleme
if (isset($_POST['not_ekle'])) {
    $baslik = htmlspecialchars($_POST['baslik']);
    $icerik = htmlspecialchars($_POST['icerik']);
    $stmt = $db->prepare("INSERT INTO ajanda (kullanici_id, not_baslik, not_icerik) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $baslik, $icerik]);
    header("Location: index.php?sayfa=2000");
    exit();
}

// Not Silme
if (isset($_GET['sil'])) {
    $stmt = $db->prepare("DELETE FROM ajanda WHERE id = ? AND kullanici_id = ?");
    $stmt->execute([$_GET['sil'], $user_id]);
    header("Location: index.php?sayfa=2000");
    exit();
}

// Notları Listeleme
$notlar = $db->prepare("SELECT * FROM ajanda WHERE kullanici_id = ? ORDER BY tarih DESC");
$notlar->execute([$user_id]);
$tum_notlar = $notlar->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="ajanda-container fade-in">
    <h2 style="color:var(--text-main); margin-bottom:20px;">📝 Bitki Ajandam</h2>

    <!-- Not Ekleme Formu -->
    <div class="dash-card" style="margin-bottom:25px;">
        <h4 style="color:var(--text-main); margin-bottom:15px;">📌 Yeni Not Ekle</h4>
        <form method="POST">
            <input type="text" name="baslik" placeholder="Bitki Adı veya Not Başlığı (Örn: Monstera Sulama)" required>
            <textarea name="icerik" placeholder="Sulama notları, gelişim takibi, gözlemler..." rows="3" required></textarea>
            <button name="not_ekle" type="submit" class="btn-canli">📌 Notu Kaydet</button>
        </form>
    </div>

    <!-- Notlar Listesi -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h3 style="color:var(--text-main);">📋 Notlarım (<?php echo count($tum_notlar); ?>)</h3>
    </div>

    <?php if(count($tum_notlar) > 0): ?>
        <?php foreach($tum_notlar as $not): ?>
            <div class="not-kart">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h4>
                        <?php echo htmlspecialchars($not['not_baslik']); ?>
                        <small style="color:var(--text-light); font-weight:400;">
                            (<?php echo date('d.m.Y H:i', strtotime($not['tarih'])); ?>)
                        </small>
                    </h4>
                    <a href="index.php?sayfa=2000&sil=<?php echo $not['id']; ?>" 
                       onclick="return confirm('Bu notu silmek istediğine emin misin?')"
                       class="btn-action reject">🗑️ Sil</a>
                </div>
                <p style="margin-top:8px;"><?php echo nl2br(htmlspecialchars($not['not_icerik'])); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="dash-card" style="text-align:center; padding:50px;">
            <div style="font-size:3rem; margin-bottom:15px;">📝</div>
            <h3 style="color:var(--text-main); margin-bottom:8px;">Henüz not yok</h3>
            <p style="color:var(--text-light);">Bitkilerinle ilgili notlarını buraya ekleyebilirsin!</p>
        </div>
    <?php endif; ?>
</div>