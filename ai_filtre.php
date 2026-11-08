<?php
/**
 * =====================================================================
 * FloryCon V2.0 — KELIME TABANLI İÇERİK FİLTRELEME MODÜLÜ
 * =====================================================================
 *
 * Bu modül, kullanıcı içeriklerini önceden tanımlanmış küfür/argo/hakaret
 * kelime listesiyle kontrol eder. Ağırlıklı puanlama sistemi kullanır.
 *
 * Ağırlık Seviyeleri:
 *   3 → Ağır küfür (en kötü ifadeler)
 *   2 → Orta seviye küfür/argo
 *   1 → Hafif argo / kaba ifadeler
 *
 * Özellikler:
 *   - 300+ Türkçe/İngilizce küfür, argo, tehdit ve nefret söylemi
 *   - Türkçe karakter normalleştirme (ç→c, ş→s, ğ→g, ı→i, ö→o, ü→u)
 *   - Noktalama temizleme ("a.p.t.a.l" → "aptal")
 *   - Leetspeak dönüşümü (@ → a, 3 → e, 1 → i, 0 → o, 5 → s)
 *   - Tekrar karakter temizleme ("apttaaaal" → "aptal")
 *   - Boşluk ekleme tespiti ("a p t a l" → "aptal")
 *   - 10 farklı metin varyasyonu kontrol ediliyor
 *
 * =====================================================================
 */

// =====================================================================
// KÜFÜR / ARGO KELİME LİSTESİ (Ağırlık bazlı)
// =====================================================================

/**
 * Ağırlık 3: Ağır küfürler — Her tespit -30 puan
 */
$AGIR_KUFURLER = [
    // Türkçe — Ağır
    'amk', 'aq', 'amq', 'amına', 'amina', 'aminakoyim', 'amınakoyim',
    'amınakoyayım', 'aminakoyayim', 'amınakoydugum', 'aminakoydugum',
    'ananı', 'anani', 'ananızı', 'ananizi', 'ananıskm', 'anasını', 'anasini',
    'ananısikeyim', 'anasınısikeyim', 'orospu', 'oruspu', 'orusbu', 'orosbu',
    'orospuçocuğu', 'orospucocugu', 'orospucocu', 'oçocuğu',
    'piç', 'pic', 'piçkurusu',
    'sikeyim', 'sikiyim', 'sikerim', 'sikerım', 'sikerm', 'sikim',
    'siktir', 'siktirgit', 'siktimin', 'siktigimin', 'siktigim',
    'skmn', 'skm', 'sktr', 'sktrgt',
    'yarrak', 'yarak', 'yarrag', 'yarag', 'yarraklarımı',
    'taşak', 'tasak', 'taşşak', 'tassak',
    'götveren', 'gotveren', 'götoğlanı', 'gotoglani',
    'ibne', 'ibné', 'ibneler',
    'pezevenk', 'pzvnk',
    'gerizekalı', 'gerizekalılar',
    'gavat', 'kahpe', 'fahişe', 'fahise', 'kaltak', 'sürtük', 'surtuk',
    'döl', 'dol', 'meni',

    // İngilizce — Ağır
    'fuck', 'fucker', 'fucking', 'fuckin', 'fck', 'fuk', 'fuckyou',
    'motherfucker', 'mfker', 'mfer',
    'shit', 'bullshit', 'shithead', 'shitty',
    'bitch', 'bitches', 'sonofabitch',
    'asshole', 'arsehole',
    'cunt', 'pussy', 'dick', 'cock', 'penis',
    'nigger', 'nigga', 'negro',
    'whore', 'slut', 'hoe',
    'retard', 'retarded',
];

/**
 * Ağırlık 2: Orta seviye küfürler — Her tespit -20 puan
 */
