<?php
// db.php - Veritabanı Bağlantısı (PDO)
$host = 'sql108.infinityfree.com';
$dbname = 'if0_42394005_florycon'; // Buraya az önce kurduğun veritabanı adını yaz
$username = 'if0_42394005';
$password = 'ayranlisoda'; // XAMPP kullanıyorsan genelde boştur

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Hata modunu aktifleştiriyoruz (Hocanın istediği hata yönetimi için)
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Bağlantı hatası bebiş: " . $e->getMessage());
}
?>