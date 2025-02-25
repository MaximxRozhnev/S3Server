<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
header('Content-Type: application/json; charset=utf-8');
session_start();

// Подключение к базе данных
$connection = getDatabaseConnection();
if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Не удалось подключиться к базе данных.']);
    exit;
}

$request = json_decode(file_get_contents('php://input'), true);
$action = $request['action'] ?? null;

try {
    switch($action) {
        case 'getDoctors':
            if (isset($_SESSION['bucket'])) {
                $bucket = $connection->real_escape_string($_SESSION['bucket']);
                
                $query = "
                    SELECT u.ID as UserID, u.FIO, u.Email 
                    FROM users u
                    INNER JOIN doctors_list d ON u.ID = d.User_id
                    WHERE d.Mode_id = '$bucket'
                ";
                
                $result = $connection->query($query);
                $response = [];
                if ($result instanceof mysqli_result) {
                    while ($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
        
                echo json_encode(['success' => true, 'data' => $response]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Bucket не установлен в сессии.']);
            }
            exit;
            break;
            
        case 'getPatients':
            $doctor_ID = $connection->real_escape_string($_SESSION['user_data']['ID']);
            $role_ID = $_SESSION['user_data']['Role_id'];
            $bucket = $connection->real_escape_string($_SESSION['bucket']);
            $page = (int)($request['page'] ?? 1); // Текущая страница
            $limit = (int)($request['limit'] ?? 20); // Лимит записей на странице
            $offset = ($page - 1) * $limit; // Вычисляем смещение
        
            // Считаем общее количество записей
            $countQuery = "
                SELECT COUNT(*) AS total
                FROM patients
                WHERE Mode_id = '" . $bucket . "'
                  AND (
                    '" . (int)$role_ID . "' != 2
                    OR EXISTS (
                        SELECT 1
                        FROM patient_doctors_access
                        WHERE patient_doctors_access.Patient_ID = patients.ID
                          AND patient_doctors_access.Doctor_ID = '" . $doctor_ID . "'
                    )
                  )
            ";
            $countResult = $connection->query($countQuery);
            $totalRecords = 0;
            if ($countResult instanceof mysqli_result) {
                $totalRecords = $countResult->fetch_assoc()['total'] ?? 0;
            }
        
            // Получаем записи для текущей страницы
            $query = "
                SELECT *,
                       DATE_FORMAT(Birthday, '%d.%m.%Y') AS BirthdayFormatted
                FROM patients
                WHERE Mode_id = '" . $bucket . "'
                  AND (
                    '" . (int)$role_ID . "' != 2
                    OR EXISTS (
                        SELECT 1
                        FROM patient_doctors_access
                        WHERE patient_doctors_access.Patient_ID = patients.ID
                          AND patient_doctors_access.Doctor_ID = '" . $doctor_ID . "'
                    )
                  )
                LIMIT $limit OFFSET $offset
            ";
            $result = $connection->query($query);
        
            // Формируем данные ответа
            $response = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        
            echo json_encode([
                'success' => true,
                'data' => $response,
                'totalRecords' => $totalRecords,
                'page' => $page,
                'limit' => $limit
            ]);
            exit;
            break;

        case 'getExaminations':
            $patientId = $request['patientId'] ?? null;
        
            if (!$patientId || !is_numeric($patientId)) {
                echo json_encode(['success' => false, 'message' => 'Некорректный идентификатор пациента.']);
                exit;
            }
        
            $escapedPatientId = $connection->real_escape_string($patientId);
        
            // Объединяем данные из examinations и results
            $query = "
                SELECT 
                    e.*, 
                    r.ID AS Result_ID, 
                    r.Path AS Result_Path, 
                    r.Upload_by AS Result_Upload_by, 
                    r.Upload_date AS Result_Upload_date, 
                    r.Upload_time AS Result_Upload_time
                FROM examinations e
                LEFT JOIN results r ON e.ID = r.Examination_id
                WHERE e.Patient_id = '$escapedPatientId'
            ";
        
            $result = $connection->query($query);
        
            if ($result instanceof mysqli_result) {
                $response = [];
                while ($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
        
                echo json_encode(['success' => true, 'data' => $response]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка при выполнении запроса.']);
            }
            exit;
            break;

                
        case 'checkPath':
            $patientPath = $request['patientPath'] ?? '';
        
            if (empty($patientPath)) {
                echo json_encode(['success' => false, 'message' => 'Путь к файлу не указан.']);
                exit;
            }
        
            $escapedPath = $connection->real_escape_string($patientPath);
        
            $query = "
                SELECT COUNT(*) AS count 
                FROM examinations 
                WHERE Path = '$escapedPath'
            ";
        
            $result = $connection->query($query);
        
            if ($result instanceof mysqli_result) {
                $row = $result->fetch_assoc();
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка выполнения запроса.']);
            }
            exit;
            break;
        
        case 'insertExamination':
            $patientId = $request['patientId'] ?? null;
            $patientPath = $request['patientPath'] ?? '';
            $uploadBy = $request['uploadBy'] ?? null;
        
            if (!$patientId || empty($patientPath) || !$uploadBy) {
                echo json_encode(['success' => false, 'message' => 'Некорректные входные данные.']);
                exit;
            }
        
            $escapedPath = $connection->real_escape_string($patientPath);
            $uploadTime = date('H:i:s'); // Текущее время в формате HH:MM:SS

            $query = "
                INSERT INTO examinations (Patient_id, Path, Upload_date, Upload_time, Upload_by)
                VALUES ($patientId, '$escapedPath', CURDATE(), '$uploadTime', $uploadBy)
            ";

            if ($connection->query($query)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка выполнения запроса: ' . $connection->error]);
            }
            exit;
            break;

        case 'filterPatients':
            $searchInput = $request['searchInput'] ?? '';
            $birthdayFrom = $request['birthdayFrom'] ?? null;
            $birthdayTo = $request['birthdayTo'] ?? null;
            $alphabetOrder = $request['alphabetOrder'] ?? null;
            $recentPatients = $request['recentPatients'] ?? null;
            $bucket = $_SESSION['bucket'] ?? null;
            $role_ID = $_SESSION['user_data']['Role_id'] ?? null;
            $doctor_ID = $_SESSION['user_data']['ID'] ?? null;
        
            $page = (int)($request['page'] ?? 1);
            $limit = (int)($request['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
        
            if (!$bucket || !is_numeric($bucket)) {
                echo json_encode(['success' => false, 'message' => 'Некорректный идентификатор режима.']);
                exit;
            }
        
            $conditions = [
                "p.Mode_id = '$bucket'",
                "('$role_ID' != '2' OR EXISTS (
                    SELECT 1 
                    FROM patient_doctors_access 
                    WHERE patient_doctors_access.Patient_ID = p.ID
                      AND patient_doctors_access.Doctor_ID = '$doctor_ID'
                ))",
            ];
        
            if (is_numeric($recentPatients) && $recentPatients > 0) {
                $recentPatientsEscaped = (int)$recentPatients;
            
                // Выполняем запрос, ограниченный количеством $recentPatients
               $query = "
                    SELECT p.*, 
                           DATE_FORMAT(p.Birthday, '%d.%m.%Y') AS BirthdayFormatted,
                           COALESCE(exam_count.M, 0) AS M, 
                           COALESCE(res_count.N, 0) AS N
                    FROM patients p
                    INNER JOIN (
                        SELECT e.Patient_id, e.Upload_date, e.Upload_time
                        FROM examinations e
                        INNER JOIN (
                            SELECT Patient_id, MAX(CONCAT(Upload_date, ' ', Upload_time)) AS max_upload
                            FROM examinations
                            GROUP BY Patient_id
                        ) AS latest_exam
                        ON e.Patient_id = latest_exam.Patient_id 
                        AND CONCAT(e.Upload_date, ' ', e.Upload_time) = latest_exam.max_upload
                    ) AS e ON e.Patient_id = p.ID
                    LEFT JOIN (
                        SELECT Patient_id, COUNT(ID) AS M 
                        FROM examinations 
                        GROUP BY Patient_id
                    ) AS exam_count ON exam_count.Patient_id = p.ID
                    LEFT JOIN (
                        SELECT e.Patient_id, COUNT(r.ID) AS N 
                        FROM results r
                        INNER JOIN examinations e ON r.Examination_id = e.ID
                        GROUP BY e.Patient_id
                    ) AS res_count ON res_count.Patient_id = p.ID
                    WHERE " . implode(' AND ', $conditions) . "
                    ORDER BY e.Upload_date DESC, e.Upload_time DESC
                    LIMIT $recentPatientsEscaped
                ";


            
                $result = $connection->query($query);
            
                if ($result instanceof mysqli_result) {
                    $response = [];
                    while ($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
            
                    echo json_encode([
                        'success' => true,
                        'data' => $response,
                        'totalRecords' => $recentPatientsEscaped, // Показываем количество выбранных записей
                        'page' => 1, // Мы не делаем пагинацию, поэтому всегда 1
                        'limit' => $recentPatientsEscaped, // Мы выводим ровно столько записей, сколько указано
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка выполнения запроса: ' . $connection->error]);
                }
            } else {
                // Стандартная фильтрация без учета последних записей
                if (!empty($searchInput)) {
                    $searchInputEscaped = $connection->real_escape_string($searchInput);
                    $conditions[] = "(p.FIO COLLATE utf8_general_ci LIKE '%$searchInputEscaped%' 
                                     OR DATE_FORMAT(p.Birthday, '%d.%m.%Y') COLLATE utf8_general_ci LIKE '%$searchInputEscaped%')";
                }
        
                if (!empty($birthdayFrom) && !empty($birthdayTo)) {
                    $birthdayFromEscaped = $connection->real_escape_string($birthdayFrom);
                    $birthdayToEscaped = $connection->real_escape_string($birthdayTo);
                    $conditions[] = "p.Birthday BETWEEN '$birthdayFromEscaped' AND '$birthdayToEscaped'";
                }
        
                $orderBy = '';
                if ($alphabetOrder === 'ASC' || $alphabetOrder === 'DESC') {
                    $orderBy = "ORDER BY p.FIO $alphabetOrder";
                }
        
                // Считаем общее количество записей
                $countQuery = "
                    SELECT COUNT(*) AS total
                    FROM patients p
                    WHERE " . implode(' AND ', $conditions);
                $countResult = $connection->query($countQuery);
                $totalRecords = 0;
                if ($countResult instanceof mysqli_result) {
                    $totalRecords = $countResult->fetch_assoc()['total'] ?? 0;
                }
        
                // Получаем записи для текущей страницы
                $query = "
                    SELECT 
                        p.*, 
                        DATE_FORMAT(p.Birthday, '%d.%m.%Y') AS BirthdayFormatted,
                        COALESCE(exam_count.M, 0) AS M, 
                        COALESCE(res_count.N, 0) AS N
                    FROM patients p
                    LEFT JOIN (
                        SELECT Patient_id, COUNT(ID) AS M 
                        FROM examinations 
                        GROUP BY Patient_id
                    ) AS exam_count ON exam_count.Patient_id = p.ID
                    LEFT JOIN (
                        SELECT e.Patient_id, COUNT(r.ID) AS N 
                        FROM results r
                        INNER JOIN examinations e ON r.Examination_id = e.ID
                        GROUP BY e.Patient_id
                    ) AS res_count ON res_count.Patient_id = p.ID
                    WHERE " . implode(' AND ', $conditions) . "
                    $orderBy
                    LIMIT $limit OFFSET $offset
                ";

        
                $result = $connection->query($query);
        
                if ($result instanceof mysqli_result) {
                    $response = [];
                    while ($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
        
                    echo json_encode([
                        'success' => true,
                        'data' => $response,
                        'totalRecords' => $totalRecords,
                        'page' => $page,
                        'limit' => $limit
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка выполнения запроса: ' . $connection->error]);
                }
            }
            exit;

            
            
        case 'checkConclusion':
            $examinationId = $connection->real_escape_string($request['examinationId'] ?? null);
            if (!$examinationId) {
                echo json_encode(['success' => false, 'message' => 'Некорректный ID обследования.']);
                exit;
            }
        
            $query = "SELECT * FROM results WHERE Examination_id = '$examinationId'";
            $result = $connection->query($query);
            $data = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        
        case 'insertConclusion':
            $path = $connection->real_escape_string($request['path'] ?? '');
            $examinationId = $connection->real_escape_string($request['examinationId'] ?? null);
            $uploadBy = $connection->real_escape_string($request['uploadBy'] ?? null);
            $uploadDate = date('Y-m-d');
            $uploadTime = date('H:i:s');
        
            if (!$path || !$examinationId || !$uploadBy) {
                echo json_encode(['success' => false, 'message' => 'Некорректные данные для записи.']);
                exit;
            }
        
            $query = "
                INSERT INTO results (Path, Examination_id, Upload_by, Upload_date, Upload_time)
                VALUES ('$path', '$examinationId', '$uploadBy', '$uploadDate', '$uploadTime')
            ";
            if ($connection->query($query)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка записи в базу данных: ' . $connection->error]);
            }
            exit;
        case 'getEmailsForPatient': 
            $Patient_ID = $connection->real_escape_string($request['patientId'] ?? '0');
            $query = "
                SELECT u.Email 
                FROM patient_doctors_access pda
                JOIN users u ON pda.Doctor_ID = u.ID
                WHERE pda.Patient_ID = $Patient_ID;
            ";
            $result = $connection->query($query);
            $data = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
            
    


        default: 
            echo json_encode(['success' => false, 'message' => 'Не выбрано, что нужно получить']);
            exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}

// Закрываем соединение
$connection->close();