$ORTA_KUFURLER = [
    // Türkçe — Orta
    'bok', 'boktan', 'boklu',
    'hıyar', 'hiyar', 'dangalak',
    'salak', 'salakmısın', 'salaklar',
    'gerizekalı', 'gerizekali',
    'manyak', 'manyaklar',
    'dingil', 'dingilsin',
    'am', 'amcık', 'amcik', 'amcığ', 'amcig',
    'göt', 'got', 'götün', 'gotun', 'gote', 'göte',
    'sik', 'sikik', 'sikko', 'siko',
    'mal', 'malısın', 'malsin', 'mallsın', 'mallsin',
    'ezik', 'eziksin',
    'dangalak', 'hödük', 'hoduk',
    'zukkafa', 'zukafa',
    'embesil', 'enayi', 'enayiler',
    'yavşak', 'yavsak', 'yavsaklar',
    'çük', 'cuk', 'çüksüz',
    'angut', 'andaval',
    'puşt', 'pust', 'puştlar',
    'kodumun', 'kodumuncocugu',
    'hassiktir', 'hasiktir', 'hadi siktir',
    'kancık', 'kancik',
    'kevaşe', 'kevase',
    'it oğlu it', 'itogluit',
    'şerefsiz', 'serefsiz', 'serefsizler',
    'namussuz', 'namusssuz',
    'alçak', 'alcak', 'aşağılık', 'asagilik',
    'pislik', 'pislikler',
    'rezil', 'rezilsin',
    'kepaze', 'kepazesin',

    // İngilizce — Orta
    'damn', 'dammit', 'goddamn',
    'ass', 'arse', 'dumbass',
    'bastard', 'bastards',
    'crap', 'crappy',
    'idiot', 'idiots', 'idiotic',
    'moron', 'morons',
    'stupid', 'stupidity',
    'dumb', 'dumbass',
    'suck', 'sucker', 'sucks',
    'loser', 'losers',
    'jerk', 'jerks',
    'scumbag', 'scum',
    'douche', 'douchebag',
    'prick', 'wanker',
    'twat', 'tosser',
];

/**
 * Ağırlık 1: Hafif argo / kaba ifadeler — Her tespit -10 puan
 */
$HAFIF_KUFURLER = [
    // Türkçe — Hafif
    'aptal', 'aptalsın', 'aptalsin', 'aptallar',
    'budala', 'budalalar',
    'geri zekalı', 'geri zekali',
    'ahmak', 'ahmaklar',
    'saloz', 'salozsun',
    'avanak', 'avanaklar',
    'mankafa', 'mankafalar',
    'hımbıl', 'himbil',
    'saçma', 'sacma', 'saçmalama',
    'berbat', 'berbatsın',
    'beyin yoksunu',
    'kafasız', 'kafasiz',
    'gerzek', 'gerrzek',
    'kötü', 'kotu', 'igrenç', 'igrenc',
    'defol', 'siktir git', 'defolup',
    'kalın kafalı', 'kalinkafalı',
    'beceriksiz', 'beceriksizsin',
    'işe yaramaz', 'ise yaramaz',
    'boşver', 'sana ne', 'karışma',
    'hırt', 'hirt',
    'yobaz', 'yobazlar',
    'soytarı', 'soytari',
    'palyaço', 'palyaco',
    'serseri', 'serseriler',
    'haylaz', 'haylazlar',
    'terbiyesiz', 'terbiyesizler',
    'edepsiz', 'edepsizler',
    'küstah', 'kustah', 'küstahlar',
    'hadsiz', 'hadsizsin',
    'densiz', 'densizler',
    'saygısız', 'saygisiz',
    'kıytırık', 'kiytirık',

    // İngilizce — Hafif
    'fool', 'foolish', 'fools',
    'dork', 'nerd', 'geek',
    'lame', 'pathetic',
    'salty', 'toxic',
    'noob', 'newbie',
    'trash', 'trashy', 'garbage',
    'hell', 'heck',
    'freaking', 'freakin', 'frigging',
    'darn', 'dang',
    'shut up', 'shutup',
    'annoying',
    'ugly', 'hideous',
    'weirdo', 'creep', 'creepy',
    'gross', 'disgusting',
    'useless',

    // Tehdit / Şiddet — Hafif → Orta arası
    'öldürürüm', 'olduruDurum', 'gebertirim',
    'seni bıçaklarım', 'bıçaklarım',
    'kafanı koparırım', 'kafani koparirim',
    'öleceksin', 'oleceksin', 'gebertecegim',

    // Nefret söylemi
    'ırkçı', 'irkci', 'ırkçılık',
    'homofobik', 'transfobik',
    'faşist', 'fasist', 'nazi',
    'terörist', 'terorist',
];


