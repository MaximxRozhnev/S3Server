<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);
    $connection = getDatabaseConnection(); //$mysqli;
        
    $sql = "SELECT * FROM `modes` WHERE 1";
    $result = $connection->query($sql);
    
    if ($result === false) {
        echo json_encode(['success' => false, 'message' => 'Ошибка выполнения SQL: ' . $connection->error]);
        $connection->close();
        exit;
    }
    
    // Формируем массив
    $modes = [];
    while ($row = $result->fetch_assoc()) {
        $modes[$row['ID']] = $row['Name'];
    }
    $_SESSION['bucket_modes'] = $modes;
    $connection->close();
    
    
    
    if ($requestData['action'] === 'updateBucket' && isset($requestData['bucketId'])) {
        // Обновляем $_SESSION['bucket'] на новое значение
        $_SESSION['bucket'] = $requestData['bucketId'];
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Метод запроса не поддерживается']);
}
