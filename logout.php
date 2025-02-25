<?php
session_start();

// Проверяем, если кнопка "logout" была нажата
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();

    // Перенаправляем на страницу входа или главную страницу
    header("Location: login.php");
    exit;
}
?>