// =====================================================================
// METİN NORMALİZASYON FONKSİYONLARI
// =====================================================================

/**
 * Türkçe karakterleri normalleştirir: ç→c, ş→s, ğ→g, ı→i, ö→o, ü→u
 */
function _turkce_normalize($metin)
{
    $harita = [
        'ç' => 'c', 'Ç' => 'C',
        'ş' => 's', 'Ş' => 'S',
        'ğ' => 'g', 'Ğ' => 'G',
        'ı' => 'i', 'I' => 'I',
        'ö' => 'o', 'Ö' => 'O',
        'ü' => 'u', 'Ü' => 'U',
        'İ' => 'I',
    ];
    return strtr($metin, $harita);
}

/**
 * Noktalama/özel karakterleri temizler: "a.p.t.a.l" → "aptal"
 */
function _noktalama_temizle($metin)
{
    return preg_replace('/[^a-zA-Z0-9\s]/', '', $metin);
}

/**
 * Leetspeak dönüşümü: @ → a, 3 → e, 1 → i, 0 → o, 5 → s, 7 → t
 */
function _leetspeak_coz($metin)
{
    $harita = [
        '@' => 'a', '4' => 'a',
        '3' => 'e',
        '1' => 'i', '!' => 'i',
        '0' => 'o',
        '5' => 's', '$' => 's',
        '7' => 't',
    ];
    return strtr($metin, $harita);
}

/**
 * Tekrar eden karakterleri sadeleştirir: "apttaaaal" → "aptal"
 */
function _tekrar_temizle($metin)
{
    return preg_replace('/(.)\1{2,}/', '$1', $metin);
}

/**
 * Boşlukları kaldırır: "a p t a l" → "aptal"
 */
function _bosluk_kaldir($metin)
{
    return preg_replace('/\s+/', '', $metin);
}


// =====================================================================
// ANA FİLTRE FONKSİYONU — Kelime Tabanlı Filtreleme
// =====================================================================

/**
 * İçeriği küfür kelime listesiyle kontrol eder.
 *
 * @param  string $metin  Analiz edilecek metin
 * @return array  [
 *   'temiz'             => bool,    // İçerik temiz mi?
 *   'skor'              => float,   // 0-100 arası temizlik skoru
 *   'agirlik_toplam'    => int,     // Toplam ağırlık (puan düşürme çarpanı)
 *   'bulunan_kelimeler' => array,   // Tespit edilen küfürler
 *   'toxicity'          => float,   // 0-100 toksisite skoru
 *   'severe_toxicity'   => float,   // 0-100 ağır toksisite
 *   'kaynak'            => string   // 'kelime_filtre'
 * ]
 */
