<?php
session_start();
// Oturum açılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['admin_login'])) {
    header("Location: login.php");
    exit;
}

require 'baglanti.php';

// GEÇMİŞ İŞLEMLERİ ÇEKME
$sorgu = $db->query("
    SELECT i.islemID, i.girissaati, i.cikissaati, i.toplamucret, m.plaka, m.musteriad, a.katno, a.blokkodu 
    FROM islemler i
    INNER JOIN musteriler m ON i.musteriID = m.musteriID
    INNER JOIN alanlar a ON i.alanID = a.alanID
    WHERE i.cikissaati IS NOT NULL
    ORDER BY i.cikissaati DESC
");
$gecmis_islemler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

$toplam_gelir = 0;
foreach ($gecmis_islemler as $islem) {
    $toplam_gelir += $islem['toplamucret'];
}
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Geçmiş İşlemler - Raporlar</title>
    <link rel="stylesheet" href="layout.css?v=7">
    <link rel="stylesheet" href="gecmis.css">
</head>
<body>
<?php $otoparkNav = 'gecmis'; include 'site_header.php'; ?>

<div class="panel">
    <h2>Geçmiş Otopark İşlemleri</h2>
    
    <div class="ozet-kutu">
        Şu ana kadar tamamlanan işlemlerden elde edilen toplam gelir: <strong><?= number_format($toplam_gelir, 2) ?> TL</strong>
    </div>

    <?php if (count($gecmis_islemler) > 0): ?>
        <table>
            <tr>
                <th>İşlem ID</th>
                <th>Plaka</th>
                <th>Müşteri</th>
                <th>Park Yeri</th>
                <th>Giriş Saati</th>
                <th>Çıkış Saati</th>
                <th>Tahsil Edilen Ücret</th>
            </tr>
            <?php foreach ($gecmis_islemler as $islem): ?>
                <tr>
                    <td>#<?= $islem['islemID'] ?></td>
                    <td><strong><?= htmlspecialchars($islem['plaka']) ?></strong></td>
                    <td><?= htmlspecialchars($islem['musteriad']) ?></td>
                    <td><?= htmlspecialchars($islem['katno']) ?> - <?= htmlspecialchars($islem['blokkodu']) ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($islem['girissaati'])) ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($islem['cikissaati'])) ?></td>
                    <td><strong><?= htmlspecialchars($islem['toplamucret']) ?> TL</strong></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Henüz tamamlanmış (çıkış yapmış) bir işlem bulunmuyor.</p>
    <?php endif; ?>

</div>

</body>
</html>