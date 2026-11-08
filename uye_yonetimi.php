<?php
// uye_yonetimi.php (ID: 5300) — Admin Üye Yönetim Sayfası
require_once 'db.php';

// Yetki Kontrolü
if(!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php?sayfa=1000");
    exit();
}

$mesaj = '';
$mesaj_tip = '';

// Hesap Silme
if (isset($_GET['sil_id'])) {
    $sil_id = (int)$_GET['sil_id'];
    
    // Admini silmeyi engelle
    $kontrol = $db->prepare("SELECT rol FROM kullanicilar WHERE id = ?");
    $kontrol->execute([$sil_id]);
    $hedef_rol = $kontrol->fetchColumn();
    
    if ($hedef_rol === 'admin') {
        $mesaj = "❌ Admin hesabı silinemez!";
        $mesaj_tip = 'hata';
    } else {
        // İlişkili verileri temizle
        $db->prepare("DELETE FROM yorumlar WHERE kullanici_id = ?")->execute([$sil_id]);
        $db->prepare("DELETE FROM favoriler WHERE kullanici_id = ?")->execute([$sil_id]);
        $db->prepare("DELETE FROM ajanda WHERE kullanici_id = ?")->execute([$sil_id]);
        $db->prepare("DELETE FROM jetonlar WHERE kullanici_id = ?")->execute([$sil_id]);
        $db->prepare("DELETE FROM takvim WHERE kullanici_id = ?")->execute([$sil_id]);
        $db->prepare("DELETE FROM talepler WHERE kullanici_id = ?")->execute([$sil_id]);
        $db->prepare("DELETE FROM forum_yanitlar WHERE kullanici_id = ?")->execute([$sil_id]);
        $db->prepare("DELETE FROM forum_konular WHERE kullanici_id = ?")->execute([$sil_id]);
        $db->prepare("DELETE FROM kullanicilar WHERE id = ?")->execute([$sil_id]);
        
        $mesaj = "✅ Kullanıcı başarıyla silindi.";
        $mesaj_tip = 'basari';
    }
}

// Rol Değiştirme
if (isset($_GET['rol_degistir']) && isset($_GET['yeni_rol'])) {
    $hedef_id = (int)$_GET['rol_degistir'];
    $yeni_rol = $_GET['yeni_rol'] === 'admin' ? 'admin' : 'user';
    
    // Kendi rolünü değiştirmeyi engelle
    if ($hedef_id == $_SESSION['user_id']) {
        $mesaj = "❌ Kendi rolünüzü değiştiremezsiniz!";
        $mesaj_tip = 'hata';
    } else {
        $guncelle = $db->prepare("UPDATE kullanicilar SET rol = ? WHERE id = ?");
        $guncelle->execute([$yeni_rol, $hedef_id]);
        $mesaj = "✅ Kullanıcı rolü '$yeni_rol' olarak güncellendi.";
        $mesaj_tip = 'basari';
    }
}

// Arama
$arama = $_GET['ara'] ?? '';
if ($arama) {
    $uyeler_stmt = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_ad LIKE ? OR eposta LIKE ? ORDER BY id DESC");
    $uyeler_stmt->execute(["%$arama%", "%$arama%"]);
} else {
    $uyeler_stmt = $db->query("SELECT * FROM kullanicilar ORDER BY id DESC");
}
$uyeler = $uyeler_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; flex-wrap:wrap; gap:15px;">
        <h2 style="color:var(--text-main);">👥 Üye Yönetimi</h2>
        
        <form action="index.php" method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="sayfa" value="5300">
            <input type="text" name="ara" placeholder="🔍 Kullanıcı ara..." value="<?php echo htmlspecialchars($arama); ?>"
                   style="width:220px; margin-bottom:0; padding:10px 18px; border-radius:50px;">
            <button type="submit" class="btn-sm">Ara</button>
        </form>
    </div>

    <?php if($mesaj): ?>
        <div class="mesaj <?php echo $mesaj_tip; ?>"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <div class="dash-card" style="padding:0; overflow:hidden;">
        <div class="uye-tablo-container">
            <table>
                <tr>
                    <th>Profil</th>
                    <th>Kullanıcı Adı</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>Aktiflik</th>
                    <th>Jeton</th>
                    <th>AI Skoru</th>
                    <th>İşlemler</th>
                </tr>
                <?php foreach($uyeler as $u): ?>
                    <tr>
                        <td>
                            <img src="uploads/<?php echo $u['profil_resmi'] ?? 'default.png'; ?>" class="uye-avatar" alt="avatar">
                        </td>
                        <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($u['kullanici_ad']); ?></strong></td>
                        <td style="color:var(--text-light); font-size:0.85rem;"><?php echo htmlspecialchars($u['eposta'] ?? '—'); ?></td>
                        <td>
                            <span class="badge <?php echo $u['rol']; ?>">
                                <?php echo $u['rol'] == 'admin' ? '🛡️ Admin' : '🌿 Üye'; ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight:600; color:var(--primary);">
                                <?php echo number_format($u['aktiflik_puani'] ?? 0, 1); ?>
                            </span>
                        </td>
                        <td>🍀 <?php echo $u['toplam_jeton'] ?? 0; ?></td>
                        <td>
                            <?php 
                            $ai = $u['ai_skor'] ?? 100;
                            $renk = $ai >= 70 ? '#10b981' : ($ai >= 40 ? '#f59e0b' : '#ef4444');
                            ?>
                            <span style="font-weight:600; color:<?php echo $renk; ?>;"><?php echo number_format($ai, 0); ?></span>
                            <div class="ai-skor-bar">
                                <div class="ai-skor-bar-fill" style="width:<?php echo $ai; ?>%; background:<?php echo $renk; ?>;"></div>
                            </div>
                        </td>
                        <td>
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <!-- Rol Değiştir -->
                                <?php if($u['rol'] == 'user'): ?>
                                    <a href="index.php?sayfa=5300&rol_degistir=<?php echo $u['id']; ?>&yeni_rol=admin" 
                                       class="btn-action approve" onclick="return confirm('Bu kullanıcıyı admin yapmak istediğinize emin misiniz?')">
                                        Admin Yap
                                    </a>
                                <?php else: ?>
                                    <a href="index.php?sayfa=5300&rol_degistir=<?php echo $u['id']; ?>&yeni_rol=user" 
                                       class="btn-action edit" onclick="return confirm('Admin yetkisini kaldırmak istediğinize emin misiniz?')">
                                        Üye Yap
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Sil -->
                                <a href="index.php?sayfa=5300&sil_id=<?php echo $u['id']; ?>" 
                                   class="btn-action reject" style="margin-left:5px;"
                                   onclick="return confirm('Bu kullanıcıyı ve tüm verilerini silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')">
                                    Sil
                                </a>
                            <?php else: ?>
                                <span style="color:var(--text-light); font-size:0.8rem;">Siz</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div style="margin-top:15px; text-align:center; color:var(--text-light); font-size:0.85rem;">
        Toplam <?php echo count($uyeler); ?> üye listeleniyor.
    </div>
</div>
