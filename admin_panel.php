<?php
session_start();
// Oturum açılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['admin_login'])) {
    header("Location: login.php");
    exit;
}

require 'baglanti.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mesaj = "";
$sorgulanan_plaka = "";
$arac_kayitli_mi = false;
$arac_iceride_mi = false;
$musteri_bilgisi = [];

/**
 * Tek GET isteği için göster — yenilemede kaybolur (PRG).
 */
function admin_panel_flash_redirect(string $mesaj, string $sorgulanan_plaka = '', bool $arac_kayitli_mi = false, bool $arac_iceride_mi = false, $musteri_bilgisi = null): void {
    $_SESSION['admin_pf'] = [
        'mesaj' => $mesaj,
        'sorgulanan_plaka' => $sorgulanan_plaka,
        'arac_kayitli_mi' => $arac_kayitli_mi,
        'arac_iceride_mi' => $arac_iceride_mi,
        'musteri_bilgisi' => ($musteri_bilgisi && is_array($musteri_bilgisi)) ? $musteri_bilgisi : [],
    ];
    header('Location: admin_panel.php', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($_SESSION['admin_pf'])) {
    $f = $_SESSION['admin_pf'];
    unset($_SESSION['admin_pf']);
    $mesaj = $f['mesaj'] ?? '';
    $sorgulanan_plaka = $f['sorgulanan_plaka'] ?? '';
    $arac_kayitli_mi = $f['arac_kayitli_mi'] ?? false;
    $arac_iceride_mi = $f['arac_iceride_mi'] ?? false;
    $musteri_bilgisi = $f['musteri_bilgisi'] ?? [];
}

// 1. MÜŞTERİ VE ARAÇ KAYDINI TAMAMEN SİLME
if (isset($_POST['musteri_sil'])) {
    $id = $_POST['silinecek_musteri_id'];
    $db->prepare("DELETE FROM islemler WHERE musteriID = ?")->execute([$id]);
    $sil = $db->prepare("DELETE FROM musteriler WHERE musteriID = ?");
    $sil->execute([$id]);
    admin_panel_flash_redirect(
        "<div class='basarili'>Araç ve ilgili tüm kayıtlar sistemden tamamen silindi.</div>"
    );
}

// 2. PLAKA SORGULAMA (manuel + OCR form POST)
if (isset($_POST['plaka_sorgula']) || isset($_POST['plaka'])) {
    $sorgulanan_plaka = strtoupper(trim($_POST['plaka']));

    $sorgu = $db->prepare("SELECT * FROM musteriler WHERE plaka = :plaka");
    $sorgu->execute(['plaka' => $sorgulanan_plaka]);
    $musteri_bilgisi = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($musteri_bilgisi) {
        $iceride_sorgu = $db->prepare("SELECT islemID FROM islemler WHERE musteriID = ? AND cikissaati IS NULL");
        $iceride_sorgu->execute([$musteri_bilgisi['musteriID']]);
        $iceride_durum = $iceride_sorgu->fetch(PDO::FETCH_ASSOC);

        if ($iceride_durum) {
            admin_panel_flash_redirect(
                "<div class='hata' style='background-color: #fff3cd; border-left: 4px solid #ffc107; color: #856404;'>
                  <strong>Dikkat:</strong> Bu araç şu anda otoparkta bulunuyor!</div>",
                $sorgulanan_plaka,
                false,
                true,
                $musteri_bilgisi
            );
        }
        admin_panel_flash_redirect(
            "<div class='basarili'>Plaka sistemde kayıtlı. Giriş onaylanabilir.</div>",
            $sorgulanan_plaka,
            true,
            false,
            $musteri_bilgisi
        );
    }
    admin_panel_flash_redirect(
        "<div class='hata'>Plaka bulunamadı! Yeni kayıt oluşturulmalıdır.</div>",
        $sorgulanan_plaka
    );
}

// 3. YENİ ARAÇ KAYDETME
if (isset($_POST['yeni_kayit'])) {
    $ad = $_POST['musteriad'];
    $tel = $_POST['telefon'];
    $plaka = strtoupper(trim($_POST['yeni_plaka']));
    $tip = $_POST['aractipi'];
    $ucret = $_POST['saatlikucret'];

    $ekle = $db->prepare("INSERT INTO musteriler (musteriad, telefon, plaka, aractipi, saatlikucret) VALUES (?, ?, ?, ?, ?)");
    $ekle->execute([$ad, $tel, $plaka, $tip, $ucret]);
    $sorgu = $db->prepare("SELECT * FROM musteriler WHERE plaka = ?");
    $sorgu->execute([$plaka]);
    $musteri_bilgisi = $sorgu->fetch(PDO::FETCH_ASSOC);
    admin_panel_flash_redirect(
        "<div class='basarili'>Yeni araç kaydedildi.</div>",
        $plaka,
        true,
        false,
        $musteri_bilgisi
    );
}


// 4. OTOPARKA GİRİŞ
if (isset($_POST['giris_yap'])) {
    $musteriID = $_POST['musteri_id'];
    $adminID = 1;

    // YENİ: Alt kat dolmadan üste çıkmayan ve kendi içinde rastgele yer veren SQL Sorgusu
    $alan_sorgu = $db->query("
        SELECT alanID 
        FROM alanlar 
        WHERE alandurum = 'BOŞ' 
          AND katno = (SELECT MIN(katno) FROM alanlar WHERE alandurum = 'BOŞ')
        ORDER BY RAND() 
        LIMIT 1
    ")->fetch();

    if ($alan_sorgu) {
        $alanID = $alan_sorgu['alanID'];
        $db->prepare("INSERT INTO islemler (musteriID, alanID, adminID) VALUES (?, ?, ?)")->execute([$musteriID, $alanID, $adminID]);
        $db->prepare("UPDATE alanlar SET alandurum = 'DOLU' WHERE alanID = ?")->execute([$alanID]);
        
        // Mesajı da yeni sisteme göre biraz süsleyebiliriz
        admin_panel_flash_redirect("<div class='basarili'>Giriş yapıldı, araç o kattaki rastgele bir boş yere atandı ve sayaç aktif.</div>");
    } else {
        admin_panel_flash_redirect("<div class='hata'>Boş yer yok!</div>");
    }
}

$toplam_yer = $db->query("SELECT COUNT(*) FROM alanlar")->fetchColumn();
$bos_yer = $db->query("SELECT COUNT(*) FROM alanlar WHERE alandurum = 'BOŞ'")->fetchColumn();
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Otopark Admin Paneli</title>
    <link rel="stylesheet" href="layout.css?v=7">
    <link rel="stylesheet" href="admin.css?v=9">
    <style>
        .ocr-container { background: #fafbfc; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #e0e5ea; }
        /* Eskisi gibi dar alan (max 400px); yükseklik en-boy ile sabit — kamera açılınca büyümez */
        .kamera-cerceve {
            position: relative;
            width: 100%;
            max-width: 400px;
            height: 0;
            margin: 0 auto 10px;
            padding-bottom: 56.25%;
            border-radius: 8px;
            overflow: hidden;
            background: #000;
        }
        #kamera {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        #ocrDurum { margin-top: 10px; font-weight: bold; }
    </style>
</head>
<body>
<?php $otoparkNav = 'panel'; include 'site_header.php'; ?>

<div class="panel panel--yonetim">

    <div class="kapasite">
        Otopark Durumu: <?= ($toplam_yer - $bos_yer) ?> İçeride | <?= $bos_yer ?> Boş
    </div>

    <div class="admin-2kolon">
        <div class="admin-sol">
            <h2>Plaka Sorgulama &amp; AI Tanıma</h2>
            <div class="ocr-container">
                <div class="kamera-cerceve">
                    <video id="kamera" autoplay playsinline muted></video>
                </div>
                <canvas id="canvas" style="display:none;"></canvas>
                <div id="ocrDurum">Sistem Hazır</div>
                <button type="button" id="kameraAcBtn" style="background-color: #007bff; width: auto;">Kamerayı Başlat</button>
                <button type="button" id="plakaOkuBtn" style="background-color: #ffc107; color: #000; width: auto; display: none;">📸 Plaka Oku</button>
            </div>
            <form method="POST" id="plakaForm" class="pf">
                <label for="plakaInput" class="vh">Plaka</label>
                <div class="tpl">
                    <input type="text" name="plaka" id="plakaInput" class="tpl-i"
                           value="<?= htmlspecialchars($sorgulanan_plaka) ?>" placeholder="34TAB123"
                           maxlength="24" autocomplete="off" spellcheck="false" required>
                </div>
                <button type="submit" name="plaka_sorgula">Sorgula</button>
            </form>
        </div>

        <div class="admin-sag">
            <h2 class="admin-sag-baslik">Sorgu sonucu</h2>
            <?php
            $sonuc_var = $mesaj !== ''
                || ($arac_kayitli_mi && $musteri_bilgisi && !$arac_iceride_mi)
                || (!empty($sorgulanan_plaka) && !$arac_kayitli_mi && !$arac_iceride_mi);
            ?>
            <?php if (!$sonuc_var): ?>
                <p class="admin-sag-bos">Plaka arattığınızda uyarılar, kayıtlı araç bilgisi veya yeni kayıt formu burada görünür.</p>
            <?php endif; ?>
            <?= $mesaj ?>

            <?php if ($arac_kayitli_mi && $musteri_bilgisi && !$arac_iceride_mi): ?>
                <h3>Araç bilgileri</h3>
                <table>
                    <tr><th>Müşteri</th><td><?= htmlspecialchars($musteri_bilgisi['musteriad']) ?></td></tr>
                    <tr><th>Telefon</th><td><?= htmlspecialchars($musteri_bilgisi['telefon']) ?></td></tr>
                    <tr><th>Araç tipi</th><td><?= htmlspecialchars($musteri_bilgisi['aractipi']) ?></td></tr>
                    <tr><th>Ücret</th><td><?= htmlspecialchars($musteri_bilgisi['saatlikucret']) ?> TL</td></tr>
                </table>
                <form method="POST" style="margin-top: 15px;">
                    <input type="hidden" name="musteri_id" value="<?= $musteri_bilgisi['musteriID'] ?>">
                    <button type="submit" name="giris_yap" style="background-color: #28a745;">Girişi Onayla</button>
                </form>
                <form method="POST" onsubmit="return confirm('Silinsin mi?');">
                    <input type="hidden" name="silinecek_musteri_id" value="<?= $musteri_bilgisi['musteriID'] ?>">
                    <button type="submit" name="musteri_sil" style="background-color: #dc3545;">Kaydı Sil</button>
                </form>
            <?php elseif (!empty($sorgulanan_plaka) && !$arac_kayitli_mi && !$arac_iceride_mi): ?>
                <h3>Yeni araç kaydı</h3>
                <form method="POST">
                    <input type="hidden" name="yeni_plaka" value="<?= htmlspecialchars($sorgulanan_plaka) ?>">
                    <label>Ad Soyad:</label> <input type="text" name="musteriad" required>
                    <label>Telefon:</label> <input type="text" name="telefon" placeholder="0 555 555 55 55" required>
                    <label>Araç tipi:</label>
                    <select name="aractipi" id="aractipi" onchange="ucretiOtomatikDoldur()" required>
                        <option value="">Seçiniz...</option>
                        <option value="Otomobil">Otomobil</option>
                        <option value="SUV">SUV</option>
                        <option value="Motor">Motor</option>
                        <option value="Minibüs">Minibüs</option>
                    </select>
                    <label>Saatlik ücret:</label>
                    <input type="number" step="0.01" name="saatlikucret" id="saatlikucret" required>
                    <button type="submit" name="yeni_kayit">Kaydet</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function ucretiOtomatikDoldur() {
        const ucretler = { "Otomobil": "50.00", "SUV": "70.00", "Motor": "25.00", "Minibüs": "90.00" };
        document.getElementById("saatlikucret").value = ucretler[document.getElementById("aractipi").value] || "";
    }

    const video = document.getElementById('kamera');
    const canvas = document.getElementById('canvas');
    const ocrDurum = document.getElementById('ocrDurum');
    const plakaInput = document.getElementById('plakaInput');
    if (plakaInput) {
        plakaInput.addEventListener('input', () => {
            const p = plakaInput.selectionStart;
            plakaInput.value = plakaInput.value.toUpperCase();
            if (p != null) plakaInput.setSelectionRange(p, p);
        });
    }

    document.getElementById('kameraAcBtn').onclick = async () => {
        try {
            video.srcObject = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
            document.getElementById('kameraAcBtn').style.display = 'none';
            document.getElementById('plakaOkuBtn').style.display = 'inline-block';
            ocrDurum.innerText = "Kamera Aktif";
        } catch (e) { ocrDurum.innerText = "Kamera Hatası"; }
    };

    document.getElementById('plakaOkuBtn').onclick = async () => {
        ocrDurum.innerText = "Tanımlanıyor...";
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        const imageData = canvas.toDataURL('image/jpeg', 0.8);

        try {
            const res = await fetch('ocr_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ base64_image: imageData })
            });
            const data = await res.json();
            if (data.status === "success") {
                plakaInput.value = data.plaka;
                document.getElementById('plakaForm').submit();
            } else {
                ocrDurum.innerText = data.message ? ("Okunamadı: " + data.message) : "Okunamadı";
            }
        } catch (e) {
            ocrDurum.innerText = "Bağlantı hatası (OCR servisi)";
        }
    };
</script>
</body>
</html>