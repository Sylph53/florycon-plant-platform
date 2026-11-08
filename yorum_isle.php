<?php
// yorum_isle.php — Yorum Beğeni / Düzenleme / Silme AJAX Endpoint
session_start();
require_once 'db.php';
require_once 'ai_filtre.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['basarili' => false, 'mesaj' => 'Giriş yapılmamış']);
    exit();
}

$user_id = $_SESSION['user_id'];
$rol = $_SESSION['rol'] ?? 'user';
$islem = $_POST['islem'] ?? '';
$yorum_id = isset($_POST['yorum_id']) ? (int)$_POST['yorum_id'] : 0;

if ($yorum_id <= 0 && $islem !== 'begeni') {
    echo json_encode(['basarili' => false, 'mesaj' => 'Geçersiz yorum ID']);
    exit();
}

// =====================================================================
// 1. BEĞENİ İŞLEMİ
// =====================================================================
if ($islem === 'begeni') {
    $yorum_id = isset($_POST['yorum_id']) ? (int)$_POST['yorum_id'] : 0;
    if ($yorum_id <= 0) {
        echo json_encode(['basarili' => false, 'mesaj' => 'Geçersiz yorum']);
        exit();
    }

    // Beğeni var mı kontrol
    $kontrol = $db->prepare("SELECT id FROM yorum_begeniler WHERE yorum_id = ? AND kullanici_id = ?");
    $kontrol->execute([$yorum_id, $user_id]);

    if ($kontrol->fetch()) {
        // Beğeniyi geri al
        $sil = $db->prepare("DELETE FROM yorum_begeniler WHERE yorum_id = ? AND kullanici_id = ?");
        $sil->execute([$yorum_id, $user_id]);
        $durum = 'kaldirildi';
    } else {
        // Beğen
        $ekle = $db->prepare("INSERT INTO yorum_begeniler (yorum_id, kullanici_id) VALUES (?, ?)");
        $ekle->execute([$yorum_id, $user_id]);
        $durum = 'begenildi';
    }

    // Toplam beğeni sayısı
    $toplam = $db->prepare("SELECT COUNT(*) FROM yorum_begeniler WHERE yorum_id = ?");
    $toplam->execute([$yorum_id]);
    $toplam_begeni = $toplam->fetchColumn();

    echo json_encode([
        'basarili' => true,
        'durum' => $durum,
        'toplam_begeni' => $toplam_begeni
    ]);
    exit();
}

// =====================================================================
// 2. YORUM SİLME
// =====================================================================
if ($islem === 'sil') {
    // Yorumun sahibini kontrol et
    $yorum_stmt = $db->prepare("SELECT kullanici_id FROM yorumlar WHERE id = ?");
    $yorum_stmt->execute([$yorum_id]);
    $yorum = $yorum_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$yorum) {
        echo json_encode(['basarili' => false, 'mesaj' => 'Yorum bulunamadı']);
        exit();
    }

    // Yetki kontrolü: Kendi yorumu veya admin
    if ($yorum['kullanici_id'] != $user_id && $rol !== 'admin') {
        echo json_encode(['basarili' => false, 'mesaj' => 'Bu yorumu silme yetkiniz yok']);
        exit();
    }

    // Önce beğenileri sil
    $db->prepare("DELETE FROM yorum_begeniler WHERE yorum_id = ?")->execute([$yorum_id]);
    // Yorumu sil
    $sil = $db->prepare("DELETE FROM yorumlar WHERE id = ?");
    $sil->execute([$yorum_id]);

    echo json_encode(['basarili' => true, 'mesaj' => 'Yorum silindi']);
    exit();
}

// =====================================================================
// 3. YORUM DÜZENLEME
// =====================================================================
if ($islem === 'duzenle') {
    $yeni_metin = trim($_POST['yeni_metin'] ?? '');

    if (empty($yeni_metin)) {
        echo json_encode(['basarili' => false, 'mesaj' => 'Yorum boş olamaz']);
        exit();
    }

    // Yorumun sahibini kontrol et
    $yorum_stmt = $db->prepare("SELECT kullanici_id FROM yorumlar WHERE id = ?");
    $yorum_stmt->execute([$yorum_id]);
    $yorum = $yorum_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$yorum) {
        echo json_encode(['basarili' => false, 'mesaj' => 'Yorum bulunamadı']);
        exit();
    }

    // Yetki kontrolü: Kendi yorumu veya admin
    if ($yorum['kullanici_id'] != $user_id && $rol !== 'admin') {
        echo json_encode(['basarili' => false, 'mesaj' => 'Bu yorumu düzenleme yetkiniz yok']);
        exit();
    }

    // AI Filtre kontrolü
    $filtre = icerik_filtrele($yeni_metin);
    if (!$filtre['temiz']) {
        kullanici_ai_skor_guncelle($db, $user_id, $filtre['agirlik_toplam']);
        echo json_encode(['basarili' => false, 'mesaj' => '⚠️ Düzenlenen içerikte uygunsuz ifadeler tespit edildi.']);
        exit();
    }

    $yeni_metin_guvenli = htmlspecialchars($yeni_metin);

    // Güncelle
    $guncelle = $db->prepare("UPDATE yorumlar SET yorum_metni = ?, ai_skor = ? WHERE id = ?");
    $guncelle->execute([$yeni_metin_guvenli, $filtre['skor'], $yorum_id]);

    echo json_encode([
        'basarili' => true,
        'mesaj' => 'Yorum güncellendi',
        'yeni_metin' => $yeni_metin_guvenli
    ]);
    exit();
}

echo json_encode(['basarili' => false, 'mesaj' => 'Geçersiz işlem']);
?>
