<?php
// bitki_formu.php (ID: 2400) — Forum Sayfası + Beğeni Sistemi
require_once 'db.php';
require_once 'ai_filtre.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?sayfa=110");
    exit();
}

$user_id = $_SESSION['user_id'];
$alt_sayfa = $_GET['forum'] ?? 'liste';
$konu_id = $_GET['konu_id'] ?? 0;

// YENİ KONU OLUŞTUR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['konu_olustur'])) {
    $baslik = htmlspecialchars($_POST['baslik']);
    $icerik = htmlspecialchars($_POST['icerik']);

    $filtre_baslik = icerik_filtrele($baslik);
    $filtre_icerik = icerik_filtrele($icerik);

    if (!$filtre_baslik['temiz'] || !$filtre_icerik['temiz']) {
        $forum_hata = "⚠️ İçeriğinizde uygunsuz ifadeler tespit edildi. Lütfen düzenleyip tekrar deneyin.";
        $agirlik = max($filtre_baslik['agirlik_toplam'], $filtre_icerik['agirlik_toplam']);
        kullanici_ai_skor_guncelle($db, $user_id, $agirlik);
    } else {
        $ekle = $db->prepare("INSERT INTO forum_konular (kullanici_id, baslik, icerik) VALUES (?, ?, ?)");
        $ekle->execute([$user_id, $baslik, $icerik]);
        header("Location: index.php?sayfa=2400");
        exit();
    }
}

// YANIT EKLE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['yanit_gonder'])) {
    $yanit_icerik = htmlspecialchars($_POST['yanit_icerik']);
    $yanit_konu_id = (int) $_POST['konu_id'];
    $alinti_id = !empty($_POST['alinti_id']) ? (int) $_POST['alinti_id'] : null;

    $filtre = icerik_filtrele($yanit_icerik);

    if (!$filtre['temiz']) {
        $forum_hata = "⚠️ Yanıtınızda uygunsuz ifadeler tespit edildi.";
        kullanici_ai_skor_guncelle($db, $user_id, $filtre['agirlik_toplam']);
        $alt_sayfa = 'konu';
        $konu_id = $yanit_konu_id;
    } else {
        $ekle = $db->prepare("INSERT INTO forum_yanitlar (konu_id, kullanici_id, alinti_yanit_id, icerik) VALUES (?, ?, ?, ?)");
        $ekle->execute([$yanit_konu_id, $user_id, $alinti_id, $yanit_icerik]);

        // Aktiflik puanını güncelle
        aktiflik_puani_guncelle($db, $user_id);

        header("Location: index.php?sayfa=2400&forum=konu&konu_id=" . $yanit_konu_id);
        exit();
    }
}
?>

