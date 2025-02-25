<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
$connection = getDatabaseConnection();

$patient_id = $_GET['patient_id'] ?? 0;

// Запрос на получение количества обследований (M) и заключений (N)
$stmt = $connection->prepare("
    SELECT 
        COUNT(e.ID) AS examinationsCount,
        COUNT(r.ID) AS resultsCount
    FROM examinations e
    LEFT JOIN results r ON e.ID = r.Examination_id
    WHERE e.Patient_id = ?
");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$stmt->bind_result($examinationsCount, $resultsCount);
$stmt->fetch();
$stmt->close();

// Возвращаем JSON
echo json_encode([
    'examinationsCount' => $examinationsCount,
    'resultsCount' => $resultsCount
]);
?>
