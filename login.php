<?php
session_start();

$step = $_GET['step'] ?? 'login'; // Текущий шаг (login или verify)
$error = $_SESSION['error'] ?? null; // Сообщения об ошибках
unset($_SESSION['error']); // Удаляем ошибку после вывода

$login = $_GET['login'] ?? ''; // Логин, если он был передан через GET
$password = $_GET['password'] ?? ''; // Пароль, если он был передан через GET
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="css/auth.css?ver=<?= time() ?>">
</head>

<body>
<div class="auth-container">
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 'login'): ?>
        <form action="login_process.php?action=login" method="POST" class="auth-form">
            <h2>Вход</h2>
            <input type="text" name="login" placeholder="Логин" value="<?= htmlspecialchars($login) ?>" required>
            <input type="password" name="password" placeholder="Пароль" value="<?= htmlspecialchars($password) ?>" required>
            <button type="submit">Войти</button>
        </form>
    <?php elseif ($step === 'verify'): ?>
        <form action="login_process.php?action=verify" method="POST" class="auth-form">
            <h2>Подтверждение</h2>
            <input type="text" name="code" placeholder="Код подтверждения" required>
            <button type="submit">Подтвердить</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
