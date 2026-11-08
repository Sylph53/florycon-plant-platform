<?php
// takvim.php (ID: 2500) — Bitki Takvimi
require_once 'db.php';

if(!isset($_SESSION['user_id'])) { header("Location: index.php?sayfa=110"); exit(); }

$user_id = $_SESSION['user_id'];

// Ay ve Yil navigasyonu
$ay = isset($_GET['ay']) ? (int)$_GET['ay'] : (int)date('m');
$yil = isset($_GET['yil']) ? (int)$_GET['yil'] : (int)date('Y');

$onceki_ay = $ay - 1;
$onceki_yil = $yil;
if ($onceki_ay < 1) { $onceki_ay = 12; $onceki_yil--; }

$sonraki_ay = $ay + 1;
$sonraki_yil = $yil;
if ($sonraki_ay > 12) { $sonraki_ay = 1; $sonraki_yil++; }

$ay_adlari = ['', 'Ocak', 'Subat', 'Mart', 'Nisan', 'Mayis', 'Haziran', 'Temmuz', 'Agustos', 'Eylul', 'Ekim', 'Kasim', 'Aralik'];
$gun_adlari = ['Pzt', 'Sal', 'Car', 'Per', 'Cum', 'Cmt', 'Paz'];

$ilk_gun = date('N', mktime(0, 0, 0, $ay, 1, $yil));
$ayin_gun_sayisi = date('t', mktime(0, 0, 0, $ay, 1, $yil));
$bugun = date('Y-m-d');

// Not Ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['not_ekle'])) {
    $tarih = $_POST['tarih'];
    $baslik = htmlspecialchars($_POST['baslik']);
    $icerik = htmlspecialchars($_POST['icerik']);
    $kategori = $_POST['kategori'];
    
    $kategori_renkler = [
        'sulama' => '#3b82f6',
        'gubreleme' => '#f59e0b',
        'budama' => '#ef4444',
        'nakil' => '#8b5cf6',
        'genel' => '#6b7280'
    ];
    $renk = isset($kategori_renkler[$kategori]) ? $kategori_renkler[$kategori] : '#6b7280';
    
    $ekle = $db->prepare("INSERT INTO takvim (kullanici_id, tarih, baslik, icerik, kategori, renk) VALUES (?, ?, ?, ?, ?, ?)");
    $ekle->execute([$user_id, $tarih, $baslik, $icerik, $kategori, $renk]);
    
    header("Location: index.php?sayfa=2500&ay=$ay&yil=$yil");
    exit();
}

// Not Silme
if (isset($_GET['not_sil'])) {
    $not_id = (int)$_GET['not_sil'];
    $sil = $db->prepare("DELETE FROM takvim WHERE id = ? AND kullanici_id = ?");
    $sil->execute([$not_id, $user_id]);
    header("Location: index.php?sayfa=2500&ay=$ay&yil=$yil");
    exit();
}

// Bu aydaki notlari cek
$notlar_stmt = $db->prepare("SELECT * FROM takvim WHERE kullanici_id = ? AND MONTH(tarih) = ? AND YEAR(tarih) = ? ORDER BY tarih");
$notlar_stmt->execute([$user_id, $ay, $yil]);
$notlar = $notlar_stmt->fetchAll(PDO::FETCH_ASSOC);

// Notlari tarihe gore grupla
$not_harita = [];
foreach ($notlar as $n) {
    $gun = (int)date('j', strtotime($n['tarih']));
    $not_harita[$gun][] = $n;
}

$kategori_listesi = [
    'sulama' => ['ikon' => '💧', 'ad' => 'Sulama'],
    'gubreleme' => ['ikon' => '🧪', 'ad' => 'Gubreleme'],
    'budama' => ['ikon' => '✂️', 'ad' => 'Budama'],
    'nakil' => ['ikon' => '🪴', 'ad' => 'Nakil'],
    'genel' => ['ikon' => '📝', 'ad' => 'Genel']
];
?>

