<?php
require 'baglanti.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adsoyad = trim($_POST['adsoyad']);
    $plaka = strtoupper(trim($_POST['plaka'])); // Plakayı sisteme uygun olarak büyütüyoruz

    // İçeride olan aracı bulmak için JOIN ile islemler ve musteriler tablolarını bağlıyoruz
    $sorgu = $db->prepare("
        SELECT m.musteriad, m.saatlikucret, i.girissaati 
        FROM musteriler m
        INNER JOIN islemler i ON m.musteriID = i.musteriID
        WHERE m.plaka = :plaka 
          AND m.musteriad = :adsoyad 
          AND i.cikissaati IS NULL
        LIMIT 1
    ");
    
    $sorgu->execute([
        'plaka' => $plaka,
        'adsoyad' => $adsoyad
    ]);
    
    $arac = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($arac) {
        // Çıkış panelinizdeki hesaplama mantığının birebir aynısı
        $cikis_zamani = date('Y-m-d H:i:s');
        
        $giris_obj = new DateTime($arac['girissaati']);
        $cikis_obj = new DateTime($cikis_zamani);
        $fark = $giris_obj->diff($cikis_obj);
        
        $toplam_saat = ($fark->days * 24) + $fark->h + ($fark->i / 60);
        $hesaplanacak_saat = ceil($toplam_saat); // Küsüratlı saati yukarı yuvarlar
        if ($hesaplanacak_saat == 0) { $hesaplanacak_saat = 1; } // En az 1 saat alınır

        $toplam_tutar = $hesaplanacak_saat * $arac['saatlikucret'];

        echo json_encode([
            'status' => 'success',
            'musteri' => htmlspecialchars($arac['musteriad']),
            'saat' => $hesaplanacak_saat,
            'ucret' => number_format($toplam_tutar, 2)
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Otoparkta aktif park halinde olan, girdiğiniz plaka ve isme ait bir kayıt bulunamadı. Lütfen bilgilerinizi kontrol ediniz.'
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
}
?>