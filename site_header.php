<?php
$otoparkNav = $otoparkNav ?? 'panel';
$aktif = fn ($k) => ($otoparkNav === $k) ? ' class="aktif"' : '';
?><header class="site-head">
    <a class="site-logo" href="index.php">Otopark</a>
    <nav class="site-nav">
        <a href="admin_panel.php"<?= $aktif('panel') ?>>Plaka girişi</a>
        <a href="musteriler.php" class="<?= $otoparkNav == 'musteriler' ? 'aktif' : '' ?>">Müşteriler</a>
        <a href="icerideki_araclar.php"<?= $aktif('iceride') ?>>İçerideki araçlar</a>
        <a href="gecmis_islemler.php"<?= $aktif('gecmis') ?>>Geçmiş işlemler</a>
        <a href="logout.php"<?= $aktif('logout') ?>>Çıkış</a>
    </nav>
</header>
