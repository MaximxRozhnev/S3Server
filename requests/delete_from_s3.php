<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
require '../vendor/autoload.php'; // Путь к автозагрузчику Composer

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

header('Content-Type: application/json; charset=utf-8');
session_start();

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Используйте POST-запрос для удаления.']);
    exit;
}

// Получаем данные из тела запроса
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['filePath']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Не переданы необходимые данные.']);
    exit;
}

$filePath = $data['filePath'];
$action = $data['action']; // Тип действия: 'deleteFile', 'deleteConclusion', 'deleteExamination'
$examinationId = isset($data['examinationId']) ? (int)$data['examinationId'] : null;
$isConclusion = isset($data['isConclusion']) ? (bool)$data['isConclusion'] : false;
$deleteLocally = $data['deleteLocally'] ?? false; // Флаг локального удаления

// Подключение к базе данных
$connection = getDatabaseConnection();
if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Не удалось подключиться к базе данных.']);
    exit;
}

try {
    // В PHP коде проверяем тип действия и удаляем нужный файл
    if ($action === 'deleteFile') {
        // Удаляем только файл
        if ($deleteLocally) {
            // Локальное удаление
            if (file_exists($filePath)) {
                unlink($filePath);
                echo json_encode(['success' => true, 'message' => 'Файл успешно удален локально.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Файл не найден для удаления.']);
            }
        } else {
            // Удаление из S3
            $s3 = getS3Client(); // Получаем экземпляр клиента S3
            $bucket = $_SESSION['bucket_modes'][$_SESSION['bucket']]; // Получаем имя bucket из сессии
    
            $s3->deleteObject([
                'Bucket' => $bucket,
                'Key' => $filePath,
            ]);
            echo json_encode(['success' => true, 'message' => 'Файл успешно удален из S3.']);
        }
    } elseif ($action === 'deleteConclusion') {
        // Удаляем файл заключения и запись в таблице results
        if (!$examinationId) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID обследования для удаления заключения.']);
            exit;
        }
    
        if (!$deleteLocally) {
            $s3 = getS3Client();
            $bucket = $_SESSION['bucket_modes'][$_SESSION['bucket']];
            $s3->deleteObject([
                'Bucket' => $bucket,
                'Key' => $filePath,
            ]);
        } else {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    
        $stmt = $connection->prepare('DELETE FROM results WHERE Examination_id = ?');
        $stmt->bind_param('i', $examinationId);
        $stmt->execute();
    
        echo json_encode(['success' => true, 'message' => 'Заключение успешно удалено.']);
    } elseif ($action === 'deleteExamination') {
        // Удаляем обследование, заключение и их записи в БД
        if (!$examinationId) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID обследования для удаления.']);
            exit;
        }
    
        // Получаем путь к файлу обследования
        $stmtExam = $connection->prepare('SELECT Path FROM examinations WHERE ID = ?');
        $stmtExam->bind_param('i', $examinationId);
        $stmtExam->execute();
        $resultExam = $stmtExam->get_result();
    
        if ($row = $resultExam->fetch_assoc()) {
            $examPath = $row['Path'];
            if ($deleteLocally) {
                if (file_exists($examPath)) {
                    unlink($examPath);
                }
            } else {
                $s3 = getS3Client();
                $bucket = $_SESSION['bucket_modes'][$_SESSION['bucket']];
                $s3->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $examPath,
                ]);
            }
        }
    
        // Удаление заключения (если есть)
        $stmtResult = $connection->prepare('SELECT Path FROM results WHERE Examination_id = ?');
        $stmtResult->bind_param('i', $examinationId);
        $stmtResult->execute();
        $result = $stmtResult->get_result();
    
        while ($row = $result->fetch_assoc()) {
            $resultPath = $row['Path'];
            if ($deleteLocally) {
                if (file_exists($resultPath)) {
                    unlink($resultPath);
                }
            } else {
                $s3->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $resultPath,
                ]);
            }
        }
    
        $stmtResult = $connection->prepare('DELETE FROM results WHERE Examination_id = ?');
        $stmtResult->bind_param('i', $examinationId);
        $stmtResult->execute();
    
        // Удаление обследования из базы
        $stmtExamDelete = $connection->prepare('DELETE FROM examinations WHERE ID = ?');
        $stmtExamDelete->bind_param('i', $examinationId);
        $stmtExamDelete->execute();
    
        echo json_encode(['success' => true, 'message' => 'Обследование, связанные заключения и файлы успешно удалены.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при выполнении операции: ' . $e->getMessage()]);
} finally {
    $connection->close();
}
