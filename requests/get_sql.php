<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';

header('Content-Type: application/json; charset=utf-8');

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Используйте POST-запрос для выполнения SQL.']);
    exit;
}

// Читаем данные из POST-запроса
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['sql'])) {
    echo json_encode(['success' => false, 'message' => 'SQL-запрос не передан.']);
    exit;
}

$sql = $data['sql']; // Получаем SQL-запрос

// Подключаемся к базе данных
$connection = getDatabaseConnection();
if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Не удалось подключиться к базе данных.']);
    exit;
}

// Выполняем SQL-запрос
try {
    $result = $connection->query($sql);

    if ($result === false) {
        throw new Exception($connection->error);
    }

    // Обрабатываем результат запроса
    $response = [];
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка выполнения SQL: ' . $e->getMessage()]);
} finally {
    $connection->close();
}
