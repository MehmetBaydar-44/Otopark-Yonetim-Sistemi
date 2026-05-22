<?php
require 'baglanti.php';

// Kapasite Bilgisini Veritabanından Çekme
$toplam_yer = $db->query("SELECT COUNT(*) FROM alanlar")->fetchColumn();
$bos_yer = $db->query("SELECT COUNT(*) FROM alanlar WHERE alandurum = 'BOŞ'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Güvenli Otopark - Ana Sayfa</title>
    <link rel="stylesheet" href="ana_sayfa.css">
</head>
<body>


    <header>
        <h1>🚗 Güvenli Otopark Sistemleri</h1>
        <p>Aracınız bizimle güvende</p>
    </header>

    <div class="container">
        <div class="card">
            <h2>Otopark Durumu</h2>
            <div class="capacity-box">
                <h3>Şu Anki Boş Yer Sayısı</h3>
                <div class="number"><?= $bos_yer ?> / <?= $toplam_yer ?></div>
                <p>Otoparkımız hizmete açıktır.</p>
            </div>

            <h2>Saatlik Fiyat Listesi</h2>
            <table>
                <tr><th>Araç Tipi</th><th>Saatlik Ücret (TL)</th></tr>
                <tr><td>Otomobil</td><td>50.00 ₺</td></tr>
                <tr><td>SUV</td><td>70.00 ₺</td></tr>
                <tr><td>Motor</td><td>25.00 ₺</td></tr>
                <tr><td>Minibüs</td><td>90.00 ₺</td></tr>
            </table>
            <p style="font-size: 0.85rem; color: #666; margin-top: 10px;">* Ücretlendirme, otoparkta kalınan saat ile aracınızın saatlik ücretinin çarpılmasıyla hesaplanır. Küsuratlı saatler yukarı yuvarlanır.</p>
        </div>

        <div class="card">
            <h2>Güncel Borç Sorgulama</h2>
            <p style="margin-bottom: 1.5rem; color: #666;">Otoparktaki aracınızın şu anki ücretini öğrenmek için bilgilerinizi giriniz.</p>
            
            <form id="query-form">
                <div class="form-group">
                    <label>Ad Soyad (Sisteme Kayıtlı Olan)</label>
                    <input type="text" name="adsoyad" id="adsoyad" placeholder="Örn: Ahmet Yılmaz" required>
                </div>
                
                <div class="form-group">
                    <label>Araç Plakası</label>
                    <input type="text" name="plaka" id="plaka" placeholder="Örn: 34ABC123" required>
                </div>

                <button type="submit">Ücretimi Sorgula</button>
            </form>

            <div id="result-box">
                <p id="result-message"></p>
                <h2 id="result-price" style="margin-top: 10px;"></h2>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('query-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const resultBox = document.getElementById('result-box');
            const resultMessage = document.getElementById('result-message');
            const resultPrice = document.getElementById('result-price');

            fetch('sorgula.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                resultBox.style.display = 'block';
                
                if(data.status === 'success') {
                    resultBox.className = 'success-box';
                    resultMessage.innerHTML = `Sayın <strong>${data.musteri}</strong>, <br> Aracınız <strong>${data.saat} saattir</strong> otoparkımızda bulunmaktadır.`;
                    resultPrice.innerHTML = `Şu Anki Tutar: ${data.ucret} ₺`;
                    resultPrice.style.color = '#155724';
                } else {
                    resultBox.className = 'error-box';
                    resultMessage.innerHTML = data.message;
                    resultPrice.innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                resultBox.style.display = 'block';
                resultBox.className = 'error-box';
                resultMessage.innerHTML = 'Sorgulama sırasında bir sistem hatası oluştu.';
            });
        });
    </script>
</body>
</html>