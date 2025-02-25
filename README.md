# S3Server

## 🇬🇧 English | 🇷🇺 Русский

---

## 🌍 English Version

S3Server is a PHP application for uploading, storing, and managing medical examination and conclusion files using Amazon S3 cloud storage.

### 🚀 Features
- 🔹 User authentication (`auth.php`, `login.php`, `logout.php`)
- 🔹 File uploading with chunked transfer (`upload_chunk.php`, `upload_to_s3.php`)
- 🔹 Generating pre-signed URLs for file access (`get_presigned_url.php`, `get_signed_url.php`)
- 🔹 User and settings management (`admin.php`, `settings.php`)
- 🔹 Examination management interface (`get_patient_examinations.php`)

### 📂 Project Structure
- `css/` — Frontend styles  
- `js/` — Client-side scripts  
- `images/` — Image files (favicon, logos, etc.)  
- `requests/` — API request handling (file operations, sessions, etc.)  
- `vendor/` — Dependencies (AWS SDK)  
- `SQL/sql.sql` — Database schema  

### 🔧 Installation and Setup

#### 1️⃣ Clone the Repository
```sh
git clone https://github.com/MaximxRozhnev/S3Server.git
cd S3Server

## 🌍 Русская версия
S3Server — это PHP-приложение для загрузки, хранения и управления файлами обследований и заключений в облачном хранилище Amazon S3.

🚀 Функциональность
🔹 Авторизация пользователей (auth.php, login.php, logout.php)
🔹 Загрузка файлов с разбивкой на части (upload_chunk.php, upload_to_s3.php)
🔹 Генерация предподписанных ссылок для скачивания (get_presigned_url.php, get_signed_url.php)
🔹 Управление пользователями и настройками (admin.php, settings.php)
🔹 Интерфейс управления обследованиями (get_patient_examinations.php)
📂 Структура проекта
css/ — Стили фронтенда
js/ — Скрипты для клиентской логики
images/ — Файлы изображений (favicon и др.)
requests/ — Обработка API-запросов (загрузка файлов, сессии и т. д.)
vendor/ — Зависимости (AWS SDK)
SQL/sql.sql — Схема базы данных
