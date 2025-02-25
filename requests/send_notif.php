<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
require '../vendor/autoload.php';  // Путь к автозагрузчику Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendNotificationEmail($emailAddresses, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        // Настройки сервера
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPDebug = 0;
        $mail->Host = 'ssl://smtp.yandex.ru';
        $mail->Port = 465;
        $mail->Username = 'c3nter911@yandex.ru';
        $mail->Password = 'jqzlyjrlmpimzhdr';
        $mail->setFrom('c3nter911@yandex.ru', 'Center 911');

        // Получатели
        foreach ($emailAddresses as $email) {
            $mail->addAddress($email);
        }

        // Контент
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Создаем HTML-сообщение
        $mail->Body = '
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        color: #333;
                        background-color: #f9f9f9;
                        padding: 20px;
                    }
                    .container {
                        max-width: 600px;
                        margin: auto;
                        background-color: #fff;
                        border-radius: 8px;
                        box-shadow: 0 0 10px rgba(0,0,0,0.1);
                        padding: 20px;
                    }
                    h1 {
                        color: #4CAF50;
                    }
                    p {
                        font-size: 16px;
                        line-height: 1.5;
                    }
                    .button {
                        display: inline-block;
                        padding: 10px 20px;
                        font-size: 16px;
                        color: #fff;
                        background-color: #4CAF50;
                        text-decoration: none;
                        border-radius: 4px;
                        margin-top: 20px;
                    }
                    .button:hover {
                        background-color: #45a049;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>Новое обследование</h1>
                    <p>' . htmlspecialchars($message) . '</p>
                    <a href="https://s3server.center911.ru" class="button">Перейти</a>
                </div>
            </body>
            </html>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        return 'Сообщение не было отправлено. Ошибка: ' . $mail->ErrorInfo;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Устанавливаем заголовок для JSON-ответа
    $emails = json_decode($_POST['emails'] ?? '[]', true); // Декодируем JSON в массив
    $subject = $_POST['subject'] ?? " ";
    $message = $_POST['message'] ?? " ";
    
    if (!is_array($emails)) {
        echo json_encode(['status' => 'error', 'message' => 'Некорректный формат email-адресов']);
        exit;
    }

    //$subject = 'Новый пациент загружен';
    //$message = 'Вы можете просмотреть загруженное обследование, перейдя по следующей ссылке:';

    $result = sendNotificationEmail($emails, $subject, $message);

    echo json_encode(['status' => $result === true ? 'success' : 'error', 'message' => $result]);
    exit;
} else {
    header('Content-Type: application/json'); // Устанавливаем заголовок для JSON-ответа
    echo json_encode(['status' => 'error', 'message' => 'Неверный метод запроса']);
}
?>
