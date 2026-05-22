<?php
session_start();
// Oturum açılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['admin_login'])) {
    header("Location: login.php");
    exit;
}

require 'baglanti.php';

$mesaj = "";

// 1. MÜŞTERİ BİLGİLERİNİ GÜNCELLEME İŞLEMİ
if (isset($_POST['musteri_guncelle'])) {
    $musteriID = $_POST['musteriID'];
    $ad = trim($_POST['musteriad']);
    $tel = trim($_POST['telefon']);
    $plaka = strtoupper(trim($_POST['plaka']));
    $tip = $_POST['aractipi'];
    $ucret = $_POST['saatlikucret'];

    // Başka bir müşteride bu plaka var mı kontrolü
    $plaka_kontrol = $db->prepare("SELECT musteriID FROM musteriler WHERE plaka = ? AND musteriID != ?");
    $plaka_kontrol->execute([$plaka, $musteriID]);
    
    if ($plaka_kontrol->fetch()) {
        $mesaj = "<div class='hata' style='background: #ffdddd; border-left: 4px solid #f44336; padding: 10px; margin-bottom: 15px;'>Bu plaka zaten başka bir müşteriye kayıtlı!</div>";
    } else {
        $guncelle = $db->prepare("UPDATE musteriler SET musteriad=?, telefon=?, plaka=?, aractipi=?, saatlikucret=? WHERE musteriID=?");
        if ($guncelle->execute([$ad, $tel, $plaka, $tip, $ucret, $musteriID])) {
            $mesaj = "<div class='basarili' style='background: #ddffdd; border-left: 4px solid #4CAF50; padding: 10px; margin-bottom: 15px;'>Müşteri bilgileri başarıyla güncellendi.</div>";
        } else {
            $mesaj = "<div class='hata' style='background: #ffdddd; border-left: 4px solid #f44336; padding: 10px; margin-bottom: 15px;'>Güncelleme sırasında hata oluştu.</div>";
        }
    }
}

// 2. MÜŞTERİYİ SİLME İŞLEMİ
if (isset($_POST['musteri_sil'])) {
    $id = $_POST['silinecek_id'];
    $db->prepare("DELETE FROM islemler WHERE musteriID = ?")->execute([$id]);
    $db->prepare("DELETE FROM musteriler WHERE musteriID = ?")->execute([$id]);
    $mesaj = "<div class='basarili' style='background: #ddffdd; border-left: 4px solid #4CAF50; padding: 10px; margin-bottom: 15px;'>Müşteri ve tüm kayıtları başarıyla silindi.</div>";
}

// 3. DÜZENLEME MODU (GET İle ID Gelirse Çalışır)
$duzenlenecek_musteri = null;
if (isset($_GET['duzenle'])) {
    $sorgu = $db->prepare("SELECT * FROM musteriler WHERE musteriID = ?");
    $sorgu->execute([$_GET['duzenle']]);
    $duzenlenecek_musteri = $sorgu->fetch(PDO::FETCH_ASSOC);
}

