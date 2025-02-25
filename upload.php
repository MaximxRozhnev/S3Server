<?php
session_start();
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Проверяем наличие файла
if (!isset($_FILES['file'])) {
    die(json_encode(["error" => "Файл не загружен."]));
}

$filePath = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$bucketName = 'mtest';
$key = 'uploads/' . $fileName;
$partSize = 8 * 1024 * 1024; // 8MB
$fileSize = filesize($filePath);
$totalParts = ceil($fileSize / $partSize);

// Настройки S3
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-west-1',
    'use_path_style_endpoint' => true,
    'credentials' => [
        'key'    => 'admin-6895844507',
        'secret' => 'Vv!rFMv(9x',
    ],
    'endpoint' => 'https://ru-msk-avt-1.store.cloud.mts.ru'
]);

try {
    // 1️⃣ **Создаём загрузку или возобновляем старую**
    if (!isset($_SESSION['uploadId'])) {
        $result = $s3->createMultipartUpload([
            'Bucket' => $bucketName,
            'Key'    => $key,
        ]);
        $_SESSION['uploadId'] = $result['UploadId'];
        $_SESSION['parts'] = [];
    }

    $uploadId = $_SESSION['uploadId'];

    $file = fopen($filePath, 'rb');
    $partNumber = count($_SESSION['parts']) + 1;

    // 2️⃣ **Загружаем оставшиеся части**
    while (!feof($file) && $partNumber <= $totalParts) {
        if (isset($_SESSION['parts'][$partNumber])) {
            $partNumber++;
            continue;
        }

        $partData = fread($file, $partSize);

        try {
            $uploadPart = $s3->uploadPart([
                'Bucket'     => $bucketName,
                'Key'        => $key,
                'UploadId'   => $uploadId,
                'PartNumber' => $partNumber,
                'Body'       => $partData,
            ]);

            $_SESSION['parts'][$partNumber] = [
                'PartNumber' => $partNumber,
                'ETag'       => $uploadPart['ETag'],
            ];

            $_SESSION['progress'] = round(($partNumber / $totalParts) * 100, 2);

        } catch (AwsException $e) {
            fclose($file);
            die(json_encode(["error" => "Ошибка загрузки части $partNumber: " . $e->getMessage()]));
        }

        $partNumber++;
    }
    fclose($file);

    // 3️⃣ **Если загружены все части — завершаем загрузку**
    if (count($_SESSION['parts']) == $totalParts) {
        $s3->completeMultipartUpload([
            'Bucket'   => $bucketName,
            'Key'      => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => array_values($_SESSION['parts']),
            ],
        ]);

        unset($_SESSION['uploadId']);
        unset($_SESSION['parts']);
        $_SESSION['progress'] = 100;

        echo json_encode(["success" => "Файл загружен!"]);
    } else {
        echo json_encode(["progress" => $_SESSION['progress'], "message" => "Части загружаются..."]);
    }
} catch (AwsException $e) {
    echo json_encode(["error" => "Ошибка: " . $e->getMessage()]);
}
