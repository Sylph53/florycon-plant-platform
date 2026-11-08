<nav class="navbar">
    <div class="brand">
        <a href="index.php?sayfa=1000">
            <span>🌿 FloryCon</span>
        </a>
    </div>

    <div class="nav-links">
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="nav-user-area">
                <!-- Jeton Sayacı -->
                <div class="jeton-sayac" title="Toplam Jetonların">
                    🍀 <span id="jetonSayac"><?php echo $_SESSION['toplam_jeton'] ?? 0; ?></span>
                </div>

                <!-- Tema Değiştirme -->
                <button type="button" class="nav-control-btn" id="temaBtn" title="Tema Değiştir" onclick="temaDegistir()">
                    🌿
                </button>

                <!-- Müzik Kontrol -->
                <button type="button" class="nav-control-btn" id="muzikBtn" title="Müzik Aç/Kapat" onclick="muzikToggle()">
                    <div class="muzik-kontrol">
                        <div class="muzik-bar" id="bar1"></div>
                        <div class="muzik-bar" id="bar2"></div>
                        <div class="muzik-bar" id="bar3"></div>
                        <div class="muzik-bar" id="bar4"></div>
                    </div>
                </button>
            </div>
        <?php else: ?>
            <!-- Tema Değiştirme (Misafirler için de) -->
            <button type="button" class="nav-control-btn" id="temaBtn" title="Tema Değiştir" onclick="temaDegistir()">
                🌿
            </button>

            <a href="index.php?sayfa=110" class="btn-hayalet" style="margin-right: 10px;">
                Giriş Yap
            </a>
            <a href="index.php?sayfa=120" class="btn-canli" style="padding: 10px 20px; font-size: 0.9rem;">
                Kayıt Ol
            </a>
        <?php endif; ?>
    </div>
</nav>