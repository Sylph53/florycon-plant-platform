<?php
// forum_begeni.php — Forum Beğeni AJAX Endpoint
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['basarili' => false, 'mesaj' => 'Giriş yapılmamış']);
    exit();
}

$user_id = $_SESSION['user_id'];
$yanit_id = isset($_POST['yanit_id']) ? (int)$_POST['yanit_id'] : 0;

if ($yanit_id <= 0) {
    echo json_encode(['basarili' => false, 'mesaj' => 'Geçersiz yanıt']);
    exit();
}

// Beğeni var mı kontrol
$kontrol = $db->prepare("SELECT id FROM forum_begeniler WHERE yanit_id = ? AND kullanici_id = ?");
$kontrol->execute([$yanit_id, $user_id]);

if ($kontrol->fetch()) {
    // Zaten beğenmiş — beğeniyi geri al
    $sil = $db->prepare("DELETE FROM forum_begeniler WHERE yanit_id = ? AND kullanici_id = ?");
    $sil->execute([$yanit_id, $user_id]);
    $durum = 'kaldirildi';
} else {
    // Beğen
    $ekle = $db->prepare("INSERT INTO forum_begeniler (yanit_id, kullanici_id) VALUES (?, ?)");
    $ekle->execute([$yanit_id, $user_id]);
    $durum = 'begenildi';
}

// Yeni toplam beğeni sayısı
$toplam = $db->prepare("SELECT COUNT(*) FROM forum_begeniler WHERE yanit_id = ?");
$toplam->execute([$yanit_id]);
$toplam_begeni = $toplam->fetchColumn();

echo json_encode([
    'basarili' => true,
    'durum' => $durum,
    'toplam_begeni' => $toplam_begeni
]);
?>
