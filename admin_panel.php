<?php
// admin_panel.php (ID: 5000) — Dinamik Veri + Grafikler
require_once 'db.php';

// Yetki Kontrolü
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php?sayfa=1000");
    exit();
}

// === İSTATİSTİKLER ===
$toplam_bitki = $db->query("SELECT COUNT(*) FROM bitkiler")->fetchColumn();
$bekleyen_talep = $db->query("SELECT COUNT(*) FROM talepler WHERE durum = 'beklemede'")->fetchColumn();
$toplam_yorum = $db->query("SELECT COUNT(*) FROM yorumlar")->fetchColumn();
$toplam_uye = $db->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn();
$toplam_forum_konu = $db->query("SELECT COUNT(*) FROM forum_konular")->fetchColumn();
$toplam_forum_yanit = $db->query("SELECT COUNT(*) FROM forum_yanitlar")->fetchColumn();

// Ortalama AI Skoru
$ort_ai = $db->query("SELECT COALESCE(AVG(ai_skor), 100) FROM kullanicilar")->fetchColumn();

// Düşük AI skorlu kullanıcı sayısı (50 altı)
$dusuk_ai = $db->query("SELECT COUNT(*) FROM kullanicilar WHERE ai_skor < 50")->fetchColumn();

// === SON 7 GÜN BİTKİ EKLEME VERİSİ ===
$haftalik_veri = [];
$haftalik_etiketler = [];
for ($i = 6; $i >= 0; $i--) {
    $tarih = date('Y-m-d', strtotime("-$i days"));
    $gun_adi = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    $gun_no = date('w', strtotime("-$i days"));
    $haftalik_etiketler[] = $gun_adi[$gun_no];

    $stmt = $db->prepare("SELECT COUNT(*) FROM bitkiler WHERE DATE(olusturma_tarihi) = ?");
    $stmt->execute([$tarih]);
    $sayi = $stmt->fetchColumn();
    // Eğer olusturma_tarihi yoksa 0 al (eski kayıtlar)
    $haftalik_veri[] = (int)$sayi;
}

// === SON 7 GÜN YORUM VERİSİ ===
$yorum_haftalik = [];
for ($i = 6; $i >= 0; $i--) {
    $tarih = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) FROM yorumlar WHERE DATE(tarih) = ?");
    $stmt->execute([$tarih]);
    $yorum_haftalik[] = (int)$stmt->fetchColumn();
}

// === SON 7 GÜN ÜYE KAYIT VERİSİ ===
$uye_haftalik = [];
for ($i = 6; $i >= 0; $i--) {
    $tarih = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) FROM kullanicilar WHERE DATE(kayit_tarihi) = ?");
    $stmt->execute([$tarih]);
    $uye_haftalik[] = (int)$stmt->fetchColumn();
}

