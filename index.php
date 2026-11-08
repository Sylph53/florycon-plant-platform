<?php
// index.php - V2.0 Profesyonel Router
ob_start(); // Output buffering — header() hatalarını önler
session_start();
require_once 'db.php';
require_once 'ai_filtre.php';

$sayfa = $_GET['sayfa'] ?? '1000';

// --- Aktiflik puanını session'a yükle ---
if (isset($_SESSION['user_id'])) {
    $puan = aktiflik_puani_guncelle($db, $_SESSION['user_id']);
    $_SESSION['aktiflik_puani'] = $puan;
    
    $jeton_stmt = $db->prepare("SELECT COALESCE(SUM(miktar), 0) as toplam FROM jetonlar WHERE kullanici_id = ?");
    $jeton_stmt->execute([$_SESSION['user_id']]);
    $_SESSION['toplam_jeton'] = $jeton_stmt->fetch(PDO::FETCH_ASSOC)['toplam'];
}

// --- KUSURSUZ HAFIZA SİSTEMİ ---
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['sayfa']) && $_GET['sayfa'] == '1100' && isset($_GET['id'])) {
        $_SESSION['son_sayfa'] = 'index.php?sayfa=1100&id=' . $_GET['id'];
    }
}

if ($sayfa == '1000' && isset($_SESSION['user_id'])) {
    header("Location: index.php?sayfa=2300");
    exit();
}

// Dashboard sayfaları
$dashboard_sayfalar = ['2050', '2100', '2200', '2300', '2400', '2500', '5000', '5100', '5200', '5250', '5300'];

if (isset($_SESSION['user_id'])) {
    $dashboard_sayfalar[] = '1100';
}

$is_dashboard = in_array($sayfa, $dashboard_sayfalar);

if ($is_dashboard && !isset($_SESSION['user_id'])) {
    header("Location: index.php?sayfa=110");
    exit();
}

