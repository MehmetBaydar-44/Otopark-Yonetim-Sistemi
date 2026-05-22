<?php
// 1. PHP'nin saat dilimini Türkiye olarak ayarlıyoruz
date_default_timezone_set('Europe/Istanbul');

$host = 'localhost';
$dbname = 'otopark';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. MySQL'in saat dilimini Türkiye (+03:00) olarak ayarlıyoruz
    $db->exec("SET time_zone = '+03:00';");
    
} catch(PDOException $e) {
    echo "Bağlantı hatası: " . $e->getMessage();
    exit;
}
?>