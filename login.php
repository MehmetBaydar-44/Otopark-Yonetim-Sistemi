<?php
session_start();
require 'baglanti.php';

// Eğer zaten giriş yapılmışsa panel yönlendir
if (isset($_SESSION['admin_login'])) {
    header("Location: admin_panel.php");
    exit;
}

$mesaj = "";

if (isset($_POST['giris_yap'])) {
    $kullanici = trim($_POST['kullanici']);
    $sifre = trim($_POST['sifre']);

    // Senin sütun isimlerine göre sorgu (adminmail ve adminsifre)
    $sorgu = $db->prepare("SELECT * FROM adminler WHERE adminmail = ? AND adminsifre = ?");
    $sorgu->execute([$kullanici, $sifre]);
    $admin = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Giriş başarılı: Oturum değişkenlerini ata
        $_SESSION['admin_login'] = true;
        $_SESSION['admin_id'] = $admin['adminID'];
        $_SESSION['admin_isim'] = $admin['adminad'];
        $_SESSION['admin_eposta'] = $admin['adminmail'];
        
        header("Location: admin_panel.php");
        exit;
    } else {
        $mesaj = "<div style='color:red; background:#f8d7da; padding:10px; margin-bottom:15px; border-radius:4px;'>Kullanıcı adı veya şifre hatalı!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Otopark Yönetimi - Admin Girişi</title>
    <link rel="stylesheet" href="Login1.css">
</head>
<body>
    <div class="login-kutu">
        <h2>Admin Girişi</h2>
        <?= $mesaj ?>
        <form method="POST" autocomplete="off">
            <label>Kullanıcı Adı:</label>
            <input type="text" name="kullanici" required placeholder="Kullanıcı adınızı girin">
            <label>Şifre:</label>
            <input type="password" name="sifre" required placeholder="Şifrenizi girin">
            <button type="submit" name="giris_yap">Giriş Yap</button>
        </form>
    </div>
</body>
</html>