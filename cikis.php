<?php
// cikis.php — Güvenli Çıkış (Veri kaydetme dahil)
session_start();
require_once 'db.php';
require_once 'ai_filtre.php';

// Çıkış öncesi verileri kaydet
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Aktiflik puanını güncelle ve kaydet
    aktiflik_puani_guncelle($db, $user_id);

    // Toplam jeton sayısını güncelle
    $jeton_stmt = $db->prepare("SELECT COALESCE(SUM(miktar), 0) as toplam FROM jetonlar WHERE kullanici_id = ?");
    $jeton_stmt->execute([$user_id]);
    $toplam_jeton = $jeton_stmt->fetch(PDO::FETCH_ASSOC)['toplam'];

    $jeton_guncelle = $db->prepare("UPDATE kullanicilar SET toplam_jeton = ? WHERE id = ?");
    $jeton_guncelle->execute([$toplam_jeton, $user_id]);
}

// Session'ı yok et
session_destroy();
header("Location: index.php?sayfa=1000");
exit();
?>