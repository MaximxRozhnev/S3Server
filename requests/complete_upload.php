<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
require '../vendor/autoload.php';

use Aws\S3\S3Client;

header('Content-Type: application/json; charset=utf-8');

session_start();

$data = json_decode(file_get_contents('php://input'), true);
$s3 = getS3Client();
$bucket = $_SESSION['bucket_modes'][$_SESSION['bucket']];

if (empty($data['path']) || empty($data['uploadId']) || empty($data['parts'])) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных для завершения загрузки.']);
    exit;
}

$uploadId = $data['uploadId'];
$uploadedParts = $_SESSION['uploaded_parts'][$uploadId] ?? [];
if (!isset($_SESSION['uploaded_parts'][$uploadId])) {
    echo json_encode(['success' => false, 'message' => 'Не найдено данных для завершения загрузки.']);
    exit;
}


$missingParts = [];
foreach ($data['parts'] as $part) {
    if (!isset($uploadedParts[$part['PartNumber']])) {
        $missingParts[] = $part['PartNumber'];
    }
}

if (!empty($missingParts)) {
    echo json_encode(['success' => false, 'message' => 'Некоторые части отсутствуют', 'missingParts' => $missingParts]);
    exit;
}

// Упорядочиваем части
$sortedParts = [];
foreach ($uploadedParts as $partNumber => $etag) {
    $sortedParts[] = ['PartNumber' => $partNumber, 'ETag' => $etag];
}

try {
    $result = $s3->completeMultipartUpload([
        'Bucket' => $bucket,
        'Key' => $data['path'],
        'UploadId' => $uploadId,
        'MultipartUpload' => ['Parts' => $sortedParts]
    ]);

    unset($_SESSION['uploaded_parts'][$uploadId]);

    echo json_encode(['success' => true, 'location' => $result['Location']]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка завершения: ' . $e->getMessage(), 'code' => $e->getCode()]);
}
?>
