<?php
// favoriler.php (ID: 2100) — Favori Bitkilerim
require_once 'db.php';

if(!isset($_SESSION['user_id'])) { header("Location: index.php?sayfa=110"); exit(); }

$user_id = $_SESSION['user_id'];

// Favorilerden Çıkarma
if (isset($_GET['cikar'])) {
    $cikar = $db->prepare("DELETE FROM favoriler WHERE bitki_id = ? AND kullanici_id = ?");
    $cikar->execute([$_GET['cikar'], $user_id]);
    header("Location: index.php?sayfa=2100");
    exit();
}

// Favori Bitkileri Listele
$fav_sorgu = $db->prepare("SELECT b.* FROM favoriler f JOIN bitkiler b ON f.bitki_id = b.id WHERE f.kullanici_id = ?");
$fav_sorgu->execute([$user_id]);
$favoriler = $fav_sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in">
    <h2 style="color:var(--text-main); margin-bottom:20px;">❤️ Favori Bitki Kütüphanem</h2>
    
    <?php if(count($favoriler) > 0): ?>
        <div class="bitki-grid">
            <?php foreach($favoriler as $b): ?>
                <div class="bitki-kart">
                    <a href="index.php?sayfa=1100&id=<?php echo $b['id']; ?>">
                        <?php if(!empty($b['resim'])): ?>
                            <img src="uploads/<?php echo $b['resim']; ?>" alt="<?php echo htmlspecialchars($b['ad']); ?>">
                        <?php else: ?>
                            <div style="height:220px; background:var(--menu-item-bg); display:flex; align-items:center; justify-content:center; font-size:3rem;">🌱</div>
                        <?php endif; ?>
                    </a>
                    <div class="bitki-kart-body">
                        <h3><?php echo htmlspecialchars($b['ad']); ?></h3>
                        <p><?php echo mb_substr($b['aciklama'], 0, 70); ?>...</p>
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <a href="index.php?sayfa=1100&id=<?php echo $b['id']; ?>" class="btn-sm">İncele</a>
                            <a href="index.php?sayfa=2100&cikar=<?php echo $b['id']; ?>" 
                               onclick="return confirm('Favorilerden çıkarmak istediğine emin misin?')"
                               class="btn-action reject" style="padding:8px 14px;">
                                💔 Kaldır
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="dash-card" style="text-align:center; padding:60px;">
            <div style="font-size:3rem; margin-bottom:15px;">❤️</div>
            <h3 style="color:var(--text-main); margin-bottom:8px;">Henüz favori bitkin yok</h3>
            <p style="color:var(--text-light); margin-bottom:20px;">Bitki arşivinden beğendiğin bitkileri favorilerine ekleyebilirsin!</p>
            <a href="index.php?sayfa=2300" class="btn-canli" style="padding:10px 24px;">📚 Arşive Git</a>
        </div>
    <?php endif; ?>
</div>