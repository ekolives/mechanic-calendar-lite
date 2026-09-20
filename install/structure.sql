-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db:3306
-- Generation Time: Cze 08, 2026 at 04:16 PM
-- Wersja serwera: 10.6.25-MariaDB-ubu2204
-- Wersja PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `db_moto`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `audit`
--

CREATE TABLE `audit` (
  `sys_id` int(11) NOT NULL,
  `sys_submiter` varchar(255) NOT NULL,
  `sys_createdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `process` varchar(255) NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `audit`
--

INSERT INTO `audit` (`sys_id`, `sys_submiter`, `sys_createdate`, `process`, `value`) VALUES
(1, 'admin', '2026-06-06 11:04:47', 'logowanie', 'success');
-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `calendar_slots`
--

CREATE TABLE `calendar_slots` (
  `slot_id` int(11) NOT NULL,
  `sys_submiter` varchar(255) DEFAULT NULL,
  `sys_createdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `sys_updatedate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `sys_updatedby` varchar(255) NOT NULL,
  `mechanic_id` int(11) NOT NULL,
  `slot_date` date NOT NULL,
  `slot_time_start` time NOT NULL,
  `reservation_state` int(2) NOT NULL,
  `reservation_title` varchar(255) DEFAULT NULL,
  `reservation_description` text DEFAULT NULL,
  `reservation_phone` varchar(50) DEFAULT NULL,
  `reservation_vin` varchar(50) DEFAULT NULL,
  `reservation_plate` varchar(50) DEFAULT NULL,
  `slot_time_end` time NOT NULL DEFAULT '08:30:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `calendar_slots`
--

INSERT INTO `calendar_slots` (`slot_id`, `sys_submiter`, `sys_createdate`, `sys_updatedate`, `sys_updatedby`, `mechanic_id`, `slot_date`, `slot_time_start`, `reservation_state`, `reservation_title`, `reservation_description`, `reservation_phone`, `reservation_vin`, `reservation_plate`, `slot_time_end`) VALUES
(1, '1', '2026-06-07 06:38:38', '2026-06-08 15:56:37', '1', 1, '2026-06-01', '08:00:00', 1, 'Test Test', 'i dodatkowy opis', '666 666 333', 'vw5454454545', 'dddd', '13:30:00');
-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `language`
--

CREATE TABLE `language` (
  `id` int(11) NOT NULL,
  `sys` varchar(30) NOT NULL,
  `locale` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin2 COLLATE=latin2_general_ci;

--
-- Zrzut danych tabeli `language`
--

INSERT INTO `language` (`id`, `sys`, `locale`, `name`) VALUES
(1, 'lang_LoginName', 'en', 'Login Name'),
(2, 'lang_LoginName', 'pl', 'Użytkownik'),
(3, 'lang_password', 'pl', 'Hasło'),
(4, 'lang_password', 'en', 'Password'),
(5, 'lang_WebTitle', 'pl', 'Kalendarz mechanika '),
(6, 'lang_submit', 'pl', 'Zatwierdź'),
(7, 'lang_submit', 'en', 'Submit'),
(8, 'lang_WebTitle', 'en', 'ekolives.com'),
(9, 'lang_login', 'pl', 'Logowanie'),
(10, 'lang_login', 'en', 'Log In'),
(195, 'lang_show', 'pl', 'Pokaż'),
(196, 'lang_show', 'en', 'Show'),
(197, 'lang_prev_week', 'pl', 'Poprzedni tydzień'),
(198, 'lang_prev_week', 'en', 'Last week'),
(199, 'lang_prev_day', 'pl', 'Poprzedni dzień'),
(200, 'lang_prev_day', 'en', 'Last day'),
(201, 'lang_next_week', 'pl', 'Następny tydzień'),
(202, 'lang_next_week', 'en', 'Next week'),
(203, 'lang_next_day', 'pl', 'Następny dzień'),
(204, 'lang_next_day', 'en', 'Next day'),
(205, 'lang_settings', 'pl', 'Ustawienia'),
(206, 'lang_settings', 'en', 'Settings'),
(207, 'lang_daily_view', 'pl', 'Widok Dni'),
(208, 'lang_daily_view', 'en', 'Daily View'),
(209, 'lang_weekly_view', 'pl', 'Widok Tygodni'),
(210, 'lang_weekly_view', 'en', 'Weekly View'),
(211, 'lang_reservation_list', 'pl', 'Lista rezerwacji'),
(212, 'lang_reservation_list', 'en', 'Reservation List'),
(213, 'lang_date', 'pl', 'Data'),
(214, 'lang_date', 'en', 'Date'),
(215, 'lang_monday', 'pl', 'Poniedziałek'),
(216, 'lang_monday', 'en', 'Monday'),
(217, 'lang_tuesday', 'pl', 'Wtorek'),
(218, 'lang_tuesday', 'en', 'Tuesday'),
(219, 'lang_wednesday', 'pl', 'Środa'),
(220, 'lang_wednesday', 'en', 'Wednesday'),
(221, 'lang_thursday', 'pl', 'Czwartek'),
(222, 'lang_thursday', 'en', 'Thursday'),
(223, 'lang_friday', 'pl', 'Piątek'),
(224, 'lang_friday', 'en', 'Friday'),
(225, 'lang_saturday', 'pl', 'Sobota'),
(226, 'lang_saturday', 'en', 'Saturday'),
(227, 'lang_sunday', 'pl', 'Niedziela'),
(228, 'lang_sunday', 'en', 'Sunday'),
(229, 'lang_time_start', 'pl', 'Godzina start'),
(230, 'lang_time_start', 'en', 'Start time'),
(231, 'lang_time_end', 'pl', 'Godzina koniec'),
(232, 'lang_time_end', 'en', 'End time'),
(233, 'lang_title', 'pl', 'Tytuł'),
(234, 'lang_title', 'en', 'Title'),
(235, 'lang_description', 'pl', 'Opis'),
(236, 'lang_description', 'en', 'Description'),
(237, 'lang_phone', 'pl', 'Telefon'),
(238, 'lang_phone', 'en', 'Phone'),
(239, 'lang_plate', 'pl', 'Rejestracja'),
(240, 'lang_plate', 'en', 'Plate'),
(241, 'lang_vin', 'pl', 'Vin'),
(242, 'lang_vin', 'en', 'Vin'),
(243, 'lang_new_reservation_for', 'pl', 'Nowa rezerwacja dla'),
(244, 'lang_new_reservation_for', 'en', 'New reservation for'),
(245, 'lang_save_reservation', 'pl', 'Zapisz Rezerwację'),
(246, 'lang_save_reservation', 'en', 'Save Reservation'),
(247, 'lang_edit_reservation', 'pl', 'Zmień Rezerwację'),
(248, 'lang_edit_reservation', 'en', 'Edit Reservation'),
(249, 'lang_delete', 'pl', 'Usuń Rezerwację'),
(250, 'lang_delete', 'en', 'Delete'),
(251, 'lang_save_changes', 'pl', 'Zapisz Zmiany'),
(252, 'lang_save_changes', 'en', 'Save Changes'),
(253, 'lang_creation_date', 'pl', 'Data Stworzenia'),
(254, 'lang_creation_date', 'en', 'Creation Date'),
(255, 'lang_created_by', 'pl', 'Stworzony przez'),
(256, 'lang_created_by', 'en', 'Created by'),
(257, 'lang_updated_by', 'pl', 'Zmodyfikowany przez'),
(258, 'lang_updated_by', 'en', 'Updated by'),
(259, 'lang_updated_date', 'pl', 'Data Modyfikacji'),
(260, 'lang_updated_date', 'en', 'Updated date'),
(261, 'lang_cancelled', 'pl', 'Odwołana?'),
(262, 'lang_cancelled', 'en', 'Cancelled?'),
(263, 'lang_yes', 'pl', 'Tak'),
(264, 'lang_yes', 'en', 'Yes'),
(265, 'lang_no', 'pl', 'Nie'),
(266, 'lang_no', 'en', 'No'),
(267, 'lang_slot_name', 'pl', 'Nazwa Slotu'),
(268, 'lang_slot_name', 'en', 'Slot Name'),
(269, 'lang_mechanic', 'pl', 'Mechanik'),
(270, 'lang_mechanic', 'en', 'Mechanic'),
(271, 'lang_sort', 'pl', 'Sortowanie'),
(272, 'lang_sort', 'en', 'Sort'),
(273, 'lang_color', 'en', 'Color'),
(274, 'lang_color', 'pl', 'Kolor'),
(275, 'lang_work_start', 'pl', 'Start pracy'),
(276, 'lang_work_start', 'en', 'Work start'),
(277, 'lang_work_end', 'pl', 'Koniec pracy'),
(278, 'lang_work_end', 'en', 'Work End'),
(279, 'lang_change', 'pl', 'Zmień'),
(280, 'lang_change', 'en', 'Change'),
(281, 'lang_users', 'pl', 'Użytkownicy'),
(282, 'lang_users', 'en', 'Users'),
(283, 'lang_help', 'pl', 'Pomoc'),
(284, 'lang_help', 'en', 'Help'),
(285, 'lang_slot_translations', 'pl', 'Tłumaczenia'),
(286, 'lang_slot_translations', 'en', 'Translations'),
(287, 'lang_back', 'pl', 'Powrót do kalendarza'),
(288, 'lang_back', 'en', 'Back to the calendar'),
(289, 'lang_permissions', 'pl', 'Uprawnienia'),
(290, 'lang_permissions', 'en', 'Permissions'),
(291, 'lang_status', 'pl', 'Status'),
(292, 'lang_status', 'en', 'Status'),
(293, 'lang_action', 'pl', 'Akcja'),
(294, 'lang_action', 'en', 'Action'),
(295, 'lang_admin', 'pl', 'Administrator'),
(296, 'lang_admin', 'en', 'Administrator'),
(297, 'lang_user', 'pl', 'Użytkownik'),
(298, 'lang_user', 'en', 'User'),
(299, 'lang_collaborator', 'pl', 'Wspołpracownik'),
(300, 'lang_collaborator', 'en', 'Collaborato'),
(301, 'lang_acive', 'pl', 'Aktywny'),
(302, 'lang_acive', 'en', 'Active'),
(303, 'lang_inacive', 'en', 'Inactive'),
(304, 'lang_inacive', 'pl', 'Nieaktywny');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `mechanics`
--

CREATE TABLE `mechanics` (
  `mechanic_id` int(11) NOT NULL,
  `sys_createdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `sys_updatedate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `sys_submitter` varchar(255) NOT NULL,
  `sys_updatedby` varchar(255) NOT NULL,
  `mechanic_name` varchar(255) NOT NULL,
  `mechanic_sort` int(11) NOT NULL DEFAULT 0,
  `color` varchar(20) NOT NULL,
  `font_color` varchar(20),
  `work_start_time` time NOT NULL DEFAULT '00:00:00',
  `work_end_time` time NOT NULL DEFAULT '00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `mechanics`
--

INSERT INTO `mechanics` (`mechanic_id`, `sys_createdate`, `sys_updatedate`, `sys_submitter`, `sys_updatedby`, `mechanic_name`, `mechanic_sort`, `color`, `work_start_time`, `work_end_time`) VALUES
(1, '2026-06-04 14:41:18', '2026-06-06 06:27:07', '', '', 'Krzysiek', 1, '#cbabc8', '08:00:00', '16:00:00'),
(2, '2026-06-04 14:41:18', '2026-06-06 06:27:13', '', '', 'Jacek', 2, '#99ccff', '08:00:00', '16:00:00'),
(3, '2026-06-04 14:41:18', '2026-06-06 06:28:15', '', '', 'Marian', 3, '#ccffff', '08:00:00', '16:00:00'),
(4, '2026-06-04 14:41:18', '2026-06-06 06:27:28', '', '', 'Julek', 4, '#00cc99', '09:00:00', '17:00:00'),
(5, '2026-06-04 14:41:18', '2026-06-06 06:28:09', '', '', 'Organizacyjne', 5, '#ff8000', '08:00:00', '18:00:00');



--
-- Struktura tabeli dla tabeli `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `sys_createdate` datetime NOT NULL DEFAULT current_timestamp(),
  `sys_submitter` int(11) NOT NULL,
  `sys_updatedate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `sys_updatedby` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mechanic_id` int(11) NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `settings`
--

CREATE TABLE `settings` (
  `sys_id` int(11) NOT NULL,
  `sys_submiter` varchar(255) NOT NULL,
  `sys_createdate` datetime NOT NULL DEFAULT current_timestamp(),
  `sys_updatedby` varchar(255) NOT NULL,
  `sys_updatedate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL,
  `setting_number` int(11) NOT NULL,
  `setting_name` varchar(255) NOT NULL,
  `setting_values` varchar(255) NOT NULL,
  `setting_sort` int(11) NOT NULL,
  `setting_locale` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `sys_submiter` varchar(255) NOT NULL,
  `sys_createdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `sys_updatedby` varchar(255) NOT NULL,
  `sys_updatedate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `user_login` varchar(255) NOT NULL,
  `user_status` int(11) NOT NULL,
  `user_password` char(64) NOT NULL,
  `user_permissions` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--
-- Zrzut danych tabeli `users`
--

INSERT INTO `users` (`user_id`, `sys_submiter`, `sys_createdate`, `sys_updatedby`, `sys_updatedate`, `user_login`, `user_status`, `user_password`, `user_permissions`) VALUES
(1, 'InstallAdmin', '2026-01-01 00:00:02', 'InstallAdmin', '2026-06-08 15:41:48', 'admin', 1, '$2y$10$spZhjceuR/EQd8ifBOszlu0t/WSJ47UaAYbTxOTHvOgiZaDZcIQEO', 1);

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `audit`
--
ALTER TABLE `audit`
  ADD PRIMARY KEY (`sys_id`);

--
-- Indeksy dla tabeli `calendar_slots`
--
ALTER TABLE `calendar_slots`
  ADD PRIMARY KEY (`slot_id`);

--
-- Indeksy dla tabeli `language`
--
ALTER TABLE `language`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `mechanics`
--
ALTER TABLE `mechanics`
  ADD PRIMARY KEY (`mechanic_id`);

--
-- Indeksy dla tabeli `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`sys_id`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_login` (`user_login`);

--
-- AUTO_INCREMENT dla zrzuconych tabel
--

--
-- AUTO_INCREMENT dla tabeli `audit`
--
ALTER TABLE `audit`
  MODIFY `sys_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT dla tabeli `calendar_slots`
--
ALTER TABLE `calendar_slots`
  MODIFY `slot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT dla tabeli `language`
--
ALTER TABLE `language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT dla tabeli `mechanics`
--
ALTER TABLE `mechanics`
  MODIFY `mechanic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;


--
-- AUTO_INCREMENT dla tabeli `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;



--
-- AUTO_INCREMENT dla tabeli `settings`
--
ALTER TABLE `settings`
  MODIFY `sys_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT dla tabeli `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
