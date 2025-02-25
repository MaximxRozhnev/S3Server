<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
session_start();

// Подключение к S3
$s3 = getS3Client();
$bucket = $_SESSION['bucket_modes'][$_SESSION['bucket']]; // Получаем имя bucket из сессии

$request = json_decode(file_get_contents('php://input'), true);
$filePath = $request['filePath'];

$response = ['success' => false, 'exists' => false];

if (empty($filePath)) {
    echo json_encode($response);
    exit;
}

try {
    // Проверка существования файла на S3
    $headObject = $s3->headObject([
        'Bucket' => $bucket,
        'Key' => $filePath,
    ]);

    // Если файл существует, он вернет метаданные, и мы знаем, что он существует
    if ($headObject) {
        $response['success'] = true;
        $response['exists'] = true;
    }
} catch (Aws\S3\Exception\S3Exception $e) {
    // Файл не существует
    $response['success'] = true;
    $response['exists'] = false;
}

echo json_encode($response);
?>
