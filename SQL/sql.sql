-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Фев 25 2025 г., 05:35
-- Версия сервера: 5.7.21-20-beget-5.7.21-20-1-log
-- Версия PHP: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `nashce3p_s3serv`
--

-- --------------------------------------------------------

--
-- Структура таблицы `doctors_list`
--
-- Создание: Янв 17 2025 г., 09:27
--

DROP TABLE IF EXISTS `doctors_list`;
CREATE TABLE `doctors_list` (
  `ID` int(11) NOT NULL,
  `User_id` int(11) NOT NULL,
  `Mode_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `examinations`
--
-- Создание: Фев 04 2025 г., 00:32
-- Последнее обновление: Фев 24 2025 г., 11:38
--

DROP TABLE IF EXISTS `examinations`;
CREATE TABLE `examinations` (
  `ID` int(11) NOT NULL,
  `Patient_id` int(11) NOT NULL,
  `Path` text NOT NULL,
  `Upload_date` date NOT NULL,
  `Upload_time` time NOT NULL,
  `Upload_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Структура таблицы `modes`
--
-- Создание: Янв 17 2025 г., 10:27
--

DROP TABLE IF EXISTS `modes`;
CREATE TABLE `modes` (
  `ID` int(11) NOT NULL,
  `Name` text NOT NULL,
  `Desk` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `patients`
--
-- Создание: Фев 04 2025 г., 00:32
-- Последнее обновление: Фев 24 2025 г., 11:37
--

DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `ID` int(11) NOT NULL,
  `FIO` text NOT NULL,
  `Birthday` date NOT NULL,
  `Created_by` int(11) NOT NULL,
  `Created_date` date NOT NULL,
  `Mode_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `patient_doctors_access`
--
-- Создание: Янв 17 2025 г., 09:27
-- Последнее обновление: Фев 24 2025 г., 11:37
--

DROP TABLE IF EXISTS `patient_doctors_access`;
CREATE TABLE `patient_doctors_access` (
  `ID` int(11) NOT NULL,
  `Patient_ID` int(11) NOT NULL,
  `Doctor_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `results`
--
-- Создание: Фев 04 2025 г., 00:32
-- Последнее обновление: Фев 25 2025 г., 02:24
--

DROP TABLE IF EXISTS `results`;
CREATE TABLE `results` (
  `ID` int(11) NOT NULL,
  `Path` text NOT NULL,
  `Examination_id` int(11) NOT NULL,
  `Upload_by` int(11) NOT NULL,
  `Upload_date` date NOT NULL,
  `Upload_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- --------------------------------------------------------

--
-- Структура таблицы `roles`
--
-- Создание: Дек 25 2024 г., 08:47
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `ID` int(11) NOT NULL,
  `Desk` text NOT NULL,
  `Name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `roles`
--

INSERT INTO `roles` (`ID`, `Desk`, `Name`) VALUES
(1, 'Администратор', 'Admin'),
(2, 'Врач', 'Doctor'),
(3, 'Лаборант', 'Laborant'),
(4, 'Администратор регистратуры', 'Reg');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--
-- Создание: Дек 25 2024 г., 03:58
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `ID` int(11) NOT NULL,
  `Login` text NOT NULL,
  `Password` text NOT NULL,
  `FIO` text NOT NULL,
  `Email` text NOT NULL,
  `Role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `doctors_list`
--
ALTER TABLE `doctors_list`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `User_id` (`User_id`,`Mode_id`),
  ADD KEY `Mode_id` (`Mode_id`);

--
-- Индексы таблицы `examinations`
--
ALTER TABLE `examinations`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Patient_id` (`Patient_id`),
  ADD KEY `Upload_by` (`Upload_by`);

--
-- Индексы таблицы `modes`
--
ALTER TABLE `modes`
  ADD PRIMARY KEY (`ID`);

--
-- Индексы таблицы `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Created_by` (`Created_by`,`Mode_id`),
  ADD KEY `Mode_id` (`Mode_id`);

--
-- Индексы таблицы `patient_doctors_access`
--
ALTER TABLE `patient_doctors_access`
  ADD PRIMARY KEY (`ID`);

--
-- Индексы таблицы `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Examination_id` (`Examination_id`,`Upload_by`),
  ADD KEY `Upload_by` (`Upload_by`);

--
-- Индексы таблицы `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`ID`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Role_id` (`Role_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `doctors_list`
--
ALTER TABLE `doctors_list`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `examinations`
--
ALTER TABLE `examinations`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `modes`
--
ALTER TABLE `modes`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `patients`
--
ALTER TABLE `patients`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `patient_doctors_access`
--
ALTER TABLE `patient_doctors_access`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `results`
--
ALTER TABLE `results`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `roles`
--
ALTER TABLE `roles`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `doctors_list`
--
ALTER TABLE `doctors_list`
  ADD CONSTRAINT `doctors_list_ibfk_1` FOREIGN KEY (`Mode_id`) REFERENCES `modes` (`ID`),
  ADD CONSTRAINT `doctors_list_ibfk_2` FOREIGN KEY (`User_id`) REFERENCES `users` (`ID`);

--
-- Ограничения внешнего ключа таблицы `examinations`
--
ALTER TABLE `examinations`
  ADD CONSTRAINT `examinations_ibfk_1` FOREIGN KEY (`Upload_by`) REFERENCES `users` (`ID`),
  ADD CONSTRAINT `examinations_ibfk_2` FOREIGN KEY (`Patient_id`) REFERENCES `patients` (`ID`);

--
-- Ограничения внешнего ключа таблицы `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`Created_by`) REFERENCES `users` (`ID`),
  ADD CONSTRAINT `patients_ibfk_2` FOREIGN KEY (`Mode_id`) REFERENCES `modes` (`ID`);

--
-- Ограничения внешнего ключа таблицы `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`Upload_by`) REFERENCES `users` (`ID`),
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`Examination_id`) REFERENCES `examinations` (`ID`);

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`Role_id`) REFERENCES `roles` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