<div class="takvim-container fade-in">
    <div class="takvim-header">
        <h2>📅 <?php echo $ay_adlari[$ay] . ' ' . $yil; ?></h2>
        <div class="takvim-nav">
            <a href="index.php?sayfa=2500&ay=<?php echo $onceki_ay; ?>&yil=<?php echo $onceki_yil; ?>">
                <button type="button">&#8592; Onceki</button>
            </a>
            <a href="index.php?sayfa=2500&ay=<?php echo date('m'); ?>&yil=<?php echo date('Y'); ?>">
                <button type="button" style="background:var(--accent);">Bugun</button>
            </a>
            <a href="index.php?sayfa=2500&ay=<?php echo $sonraki_ay; ?>&yil=<?php echo $sonraki_yil; ?>">
                <button type="button">Sonraki &#8594;</button>
            </a>
        </div>
    </div>

    <!-- Kategori Renk Aciklamasi -->
    <div style="display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap;">
        <?php foreach($kategori_listesi as $kat_key => $kat): ?>
            <span style="display:flex; align-items:center; gap:5px; font-size:0.8rem; color:var(--text-light);">
                <?php echo $kat['ikon']; ?> <?php echo $kat['ad']; ?>
            </span>
        <?php endforeach; ?>
    </div>

    <!-- Takvim Grid -->
    <div class="takvim-grid">
        <?php foreach ($gun_adlari as $gun): ?>
            <div class="takvim-gun-baslik"><?php echo $gun; ?></div>
        <?php endforeach; ?>

        <?php for ($i = 1; $i < $ilk_gun; $i++): ?>
            <div class="takvim-gun bos"></div>
        <?php endfor; ?>

        <?php for ($gun = 1; $gun <= $ayin_gun_sayisi; $gun++): 
            $bugun_tarih = sprintf('%04d-%02d-%02d', $yil, $ay, $gun);
            $bugun_mu = ($bugun_tarih == $bugun);
        ?>
            <div class="takvim-gun <?php echo $bugun_mu ? 'bugun' : ''; ?>" 
                 onclick="takvimModal('<?php echo $bugun_tarih; ?>', <?php echo $gun; ?>)">
                <div class="gun-no"><?php echo $gun; ?></div>
                <?php if(isset($not_harita[$gun])): ?>
                    <?php foreach($not_harita[$gun] as $not): ?>
                        <div class="takvim-not <?php echo $not['kategori']; ?>" title="<?php echo htmlspecialchars($not['baslik']); ?>">
                            <?php echo isset($kategori_listesi[$not['kategori']]) ? $kategori_listesi[$not['kategori']]['ikon'] : '📝'; ?> <?php echo mb_substr($not['baslik'], 0, 10); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Bu Aydaki Notlar -->
    <?php if(count($notlar) > 0): ?>
        <div style="margin-top:30px;">
            <h3 style="color:var(--text-main); margin-bottom:15px;">📋 Bu Aydaki Notlarim</h3>
            <?php foreach($notlar as $n): ?>
                <div class="not-kart" style="border-left-color: <?php echo $n['renk']; ?>;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h4>
                            <?php echo isset($kategori_listesi[$n['kategori']]) ? $kategori_listesi[$n['kategori']]['ikon'] : '📝'; ?>
                            <?php echo htmlspecialchars($n['baslik']); ?>
                            <small style="color:var(--text-light); font-weight:400;">(<?php echo date('d.m.Y', strtotime($n['tarih'])); ?>)</small>
                        </h4>
                        <a href="index.php?sayfa=2500&ay=<?php echo $ay; ?>&yil=<?php echo $yil; ?>&not_sil=<?php echo $n['id']; ?>" 
                           onclick="return confirm('Bu notu silmek istediginize emin misiniz?')"
                           style="color:var(--danger); font-size:0.85rem; font-weight:600;">Sil</a>
                    </div>
                    <p style="margin-top:6px;"><?php echo htmlspecialchars($n['icerik']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Takvim Not Ekleme Modal -->
<div class="takvim-modal" id="takvimModal">
    <div class="takvim-modal-icerik">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="color:var(--text-main);">📝 Not Ekle — <span id="modalTarihGoster"></span></h3>
            <button type="button" onclick="modalKapat()" style="background:none; font-size:1.3rem; cursor:pointer; color:var(--text-light);">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="tarih" id="modalTarihInput">
            <input type="text" name="baslik" placeholder="Not basligi (Orn: Monstera sulama)" required>
            <select name="kategori" style="margin-bottom:15px;">
                <option value="sulama">💧 Sulama</option>
                <option value="gubreleme">🧪 Gubreleme</option>
                <option value="budama">✂️ Budama</option>
                <option value="nakil">🪴 Nakil</option>
                <option value="genel" selected>📝 Genel</option>
            </select>
            <textarea name="icerik" placeholder="Detayli not..." rows="3"></textarea>
            <button type="submit" name="not_ekle" class="btn-canli" style="width:100%;">Notu Kaydet</button>
        </form>
    </div>
</div>

<script>
function takvimModal(tarih, gun) {
    document.getElementById('takvimModal').classList.add('aktif');
    document.getElementById('modalTarihInput').value = tarih;
    document.getElementById('modalTarihGoster').textContent = gun + ' <?php echo $ay_adlari[$ay]; ?>';
}
function modalKapat() {
    document.getElementById('takvimModal').classList.remove('aktif');
}
document.getElementById('takvimModal').addEventListener('click', function(e) {
    if (e.target === this) modalKapat();
});
</script>
