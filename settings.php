<?php
require __DIR__ . '/vendor/autoload.php';
use Aws\S3\S3Client;
// Настройки базы данных MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'name');
define('DB_USER', 'user');
define('DB_PASSWORD', 'pass');

// Настройки S3
define('S3_REGION', 'us-west-1'); // Например, 'us-east-1'
define('S3_ACCESS_KEY', 'ACCESS_KEY');
define('S3_SECRET_KEY', 'SECRET_KEY');
define('S3_DEFAULT_BUCKET', 1); // ID бакета из таблицы modes
define('S3_ENDPOINT', 'ENDPOINT');

// Настройки почты (SMTP)
define('SMTP_HOST', 'host');
define('SMTP_PORT', 0);
define('SMTP_USER', 'user');
define('SMTP_PASSWORD', 'pass');
define('SMTP_FROM_EMAIL', 'email');
define('SMTP_FROM_NAME', 'name');

// Подключение к MySQL через MySQLi
function getDatabaseConnection() {
    static $connection = null;

    // Используем кеширование подключения для оптимизации
    if ($connection === null) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if ($connection->connect_error) {
            die('Ошибка подключения к базе данных: ' . $connection->connect_error);
        }

        $connection->set_charset("utf8"); // Установка кодировки UTF-8
    }

    return $connection;
}

// Подключение к S3
function getS3Client() {
    return new S3Client([
      'version' => 'latest',
      'region'  	=> S3_REGION,
      'use_path_style_endpoint' => true,
      'credentials' => [
          'key'	=> S3_ACCESS_KEY,
          'secret' => S3_SECRET_KEY,
      ],
      'endpoint' => S3_ENDPOINT // Исправленный endpoint
    ]);
}

// Функция отправки письма через SMTP
function sendMail($to, $subject, $message, $isHtml = false) {
    require_once 'vendor/autoload.php'; // Подключение PHPMailer

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Настройки SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Шифрование
        $mail->Port = SMTP_PORT;

        // Настройка отправителя и получателя
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Установка кодировки
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Настройка темы письма
        $mail->Subject = $subject;

        // Настройки формата письма
        if ($isHtml) {
            $mail->isHTML(true);
            $mail->Body = $message; // HTML-тело письма
        } else {
            $mail->isHTML(false);
            $mail->Body = $message; // Текстовое тело письма
        }

        $mail->send();
        return true;
    } catch (PHPMailer\PHPMailer\Exception $e) {
        return 'Ошибка отправки письма: ' . $mail->ErrorInfo;
    }
}
