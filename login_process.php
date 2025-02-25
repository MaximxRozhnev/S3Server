<?php
# $_SESSION['bucket'] = 1 - по умолчанию, какой бакет выбран
# $_SESSION['user_data'] - Данные о пользователе
# $_SESSION['verify_code'] - Код подтверждения почты
# $_SESSION['error'] - Сохранение ошибки в процессе обработки
# $_SESSION['login'] - Авторизован ли пользователь
# $_SESSION['bucket_modes'] - Названия бакетов по ID

session_start();
include 'settings.php'; // Подключаем настройки базы данных и почтового клиента
require 'vendor/autoload.php'; // Подключение зависимостей Composer

$action = $_GET['action'] ?? null; // Действие, которое выполняется (login или verify)

$mysqli = getDatabaseConnection();

if ($action === 'login') {
    // Получаем логин и пароль из POST-запроса
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$login || !$password) {
        $_SESSION['error'] = 'Логин и пароль обязательны.';
        header('Location: login.php');
        exit;
    }

    // Получаем данные пользователя из базы данных
    $stmt = $mysqli->prepare("SELECT users.*, roles.Name AS user_role FROM users LEFT JOIN roles ON users.Role_id = roles.ID WHERE users.Login = ?");
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $_SESSION['error'] = 'Пользователь не найден.';
        header('Location: login.php');
        exit;
    }

    // Проверяем правильность пароля
    if ($password == $user['Password']) {
        // Успешная авторизация, генерируем код
        $_SESSION['user_data'] = $user;
        $code = rand(1000, 9999);
        $_SESSION['verify_code'] = $code;

        // Настройка данных для отправки email
        $email = $user['Email'];
        $subject = 'Код подтверждения';
        $message = "
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f9;
                    color: #333;
                    padding: 20px;
                }
                .container {
                    background-color: #ffffff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    max-width: 600px;
                    margin: auto;
                }
                .header {
                    font-size: 20px;
                    font-weight: bold;
                    color: #007BFF;
                    text-align: center;
                    margin-bottom: 20px;
                }
                .code {
                    font-size: 24px;
                    font-weight: bold;
                    color: #FF5722;
                    text-align: center;
                    margin: 20px 0;
                }
                .footer {
                    font-size: 12px;
                    color: #aaa;
                    margin-top: 20px;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>Код подтверждения</div>
                <p>Здравствуйте!</p>
                <p>Ваш код подтверждения:</p>
                <div class='code'>$code</div>
                <p>Пожалуйста, используйте этот код для завершения авторизации. Код действителен в течение 5 минут.</p>
                <div class='footer'>Если вы не запрашивали код, пожалуйста, проигнорируйте это сообщение.</div>
            </div>
        </body>
        </html>";

    
        // Отправка кода на email
        $result = sendMail($email, $subject, $message, true);
        
        if ($result !== true) {
            $_SESSION['error'] = 'Ошибка отправки письма: ' . $result;
            header('Location: login.php');
            exit;
        }

        // Переходим на страницу ввода кода
        header('Location: login.php?step=verify');
        exit;
    } else {
        $_SESSION['error'] = 'Неверный логин или пароль.';
        header('Location: login.php');
        exit;
    }

} elseif ($action === 'verify') {
    // Проверка кода подтверждения
    $code = $_POST['code'] ?? '';

    if (!isset($_SESSION['verify_code'])) {
        $_SESSION['error'] = 'Сессия верификации не найдена.';
        header('Location: login.php');
        exit;
    }

    // * [ Авторизовались!!! ] *
    if ($code == $_SESSION['verify_code']) {
        // Успешная верификация, сессия авторизована
        $_SESSION['bucket'] = S3_DEFAULT_BUCKET; // Устанавливаем bucket для сессии
        $_SESSION['login'] = true;
        
        //  ** [ Получение названия бакетов ] **
        $connection = $mysqli;
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
        // ------------------------------------
        
        unset($_SESSION['verify_code']); // Удаляем код из сессии
        header('Location: index.php'); // Перенаправляем на главную страницу
        exit;
    } else {
        $_SESSION['error'] = 'Неверный код подтверждения.';
        header('Location: login.php?step=verify');
        exit;
    }

} else {
    $_SESSION['error'] = 'Неизвестное действие.';
    header('Location: login.php');
    exit;
}

$mysqli->close();