function icerik_filtrele($metin)
{
    global $AGIR_KUFURLER, $ORTA_KUFURLER, $HAFIF_KUFURLER;

    // Boş metin kontrolü
    $metin = trim($metin);
    if (empty($metin)) {
        return [
            'temiz' => true,
            'skor' => 100,
            'agirlik_toplam' => 0,
            'bulunan_kelimeler' => [],
            'toxicity' => 0,
            'severe_toxicity' => 0,
            'kaynak' => 'empty'
        ];
    }

    // ─── Metin Varyasyonları Oluştur (10 farklı kontrol) ───
    $orijinal = mb_strtolower($metin, 'UTF-8');
    $varyasyonlar = [];

    // 1. Orijinal (küçük harf)
    $varyasyonlar[] = $orijinal;

    // 2. Türkçe normalize
    $tr_norm = _turkce_normalize($orijinal);
    $varyasyonlar[] = $tr_norm;

    // 3. Noktalama temizlenmiş
    $noktasiz = _noktalama_temizle($orijinal);
    $varyasyonlar[] = $noktasiz;

    // 4. Leetspeak çözülmüş
    $leetfree = _leetspeak_coz($orijinal);
    $varyasyonlar[] = $leetfree;

    // 5. Tekrar karakter temizlenmiş
    $tekrarsiz = _tekrar_temizle($orijinal);
    $varyasyonlar[] = $tekrarsiz;

    // 6. Boşluk kaldırılmış
    $bosluksuz = _bosluk_kaldir($orijinal);
    $varyasyonlar[] = $bosluksuz;

    // 7. Türkçe norm + noktalama
    $varyasyonlar[] = _noktalama_temizle($tr_norm);

    // 8. Leetspeak + Türkçe norm
    $varyasyonlar[] = _turkce_normalize(_leetspeak_coz($orijinal));

    // 9. Tekrar + Türkçe norm + noktalama
    $varyasyonlar[] = _noktalama_temizle(_turkce_normalize(_tekrar_temizle($orijinal)));

    // 10. Hepsi bir arada
    $varyasyonlar[] = _noktalama_temizle(_turkce_normalize(_leetspeak_coz(_tekrar_temizle(_bosluk_kaldir($orijinal)))));

    // Tekrarları kaldır
    $varyasyonlar = array_unique($varyasyonlar);

    // ─── Kelime Eşleştirme ───
    $bulunanlar = [];
    $agirlik_toplam = 0;

    $kelime_listeleri = [
        3 => $AGIR_KUFURLER,
        2 => $ORTA_KUFURLER,
        1 => $HAFIF_KUFURLER,
    ];

    foreach ($kelime_listeleri as $agirlik => $liste) {
        foreach ($liste as $kelime) {
            $kelime_lower = mb_strtolower($kelime, 'UTF-8');
            $kelime_norm = _turkce_normalize($kelime_lower);

            foreach ($varyasyonlar as $varyasyon) {
                // Hem orijinal küfür hem de normalize edilmiş versiyonu ara
                if (
                    mb_strpos($varyasyon, $kelime_lower) !== false ||
                    mb_strpos($varyasyon, $kelime_norm) !== false
                ) {
                    // Aynı kelimeyi birden fazla ekleme
                    $anahtar = $kelime_lower . '_' . $agirlik;
                    if (!isset($bulunanlar[$anahtar])) {
                        $seviye = $agirlik == 3 ? 'AĞIR' : ($agirlik == 2 ? 'ORTA' : 'HAFİF');
                        $bulunanlar[$anahtar] = [
                            'kelime' => $kelime,
                            'agirlik' => $agirlik,
                            'seviye' => $seviye
                        ];
                        $agirlik_toplam += $agirlik;
                    }
                    break; // Bu varyasyon eşleşti, sonrakine geç
                }
            }
        }
    }

    // ─── Skor Hesaplama ───
    $bulunan_liste = [];
    foreach ($bulunanlar as $b) {
        $bulunan_liste[] = $b['seviye'] . ': ' . $b['kelime'];
    }

    // Toksisite: ağırlık toplamına göre (max 100)
    $toxicity = min(100, $agirlik_toplam * 15);
    $severe_toxicity = 0;

    // Ağır küfür varsa severe_toxicity'yi hesapla
    foreach ($bulunanlar as $b) {
        if ($b['agirlik'] == 3) {
            $severe_toxicity = min(100, $severe_toxicity + 30);
        }
    }

    // Temizlik skoru
    $temizlik_skoru = max(0, 100 - $toxicity);

    // İçerik temiz mi?
    $temiz = ($agirlik_toplam == 0);

    return [
        'temiz' => $temiz,
        'skor' => $temizlik_skoru,
        'agirlik_toplam' => $agirlik_toplam,
        'bulunan_kelimeler' => $bulunan_liste,
        'toxicity' => $toxicity,
        'severe_toxicity' => $severe_toxicity,
        'kaynak' => 'kelime_filtre'
    ];
}


