<?php
session_start();
// Проверка авторизации
if (!isset($_SESSION['login'])) {
    header('Location: login.php'); // Перенаправление на страницу авторизации
    exit();
}
?>
