<?php 
    include 'settings.php'; // Подключаем настройки базы данных и почтового клиента
    include 'auth.php'; // Подключаем настройки базы данных и почтового клиента
    session_start();
    $user_data = $_SESSION['user_data'];
    $roleID = $user_data['Role_id'];  // Пример: роль берется из сессии
    //print_r($user_data);

    // ** [ Генерация кнопок левой панели ] **
    // Определяем переменные для каждой кнопки
    $showAdminButton = false;
    $showAddPatientButton = false; // Кнопка "Добавить пациента"
    $showAddExaminationButton = false; // Кнопка "Добавить обследование"
    $showAddConclusionButton = false; // Кнопка "Добавить заключение"
    $showSelectModeButton = false; // Кнопка "Выбрать режим"
    
    
    switch ($roleID) {
        case 1: // Admin
            $showAdminButton = true;
            $showAddPatientButton = true;
            $showAddExaminationButton = true;
            $showAddConclusionButton = true;
            $showSelectModeButton = true;
            break;
        case 2: // Vrach
            $showAddConclusionButton = true;
            $showSelectModeButton = true;
            break;
        case 3: // Laborant
            $showAddPatientButton = true;
            $showAddExaminationButton = true;
            $showSelectModeButton = true;
            break;
        case 4: // Reg
            $showAddPatientButton = false;
            $showAddExaminationButton = false;
            $showAddConclusionButton = false;
            $showSelectModeButton = true;
            break;
        default:
            // В случае неизвестной роли, не показываем кнопки
            $showAddPatientButton = false;
            $showAddExaminationButton = false;
            $showAddConclusionButton = false;
            $showSelectModeButton = false;
            break;
    }
    
    
    $connection = getDatabaseConnection();
    // Получаем режимы из базы данных
    $query = "SELECT * FROM `modes` WHERE 1";
    $result = $connection->query($query);
    
    $modeOptions = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $modeOptions[] = $row;
        }
    }
    
    $nowMode = "";
    $query = "SELECT Desk FROM `modes` WHERE ID = " . intval($_SESSION['bucket']); // Приведение к int для безопасности
    $result = $connection->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        $nowMode = $row['Desk'];
    }

    
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Медицинская система</title>
     <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <!-- Подключение Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=PT+Sans:wght@400;700&display=swap" rel="stylesheet">

    
    <!-- Подключение Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" type="text/css" href="/css/context-menu.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="/css/exam.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="/css/notification.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="/css/hint.css?v=<?php echo time(); ?>">
</head>
<body>
<!-- Прогресс-бар -->
<div id="progressOverlay" style="display: none;">
    <div id="progressContainer">
        <label for="progress">Загрузка:</label>
        <progress id="progress" value="0" max="100"></progress>
        <p id="progressText">0% (0 MB из 0 MB)</p>
    </div>
</div>

<!-- Уведомления -->
<div class="notification-container" id="notificationContainer"></div>

