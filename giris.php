<?php
// giris.php (ID: 110)
require_once 'db.php';

// Kusursuz Hafıza — Nereden geldiğini yakala
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_SERVER['HTTP_REFERER'])) {
    $gelinen_yer = $_SERVER['HTTP_REFERER'];
    if (strpos($gelinen_yer, 'sayfa=110') === false && strpos($gelinen_yer, 'sayfa=120') === false) {
        $_SESSION['geri_donus_url'] = $gelinen_yer;
    }
}

$mesaj = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_ad = $_POST['kullanici_ad'];
    $sifre = $_POST['sifre'];

    $sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_ad = ?");
    $sorgu->execute([$kullanici_ad]);
    $user = $sorgu->fetch();

    if ($user && password_verify($sifre, $user['sifre'])) {
        // SESSION YÖNETİMİ
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['kullanici_ad'] = $user['kullanici_ad'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['profil_resmi'] = $user['profil_resmi'] ?? 'default.png';
        $_SESSION['toplam_jeton'] = $user['toplam_jeton'] ?? 0;
        $_SESSION['aktiflik_puani'] = $user['aktiflik_puani'] ?? 0;

        // Yönlendirme
        $hedef = $_SESSION['son_sayfa'] ?? 'index.php?sayfa=2300';
        unset($_SESSION['son_sayfa']);
        
        header("Location: " . $hedef);
        exit();

    } else {
        $mesaj = "❌ Hatalı kullanıcı adı veya şifre!";
    }
}
?>

<div class="fade-in">
    <?php if($mesaj): ?>
        <div class="mesaj hata" style="margin-bottom:20px;"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="auth-form" style="background: var(--bg-card); padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow-lg); max-width: 420px; margin: 0 auto; border: 1px solid var(--border);">
        
        <div style="text-align:center; margin-bottom:25px;">
            <div style="font-size:3rem; margin-bottom:10px;">🌱</div>
            <h2 style="color:var(--text-main); font-size:1.5rem;">Hoş Geldin</h2>
            <p style="color:var(--text-light); font-size:0.9rem;">Hesabına giriş yap ve topluluğa katıl!</p>
        </div>
        
        <input type="text" name="kullanici_ad" placeholder="👤 Kullanıcı Adı" required
               value="<?php echo $_POST['kullanici_ad'] ?? ''; ?>">
        <input type="password" name="sifre" placeholder="🔒 Şifre" required>
        
        <button type="submit" class="btn-canli">Giriş Yap</button>

        <div style="text-align: center; margin-top: 20px; border-top: 1px solid var(--border); padding-top: 20px;">
            <p style="color:var(--text-light); margin-bottom:10px; font-size:0.9rem;">Hesabın yok mu?</p>
            <a href="index.php?sayfa=120" class="btn-hayalet" style="width:100%; display:block; text-align:center;">
                Hemen Kayıt Ol
            </a>
        </div>
    </form>
</div>