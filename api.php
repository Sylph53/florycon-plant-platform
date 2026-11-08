<?php
// ================================================================
// FloryCon REST API v2.0 — Tam CRUD, Güvenli, Prodüksiyona Hazır
// Mobil (React) ve Web aynı veritabanını paylaşır.
// ================================================================

// ─── KONFİGÜRASYON ─────────────────────────────────────────────
$host       = 'sql108.infinityfree.com';
$dbname     = 'if0_42394005_florycon';
$user       = 'if0_42394005';
$pass       = 'ayranlisoda';
$base_url   = 'https://florycon.freedev.app/';
$upload_dir = __DIR__ . '/uploads/';
$placeholder_img = 'https://placehold.co/400x300/E8F5E9/2D7D46?text=FloryCon';
$max_upload_size = 5 * 1024 * 1024; // 5MB
$allowed_mime    = ['image/jpeg', 'image/png', 'image/webp'];

// ─── CORS ───────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ─── YARDIMCI FONKSİYONLAR ─────────────────────────────────────
function respond($status, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'status'  => $status,
        'data'    => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function sanitize($val) {
    return htmlspecialchars(trim($val ?? ''), ENT_QUOTES, 'UTF-8');
}

function getPostData() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($ct, 'application/json')) {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }
    return $_POST;
}

function addImageUrl($row, $base_url, $placeholder) {
    if (!empty($row['resim'])) {
        $row['resim_url'] = $base_url . 'uploads/' . $row['resim'];
    } else {
        $row['resim_url'] = $placeholder;
    }
    return $row;
}

// ─── VERİTABANI BAĞLANTISI ─────────────────────────────────────
try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    respond('error', null, 'Veritabanı bağlantı hatası: ' . $e->getMessage(), 500);
}