// Tema
$aktif_tema = $_SESSION['tema'] ?? 'default';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FloryCon - Bitki severler için topluluk platformu.">
    <title>FloryCon — Bitki Topluluğu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body data-theme="<?php echo $aktif_tema; ?>">

    <?php include 'navbar.php'; ?>

    <!-- Müzik Çalar -->
    <audio id="temaMuzik" loop>
        <source src="tema-ses/muzik.mp3" type="audio/mpeg">
    </audio>

    <?php if ($is_dashboard): ?>
        <div class="dashboard-layout">

            <aside class="sidebar">
                <div class="icon">
                    <a href="index.php?sayfa=2050">⚙️</a>
                    <a href="cikis.php" style="color: var(--danger);">🚪</a>
                </div>

                <div class="user-info">
                    <a href="index.php?sayfa=2050">
                        <img src="uploads/<?php echo $_SESSION['profil_resmi'] ?? 'default.png'; ?>" class="user-avatar" alt="Profil">
                    </a>
                    <h4><?php echo $_SESSION['kullanici_ad'] ?? 'Misafir'; ?></h4>
                    <span class="user-role">
                        <?php echo isset($_SESSION['rol']) && $_SESSION['rol']=='admin' ? 'Sistem Yöneticisi 🛡️' : 'Bitki Sever 🌿'; ?>
                    </span>
                    <span class="user-puan">⭐ <?php echo number_format($_SESSION['aktiflik_puani'] ?? 0, 1); ?> Puan</span>
                </div>

                <div class="menu-list">
                    <div class="menu-title">Menü</div>

                    <a href="index.php?sayfa=2300" class="menu-item <?php echo $sayfa=='2300'?'active':''; ?>">
                        <i>📚</i> Bitki Arşivi
                    </a>
                    <a href="index.php?sayfa=2400" class="menu-item <?php echo $sayfa=='2400'?'active':''; ?>">
                        <i>💬</i> Bitki Formu
                    </a>
                    <a href="index.php?sayfa=2100" class="menu-item <?php echo $sayfa=='2100'?'active':''; ?>">
                        <i>❤️</i> Favorilerim
                    </a>
                    <a href="index.php?sayfa=2500" class="menu-item <?php echo $sayfa=='2500'?'active':''; ?>">
                        <i>📅</i> Bitki Takvimi
                    </a>

                    <?php if($_SESSION['rol'] !== 'admin'): ?>
                        <a href="index.php?sayfa=2200" class="menu-item <?php echo $sayfa=='2200'?'active':''; ?>">
                            <i>📩</i> Yayın Talebi Gönder
                        </a>
                    <?php endif; ?>

                    <a href="index.php?sayfa=2050" class="menu-item <?php echo $sayfa=='2050'?'active':''; ?>">
                        <i>⚙️</i> Hesap Ayarları
                    </a>

                    <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                        <div class="menu-title" style="margin-top:20px; color:var(--danger);">Yönetici Paneli</div>

                        <a href="index.php?sayfa=5000" class="menu-item <?php echo $sayfa=='5000'?'active':''; ?>">
                            <i>📊</i> Genel Özet
                        </a>
                        <a href="index.php?sayfa=5100" class="menu-item <?php echo $sayfa=='5100'?'active':''; ?>">
                            <i>🛡️</i> Gelen İstekleri Yönet
                        </a>
                        <a href="index.php?sayfa=5200" class="menu-item <?php echo $sayfa=='5200'?'active':''; ?>">
                            <i>🌿</i> İçerik Yönetimi
                        </a>
                        <a href="index.php?sayfa=5300" class="menu-item <?php echo $sayfa=='5300'?'active':''; ?>">
                            <i>👥</i> Üye Yönetimi
                        </a>
                    <?php endif; ?>

                    <div style="margin-top: auto; border-top:1px solid var(--border); padding-top:10px; margin-top:30px;">
                        <a href="cikis.php" class="menu-item" style="color: var(--danger);">
                            <i>🚪</i> Çıkış Yap
                        </a>
                    </div>
                </div>
            </aside>

            <main class="main-content">
                <?php
                switch ($sayfa) {
                    case '1100': include 'bitki_detay.php'; break;
                    case '2050': include 'profil_guncelle.php'; break;
                    case '2100': include 'favoriler.php'; break;
                    case '2200': include 'talep_formu.php'; break;
                    case '2300': include 'bitki_arsivi.php'; break;
                    case '2400': include 'bitki_formu.php'; break;
                    case '2500': include 'takvim.php'; break;
                    case '5000': include 'admin_panel.php'; break;
                    case '5100': include 'istek_yonetimi.php'; break;
                    case '5200': include 'icerik_yonetimi.php'; break;
                    case '5250': include 'bitki_duzenle.php'; break;
                    case '5300': include 'uye_yonetimi.php'; break;
                }
                ?>
            </main>
        </div>

    <?php else: ?>
        <div class="full-layout">
            <?php
            switch ($sayfa) {
                case '1000': include 'galeri.php'; break;
                case '1100': echo "<div class='container'>"; include 'bitki_detay.php'; echo "</div>"; break;
                case '110':  echo "<div class='container' style='max-width:500px; margin-top:50px;'>"; include 'giris.php'; echo "</div>"; break;
                case '120':  echo "<div class='container' style='max-width:500px; margin-top:50px;'>"; include 'kayit.php'; echo "</div>"; break;
                default: include 'galeri.php'; break;
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Jeton Mini Oyun -->
    <?php if(isset($_SESSION['user_id'])): ?>
        <script src="jeton_oyun.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>

    <!-- Tema & Müzik JavaScript -->
    <script>
    // TEMA SİSTEMİ
    var temalar = ['default', 'dark', 'sakura'];
    var temaIkonlari = { 'default': '🌿', 'dark': '🌙', 'sakura': '🌸' };

    function temaDegistir() {
        var mevcut = document.body.getAttribute('data-theme') || 'default';
        var index = temalar.indexOf(mevcut);
        var yeni = temalar[(index + 1) % temalar.length];

        document.body.setAttribute('data-theme', yeni);
        var btn = document.getElementById('temaBtn');
        if (btn) btn.textContent = temaIkonlari[yeni];

        // Sunucuya kaydet
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'tema_kaydet.php?tema=' + yeni, true);
        xhr.send();
        // localStorage
        localStorage.setItem('FloryCon_tema', yeni);
    }

    // Sayfa yüklenince temayı uygula
    (function() {
        var kayitliTema = localStorage.getItem('FloryCon_tema') || '<?php echo $aktif_tema; ?>';
        if (kayitliTema && kayitliTema !== 'default') {
            document.body.setAttribute('data-theme', kayitliTema);
        }
        var btn = document.getElementById('temaBtn');
        if (btn) btn.textContent = temaIkonlari[kayitliTema] || '🌿';
    })();

    // MÜZİK SİSTEMİ
    var muzikCalıyor = false;
    var muzikElement = document.getElementById('temaMuzik');

    function muzikToggle() {
        var barlar = document.querySelectorAll('.muzik-bar');
        if (muzikCalıyor) {
            muzikElement.pause();
            for (var i=0; i<barlar.length; i++) barlar[i].classList.remove('aktif');
            muzikCalıyor = false;
        } else {
            muzikElement.volume = 0.3;
            muzikElement.play().then(function() {
                for (var i=0; i<barlar.length; i++) barlar[i].classList.add('aktif');
                muzikCalıyor = true;
            }).catch(function() {});
        }
    }
    </script>

</body>
</html>
<?php ob_end_flush(); ?>