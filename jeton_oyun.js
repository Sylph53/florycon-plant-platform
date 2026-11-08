// jeton_oyun.js — FloryCon Mini Jeton Oyunu (Düzeltilmiş Stok Sistemi)
(function () {
    var ikonlar = ['🍀', '🌱', '🌻', '🌿', '🌸', '🍃', '🌾', '🌺'];
    var sonKazanma = 0;
    var MIN_BEKLEME = 15000; // 15 saniye (sunucu 20s kontrol eder)
    var toplananlar = []; // Toplanan bitki koleksiyonu

    function rastgeleIkon() {
        return ikonlar[Math.floor(Math.random() * ikonlar.length)];
    }

    function rastgelePozisyon() {
        var x = Math.random() * (window.innerWidth - 100) + 50;
        var y = Math.random() * (window.innerHeight - 200) + 100;
        return { x: x, y: y };
    }

    function jetonOlustur() {
        var ikon = document.createElement('div');
        ikon.className = 'jeton-ikon';
        var secilen = rastgeleIkon();
        ikon.textContent = secilen;

        var pos = rastgelePozisyon();
        ikon.style.left = pos.x + 'px';
        ikon.style.top = pos.y + 'px';

        ikon.addEventListener('click', function (e) {
            // 1. ÖNLEM: Çift tıklamayı engellemek için ikonu tıklamaya kapat
            ikon.style.pointerEvents = 'none';

            // 2. OYUN HİSSİYATI: Anında toplanmış gibi küçült ve şeffaflaştır
            ikon.style.transform = 'scale(2)';
            ikon.style.opacity = '0';

            // 3. Sunucuya kaydet
            fetch('jeton_kaydet.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'jeton=1&ikon=' + encodeURIComponent(secilen)
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.basarili) {
                        // GERÇEKTEN STOKLANDI: Puanı göster ve ikonu DOM'dan tamamen sil
                        // Eski hali:
                        // puanGoster(e.clientX, e.clientY, 'Hata!', '#ef4444');

                        // YENİ HALİ:
                        puanGoster(e.clientX, e.clientY, data.mesaj || 'Bilinmeyen Hata!', '#ef4444');
                        setTimeout(function () { ikon.remove(); }, 300);

                        // Değerleri güncelle
                        var sayac = document.getElementById('jetonSayac');
                        if (sayac) sayac.textContent = data.toplam_jeton;

                        var puanEl = document.querySelector('.user-puan');
                        if (puanEl && data.aktiflik_puani !== undefined) {
                            puanEl.textContent = '⭐ ' + parseFloat(data.aktiflik_puani).toFixed(1) + ' Puan';
                        }

                        var statVals = document.querySelectorAll('.profil-stat-kart .stat-val');
                        if (statVals.length >= 2) {
                            statVals[1].textContent = data.toplam_jeton;
                        }
                        if (statVals.length >= 1 && data.aktiflik_puani !== undefined) {
                            statVals[0].textContent = parseFloat(data.aktiflik_puani).toFixed(1);
                        }
                    } else {
                        // PHP REDDETTİ: İkonu ekrana geri getir! (Animasyonu tersine çevir)
                        puanGoster(e.clientX, e.clientY, 'Hata!', '#ef4444');
                        ikon.style.transform = 'scale(1)';
                        ikon.style.opacity = '1';
                        ikon.style.pointerEvents = 'auto'; // Tekrar tıklanabilsin
                        console.log('Jeton Hatası:', data.mesaj);
                    }
                })
                .catch(function (err) {
                    // BAĞLANTI KOPARSA: İkonu ekrana geri getir
                    puanGoster(e.clientX, e.clientY, 'Bağlantı Sorunu!', '#ef4444');
                    ikon.style.transform = 'scale(1)';
                    ikon.style.opacity = '1';
                    ikon.style.pointerEvents = 'auto';
                    console.log('Jeton kayıt hatası:', err);
                });
        });

        document.body.appendChild(ikon);

        // 8 saniye sonra kaybol
        setTimeout(function () {
            if (ikon.parentElement) {
                ikon.style.opacity = '0';
                ikon.style.transform = 'scale(0)';
                setTimeout(function () { ikon.remove(); }, 500);
            }
        }, 8000);
    }

    function puanGoster(x, y, metin, renk) {
        var popup = document.createElement('div');
        popup.className = 'jeton-puan-popup';
        popup.textContent = metin;
        popup.style.left = x + 'px';
        popup.style.top = y + 'px';
        popup.style.color = renk;
        document.body.appendChild(popup);
        setTimeout(function () { popup.remove(); }, 1200);
    }

    function sonrakiJeton() {
        var bekleme = Math.floor(Math.random() * 60000) + 30000; // 30-90 saniye
        setTimeout(function () {
            jetonOlustur();
            sonrakiJeton();
        }, bekleme);
    }

    // Başlat — 5 saniye sonra ilk jeton
    setTimeout(function () {
        jetonOlustur();
        sonrakiJeton();
    }, 5000);
})();

