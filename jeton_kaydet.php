<?php
// jeton_kaydet.php — Jeton kazanım API endpoint (Zamansız Sistem)
session_start();
require_once 'db.php';
require_once 'ai_filtre.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['basarili' => false, 'mesaj' => 'Giris yapilmamis']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Flood protection: Son 3 saniyede jeton kazanmış mı? (eskisi 20s idi, artık 3s)
//$son_jeton = $db->prepare("SELECT kazanilma_tarihi FROM jetonlar WHERE kullanici_id = ? ORDER BY kazanilma_tarihi DESC LIMIT 1");
//$son_jeton->execute([$user_id]);
//$son = $son_jeton->fetch(PDO::FETCH_ASSOC);

//if ($son) {
// $son_zaman = strtotime($son['kazanilma_tarihi']);
//if ($fark < 3) {
//    echo json_encode(['basarili' => false, 'mesaj' => 'Cok hizli! Biraz bekle.']);
//    exit();
//}
//}

// Jeton ekle
$ekle = $db->prepare("INSERT INTO jetonlar (kullanici_id, miktar) VALUES (?, 1)");
$ekle->execute([$user_id]);

// Toplam jetonu hesapla
$toplam = $db->prepare("SELECT COALESCE(SUM(miktar), 0) as toplam FROM jetonlar WHERE kullanici_id = ?");
$toplam->execute([$user_id]);
$toplam_jeton = $toplam->fetch(PDO::FETCH_ASSOC)['toplam'];

// Kullaniciyi guncelle
$guncelle = $db->prepare("UPDATE kullanicilar SET toplam_jeton = ? WHERE id = ?");
$guncelle->execute([$toplam_jeton, $user_id]);

// Aktiflik puanını da güncelle
$aktiflik_puani = aktiflik_puani_guncelle($db, $user_id);

$_SESSION['toplam_jeton'] = $toplam_jeton;
$_SESSION['aktiflik_puani'] = $aktiflik_puani;

echo json_encode([
    'basarili' => true,
    'toplam_jeton' => $toplam_jeton,
    'aktiflik_puani' => $aktiflik_puani,
    'mesaj' => '+1'
]);
?>