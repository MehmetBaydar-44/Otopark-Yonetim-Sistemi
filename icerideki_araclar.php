<?php
session_start();
// Oturum açılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['admin_login'])) {
    header("Location: login.php");
    exit;
}

require 'baglanti.php';

$mesaj = "";

// -- YENİ: GİRİŞ İŞLEMİNİ İPTAL ETME --
if (isset($_POST['giris_iptal'])) {
    $islemID = $_POST['iptal_islem_id'];
    $alanID = $_POST['iptal_alan_id'];

    $db->prepare("DELETE FROM islemler WHERE islemID = ?")->execute([$islemID]);
    $db->prepare("UPDATE alanlar SET alandurum = 'BOŞ' WHERE alanID = ?")->execute([$alanID]);

    $mesaj = "<div class='basarili'>Giriş işlemi iptal edildi ve park yeri boşaltıldı.</div>";
}

// ÇIKIŞ YAPMA VE ÜCRET HESAPLAMA İŞLEMİ
if (isset($_POST['cikis_yap'])) {
    $islemID = $_POST['islem_id'];
    $alanID = $_POST['alan_id'];
    $saatlik_ucret = $_POST['saatlik_ucret'];
    $giris_saati = $_POST['giris_saati'];

    $cikis_zamani = date('Y-m-d H:i:s');
    
    $giris_obj = new DateTime($giris_saati);
    $cikis_obj = new DateTime($cikis_zamani);
    $fark = $giris_obj->diff($cikis_obj);
    
    $toplam_saat = ($fark->days * 24) + $fark->h + ($fark->i / 60);
    $hesaplanacak_saat = ceil($toplam_saat);
    if ($hesaplanacak_saat == 0) { $hesaplanacak_saat = 1; }

    $toplam_tutar = $hesaplanacak_saat * $saatlik_ucret;

    $db->prepare("UPDATE islemler SET cikissaati = ?, toplamucret = ? WHERE islemID = ?")->execute([$cikis_zamani, $toplam_tutar, $islemID]);
    $db->prepare("UPDATE alanlar SET alandurum = 'BOŞ' WHERE alanID = ?")->execute([$alanID]);

    $mesaj = "<div class='basarili'>Çıkış işlemi başarılı! <br> Kalınan Süre: <strong>$hesaplanacak_saat Saat</strong> <br> Tahsil Edilen Tutar: <strong>$toplam_tutar TL</strong></div>";
}

// İÇERİDEKİ ARAÇLARI LİSTELEME
$sorgu = $db->query("
    SELECT i.islemID, i.girissaati, i.alanID, m.plaka, m.musteriad, m.saatlikucret, a.katno, a.blokkodu 
    FROM islemler i
    INNER JOIN musteriler m ON i.musteriID = m.musteriID
    INNER JOIN alanlar a ON i.alanID = a.alanID
    WHERE i.cikissaati IS NULL
");
$iceridekiler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İçerideki Araçlar - Çıkış Paneli</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="stylesheet" href="layout.css?v=7">
    <link rel="stylesheet" href="iceri_arac.css">
</head>
<body>
<?php $otoparkNav = 'iceride'; include 'site_header.php'; ?>

<div class="panel">
    <h2>Otoparktaki Araçlar (Aktif Parklar)</h2>
    <?= $mesaj ?>

    <?php if (count($iceridekiler) > 0): ?>
        <table>
            <tr>
                <th>Plaka</th>
                <th>Müşteri</th>
                <th>Park Yeri</th>
                <th>Giriş Saati</th>
                <th>Saatlik Tarife</th>
                <th>İşlem</th>
            </tr>
            <?php foreach ($iceridekiler as $arac): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($arac['plaka']) ?></strong></td>
                    <td><?= htmlspecialchars($arac['musteriad']) ?></td>
                    <td><?= htmlspecialchars($arac['katno']) ?> - <?= htmlspecialchars($arac['blokkodu']) ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($arac['girissaati'])) ?></td>
                    <td><?= htmlspecialchars($arac['saatlikucret']) ?> TL</td>
                    <td class="islem-butonlari">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="islem_id" value="<?= $arac['islemID'] ?>">
                            <input type="hidden" name="alan_id" value="<?= $arac['alanID'] ?>">
                            <input type="hidden" name="saatlik_ucret" value="<?= $arac['saatlikucret'] ?>">
                            <input type="hidden" name="giris_saati" value="<?= $arac['girissaati'] ?>">
                            <button type="submit" name="cikis_yap" style="background-color: #28a745;">Çıkış / Ücret Al</button>
                        </form>

                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Bu girişi iptal etmek istiyor musunuz? Park yeri boşaltılacak.');">
                            <input type="hidden" name="iptal_islem_id" value="<?= $arac['islemID'] ?>">
                            <input type="hidden" name="iptal_alan_id" value="<?= $arac['alanID'] ?>">
                            <button type="submit" name="giris_iptal" style="background-color: #dc3545; font-size: 12px; padding: 10px;">İptal</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Şu an otoparkta araç bulunmamaktadır.</p>
    <?php endif; ?>

</div>

</body>
</html>