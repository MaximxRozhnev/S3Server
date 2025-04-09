<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
$connection = getDatabaseConnection();

// Получаем JSON тело
$data = json_decode(file_get_contents('php://input'), true);
$patient_id = $data['patientId'] ?? 0;

if (!$patient_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Нет patient_id']);
    exit;
}

// Включаем транзакции
$connection->begin_transaction();

try {
    // Получаем все ID обследований пациента
    $stmt = $connection->prepare("SELECT ID FROM examinations WHERE Patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $examination_ids = [];
    while ($row = $result->fetch_assoc()) {
        $examination_ids[] = $row['ID'];
    }
    $stmt->close();

    // Удаляем из results
    if (!empty($examination_ids)) {
        $in = implode(',', array_fill(0, count($examination_ids), '?'));
        $types = str_repeat('i', count($examination_ids));
        $stmt = $connection->prepare("DELETE FROM results WHERE Examination_id IN ($in)");
        $stmt->bind_param($types, ...$examination_ids);
        $stmt->execute();
        $stmt->close();
    }

    // Удаляем из examinations
    $stmt = $connection->prepare("DELETE FROM examinations WHERE Patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->close();

    // Удаляем из patient_doctors_access
    $stmt = $connection->prepare("DELETE FROM patient_doctors_access WHERE Patient_ID = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $connection->prepare("DELETE FROM patients WHERE ID = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->close();

    $connection->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при удалении', 'details' => $e->getMessage()]);
}
?>