// 4. TÜM MÜŞTERİLERİ LİSTELEME SORGUSU
$musteriler_sorgu = $db->query("SELECT * FROM musteriler ORDER BY musteriID DESC");
$musteriler = $musteriler_sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Müşteri Yönetimi</title>
    <link rel="stylesheet" href="layout.css?v=7">
    <link rel="stylesheet" href="gecmis.css"> <style>
        .duzenle-form { background: #e9ecef; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ccc; }
        .duzenle-form label { font-weight: bold; display: block; margin-top: 10px; }
        .duzenle-form input, .duzenle-form select { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .islem-butonlari { display: flex; gap: 5px; }
        .btn-duzenle { background-color: #ffc107; color: #000; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold; }
        .btn-sil { background-color: #dc3545; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;}
    </style>
</head>
<body>
<?php $otoparkNav = 'musteriler'; include 'site_header.php'; ?>

<div class="panel" style="max-width: 1000px;">
    <h2>Kayıtlı Müşteriler</h2>
    <?= $mesaj ?>

    <?php if ($duzenlenecek_musteri): ?>
        <div class="duzenle-form">
            <h3 style="margin-top:0; border-bottom: 2px solid #ccc; padding-bottom:10px;">Müşteri Düzenle: <?= htmlspecialchars($duzenlenecek_musteri['plaka']) ?></h3>
            <form method="POST">
                <input type="hidden" name="musteriID" value="<?= $duzenlenecek_musteri['musteriID'] ?>">
                
                <label>Ad Soyad:</label>
                <input type="text" name="musteriad" value="<?= htmlspecialchars($duzenlenecek_musteri['musteriad']) ?>" required>
                
                <label>Telefon:</label>
                <input type="text" name="telefon" value="<?= htmlspecialchars($duzenlenecek_musteri['telefon']) ?>" required>
                
                <label>Plaka:</label>
                <input type="text" name="plaka" value="<?= htmlspecialchars($duzenlenecek_musteri['plaka']) ?>" required>
                
                <label>Araç Tipi:</label>
                <select name="aractipi" id="aractipi" onchange="ucretiOtomatikDoldur()" required>
                    <option value="Otomobil" <?= $duzenlenecek_musteri['aractipi']=='Otomobil'?'selected':'' ?>>Otomobil</option>
                    <option value="SUV" <?= $duzenlenecek_musteri['aractipi']=='SUV'?'selected':'' ?>>SUV</option>
                    <option value="Motor" <?= $duzenlenecek_musteri['aractipi']=='Motor'?'selected':'' ?>>Motor</option>
                    <option value="Minibüs" <?= $duzenlenecek_musteri['aractipi']=='Minibüs'?'selected':'' ?>>Minibüs</option>
                </select>
                
                <label>Saatlik Ücret (TL):</label>
                <input type="number" step="0.01" name="saatlikucret" id="saatlikucret" value="<?= htmlspecialchars($duzenlenecek_musteri['saatlikucret']) ?>" required>
                
                <div style="margin-top: 15px;">
                    <button type="submit" name="musteri_guncelle" style="background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">💾 Değişiklikleri Kaydet</button>
                    <a href="musteriler.php" style="background-color: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block;">❌ İptal</a>
                </div>
            </form>
        </div>
        <script>
            function ucretiOtomatikDoldur() {
                const ucretler = { "Otomobil": "50.00", "SUV": "70.00", "Motor": "25.00", "Minibüs": "90.00" };
                document.getElementById("saatlikucret").value = ucretler[document.getElementById("aractipi").value] || "";
            }
        </script>
    <?php endif; ?>

    <?php if (count($musteriler) > 0): ?>
        <table>
            <tr>
                <th>Plaka</th>
                <th>Müşteri Adı</th>
                <th>Telefon</th>
                <th>Araç Tipi</th>
                <th>Saatlik Ücret</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach ($musteriler as $m): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($m['plaka']) ?></strong></td>
                    <td><?= htmlspecialchars($m['musteriad']) ?></td>
                    <td><?= htmlspecialchars($m['telefon']) ?></td>
                    <td><?= htmlspecialchars($m['aractipi']) ?></td>
                    <td><?= htmlspecialchars($m['saatlikucret']) ?> TL</td>
                    <td class="islem-butonlari">
                        <a href="musteriler.php?duzenle=<?= $m['musteriID'] ?>" class="btn-duzenle">Düzenle</a>
                        
                        <form method="POST" onsubmit="return confirm('Bu müşteriyi ve tüm geçmiş işlemlerini tamamen silmek istediğinize emin misiniz?');" style="margin:0;">
                            <input type="hidden" name="silinecek_id" value="<?= $m['musteriID'] ?>">
                            <button type="submit" name="musteri_sil" class="btn-sil">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Sistemde henüz kayıtlı müşteri bulunmuyor.</p>
    <?php endif; ?>
</div>

</body>
</html>