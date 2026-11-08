<?php
// galeri.php (ID: 1000) — Ana Sayfa / Vitrin
require_once 'db.php';

// Arama ve Veri Çekme
$q = $_GET['q'] ?? '';
if ($q) {
    $sorgu = $db->prepare("SELECT * FROM bitkiler WHERE ad LIKE ? ORDER BY id DESC");
    $sorgu->execute(["%$q%"]);
} else {
    $sorgu = $db->query("SELECT * FROM bitkiler ORDER BY id DESC");
}
$bitkiler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

// Toplam bitki sayısı
$toplam = $db->query("SELECT COUNT(*) FROM bitkiler")->fetchColumn();
?>

<div class="hero">
    <h1>Hayalindeki Bitkiyi Bul 🌱</h1>
    <p>Bakım rehberleri, kullanıcı yorumları ve topluluk deneyimleri ile dolu.</p>
    <form action="index.php" method="GET">
        <input type="hidden" name="sayfa" value="1000">
        <input type="text" name="q" placeholder="🔍 Bitki adı ara... (Örn: Monstera)" value="<?php echo htmlspecialchars($q); ?>">
    </form>
    <div style="margin-top:20px; display:flex; justify-content:center; gap:30px; position:relative; z-index:1;">
        <div style="text-align:center;">
            <div style="font-size:1.8rem; font-weight:700;"><?php echo $toplam; ?>+</div>
            <div style="font-size:0.85rem; opacity:0.8;">Bitki Türü</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:1.8rem; font-weight:700;">
                <?php echo $db->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn(); ?>+
            </div>
            <div style="font-size:0.85rem; opacity:0.8;">Topluluk Üyesi</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:1.8rem; font-weight:700;">
                <?php echo $db->query("SELECT COUNT(*) FROM yorumlar")->fetchColumn(); ?>+
            </div>
            <div style="font-size:0.85rem; opacity:0.8;">Paylaşılan Deneyim</div>
        </div>
    </div>
</div>

<div class="container">
    <?php if($q): ?>
        <div class="mesaj bilgi" style="margin-bottom:20px;">
            🔍 "<strong><?php echo htmlspecialchars($q); ?></strong>" için <?php echo count($bitkiler); ?> sonuç bulundu.
            <a href="index.php?sayfa=1000" style="margin-left:10px; color:var(--accent); font-weight:600;">Tümünü Göster</a>
        </div>
    <?php endif; ?>

    <h2 style="margin-bottom:25px; color:var(--text-main); font-size:1.5rem;">🌿 Popüler Bitkiler</h2>
    
    <div class="bitki-grid">
        <?php if ($bitkiler): ?>
            <?php foreach ($bitkiler as $b): ?>
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
                        <a href="index.php?sayfa=1100&id=<?php echo $b['id']; ?>" class="btn-sm">İncele →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding:60px 20px;">
                <div style="font-size:3rem; margin-bottom:15px;">🔍</div>
                <h3 style="color:var(--text-main); margin-bottom:8px;">Sonuç Bulunamadı</h3>
                <p style="color:var(--text-light);">Başka bir arama yapmayı dene veya <a href="index.php?sayfa=1000" style="color:var(--primary); font-weight:600;">tüm listeyi gör.</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- CTA — Kayıt Çağrısı -->
<?php if(!isset($_SESSION['user_id'])): ?>
<div style="background:var(--bg-card); padding:60px 20px; text-align:center; margin-top:40px; border-top:1px solid var(--border);">
    <h2 style="color:var(--text-main); margin-bottom:10px;">🌿 Topluluğumuza Katıl</h2>
    <p style="color:var(--text-light); margin-bottom:25px; max-width:500px; margin-left:auto; margin-right:auto;">
        Bitki bakım rehberleri, forum tartışmaları, kişisel ajanda ve daha fazlası seni bekliyor!
    </p>
    <div style="display:flex; justify-content:center; gap:15px; flex-wrap:wrap;">
        <a href="index.php?sayfa=120" class="btn-canli">Hemen Üye Ol</a>
        <a href="index.php?sayfa=110" class="btn-hayalet">Giriş Yap</a>
    </div>
</div>
<?php endif; ?>