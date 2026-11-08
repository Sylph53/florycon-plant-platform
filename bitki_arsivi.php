<?php
// bitki_arsivi.php (ID: 2300) — Üye Bitki Arşivi + Favori Butonu
require_once 'db.php';

if(!isset($_SESSION['user_id'])) { header("Location: index.php?sayfa=110"); exit(); }

$user_id = $_SESSION['user_id'];
$q = $_GET['q'] ?? '';

if ($q) {
    $sorgu = $db->prepare("SELECT * FROM bitkiler WHERE ad LIKE ? ORDER BY id DESC");
    $sorgu->execute(["%$q%"]);
} else {
    $sorgu = $db->query("SELECT * FROM bitkiler ORDER BY id DESC");
}
$bitkiler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcının favori bitki ID'lerini çek
$fav_stmt = $db->prepare("SELECT bitki_id FROM favoriler WHERE kullanici_id = ?");
$fav_stmt->execute([$user_id]);
$fav_idler = $fav_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="fade-in">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; flex-wrap:wrap; gap:15px;">
        <h2 style="color:var(--text-main); font-size:1.5rem;">📚 Bitki Arşivi</h2>

        <form action="index.php" method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="sayfa" value="2300">
            <input type="text" name="q" placeholder="🔍 Bitki ara..." value="<?php echo htmlspecialchars($q); ?>"
                   style="width:250px; margin-bottom:0; padding:10px 18px; border-radius:50px;">
            <button type="submit" class="btn-sm">Ara</button>
        </form>
    </div>

    <?php if($q): ?>
        <div class="mesaj bilgi" style="margin-bottom:20px;">
            🔍 "<strong><?php echo htmlspecialchars($q); ?></strong>" için <?php echo count($bitkiler); ?> sonuç bulundu.
            <a href="index.php?sayfa=2300" style="margin-left:10px; color:var(--accent);">Tümünü Göster</a>
        </div>
    <?php endif; ?>

    <div class="bitki-grid">
        <?php if ($bitkiler): ?>
            <?php foreach ($bitkiler as $b):
                $favori_mi = in_array($b['id'], $fav_idler);
            ?>
                <div class="bitki-kart">
                    <a href="index.php?sayfa=1100&id=<?php echo $b['id']; ?>">
                        <?php if(!empty($b['resim'])): ?>
                            <img src="uploads/<?php echo $b['resim']; ?>" alt="<?php echo htmlspecialchars($b['ad']); ?>">
                        <?php else: ?>
                            <div style="height:220px; background:var(--menu-item-bg); display:flex; align-items:center; justify-content:center; color:var(--text-light); font-size:3rem;">🌱</div>
                        <?php endif; ?>
                    </a>
                    <div class="bitki-kart-body">
                        <h3><?php echo htmlspecialchars($b['ad']); ?></h3>
                        <p><?php echo mb_substr($b['aciklama'], 0, 80); ?>...</p>
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <a href="index.php?sayfa=1100&id=<?php echo $b['id']; ?>" class="btn-sm">İncele</a>
                            <a href="favori_isle.php?bitki_id=<?php echo $b['id']; ?>&donus=<?php echo urlencode('index.php?sayfa=2300' . ($q ? '&q=' . urlencode($q) : '')); ?>"
                               class="fav-btn <?php echo $favori_mi ? 'cikar' : 'ekle'; ?>"
                               style="padding:7px 16px; font-size:0.8rem;">
                                <?php echo $favori_mi ? '❤️ Favorimde' : '🤍 Favoriye Ekle'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding:60px 20px;">
                <div style="font-size:3rem; margin-bottom:15px;">🔍</div>
                <h3 style="color:var(--text-main); margin-bottom:8px;">Sonuç Bulunamadı</h3>
                <p style="color:var(--text-light);">Başka bir arama yapmayı dene veya <a href="index.php?sayfa=2300" style="color:var(--primary); font-weight:600;">tüm listeyi gör.</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>