// =====================================================================
// VERİTABANI FONKSİYONLARI
// =====================================================================

/**
 * Kullanıcının AI skorunu küfür ağırlığına göre düşürür.
 *
 * Ağırlık çarpanı ile puan düşüşü:
 * - agirlik=1 → -10 puan (hafif)
 * - agirlik=2 → -20 puan (orta)
 * - agirlik=3 → -30 puan (ağır)
 * - agirlik=5 → -50 puan (birden fazla tespit)
 *
 * @param PDO $db
 * @param int $kullanici_id
 * @param int $agirlik        Küfür ağırlık çarpanı (varsayılan: 1)
 * @return float               Yeni AI skoru
 */
function kullanici_ai_skor_guncelle($db, $kullanici_id, $agirlik = 1)
{
    // Mevcut AI skorunu çek
    $stmt = $db->prepare("SELECT ai_skor FROM kullanicilar WHERE id = ?");
    $stmt->execute([$kullanici_id]);
    $sonuc = $stmt->fetch(PDO::FETCH_ASSOC);

    $mevcut_skor = $sonuc['ai_skor'] ?? 100;

    // Her ağırlık puanı başına 10 düşür, 0'ın altına inmez
    $dusus = $agirlik * 10;
    $yeni_skor = max(0, $mevcut_skor - $dusus);

    // Veritabanında güncelle
    $guncelle = $db->prepare("UPDATE kullanicilar SET ai_skor = ? WHERE id = ?");
    $guncelle->execute([$yeni_skor, $kullanici_id]);

    return $yeni_skor;
}

/**
 * Aktiflik puanını hesapla ve veritabanına kaydet.
 *
 * Formül: (toplam_jeton × 0.4) + ((yorum_sayısı + forum_yanıt) × 0.6)
 *
 * @param PDO $db
 * @param int $kullanici_id
 * @return float  Hesaplanan aktiflik puanı
 */
function aktiflik_puani_guncelle($db, $kullanici_id)
{
    // Toplam jeton
    $jeton_stmt = $db->prepare("SELECT COALESCE(SUM(miktar), 0) as toplam FROM jetonlar WHERE kullanici_id = ?");
    $jeton_stmt->execute([$kullanici_id]);
    $toplam_jeton = $jeton_stmt->fetch(PDO::FETCH_ASSOC)['toplam'];

    // Toplam yorum
    $yorum_stmt = $db->prepare("SELECT COUNT(*) as toplam FROM yorumlar WHERE kullanici_id = ?");
    $yorum_stmt->execute([$kullanici_id]);
    $toplam_yorum = $yorum_stmt->fetch(PDO::FETCH_ASSOC)['toplam'];

    // Forum yanıt sayısı
    $forum_stmt = $db->prepare("SELECT COUNT(*) as toplam FROM forum_yanitlar WHERE kullanici_id = ?");
    $forum_stmt->execute([$kullanici_id]);
    $toplam_forum = $forum_stmt->fetch(PDO::FETCH_ASSOC)['toplam'];

    // Formül uygula
    $puan = ($toplam_jeton * 0.4) + (($toplam_yorum + $toplam_forum) * 0.6);

    // Veritabanına kaydet
    $guncelle = $db->prepare("UPDATE kullanicilar SET aktiflik_puani = ?, toplam_jeton = ? WHERE id = ?");
    $guncelle->execute([$puan, $toplam_jeton, $kullanici_id]);

    return $puan;
}
?>