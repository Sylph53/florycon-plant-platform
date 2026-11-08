<?php
// forum_yanit_sil.php — Forum Yanıt Silme AJAX Endpoint
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['basarili' => false, 'mesaj' => 'Giriş yapılmamış']);
    exit();
}

$user_id = $_SESSION['user_id'];
$rol = $_SESSION['rol'] ?? 'user';
$yanit_id = isset($_POST['yanit_id']) ? (int)$_POST['yanit_id'] : 0;

if ($yanit_id <= 0) {
    echo json_encode(['basarili' => false, 'mesaj' => 'Geçersiz yanıt ID']);
    exit();
}

// Yanıtın sahibini kontrol et
$yanit_stmt = $db->prepare("SELECT kullanici_id, konu_id FROM forum_yanitlar WHERE id = ?");
$yanit_stmt->execute([$yanit_id]);
$yanit = $yanit_stmt->fetch(PDO::FETCH_ASSOC);

if (!$yanit) {
    echo json_encode(['basarili' => false, 'mesaj' => 'Yanıt bulunamadı']);
    exit();
}

// Yetki kontrolü: Kendi yanıtı veya admin
if ($yanit['kullanici_id'] != $user_id && $rol !== 'admin') {
    echo json_encode(['basarili' => false, 'mesaj' => 'Bu yanıtı silme yetkiniz yok']);
    exit();
}

// Önce beğenileri sil
$db->prepare("DELETE FROM forum_begeniler WHERE yanit_id = ?")->execute([$yanit_id]);

// Bu yanıta yapılan alıntıları temizle (alıntı referansını null yap)
$db->prepare("UPDATE forum_yanitlar SET alinti_yanit_id = NULL WHERE alinti_yanit_id = ?")->execute([$yanit_id]);

// Yanıtı sil
$sil = $db->prepare("DELETE FROM forum_yanitlar WHERE id = ?");
$sil->execute([$yanit_id]);

echo json_encode([
    'basarili' => true,
    'mesaj' => 'Yanıt silindi',
    'konu_id' => $yanit['konu_id']
]);
?>
