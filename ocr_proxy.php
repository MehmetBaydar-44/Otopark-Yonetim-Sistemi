<?php
header('Content-Type: application/json; charset=utf-8');

$url = getenv('OTOPLAK_OCR_API') ?: 'http://127.0.0.1:8000/api/plaka-oku';

$body = file_get_contents('php://input');
if ($body === '' || $body === false) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Boş istek gövdesi.']);
    exit;
}

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Sunucuda cURL yüklü değil.']);
    exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 120,
]);

$response = curl_exec($ch);
$errno    = curl_errno($ch);
$errstr   = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'status'  => 'error',
        'message' => 'OCR servisine ulaşılamadı. ocr_api.py çalışıyor olmalı.',
        'detail'  => $errno ? "cURL #{$errno}: {$errstr}" : null,
    ]);
    exit;
}

if ($httpCode >= 400) {
    http_response_code($httpCode);
}

echo $response;
