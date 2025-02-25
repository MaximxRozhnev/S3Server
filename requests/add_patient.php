<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $name = $data['name'] ?? '';
    $dob = $data['dob'] ?? '';

    if (empty($name) || empty($dob)) {
        echo json_encode(['success' => false, 'message' => 'Поля ФИО и дата рождения обязательны.']);
        exit;
    }

    if (!isset($_SESSION['user_data']['ID']) || !isset($_SESSION['bucket'])) {
        echo json_encode(['success' => false, 'message' => 'Сессия не инициализирована или данные отсутствуют.']);
        exit;
    }

    $connection = getDatabaseConnection();
    if (!$connection) {
        echo json_encode(['success' => false, 'message' => 'Не удалось подключиться к базе данных.']);
        exit;
    }

    $stmt = $connection->prepare("SELECT * FROM patients WHERE FIO = ? AND Birthday = ? AND Mode_id = ?");
    $stmt->bind_param('ssi', $name, $dob, $_SESSION['bucket']);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Ошибка выполнения запроса: ' . $stmt->error]);
        exit;
    }

    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Пациент с таким ФИО и датой рождения уже существует.']);
    } else {
        $stmt = $connection->prepare("INSERT INTO `patients`(`FIO`, `Birthday`, `Created_by`, `Created_date`, `Mode_id`) VALUES (?, ?, ?, ?, ?)");
        $created_by = $_SESSION['user_data']['ID'];
        $created_date = date('Y-m-d');
        $mode_id = $_SESSION['bucket'];
        $stmt->bind_param('ssisi', $name, $dob, $created_by, $created_date, $mode_id);

        if ($stmt->execute()) {
            $patient_id = $connection->insert_id;
            // Получаем массив ID врачей из POST-запроса
            $doctor_ids = $data['doctor_ids']; // Массив ID выбранных врачей
    
            // Добавляем врачей в таблицу patient_doctors_access
            $stmtAccess = $connection->prepare("INSERT INTO `patient_doctors_access`(`Patient_ID`, `Doctor_ID`) VALUES (?, ?)");
            foreach ($doctor_ids as $doctor_id) {
                $stmtAccess->bind_param('ii', $patient_id, $doctor_id);
                $stmtAccess->execute();
            }
            
            
            // Работа с S3
            $s3 = getS3Client();
            $bucketName = $_SESSION['bucket_modes'][$_SESSION['bucket']]; // Ваше S3-хранилище
            $folderName = $name . '-' . $dob . '/'; // Имя папки (ФИО пациента)
    
            try {
                // Создаём папку
                $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key'    => $folderName,
                ]);

                echo json_encode(['success' => true, 'message' => 'Пациент успешно добавлен, папка создана в S3.']);
            } catch (Aws\Exception\AwsException $e) {
                echo json_encode(['success' => false, 'message' => 'Пациент добавлен, но произошла ошибка при работе с S3: ' .$bucketName . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении пациента: ' . $stmt->error]);
        }
    }

    $stmt->close();
}

