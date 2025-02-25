<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
require '../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Используйте POST-запрос.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['path'])) {
    echo json_encode(['success' => false, 'message' => 'Не передан путь файла.']);
    exit;
}

$s3 = getS3Client();
$bucket = $_SESSION['bucket_modes'][$_SESSION['bucket']];
$path = $data['path'];

if (!empty($_SESSION['active_uploads'][$path])) {
    echo json_encode(['success' => true, 'uploadId' => $_SESSION['active_uploads'][$path]]);
    exit;
}

try {
    $result = $s3->createMultipartUpload([
        'Bucket' => $bucket,
        'Key'    => $path,
    ]);

    $_SESSION['active_uploads'][$path] = $result['UploadId'];

    echo json_encode(['success' => true, 'uploadId' => $result['UploadId']]);
} catch (AwsException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка инициализации: ' . $e->getMessage()]);
}
?>
