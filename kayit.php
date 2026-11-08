<?php
// kayit.php (ID: 120)
require_once 'db.php';

$mesaj = '';
$mesaj_tip = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_ad = htmlspecialchars($_POST['kullanici_ad']);
    $eposta = filter_var($_POST['eposta'], FILTER_SANITIZE_EMAIL);
    $sifre = password_hash($_POST['sifre'], PASSWORD_BCRYPT);

    // Şifre uzunluk kontrolü
    if (strlen($_POST['sifre']) < 6) {
        $mesaj = "❌ Şifre en az 6 karakter olmalıdır!";
        $mesaj_tip = 'hata';
    } else {
        try {
            $sorgu = $db->prepare("INSERT INTO kullanicilar (kullanici_ad, eposta, sifre, profil_resmi, rol, aktiflik_puani, toplam_jeton, ai_skor) VALUES (?, ?, ?, 'default.png', 'user', 0, 0, 100)");
            $sorgu->execute([$kullanici_ad, $eposta, $sifre]);
            $mesaj = "✅ Kayıt başarılı! Şimdi giriş yapabilirsin.";
            $mesaj_tip = 'basari';
        } catch (PDOException $e) {
            $mesaj = "❌ Kullanıcı adı veya e-posta zaten kayıtlı!";
            $mesaj_tip = 'hata';
        }
    }
}
?>

<div class="fade-in">
    <?php if($mesaj): ?>
        <div class="mesaj <?php echo $mesaj_tip; ?>" style="margin-bottom:20px;"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="auth-form" style="background: var(--bg-card); padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow-lg); max-width: 420px; margin: 0 auto; border: 1px solid var(--border);">
        
        <div style="text-align:center; margin-bottom:25px;">
            <div style="font-size:3rem; margin-bottom:10px;">🌿</div>
            <h2 style="color:var(--text-main); font-size:1.5rem;">Aramıza Katıl</h2>
            <p style="color:var(--text-light); font-size:0.9rem;">Bitki tutkunları topluluğuna hoş geldin!</p>
        </div>
        
        <input type="text" name="kullanici_ad" placeholder="👤 Kullanıcı Adı Seç" required 
               value="<?php echo $_POST['kullanici_ad'] ?? ''; ?>">
        <input type="email" name="eposta" placeholder="📧 E-posta Adresi" required
               value="<?php echo $_POST['eposta'] ?? ''; ?>">
        <input type="password" name="sifre" placeholder="🔒 Güçlü Bir Şifre (min. 6 karakter)" required>
        
        <button type="submit" class="btn-canli">Kayıt Ol</button>

        <div style="text-align: center; margin-top: 20px; border-top: 1px solid var(--border); padding-top: 20px;">
            <p style="color:var(--text-light); margin-bottom:10px; font-size:0.9rem;">Zaten hesabın var mı?</p>
            <a href="index.php?sayfa=110" class="btn-hayalet" style="width:100%; display:block; text-align:center;">
                Giriş Yap
            </a>
        </div>
    </form>
</div>
