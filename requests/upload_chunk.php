<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
require '../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

header('Content-Type: application/json; charset=utf-8');

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'], $_POST['path'], $_POST['uploadId'], $_POST['partNumber'])) {
    echo json_encode(['success' => false, 'message' => 'Неверные параметры.']);
    exit;
}

$s3 = getS3Client();
$bucket = $_SESSION['bucket_modes'][$_SESSION['bucket']];
$file = $_FILES['file'];
$path = $_POST['path'];
$uploadId = $_POST['uploadId'];
$partNumber = (int)$_POST['partNumber'];

try {
    $result = $s3->uploadPart([
        'Bucket'     => $bucket,
        'Key'        => $path,
        'UploadId'   => $uploadId,
        'PartNumber' => $partNumber,
        'Body'       => fopen($file['tmp_name'], 'rb')
    ]);

    $_SESSION['uploaded_parts'][$uploadId][$partNumber] = $result['ETag'];

    echo json_encode(['success' => true, 'ETag' => $result['ETag']]);
} catch (AwsException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
