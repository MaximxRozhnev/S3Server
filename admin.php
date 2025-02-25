<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';  // Путь к автозагрузчику Composer

use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;

// Подключение к S3


$connection = getDatabaseConnection();
// Проверяем подключение
if ($connection->connect_error) {
    die('Ошибка подключения: ' . $connection->connect_error);
}

// Проверяем ключ безопасности
$secureKey = 'thisisprivatekey';
if (!isset($_GET['key']) || $_GET['key'] !== $secureKey) {
    die('Доступ запрещен');
}

// Функция для получения данных
function fetchData($connection, $table) {
    $result = $connection->query("SELECT * FROM $table");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Получаем список врачей (users с Role_id = 2)
$doctors = $connection->query("SELECT ID, FIO FROM users WHERE Role_id = 2")->fetch_all(MYSQLI_ASSOC);

// Получаем список режимов (Desk из modes)
$modes = $connection->query("SELECT ID, Desk FROM modes")->fetch_all(MYSQLI_ASSOC);

// Получаем данные из doctors_list с привязкой к FIO врача и Desk режима
$doctorAccessList = $connection->query("
    SELECT d.ID, u.FIO, m.Desk 
    FROM doctors_list d
    JOIN users u ON d.User_id = u.ID
    JOIN modes m ON d.Mode_id = m.ID
")->fetch_all(MYSQLI_ASSOC);

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $_SESSION['message'] = ''; // Очистка сообщения перед новым действием
        switch ($_POST['action']) {
            case 'add_user':
                $stmt = $connection->prepare("SELECT COUNT(*) FROM users WHERE Login = ?");
                $stmt->bind_param('s', $_POST['login']);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
    
                if ($count == 0) {
                    $stmt = $connection->prepare("INSERT INTO users (Login, Password, FIO, Email, Role_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('ssssi', $_POST['login'], $_POST['password'], $_POST['fio'], $_POST['email'], $_POST['role_id']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $_SESSION['message'] = 'Ошибка: Пользователь с таким логином уже существует!';
                }
                break;
    
            case 'create_mode':
                $stmt = $connection->prepare("SELECT COUNT(*) FROM modes WHERE Name = ?");
                $stmt->bind_param('s', $_POST['name']);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
    
                if ($count == 0) {
                    $stmt = $connection->prepare("INSERT INTO modes (Name, Desk) VALUES (?, ?)");
                    $stmt->bind_param('ss', $_POST['name'], $_POST['desk']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $_SESSION['message'] = 'Ошибка: Такой режим уже существует!';
                }
                break;
    
            case 'add_doctor_access':
                $stmt = $connection->prepare("SELECT COUNT(*) FROM doctors_list WHERE User_id = ? AND Mode_id = ?");
                $stmt->bind_param('ii', $_POST['user_id'], $_POST['mode_id']);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
    
                if ($count == 0) {
                    $stmt = $connection->prepare("INSERT INTO doctors_list (User_id, Mode_id) VALUES (?, ?)");
                    $stmt->bind_param('ii', $_POST['user_id'], $_POST['mode_id']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $_SESSION['message'] = 'Ошибка: Этот врач уже имеет доступ к выбранному режиму!';
                }
                break;
        
            // Удаление записей без изменений
            case 'delete_user':
                $stmt = $connection->prepare("DELETE FROM users WHERE ID = ?");
                $stmt->bind_param('i', $_POST['id']);
                $stmt->execute();
                $stmt->close();
                break;
        
            case 'delete_mode':
                $stmt = $connection->prepare("DELETE FROM modes WHERE ID = ?");
                $stmt->bind_param('i', $_POST['id']);
                $stmt->execute();
                $stmt->close();
                break;
        
            case 'delete_doctor_access':
                $stmt = $connection->prepare("DELETE FROM doctors_list WHERE ID = ?");
                $stmt->bind_param('i', $_POST['id']);
                $stmt->execute();
                $stmt->close();
                break;
        }

    }
    header("Location: admin.php?key=$secureKey");
    exit;
}

$roles = fetchData($connection, 'roles');
$users = fetchData($connection, 'users');
$accessList = fetchData($connection, 'patient_doctors_access');
$modes = fetchData($connection, 'modes');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" type="text/css" href="/css/admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="/css/hint.css?v=<?php echo time(); ?>">
    <style>
        
    </style>
</head>
<body>
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert" id="alert-box">
            <span><?= $_SESSION['message']; ?></span>
            <button class="close-btn" onclick="document.getElementById('alert-box').style.display='none'">Закрыть</button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <h1>Админ-панель</h1>
    <form action="index.php" method="get">
        <button type="submit">На главную</button>
    </form>

    <div class="action-buttons">
        <h2>Пользователи</h2>
        <div>
            <h2>Добавить пользователя</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <input type="text" name="login" placeholder="Логин" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <input type="text" name="fio" placeholder="ФИО" required>
                <input type="email" name="email" placeholder="Email" required>
                <select name="role_id" required class="styled-select">
                    <?php
                    foreach ($roles as $role) {
                        echo "<option value='{$role['ID']}'>{$role['Desk']}</option>";
                    }
                    ?>
                </select>
                <button type="submit">Добавить</button>
            </form>
        </div>
    </div>

    <table>
        <tr><th>ID</th><th>Логин</th><th>Пароль</th><th>ФИО</th><th>Email</th><th>Роль</th><th>Действие</th></tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['ID'] ?></td>
                <td><?= $user['Login'] ?></td>
                <td><?= $user['Password'] ?></td>
                <td><?= $user['FIO'] ?></td>
                <td><?= $user['Email'] ?></td>
                <td>
                    <?php
                    foreach ($roles as $role) {
                        if ($role['ID'] == $user['Role_id']) {
                            echo $role['Desk'];
                            break;
                        }
                    }
                    ?>
                </td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="id" value="<?= $user['ID'] ?>">
                        <button type="submit">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>


    <div class="action-buttons">
        <h2 class="hint-container">Режимы:
            <span class="hint" style="font-size: 14px;">Bucket вручную надо создать в S3 хранилище. Здесь оно взаимодействует только с БД.</span>
        </h2> 
        <div>
            <h2>Добавить режим</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_mode">
                <input type="text" name="name" placeholder="Название" required>
                <input type="text" name="desk" placeholder="Описание" required>
                <button type="submit">Добавить</button>
            </form>
        </div>
    </div>

    <table>
        <tr><th>ID</th><th>Название</th><th>Описание</th><th>Действие</th></tr>
        <?php foreach ($modes as $mode): ?>
            <tr>
                <td><?= $mode['ID'] ?></td>
                <td><?= $mode['Name'] ?></td>
                <td><?= $mode['Desk'] ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_mode">
                        <input type="hidden" name="id" value="<?= $mode['ID'] ?>">
                        <button type="submit">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <div class="action-buttons">
        <h2>Управление доступом врачей</h2>
        <div>
            <h2>Добавить доступ</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_doctor_access">
                <select name="user_id" required class="styled-select">
                    <option value="">Выберите врача</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= $doctor['ID'] ?>"><?= $doctor['FIO'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="mode_id" required class="styled-select">
                    <option value="">Выберите режим</option>
                    <?php foreach ($modes as $mode): ?>
                        <option value="<?= $mode['ID'] ?>"><?= $mode['Desk'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Добавить</button>
            </form>
        </div>
    </div>
    
    <table>
        <tr><th>ID</th><th>Врач</th><th>Режим</th><th>Действие</th></tr>
        <?php foreach ($doctorAccessList as $access): ?>
            <tr>
                <td><?= $access['ID'] ?></td>
                <td><?= $access['FIO'] ?></td>
                <td><?= $access['Desk'] ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_doctor_access">
                        <input type="hidden" name="id" value="<?= $access['ID'] ?>">
                        <button type="submit">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</table>
</body>
</html>