<div class="forum-container fade-in">

    <?php if ($alt_sayfa == 'yeni'): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="color:var(--text-main);">✏️ Yeni Konu Aç</h2>
            <a href="index.php?sayfa=2400" class="btn-hayalet" style="padding:8px 18px; font-size:0.85rem;">← Konulara
                Dön</a>
        </div>

        <?php if (isset($forum_hata)): ?>
            <div class="mesaj hata">
                <?php echo $forum_hata; ?>
            </div>
        <?php endif; ?>

        <div class="dash-card">
            <form method="POST">
                <label style="font-weight:600; color:var(--text-main); margin-bottom:5px; display:block;">Konu
                    Başlığı</label>
                <input type="text" name="baslik" placeholder="Örn: Monstera yaprak sararması..." required
                    value="<?php echo $_POST['baslik'] ?? ''; ?>">

                <label style="font-weight:600; color:var(--text-main); margin-bottom:5px; display:block;">İçerik</label>
                <textarea name="icerik" placeholder="Sorunuzu veya deneyiminizi detaylıca yazın..." rows="6"
                    required><?php echo $_POST['icerik'] ?? ''; ?></textarea>

                <button type="submit" name="konu_olustur" class="btn-canli" style="margin-top:10px;">💬 Konuyu
                    Paylaş</button>
            </form>
        </div>

    <?php elseif ($alt_sayfa == 'konu' && $konu_id > 0): ?>
        <?php
        $konu_stmt = $db->prepare("SELECT fk.*, k.kullanici_ad, k.profil_resmi FROM forum_konular fk JOIN kullanicilar k ON fk.kullanici_id = k.id WHERE fk.id = ?");
        $konu_stmt->execute([$konu_id]);
        $konu = $konu_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$konu) {
            echo "<div class='mesaj hata'>Konu bulunamadı.</div>";
        } else {

            $db->prepare("UPDATE forum_konular SET goruntulenme = goruntulenme + 1 WHERE id = ?")->execute([$konu_id]);

            $yanitlar_stmt = $db->prepare("SELECT fy.*, k.kullanici_ad, k.profil_resmi FROM forum_yanitlar fy JOIN kullanicilar k ON fy.kullanici_id = k.id WHERE fy.konu_id = ? ORDER BY fy.tarih ASC");
            $yanitlar_stmt->execute([$konu_id]);
            $yanitlar = $yanitlar_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Kullanıcının bu konudaki beğenilerini çek
            $begeni_idler = [];
            $begeni_kontrol = $db->prepare("SELECT yanit_id FROM forum_begeniler WHERE kullanici_id = ?");
            $begeni_kontrol->execute([$user_id]);
            $begeni_idler = $begeni_kontrol->fetchAll(PDO::FETCH_COLUMN);
            ?>

            <div style="margin-bottom:20px;">
                <a href="index.php?sayfa=2400" class="btn-hayalet" style="padding:8px 18px; font-size:0.85rem;">← Konulara
                    Dön</a>
            </div>

            <!-- Konu -->
            <div class="dash-card" style="margin-bottom:20px;">
                <div style="display:flex; gap:15px; align-items:flex-start;">
                    <img src="uploads/<?php echo $konu['profil_resmi'] ?? 'default.png'; ?>" class="forum-konu-avatar"
                        alt="avatar">
                    <div style="flex:1;">
                        <h2 style="color:var(--text-main); font-size:1.3rem; margin-bottom:5px;">
                            <?php echo htmlspecialchars($konu['baslik']); ?>
                        </h2>
                        <div class="forum-konu-meta" style="margin-bottom:12px;">
                            <span>👤
                                <?php echo htmlspecialchars($konu['kullanici_ad']); ?>
                            </span>
                            <span>📅
                                <?php echo date('d.m.Y H:i', strtotime($konu['tarih'])); ?>
                            </span>
                            <span>👁️
                                <?php echo $konu['goruntulenme']; ?>
                            </span>
                            <span>💬
                                <?php echo count($yanitlar); ?>
                            </span>
                        </div>
                        <p style="color:var(--text-light); line-height:1.7;">
                            <?php echo nl2br(htmlspecialchars($konu['icerik'])); ?>
                        </p>
                    </div>
                </div>
            </div>

            <h3 style="color:var(--text-main); margin-bottom:15px;">💬 Yanıtlar (
                <?php echo count($yanitlar); ?>)
            </h3>

            <?php if (isset($forum_hata)): ?>
                <div class="mesaj hata">
                    <?php echo $forum_hata; ?>
                </div>
            <?php endif; ?>

            <?php foreach ($yanitlar as $y):
                // Bu yanıtın beğeni sayısını çek
                $begeni_sayi_stmt = $db->prepare("SELECT COUNT(*) FROM forum_begeniler WHERE yanit_id = ?");
                $begeni_sayi_stmt->execute([$y['id']]);
                $begeni_sayisi = $begeni_sayi_stmt->fetchColumn();
                $kullanici_begenmis = in_array($y['id'], $begeni_idler);
                ?>
                <div class="forum-yanit" id="yanit-<?php echo $y['id']; ?>">
                    <?php
                    if ($y['alinti_yanit_id']) {
                        $alinti_stmt = $db->prepare("SELECT fy.icerik, k.kullanici_ad FROM forum_yanitlar fy JOIN kullanicilar k ON fy.kullanici_id = k.id WHERE fy.id = ?");
                        $alinti_stmt->execute([$y['alinti_yanit_id']]);
                        $alinti = $alinti_stmt->fetch(PDO::FETCH_ASSOC);
                        if ($alinti): ?>
                            <div class="forum-alinti">
                                <strong>
                                    <?php echo htmlspecialchars($alinti['kullanici_ad']); ?>:
                                </strong><br>
                                <?php echo mb_substr(htmlspecialchars($alinti['icerik']), 0, 150); ?>...
                            </div>
                        <?php endif;
                    } ?>

                    <div style="display:flex; gap:12px; align-items:flex-start;">
                        <img src="uploads/<?php echo $y['profil_resmi'] ?? 'default.png'; ?>"
                            style="width:35px; height:35px; border-radius:50%; object-fit:cover; border:2px solid var(--border);"
                            alt="avatar">
                        <div style="flex:1;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <strong style="color:var(--primary-dark); font-size:0.9rem;">
                                    <?php echo htmlspecialchars($y['kullanici_ad']); ?>
                                </strong>
                                <span style="font-size:0.75rem; color:var(--text-light);">
                                    <?php echo date('d.m.Y H:i', strtotime($y['tarih'])); ?>
                                </span>
                            </div>
                            <p style="color:var(--text-light); line-height:1.6; margin-bottom:8px;">
                                <?php echo nl2br(htmlspecialchars($y['icerik'])); ?>
                            </p>

                            <!-- Beğeni ve Alıntı butonları -->
                            <div style="display:flex; gap:15px; align-items:center;">
                                <button onclick="begeniToggle(<?php echo $y['id']; ?>, this)"
                                    class="forum-begeni-btn <?php echo $kullanici_begenmis ? 'begenilmis' : ''; ?>"
                                    style="background:none; border:none; cursor:pointer; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; transition:all 0.3s ease;
                                       color:<?php echo $kullanici_begenmis ? 'var(--primary)' : 'var(--text-light)'; ?>;
                                       background:<?php echo $kullanici_begenmis ? 'var(--primary-light)' : 'transparent'; ?>;">
                                    <span class="begeni-ikon">
                                        <?php echo $kullanici_begenmis ? '👍' : '👍'; ?>
                                    </span>
                                    <span class="begeni-sayi">
                                        <?php echo $begeni_sayisi; ?>
                                    </span>
                                </button>

                                <button
                                    onclick="alintilaCevapla(<?php echo $y['id']; ?>, '<?php echo addslashes($y['kullanici_ad']); ?>')"
                                    style="background:none; border:none; color:var(--accent); font-size:0.8rem; cursor:pointer; font-weight:600;">
                                    ↩️ Alıntıla
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Yanıt Formu -->
            <div class="dash-card" style="margin-top:20px;">
                <h4 style="margin-bottom:15px; color:var(--text-main);">📝 Yanıt Yaz</h4>
                <div id="alintiBilgi"
                    style="display:none; background:var(--menu-item-bg); padding:10px 15px; border-radius:8px; margin-bottom:10px; font-size:0.85rem;">
                    <span id="alintiMetin"></span>
                    <button onclick="alintiKaldir()"
                        style="background:none; border:none; color:var(--danger); cursor:pointer; float:right;">✕</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="konu_id" value="<?php echo $konu_id; ?>">
                    <input type="hidden" name="alinti_id" id="alintiIdInput" value="">
                    <textarea name="yanit_icerik" placeholder="Düşüncelerinizi paylaşın..." rows="4" required></textarea>
                    <button type="submit" name="yanit_gonder" class="btn-canli">Yanıtla</button>
                </form>
            </div>

            <script>
                function alintilaCevapla(yanitId, kullaniciAd) {
                    document.getElementById('alintiIdInput').value = yanitId;
                    document.getElementById('alintiBilgi').style.display = 'block';
                    document.getElementById('alintiMetin').innerHTML = '↩️ <strong>' + kullaniciAd + '</strong> kullanıcısına yanıt veriyorsun';
                    document.querySelector('[name="yanit_icerik"]').focus();
                }
                function alintiKaldir() {
                    document.getElementById('alintiIdInput').value = '';
                    document.getElementById('alintiBilgi').style.display = 'none';
                }

                function begeniToggle(yanitId, buton) {
                    fetch('forum_begeni.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'yanit_id=' + yanitId
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data.basarili) {
                                var sayiEl = buton.querySelector('.begeni-sayi');
                                sayiEl.textContent = data.toplam_begeni;

                                if (data.durum === 'begenildi') {
                                    buton.style.color = 'var(--primary)';
                                    buton.style.background = 'var(--primary-light)';
                                    buton.classList.add('begenilmis');
                                } else {
                                    buton.style.color = 'var(--text-light)';
                                    buton.style.background = 'transparent';
                                    buton.classList.remove('begenilmis');
                                }

                                // Küçük animasyon
                                buton.style.transform = 'scale(1.2)';
                                setTimeout(function () { buton.style.transform = 'scale(1)'; }, 200);
                            }
                        })
                        .catch(function (err) { console.log('Begeni hatasi:', err); });
                }
            </script>

        <?php } ?>

    <?php else: ?>
        <div
            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; flex-wrap:wrap; gap:15px;">
            <h2 style="color:var(--text-main);">💬 Bitki Formu</h2>
            <a href="index.php?sayfa=2400&forum=yeni" class="btn-canli" style="padding:10px 24px; font-size:0.9rem;">✏️ Yeni
                Konu Aç</a>
        </div>

        <?php
        $konular = $db->query("SELECT fk.*, k.kullanici_ad, k.profil_resmi,
                           (SELECT COUNT(*) FROM forum_yanitlar WHERE konu_id = fk.id) as yanit_sayisi
                           FROM forum_konular fk
                           JOIN kullanicilar k ON fk.kullanici_id = k.id
                           ORDER BY fk.tarih DESC");
        $tum_konular = $konular->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (count($tum_konular) > 0): ?>
            <?php foreach ($tum_konular as $k): ?>
                <div class="forum-konu-kart">
                    <img src="uploads/<?php echo $k['profil_resmi'] ?? 'default.png'; ?>" class="forum-konu-avatar" alt="avatar">
                    <div class="forum-konu-icerik">
                        <h3><a href="index.php?sayfa=2400&forum=konu&konu_id=<?php echo $k['id']; ?>">
                                <?php echo htmlspecialchars($k['baslik']); ?>
                            </a>
                        </h3>
                        <p style="color:var(--text-light); font-size:0.9rem; margin-bottom:8px;">
                            <?php echo mb_substr(htmlspecialchars($k['icerik']), 0, 120); ?>...
                        </p>
                        <div class="forum-konu-meta">
                            <span>👤
                                <?php echo htmlspecialchars($k['kullanici_ad']); ?>
                            </span>
                            <span>📅
                                <?php echo date('d.m.Y', strtotime($k['tarih'])); ?>
                            </span>
                            <span>💬
                                <?php echo $k['yanit_sayisi']; ?> yanıt
                            </span>
                            <span>👁️
                                <?php echo $k['goruntulenme']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="dash-card" style="text-align:center; padding:50px;">
                <div style="font-size:3rem; margin-bottom:15px;">💬</div>
                <h3 style="color:var(--text-main); margin-bottom:8px;">Henüz konu yok</h3>
                <p style="color:var(--text-light); margin-bottom:20px;">İlk konuyu sen aç ve tartışmayı başlat!</p>
                <a href="index.php?sayfa=2400&forum=yeni" class="btn-canli" style="padding:10px 24px;">✏️ Konu Aç</a>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>