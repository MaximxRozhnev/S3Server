<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field'])) {
    $field = $_POST['field'];

    if (isset($_SESSION[$field])) {
        echo json_encode($_SESSION[$field]); // Возвращаем JSON
    } else {
        echo json_encode(null); // Возвращаем null, если поле не найдено
    }
} else {
    echo json_encode(null);
}
?>
