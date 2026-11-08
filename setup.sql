-- =====================================================
-- FloryCon V2.0 — VERİTABANI KURULUM DOSYASI
-- Bu dosyayı phpMyAdmin'de çalıştırın
-- Veritabanı adı: bitki_kutuphanesi
-- =====================================================

USE bitki_kutuphanesi;

-- =====================================================
-- 1. MEVCUT TABLOLARA YENİ SÜTUNLAR EKLEME
-- =====================================================

-- Kullanıcılar tablosuna aktiflik puanı ve jeton sütunları
ALTER TABLE kullanicilar
    ADD COLUMN IF NOT EXISTS aktiflik_puani FLOAT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS toplam_jeton INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ai_skor FLOAT DEFAULT 100,
    ADD COLUMN IF NOT EXISTS kayit_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS ad_soyad VARCHAR(100) DEFAULT '';

-- Yorumlar tablosuna AI skor ve gizlilik sütunu
ALTER TABLE yorumlar
    ADD COLUMN IF NOT EXISTS ai_skor FLOAT DEFAULT 100,
    ADD COLUMN IF NOT EXISTS gizli TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS tarih DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Bitkiler tablosuna oluşturma tarihi (admin grafik için)
ALTER TABLE bitkiler
    ADD COLUMN IF NOT EXISTS olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP;

-- =====================================================
-- 2. TAKVİM TABLOSU
-- =====================================================
CREATE TABLE IF NOT EXISTS takvim (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    tarih DATE NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    icerik TEXT,
    kategori ENUM('sulama', 'gubreleme', 'budama', 'nakil', 'genel') DEFAULT 'genel',
    renk VARCHAR(7) DEFAULT '#10b981',
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. JETON TABLOSU
-- =====================================================
CREATE TABLE IF NOT EXISTS jetonlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    miktar INT DEFAULT 1,
    kazanilma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. FORUM — KONULAR TABLOSU
-- =====================================================
CREATE TABLE IF NOT EXISTS forum_konular (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    icerik TEXT NOT NULL,
    goruntulenme INT DEFAULT 0,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. FORUM — YANITLAR TABLOSU
-- =====================================================
CREATE TABLE IF NOT EXISTS forum_yanitlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    konu_id INT NOT NULL,
    kullanici_id INT NOT NULL,
    alinti_yanit_id INT DEFAULT NULL,
    icerik TEXT NOT NULL,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (konu_id) REFERENCES forum_konular(id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 6. FORUM — BEĞENİLER TABLOSU (YENİ)
-- =====================================================
CREATE TABLE IF NOT EXISTS forum_begeniler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    yanit_id INT NOT NULL,
    kullanici_id INT NOT NULL,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_begeni (yanit_id, kullanici_id),
    FOREIGN KEY (yanit_id) REFERENCES forum_yanitlar(id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 7. YORUM BEĞENİLER TABLOSU (Bitki Detay Yorumları İçin)
-- =====================================================
CREATE TABLE IF NOT EXISTS yorum_begeniler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    yorum_id INT NOT NULL,
    kullanici_id INT NOT NULL,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_yorum_begeni (yorum_id, kullanici_id),
    FOREIGN KEY (yorum_id) REFERENCES yorumlar(id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 8. FAVORİLER TABLOSU (Zaten varsa atla)
-- =====================================================
CREATE TABLE IF NOT EXISTS favoriler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    bitki_id INT NOT NULL,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fav (kullanici_id, bitki_id),
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    FOREIGN KEY (bitki_id) REFERENCES bitkiler(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 8. TALEPLER TABLOSU (Zaten varsa atla)
-- =====================================================
CREATE TABLE IF NOT EXISTS talepler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    bitki_adi VARCHAR(255) NOT NULL,
    icerik TEXT,
    durum ENUM('beklemede', 'onaylandi', 'reddedildi') DEFAULT 'beklemede',
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 9. AJANDA TABLOSU (Zaten varsa atla — kullanılmıyor ama veri korunur)
-- =====================================================
CREATE TABLE IF NOT EXISTS ajanda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    not_baslik VARCHAR(255) NOT NULL,
    not_icerik TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- KURULUM TAMAMLANDI!
-- Yeni tablo: forum_begeniler (forum beğeni sistemi)
-- Yeni sütun: bitkiler.olusturma_tarihi (admin grafik)
-- =====================================================