// === SON AKTİVİTELER ===
$son_yorumlar = $db->query("SELECT y.yorum_metni, y.tarih, k.kullanici_ad, b.ad as bitki_ad
                             FROM yorumlar y
                             JOIN kullanicilar k ON y.kullanici_id = k.id
                             JOIN bitkiler b ON y.bitki_id = b.id
                             ORDER BY y.id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// === EN AKTİF KULLANICILAR ===
$aktif_uyeler = $db->query("SELECT kullanici_ad, aktiflik_puani, toplam_jeton, ai_skor, profil_resmi
                             FROM kullanicilar
                             ORDER BY aktiflik_puani DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// === DÜŞÜK AI SKORLU KULLANICILAR ===
$riskli_uyeler = $db->query("SELECT id, kullanici_ad, ai_skor, profil_resmi
                              FROM kullanicilar
                              WHERE ai_skor < 70
                              ORDER BY ai_skor ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-dashboard fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap:wrap; gap:15px;">
        <h2 style="color:var(--text-main);">🛡️ Yönetici Paneli — Genel Özet</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="index.php?sayfa=5100" class="btn-sm" style="background:#f59e0b;">📩 Talepler</a>
            <a href="index.php?sayfa=5200" class="btn-sm" style="background:#3b82f6;">🌿 İçerik</a>
            <a href="index.php?sayfa=5300" class="btn-sm" style="background:#8b5cf6;">👥 Üyeler</a>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="stat-grid">
        <div class="stat-card blue">
            <div class="stat-icon">🌿</div>
            <h4>Toplam Bitki</h4>
            <div class="stat-number"><?php echo $toplam_bitki; ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">👥</div>
            <h4>Toplam Üye</h4>
            <div class="stat-number"><?php echo $toplam_uye; ?></div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon">📩</div>
            <h4>Bekleyen Talep</h4>
            <div class="stat-number"><?php echo $bekleyen_talep; ?></div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon">💬</div>
            <h4>Toplam Yorum</h4>
            <div class="stat-number"><?php echo $toplam_yorum; ?></div>
        </div>
        <div class="stat-card blue" style="background:linear-gradient(135deg, #06b6d4, #0891b2);">
            <div class="stat-icon">📋</div>
            <h4>Forum Konuları</h4>
            <div class="stat-number"><?php echo $toplam_forum_konu; ?></div>
        </div>
        <div class="stat-card <?php echo $ort_ai >= 70 ? 'green' : ($ort_ai >= 40 ? 'yellow' : 'red'); ?>">
            <div class="stat-icon">🤖</div>
            <h4>Ort. AI Skoru</h4>
            <div class="stat-number"><?php echo number_format($ort_ai, 0); ?></div>
        </div>
    </div>

    <!-- Grafikler -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:25px;">
        <div class="dash-card">
            <h3 style="color:var(--text-main); margin-bottom:15px;">📊 Haftalık Bitki Ekleme</h3>
            <canvas id="bitkiChart" height="200"></canvas>
        </div>
        <div class="dash-card">
            <h3 style="color:var(--text-main); margin-bottom:15px;">📈 Haftalık Yorum & Üye Trendi</h3>
            <canvas id="trendChart" height="200"></canvas>
        </div>
    </div>

    <!-- Alt Bölüm: Tablolar -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:25px;">

        <!-- En Aktif Kullanıcılar -->
        <div class="dash-card" style="padding:0; overflow:hidden;">
            <h3 style="padding:20px 20px 0; color:var(--text-main);">🏆 En Aktif Üyeler</h3>
            <div class="uye-tablo-container">
                <table>
                    <tr>
                        <th>Profil</th>
                        <th>Kullanıcı</th>
                        <th>Puan</th>
                        <th>Jeton</th>
                        <th>AI</th>
                    </tr>
                    <?php foreach($aktif_uyeler as $u): ?>
                        <tr>
                            <td><img src="uploads/<?php echo $u['profil_resmi'] ?? 'default.png'; ?>" class="uye-avatar" alt="avatar"></td>
                            <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($u['kullanici_ad']); ?></strong></td>
                            <td><span style="color:var(--primary); font-weight:600;"><?php echo number_format($u['aktiflik_puani'] ?? 0, 1); ?></span></td>
                            <td>🍀 <?php echo $u['toplam_jeton'] ?? 0; ?></td>
                            <td>
                                <?php
                                $ai = $u['ai_skor'] ?? 100;
                                $renk = $ai >= 70 ? '#10b981' : ($ai >= 40 ? '#f59e0b' : '#ef4444');
                                ?>
                                <span style="font-weight:600; color:<?php echo $renk; ?>;"><?php echo number_format($ai, 0); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Riskli Kullanıcılar (Düşük AI Skoru) -->
        <div class="dash-card" style="padding:0; overflow:hidden;">
            <h3 style="padding:20px 20px 0; color:var(--danger);">⚠️ Riskli Üyeler (Düşük AI Skoru)</h3>
            <div class="uye-tablo-container">
                <?php if(count($riskli_uyeler) > 0): ?>
                    <table>
                        <tr>
                            <th>Profil</th>
                            <th>Kullanıcı</th>
                            <th>AI Skoru</th>
                            <th>İşlem</th>
                        </tr>
                        <?php foreach($riskli_uyeler as $r):
                            $ai = $r['ai_skor'] ?? 100;
                            $renk = $ai >= 40 ? '#f59e0b' : '#ef4444';
                        ?>
                            <tr>
                                <td><img src="uploads/<?php echo $r['profil_resmi'] ?? 'default.png'; ?>" class="uye-avatar" alt="avatar"></td>
                                <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($r['kullanici_ad']); ?></strong></td>
                                <td>
                                    <span style="font-weight:600; color:<?php echo $renk; ?>;"><?php echo number_format($ai, 0); ?></span>
                                    <div class="ai-skor-bar">
                                        <div class="ai-skor-bar-fill" style="width:<?php echo $ai; ?>%; background:<?php echo $renk; ?>;"></div>
                                    </div>
                                </td>
                                <td>
                                    <a href="index.php?sayfa=5300" class="btn-action edit">Yönet</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <div style="text-align:center; padding:40px; color:var(--text-light);">
                        <div style="font-size:2rem; margin-bottom:10px;">✅</div>
                        <p>Riskli kullanıcı bulunmuyor.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Son Aktiviteler -->
    <div class="dash-card">
        <h3 style="color:var(--text-main); margin-bottom:15px;">🕐 Son Yorumlar</h3>
        <?php if(count($son_yorumlar) > 0): ?>
            <?php foreach($son_yorumlar as $sy): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid var(--border);">
                    <div>
                        <strong style="color:var(--primary-dark);"><?php echo htmlspecialchars($sy['kullanici_ad']); ?></strong>
                        <span style="color:var(--text-light); font-size:0.85rem;"> → <?php echo htmlspecialchars($sy['bitki_ad']); ?></span>
                        <p style="color:var(--text-light); font-size:0.85rem; margin-top:3px;"><?php echo mb_substr(htmlspecialchars($sy['yorum_metni']), 0, 80); ?>...</p>
                    </div>
                    <?php if(isset($sy['tarih'])): ?>
                        <span style="font-size:0.75rem; color:var(--text-light); white-space:nowrap;"><?php echo date('d.m H:i', strtotime($sy['tarih'])); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:var(--text-light); text-align:center; padding:20px;">Henüz yorum yok.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js Grafikleri — Dinamik Veri -->
<script>
// Bitki Ekleme Grafiği
var bitkiCtx = document.getElementById('bitkiChart').getContext('2d');
new Chart(bitkiCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($haftalik_etiketler); ?>,
        datasets: [{
            label: 'Eklenen Bitki',
            data: <?php echo json_encode($haftalik_veri); ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.6)',
            borderColor: '#10b981',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: 'var(--text-light)' },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                ticks: { color: 'var(--text-light)' },
                grid: { display: false }
            }
        }
    }
});

// Yorum & Üye Trend Grafiği
var trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($haftalik_etiketler); ?>,
        datasets: [
            {
                label: 'Yorum',
                data: <?php echo json_encode($yorum_haftalik); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#3b82f6'
            },
            {
                label: 'Yeni Üye',
                data: <?php echo json_encode($uye_haftalik); ?>,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#8b5cf6'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: 'var(--text-light)' },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                ticks: { color: 'var(--text-light)' },
                grid: { display: false }
            }
        }
    }
});
</script>

<style>
/* Admin Grid Responsive */
@media (max-width: 768px) {
    .admin-dashboard [style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>