<div class="container">

    <!-- Левый контейнер -->
    <div class="left-panel">
        <p id="your-role"></p>
        <div class="button-block">
            <div class="user-info">
                <div class="user-icon">👤</div>
                <p class="user-name">
                    <?php echo $_SESSION['user_data']['FIO'] ?? 'Гость'; ?>
                </p>
            </div>
            
            <div class="mode-info">
                <div class="mode-icon">⚙️</div>
                <p class="mode-name">
                   <?php echo $nowMode ?? 'Режим не выбран'; ?>
                </p>
            </div>
            
            <form method="POST" action="logout.php">
                <button type="submit" name="logout" <button type="submit" name="logout" style=" background-color: #D32F2F;">Выход</button>
            </form>

            <?php 
            if($showAdminButton) {
                echo '<button id="adminButton">Панель администратора</button>';
            }
            if ($showAddPatientButton) {
                echo '<button id="addPatientButton">Добавить пациента</button>';
            }
            
            if ($showAddExaminationButton) {
                echo '<button id="addExaminationButton">Добавить обследование</button>';
            }
            
            if ($showAddConclusionButton) {
                echo '<button id="addConclusionButton">Добавить заключение</button>';
            }
            
            if ($showSelectModeButton) {
                // Генерируем HTML для кнопки и выпадающего списка
                echo '<button id="selectModeButton">Выбрать режим</button>';
                echo '<select id="modeSelect" style="display: none;">'; // Скрытый список
                
                // Проверяем если $_SESSION['bucket'] существует и выбираем его
                $selectedBucket = isset($_SESSION['bucket']) ? $_SESSION['bucket'] : null;
                
                foreach ($modeOptions as $mode) {
                    $selected = ($mode['ID'] == $selectedBucket) ? 'selected' : ''; // Выбираем, если ID совпадает с выбранным
                    echo '<option value="' . $mode['ID'] . '" ' . $selected . '>' . $mode['Desk'] . '</option>';
                }
                echo '</select>';
            }    
            
            ?>

        </div>

        <div class="filter-block">
            <label>
                Поиск по тексту:
                <input type="text" id="searchInput">
            </label>
            <label>
                Дата рождения (от):
                <input type="date" id="birthdayFrom">
            </label>
            <label>
                Дата рождения (до):
                <input type="date" id="birthdayTo">
            </label>
            <label>
                Алфавитный порядок:
                <select id="alphabetOrder">
                    <option value="">-- Не выбрано --</option>
                    <option value="ASC">От А до Я</option>
                    <option value="DESC">От Я до А</option>
                </select>
            </label>
            <label>
                <span class="hint-container">Последние N пациентов:
                    <span class="hint">Фильтр выводит N последних пациентов, которым добавляли обследование</span>
                </span> 
                
                <input type="number" id="recentPatients" min="1">
            </label>
            <button id="applyFilters">Применить</button>
            <button id="resetFilters">Сбросить</button>
        </div>

    </div>

    <!-- Правый контейнер -->
    <div class="right-panel">
        <h2>Список пациентов</h2> 
        <button id="refreshButton">Обновить список</button>
        <div id="paginationContainer" class="pagination-container"></div>
        <hr></hr>
        <div>
            <ul class="patient-list" id="patientList">
                <!-- Список пациентов будет динамически обновляться -->
            </ul>
        </div>
    </div>
    
</div>

<!-- Модальное окно для отображения обследований -->
<div id="examModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeExamModal">&times;</span>
        <h3>Обследования пациента</h3>
        <hr></hr>
        <ul id="examinationList">
            <!-- Список обследований будет динамически обновляться -->
        </ul>
    </div>
</div>

<!-- Модальное окно для добавления пациента[1/2] Данные пациента -->
<div id="patientModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closePatientModal">&times;</span>
        <h2>Добавить пациента</h2>
        <input type="text" id="patientName" placeholder="ФИО пациента">
        <input type="date" id="patientDob">
        <div class="modal-buttons">
            <button id="cancelPatientModal">Отмена</button>
            <button id="openPatientModalAddDoctorsButton">Далее</button>
        </div>
    </div>
</div>


<!-- Модальное окно для добавления пациента[2/2] Выбор доступа врачей -->
<div id="patientModalAddDoctors" class="modal">
    <div class="modal-content">
        <span class="close" id="closePatientModalAddDoctors">&times;</span>
        <h3>Выберите врачей для доступа</h3>
        <div id="doctorCheckboxList">
            <!-- Здесь будут динамически добавляться чекбоксы -->
        </div>
        <div class="modal-buttons">
            <button id="backPatientButton">Назад</button>
            <button id="savePatientButton">Сохранить</button>
        </div>
    </div>
</div>


<!-- Модальное окно для добавления обследования -->
<div id="examinationModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeExaminationModal">&times;</span>
        <h2>Добавить обследование</h2>
        <div>
            <input type="text" id="searchPatientInputForExam" placeholder="Поиск пациента..." />
            <select id="selectPatientForExam">
                <option value="">Выберите пациента</option>
            </select>
        </div>
        
        <input type="file" id="fileInput" name="uploadedFile">
        <div class="modal-buttons">
            <button id="cancelExaminationModal">Отмена</button>
            <button id="saveExaminationButton">Сохранить</button>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления заключения -->
<div id="conclusionModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeConclusionModal">&times;</span>
        <h2>Добавить заключение</h2>
        <div>
            <input type="text" id="searchPatientInputForConclusion" placeholder="Поиск пациента..." />
            <select id="selectPatientForConclusion">
                <option value="">Выберите пациента</option>
            </select>
        </div>

        <select id="selectExaminationForConclusion">
            <option value="">Выберите обследование</option>
        </select>
        
        <input type="file" id="fileInputConclusion" name="uploadedFile" accept=".docx,.doc,.pdf">
        <div class="modal-buttons">
            <button id="cancelConclusionModal">Отмена</button>
            <button id="saveConclusionButton">Сохранить</button>
        </div>
        
    </div>
</div>

<?php 

?>
<script src="/js/main.js?v=<?php echo time(); ?>"></script>
<script src="/js/notification.js?v=<?php echo time(); ?>"></script>

</body>
</html>
