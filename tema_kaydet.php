<?php
// tema_kaydet.php — Tema tercihini session'a kaydet
session_start();
$tema = $_GET['tema'] ?? 'default';
$gecerli_temalar = ['default', 'dark', 'sakura'];

if (in_array($tema, $gecerli_temalar)) {
    $_SESSION['tema'] = $tema;
    echo json_encode(['basarili' => true, 'tema' => $tema]);
} else {
    echo json_encode(['basarili' => false]);
}
?>