// ─── İSTEK YÖNLENDİRME ─────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'bitkiler';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    // ================================================================
    // GET ENDPOINTS
    // ================================================================
    if ($method === 'GET') {
        switch ($action) {

            // ── TÜM BİTKİLER / TEK BİTKİ / ARAMA ──────────────
            case 'bitkiler':
            default:
                if ($id) {
                    // Tek bitki detay
                    $stmt = $db->prepare("SELECT * FROM bitkiler WHERE id = ?");
                    $stmt->execute([$id]);
                    $bitki = $stmt->fetch();
                    if (!$bitki) respond('error', null, 'Bitki bulunamadı', 404);

                    $bitki = addImageUrl($bitki, $base_url, $placeholder_img);

                    // Yorum & favori sayıları
                    $ys = $db->prepare("SELECT COUNT(*) FROM yorumlar WHERE bitki_id = ?");
                    $ys->execute([$id]);
                    $bitki['yorum_sayisi'] = (int)$ys->fetchColumn();

                    $fs = $db->prepare("SELECT COUNT(*) FROM favoriler WHERE bitki_id = ?");
                    $fs->execute([$id]);
                    $bitki['favori_sayisi'] = (int)$fs->fetchColumn();

                    respond('success', $bitki);
                }

                // Sayfalandırma
                $page  = max(1, (int)($_GET['page'] ?? 1));
                $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
                $offset = ($page - 1) * $limit;

                $arama    = sanitize($_GET['q'] ?? '');
                $kategori = sanitize($_GET['kategori'] ?? '');

                $where = [];
                $params = [];

                if ($arama) {
                    $where[] = "(ad LIKE ? OR aciklama LIKE ?)";
                    $params[] = "%$arama%";
                    $params[] = "%$arama%";
                }
                if ($kategori) {
                    $where[] = "kategori = ?";
                    $params[] = $kategori;
                }

                $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

                // Toplam sayı
                $countStmt = $db->prepare("SELECT COUNT(*) FROM bitkiler $whereSQL");
                $countStmt->execute($params);
                $total = (int)$countStmt->fetchColumn();

                // Veri
                $dataStmt = $db->prepare("SELECT b.*, 
                    (SELECT COUNT(*) FROM favoriler WHERE bitki_id = b.id) as favori_sayisi,
                    (SELECT COUNT(*) FROM yorumlar WHERE bitki_id = b.id) as yorum_sayisi
                    FROM bitkiler b $whereSQL ORDER BY b.id DESC LIMIT $limit OFFSET $offset");
                $dataStmt->execute($params);
                $bitkiler = $dataStmt->fetchAll();

                foreach ($bitkiler as &$b) {
                    $b = addImageUrl($b, $base_url, $placeholder_img);
                }

                respond('success', [
                    'items'      => $bitkiler,
                    'toplam'     => $total,
                    'sayfa'      => $page,
                    'limit'      => $limit,
                    'toplam_sayfa' => ceil($total / $limit)
                ]);
                break;

            // ── İSTATİSTİKLER ───────────────────────────────────
            case 'stats':
                $stats = [
                    'toplam_bitki'      => (int)$db->query("SELECT COUNT(*) FROM bitkiler")->fetchColumn(),
                    'toplam_kullanici'  => (int)$db->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn(),
                    'toplam_yorum'      => (int)$db->query("SELECT COUNT(*) FROM yorumlar")->fetchColumn(),
                    'toplam_forum_konu' => (int)$db->query("SELECT COUNT(*) FROM forum_konular")->fetchColumn(),
                    'toplam_favori'     => (int)$db->query("SELECT COUNT(*) FROM favoriler")->fetchColumn(),
                ];
                respond('success', $stats);
                break;

            // ── TREND BİTKİLER ──────────────────────────────────
            case 'trend':
                $limit = min(20, max(1, (int)($_GET['limit'] ?? 5)));
                $stmt = $db->prepare("SELECT b.*, 
                    (SELECT COUNT(*) FROM favoriler WHERE bitki_id = b.id) as favori_sayisi,
                    (SELECT COUNT(*) FROM yorumlar WHERE bitki_id = b.id) as yorum_sayisi
                    FROM bitkiler b ORDER BY b.id DESC LIMIT ?");
                $stmt->execute([$limit]);
                $bitkiler = $stmt->fetchAll();

                foreach ($bitkiler as &$b) {
                    $b = addImageUrl($b, $base_url, $placeholder_img);
                }
                respond('success', $bitkiler);
                break;

            // ── KATEGORİLER ─────────────────────────────────────
            case 'categories':
                $cats = $db->query("SELECT DISTINCT kategori FROM bitkiler WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);
                respond('success', $cats);
                break;

            // ── YORUMLAR (bitki_id ile) ─────────────────────────
            case 'comments':
                $bitki_id = (int)($_GET['bitki_id'] ?? 0);
                if (!$bitki_id) respond('error', null, 'bitki_id gerekli', 400);

                $stmt = $db->prepare("SELECT y.*, k.kullanici_adi, k.ad_soyad,
                    (SELECT COUNT(*) FROM yorum_begeniler WHERE yorum_id = y.id) as begeni_sayisi
                    FROM yorumlar y 
                    LEFT JOIN kullanicilar k ON y.kullanici_id = k.id
                    WHERE y.bitki_id = ? AND y.gizli = 0
                    ORDER BY y.tarih DESC");
                $stmt->execute([$bitki_id]);
                respond('success', $stmt->fetchAll());
                break;

            // ── FORUM KONULARI ──────────────────────────────────
            case 'forum_topics':
                $page  = max(1, (int)($_GET['page'] ?? 1));
                $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
                $offset = ($page - 1) * $limit;

                $total = (int)$db->query("SELECT COUNT(*) FROM forum_konular")->fetchColumn();

                $stmt = $db->prepare("SELECT fk.*, k.kullanici_adi, k.ad_soyad,
                    (SELECT COUNT(*) FROM forum_yanitlar WHERE konu_id = fk.id) as yanit_sayisi
                    FROM forum_konular fk
                    LEFT JOIN kullanicilar k ON fk.kullanici_id = k.id
                    ORDER BY fk.tarih DESC LIMIT $limit OFFSET $offset");
                $stmt->execute();

                respond('success', [
                    'items' => $stmt->fetchAll(),
                    'toplam' => $total,
                    'sayfa' => $page,
                    'toplam_sayfa' => ceil($total / $limit)
                ]);
                break;

            // ── FORUM DETAY + YANITLAR ──────────────────────────
            case 'forum_detail':
                $konu_id = (int)($_GET['id'] ?? 0);
                if (!$konu_id) respond('error', null, 'id gerekli', 400);

                // Görüntülenme artır
                $db->prepare("UPDATE forum_konular SET goruntulenme = goruntulenme + 1 WHERE id = ?")->execute([$konu_id]);

                // Konu
                $stmt = $db->prepare("SELECT fk.*, k.kullanici_adi, k.ad_soyad
                    FROM forum_konular fk
                    LEFT JOIN kullanicilar k ON fk.kullanici_id = k.id
                    WHERE fk.id = ?");
                $stmt->execute([$konu_id]);
                $konu = $stmt->fetch();
                if (!$konu) respond('error', null, 'Konu bulunamadı', 404);

                // Yanıtlar
                $yanitStmt = $db->prepare("SELECT fy.*, k.kullanici_adi, k.ad_soyad,
                    (SELECT COUNT(*) FROM forum_begeniler WHERE yanit_id = fy.id) as begeni_sayisi,
                    (SELECT fy2.icerik FROM forum_yanitlar fy2 WHERE fy2.id = fy.alinti_yanit_id LIMIT 1) as alinti_icerik,
                    (SELECT k2.kullanici_adi FROM forum_yanitlar fy3 LEFT JOIN kullanicilar k2 ON fy3.kullanici_id = k2.id WHERE fy3.id = fy.alinti_yanit_id LIMIT 1) as alinti_yazar
                    FROM forum_yanitlar fy
                    LEFT JOIN kullanicilar k ON fy.kullanici_id = k.id
                    WHERE fy.konu_id = ?
                    ORDER BY fy.tarih ASC");
                $yanitStmt->execute([$konu_id]);

                respond('success', [
                    'konu'    => $konu,
                    'yanitlar' => $yanitStmt->fetchAll()
                ]);
                break;

            // ── KULLANICI FAVORİLERİ ────────────────────────────
            case 'favorites':
                $user_id = (int)($_GET['user_id'] ?? 0);
                if (!$user_id) respond('error', null, 'user_id gerekli', 400);

                $stmt = $db->prepare("SELECT b.*, f.tarih as favori_tarih,
                    (SELECT COUNT(*) FROM yorumlar WHERE bitki_id = b.id) as yorum_sayisi,
                    (SELECT COUNT(*) FROM favoriler WHERE bitki_id = b.id) as favori_sayisi
                    FROM favoriler f 
                    JOIN bitkiler b ON f.bitki_id = b.id
                    WHERE f.kullanici_id = ?
                    ORDER BY f.tarih DESC");
                $stmt->execute([$user_id]);
                $favs = $stmt->fetchAll();

                foreach ($favs as &$f) {
                    $f = addImageUrl($f, $base_url, $placeholder_img);
                }
                respond('success', $favs);
                break;

            // ── KULLANICI PROFİLİ ───────────────────────────────
            case 'user_profile':
                $user_id = (int)($_GET['user_id'] ?? 0);
                if (!$user_id) respond('error', null, 'user_id gerekli', 400);

                $stmt = $db->prepare("SELECT id, kullanici_adi, ad_soyad, aktiflik_puani, toplam_jeton, kayit_tarihi FROM kullanicilar WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                if (!$user) respond('error', null, 'Kullanıcı bulunamadı', 404);

                // Ek istatistikler
                $fc = $db->prepare("SELECT COUNT(*) FROM favoriler WHERE kullanici_id = ?");
                $fc->execute([$user_id]);
                $user['favori_sayisi'] = (int)$fc->fetchColumn();

                $yc = $db->prepare("SELECT COUNT(*) FROM yorumlar WHERE kullanici_id = ?");
                $yc->execute([$user_id]);
                $user['yorum_sayisi'] = (int)$yc->fetchColumn();

                $fkc = $db->prepare("SELECT COUNT(*) FROM forum_konular WHERE kullanici_id = ?");
                $fkc->execute([$user_id]);
                $user['forum_konu_sayisi'] = (int)$fkc->fetchColumn();

                respond('success', $user);
                break;

            // ── FAVORİ DURUMU KONTROL ────────────────────────────
            case 'check_favorite':
                $user_id = (int)($_GET['user_id'] ?? 0);
                $bitki_id = (int)($_GET['bitki_id'] ?? 0);
                if (!$user_id || !$bitki_id) respond('error', null, 'user_id ve bitki_id gerekli', 400);

                $stmt = $db->prepare("SELECT COUNT(*) FROM favoriler WHERE kullanici_id = ? AND bitki_id = ?");
                $stmt->execute([$user_id, $bitki_id]);
                respond('success', ['is_favorite' => (int)$stmt->fetchColumn() > 0]);
                break;
        }
    }

    // ================================================================
    // POST ENDPOINTS
    // ================================================================
    if ($method === 'POST') {
        $post = getPostData();
        $postAction = $post['action'] ?? ($_GET['action'] ?? '');

        switch ($postAction) {

            // ── FAVORİ TOGGLE ────────────────────────────────────
            case 'toggle_favorite':
                $user_id  = (int)($post['user_id'] ?? 0);
                $bitki_id = (int)($post['bitki_id'] ?? 0);
                if (!$user_id || !$bitki_id) respond('error', null, 'user_id ve bitki_id gerekli', 400);

                // Var mı kontrol
                $check = $db->prepare("SELECT id FROM favoriler WHERE kullanici_id = ? AND bitki_id = ?");
                $check->execute([$user_id, $bitki_id]);

                if ($check->fetch()) {
                    $db->prepare("DELETE FROM favoriler WHERE kullanici_id = ? AND bitki_id = ?")->execute([$user_id, $bitki_id]);
                    respond('success', ['is_favorite' => false], 'Favorilerden kaldırıldı');
                } else {
                    $db->prepare("INSERT INTO favoriler (kullanici_id, bitki_id) VALUES (?, ?)")->execute([$user_id, $bitki_id]);
                    respond('success', ['is_favorite' => true], 'Favorilere eklendi');
                }
                break;

            // ── YORUM EKLE ──────────────────────────────────────
            case 'add_comment':
                $user_id  = (int)($post['user_id'] ?? 0);
                $bitki_id = (int)($post['bitki_id'] ?? 0);
                $icerik   = sanitize($post['icerik'] ?? '');

                if (!$user_id || !$bitki_id || !$icerik) {
                    respond('error', null, 'user_id, bitki_id ve icerik gerekli', 400);
                }

                $stmt = $db->prepare("INSERT INTO yorumlar (kullanici_id, bitki_id, yorum) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $bitki_id, $icerik]);
                $newId = $db->lastInsertId();

                // Kullanıcı bilgisi ile döndür
                $ret = $db->prepare("SELECT y.*, k.kullanici_adi, k.ad_soyad FROM yorumlar y LEFT JOIN kullanicilar k ON y.kullanici_id = k.id WHERE y.id = ?");
                $ret->execute([$newId]);
                respond('success', $ret->fetch(), 'Yorum eklendi');
                break;

            // ── YORUM BEĞEN ─────────────────────────────────────
            case 'toggle_comment_like':
                $user_id  = (int)($post['user_id'] ?? 0);
                $yorum_id = (int)($post['yorum_id'] ?? 0);
                if (!$user_id || !$yorum_id) respond('error', null, 'user_id ve yorum_id gerekli', 400);

                $check = $db->prepare("SELECT id FROM yorum_begeniler WHERE kullanici_id = ? AND yorum_id = ?");
                $check->execute([$user_id, $yorum_id]);
                if ($check->fetch()) {
                    $db->prepare("DELETE FROM yorum_begeniler WHERE kullanici_id = ? AND yorum_id = ?")->execute([$user_id, $yorum_id]);
                    respond('success', ['liked' => false], 'Beğeni kaldırıldı');
                } else {
                    $db->prepare("INSERT INTO yorum_begeniler (kullanici_id, yorum_id) VALUES (?, ?)")->execute([$user_id, $yorum_id]);
                    respond('success', ['liked' => true], 'Beğenildi');
                }
                break;

            // ── FORUM KONUSU AÇ ─────────────────────────────────
            case 'add_forum_topic':
                $user_id = (int)($post['user_id'] ?? 0);
                $baslik  = sanitize($post['baslik'] ?? '');
                $icerik  = sanitize($post['icerik'] ?? '');
                if (!$user_id || !$baslik || !$icerik) respond('error', null, 'user_id, baslik ve icerik gerekli', 400);

                $stmt = $db->prepare("INSERT INTO forum_konular (kullanici_id, baslik, icerik) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $baslik, $icerik]);
                respond('success', ['id' => (int)$db->lastInsertId()], 'Forum konusu oluşturuldu');
                break;

            // ── FORUM YANIT ─────────────────────────────────────
            case 'add_forum_reply':
                $user_id        = (int)($post['user_id'] ?? 0);
                $konu_id        = (int)($post['konu_id'] ?? 0);
                $icerik         = sanitize($post['icerik'] ?? '');
                $alinti_yanit_id = !empty($post['alinti_yanit_id']) ? (int)$post['alinti_yanit_id'] : null;

                if (!$user_id || !$konu_id || !$icerik) respond('error', null, 'user_id, konu_id ve icerik gerekli', 400);

                $stmt = $db->prepare("INSERT INTO forum_yanitlar (kullanici_id, konu_id, icerik, alinti_yanit_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $konu_id, $icerik, $alinti_yanit_id]);
                $newId = $db->lastInsertId();

                $ret = $db->prepare("SELECT fy.*, k.kullanici_adi, k.ad_soyad FROM forum_yanitlar fy LEFT JOIN kullanicilar k ON fy.kullanici_id = k.id WHERE fy.id = ?");
                $ret->execute([$newId]);
                respond('success', $ret->fetch(), 'Yanıt eklendi');
                break;

            // ── FORUM YANIT BEĞEN ───────────────────────────────
            case 'toggle_forum_like':
                $user_id  = (int)($post['user_id'] ?? 0);
                $yanit_id = (int)($post['yanit_id'] ?? 0);
                if (!$user_id || !$yanit_id) respond('error', null, 'user_id ve yanit_id gerekli', 400);

                $check = $db->prepare("SELECT id FROM forum_begeniler WHERE kullanici_id = ? AND yanit_id = ?");
                $check->execute([$user_id, $yanit_id]);
                if ($check->fetch()) {
                    $db->prepare("DELETE FROM forum_begeniler WHERE kullanici_id = ? AND yanit_id = ?")->execute([$user_id, $yanit_id]);
                    respond('success', ['liked' => false], 'Beğeni kaldırıldı');
                } else {
                    $db->prepare("INSERT INTO forum_begeniler (kullanici_id, yanit_id) VALUES (?, ?)")->execute([$user_id, $yanit_id]);
                    respond('success', ['liked' => true], 'Beğenildi');
                }
                break;

            // ── BİTKİ TALEBİ ────────────────────────────────────
            case 'add_plant_request':
                $user_id  = (int)($post['user_id'] ?? 0);
                $bitki_adi = sanitize($post['bitki_adi'] ?? '');
                $icerik    = sanitize($post['icerik'] ?? '');
                if (!$user_id || !$bitki_adi) respond('error', null, 'user_id ve bitki_adi gerekli', 400);

                $stmt = $db->prepare("INSERT INTO talepler (kullanici_id, bitki_adi, icerik) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $bitki_adi, $icerik]);
                respond('success', ['id' => (int)$db->lastInsertId()], 'Talep gönderildi');
                break;

            // ── GÖRSEL YÜKLEME ──────────────────────────────────
            case 'upload_image':
                if (empty($_FILES['image'])) respond('error', null, 'Dosya bulunamadı', 400);

                $file = $_FILES['image'];
                if ($file['error'] !== UPLOAD_ERR_OK) respond('error', null, 'Yükleme hatası', 400);
                if ($file['size'] > $max_upload_size) respond('error', null, 'Dosya 5MB sınırını aşıyor', 400);

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!in_array($mime, $allowed_mime)) respond('error', null, 'Geçersiz dosya türü (jpeg/png/webp)', 400);

                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $ext = match($mime) {
                    'image/jpeg' => '.jpg',
                    'image/png'  => '.png',
                    'image/webp' => '.webp',
                    default => '.jpg'
                };
                $filename = time() . '_' . bin2hex(random_bytes(4)) . $ext;
                $dest = $upload_dir . $filename;

                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    respond('error', null, 'Dosya kaydedilemedi', 500);
                }
                respond('success', [
                    'filename' => $filename,
                    'url'      => $base_url . 'uploads/' . $filename
                ], 'Görsel yüklendi');
                break;

            // ── JETON TOPLAMA ───────────────────────────────────
            case 'collect_token':
                $user_id = (int)($post['user_id'] ?? 0);
                if (!$user_id) respond('error', null, 'user_id gerekli', 400);

                // Son jeton toplama zamanı kontrol (20sn)
                $last = $db->prepare("SELECT MAX(kazanilma_tarihi) FROM jetonlar WHERE kullanici_id = ?");
                $last->execute([$user_id]);
                $lastTime = $last->fetchColumn();

                if ($lastTime && (time() - strtotime($lastTime)) < 20) {
                    respond('error', null, 'Çok hızlı! Biraz bekleyin.', 429);
                }

                $db->prepare("INSERT INTO jetonlar (kullanici_id, miktar) VALUES (?, 1)")->execute([$user_id]);
                $db->prepare("UPDATE kullanicilar SET toplam_jeton = toplam_jeton + 1, aktiflik_puani = aktiflik_puani + 0.5 WHERE id = ?")->execute([$user_id]);

                $jeton = $db->prepare("SELECT toplam_jeton, aktiflik_puani FROM kullanicilar WHERE id = ?");
                $jeton->execute([$user_id]);
                $info = $jeton->fetch();

                respond('success', $info, '+1 Jeton kazandınız! 🌿');
                break;

            default:
                respond('error', null, 'Bilinmeyen POST action: ' . $postAction, 400);
        }
    }

    // Desteklenmeyen method
    respond('error', null, 'Desteklenmeyen HTTP metodu', 405);

} catch (PDOException $e) {
    respond('error', null, 'Sorgu hatası: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    respond('error', null, 'Sunucu hatası: ' . $e->getMessage(), 500);
}
?>
