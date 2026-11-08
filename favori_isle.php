<?php
// favori_isle.php — Favori Toggle Endpoint
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php?sayfa=110");
    exit();
}

$user_id = $_SESSION['user_id'];
$bitki_id = isset($_GET['bitki_id']) ? (int)$_GET['bitki_id'] : 0;
$donus = isset($_GET['donus']) ? $_GET['donus'] : 'index.php?sayfa=1100&id=' . $bitki_id;

if ($bitki_id <= 0) {
    header("Location: index.php?sayfa=2300");
    exit();
}

// Favori var mi kontrol
$kontrol = $db->prepare("SELECT id FROM favoriler WHERE kullanici_id = ? AND bitki_id = ?");
$kontrol->execute([$user_id, $bitki_id]);

if ($kontrol->fetch()) {
    // Zaten favoride — cikar
    $sil = $db->prepare("DELETE FROM favoriler WHERE kullanici_id = ? AND bitki_id = ?");
    $sil->execute([$user_id, $bitki_id]);
} else {
    // Favori degil — ekle
    $ekle = $db->prepare("INSERT INTO favoriler (kullanici_id, bitki_id) VALUES (?, ?)");
    $ekle->execute([$user_id, $bitki_id]);
}

header("Location: " . $donus);
exit();
?>
