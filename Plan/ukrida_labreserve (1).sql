-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2026 at 09:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ukrida_labreserve`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'booking.approved', 'App\\Models\\Booking', 5, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 17:58:59'),
(2, 1, 'computer.status_changed', 'App\\Models\\Computer', 2, '{\"status\":\"online\"}', '{\"status\":\"maintenance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 20:40:54'),
(3, 1, 'computer.status_changed', 'App\\Models\\Computer', 2, '{\"status\":\"maintenance\"}', '{\"status\":\"online\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 20:40:56'),
(4, 1, 'booking.approved', 'App\\Models\\Booking', 6, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 21:15:21'),
(5, 1, 'booking.approved', 'App\\Models\\Booking', 7, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 11:46:47'),
(6, 1, 'booking.approved', 'App\\Models\\Booking', 8, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 06:17:32'),
(7, 1, 'booking.approved', 'App\\Models\\Booking', 9, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 13:39:35'),
(8, 1, 'booking.approved', 'App\\Models\\Booking', 10, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:09:40'),
(9, 1, 'booking.approved', 'App\\Models\\Booking', 11, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 16:09:54'),
(10, 1, 'computer.status_changed', 'App\\Models\\Computer', 5, '{\"status\":\"online\"}', '{\"status\":\"maintenance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 16:14:23'),
(11, 1, 'computer.status_changed', 'App\\Models\\Computer', 5, '{\"status\":\"maintenance\"}', '{\"status\":\"online\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 16:14:25'),
(14, 1, 'booking.approved', 'App\\Models\\Booking', 20, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-07 08:09:16'),
(15, 1, 'booking.approved', 'App\\Models\\Booking', 21, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-07 11:40:24'),
(16, 1, 'booking.approved', 'App\\Models\\Booking', 23, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-07 11:49:21'),
(17, 1, 'booking.approved', 'App\\Models\\Booking', 24, '{\"status\":\"under_review\"}', '{\"status\":\"approved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-07 11:51:19');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_code` varchar(30) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `booking_type` enum('full_room','computers_only','room_only') NOT NULL,
  `room_sharing` enum('exclusive','shared') DEFAULT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('draft','submitted','under_review','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'draft',
  `admin_notes` text DEFAULT NULL,
  `google_event_id` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_code`, `user_id`, `booking_type`, `room_sharing`, `date`, `start_time`, `end_time`, `status`, `admin_notes`, `google_event_id`, `submitted_at`, `reviewed_at`, `reviewed_by`, `created_at`, `updated_at`) VALUES
(5, 'LAB-0002', 2, 'computers_only', NULL, '2026-05-19', '11:00:00', '13:00:00', 'approved', NULL, NULL, '2026-05-17 17:58:12', '2026-05-17 17:58:59', 1, '2026-05-17 17:58:12', '2026-05-17 17:58:59'),
(6, 'LAB-0003', 2, 'room_only', 'exclusive', '2026-05-20', '10:00:00', '12:00:00', 'approved', NULL, NULL, '2026-05-17 21:14:10', '2026-05-17 21:15:21', 1, '2026-05-17 21:14:10', '2026-05-17 21:15:21'),
(7, 'LAB-0004', 2, 'room_only', 'exclusive', '2026-05-20', '12:00:00', '14:00:00', 'approved', NULL, NULL, '2026-05-19 09:44:59', '2026-05-19 11:46:47', 1, '2026-05-19 09:44:59', '2026-05-19 11:46:47'),
(8, 'LAB-0005', 2, 'room_only', 'shared', '2026-05-21', '10:00:00', '12:00:00', 'approved', NULL, NULL, '2026-05-20 06:03:50', '2026-05-20 06:17:32', 1, '2026-05-20 06:03:50', '2026-05-20 06:17:32'),
(9, 'LAB-0006', 3, 'full_room', NULL, '2026-05-21', '14:00:00', '15:00:00', 'approved', NULL, NULL, '2026-05-20 13:38:22', '2026-05-20 13:39:35', 1, '2026-05-20 13:38:22', '2026-05-20 13:39:35'),
(10, 'LAB-0007', 3, 'computers_only', NULL, '2026-05-21', '15:00:00', '16:00:00', 'approved', NULL, NULL, '2026-05-20 15:09:33', '2026-05-20 15:09:40', 1, '2026-05-20 15:09:33', '2026-05-20 15:09:40'),
(11, 'LAB-0008', 2, 'computers_only', NULL, '2026-05-27', '11:00:00', '13:00:00', 'approved', NULL, NULL, '2026-05-26 16:08:52', '2026-05-26 16:09:54', 1, '2026-05-26 16:08:52', '2026-05-26 16:09:54'),
(20, 'LAB-0009', 2, 'full_room', NULL, '2026-06-08', '14:00:00', '16:00:00', 'approved', NULL, NULL, '2026-06-07 08:09:03', '2026-06-07 08:09:16', 1, '2026-06-07 08:09:03', '2026-06-07 08:09:16'),
(21, 'LAB-0010', 3, 'room_only', 'exclusive', '2026-06-09', '14:00:00', '16:00:00', 'approved', NULL, NULL, '2026-06-07 11:40:10', '2026-06-07 11:40:23', 1, '2026-06-07 11:40:10', '2026-06-07 11:40:23'),
(23, 'LAB-0011', 2, 'room_only', 'shared', '2026-06-10', '14:00:00', '16:00:00', 'approved', NULL, NULL, '2026-06-07 11:49:05', '2026-06-07 11:49:21', 1, '2026-06-07 11:49:05', '2026-06-07 11:49:21'),
(24, 'LAB-0012', 2, 'computers_only', NULL, '2026-06-10', '13:00:00', '16:00:00', 'approved', NULL, NULL, '2026-06-07 11:50:49', '2026-06-07 11:51:19', 1, '2026-06-07 11:50:49', '2026-06-07 11:51:19');

-- --------------------------------------------------------

--
-- Table structure for table `booking_computers`
--

CREATE TABLE `booking_computers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `computer_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_computers`
--

INSERT INTO `booking_computers` (`id`, `booking_id`, `computer_id`) VALUES
(7, 5, 2),
(8, 10, 1),
(9, 10, 2),
(10, 10, 3),
(11, 10, 4),
(12, 10, 5),
(13, 10, 6),
(14, 10, 7),
(15, 10, 8),
(16, 10, 9),
(17, 11, 1),
(28, 20, 1),
(27, 20, 2),
(29, 20, 3),
(30, 24, 2);

-- --------------------------------------------------------

--
-- Table structure for table `booking_logbooks`
--

CREATE TABLE `booking_logbooks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `category` enum('penelitian','project_akademik','praktikum','tugas_akhir','lainnya') NOT NULL,
  `checkpoint_progress` text NOT NULL,
  `related_course` varchar(255) DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `duration_sufficient` tinyint(1) DEFAULT NULL,
  `special_software` text DEFAULT NULL,
  `needs_internet` tinyint(1) DEFAULT NULL,
  `needs_installation` tinyint(1) DEFAULT NULL,
  `external_devices` text DEFAULT NULL,
  `priority_level` enum('normal','urgent') NOT NULL DEFAULT 'normal',
  `priority_reason` text DEFAULT NULL,
  `session_target` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_logbooks`
--

INSERT INTO `booking_logbooks` (`id`, `booking_id`, `category`, `checkpoint_progress`, `related_course`, `supervisor_name`, `duration_sufficient`, `special_software`, `needs_internet`, `needs_installation`, `external_devices`, `priority_level`, `priority_reason`, `session_target`, `created_at`, `updated_at`) VALUES
(5, 5, 'penelitian', 'Training AI untuk UAS', 'Kecerdasan Buatan', 'Dr. Bintang', NULL, NULL, 1, NULL, NULL, 'normal', NULL, NULL, '2026-05-17 17:58:12', '2026-05-17 17:58:12'),
(6, 6, 'tugas_akhir', 'Sidang Akhir Skripsi', 'Tugas Akhir', 'Dr Bintang', NULL, NULL, 1, NULL, NULL, 'normal', NULL, NULL, '2026-05-17 21:14:10', '2026-05-17 21:14:10'),
(7, 7, 'penelitian', 'Ingin melakukan penelitian algoritma random forest dan decision tree', 'Kecerdasan Buatan', 'Dr Bintang', NULL, NULL, 1, NULL, NULL, 'normal', NULL, NULL, '2026-05-19 09:44:59', '2026-05-19 09:44:59'),
(8, 8, 'penelitian', 'Ingin meneliti tentang algoritma random forest pada dbd', 'Machine Learning', 'Dr Bintang', NULL, NULL, 1, NULL, NULL, 'normal', NULL, NULL, '2026-05-20 06:03:50', '2026-05-20 06:03:50'),
(9, 9, 'lainnya', 'Peminjaman untuk melakukan bimbingan', 'Bimbingan', 'Dr Bintang', NULL, NULL, 0, NULL, NULL, 'normal', NULL, NULL, '2026-05-20 13:38:23', '2026-05-20 13:38:23'),
(10, 10, 'project_akademik', 'Pembelajaran', 'Kecerdasan Tiruan', 'Dr Bintang', NULL, NULL, 1, NULL, NULL, 'normal', NULL, NULL, '2026-05-20 15:09:33', '2026-05-20 15:09:33'),
(11, 11, 'penelitian', 'Meneliti Dengue menggunakan AI', 'Kecerdasan Buatan', 'Dr Bintang', NULL, NULL, 1, NULL, NULL, 'normal', NULL, NULL, '2026-05-26 16:08:52', '2026-05-26 16:08:52'),
(20, 20, 'lainnya', 'Presentasi Tugas Akhir', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'normal', NULL, NULL, '2026-06-07 08:09:03', '2026-06-07 08:09:03'),
(21, 21, 'lainnya', 'Melakukan Rekaman', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'normal', NULL, NULL, '2026-06-07 11:40:10', '2026-06-07 11:40:10'),
(23, 23, 'lainnya', 'Melakukan Perekaman', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'normal', NULL, NULL, '2026-06-07 11:49:05', '2026-06-07 11:49:05'),
(24, 24, 'lainnya', 'Tugas Akhir 41023022', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'normal', NULL, NULL, '2026-06-07 11:50:49', '2026-06-07 11:50:49');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('ukrida-lab-reserve-cache-admin@ukrida.ac.id|::1', 'i:1;', 1779811821),
('ukrida-lab-reserve-cache-admin@ukrida.ac.id|::1:timer', 'i:1779811821;', 1779811821);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `computers`
--

CREATE TABLE `computers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `unit_number` tinyint(3) UNSIGNED NOT NULL,
  `label` varchar(20) NOT NULL,
  `status` enum('online','maintenance','offline') NOT NULL DEFAULT 'online',
  `specs_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `computers`
--

INSERT INTO `computers` (`id`, `unit_number`, `label`, `status`, `specs_note`, `created_at`, `updated_at`) VALUES
(1, 1, 'PC-01', 'online', NULL, '2026-05-07 16:38:42', '2026-05-07 16:38:42'),
(2, 2, 'PC-02', 'online', NULL, '2026-05-07 16:38:42', '2026-05-17 20:40:56'),
(3, 3, 'PC-03', 'online', NULL, '2026-05-07 16:38:42', '2026-05-07 16:38:42'),
(4, 4, 'PC-04', 'online', NULL, '2026-05-07 16:38:42', '2026-05-07 16:38:42'),
(5, 5, 'PC-05', 'online', NULL, '2026-05-07 16:38:42', '2026-05-26 16:14:25'),
(6, 6, 'PC-06', 'online', NULL, '2026-05-07 16:38:42', '2026-05-07 16:38:42'),
(7, 7, 'PC-07', 'online', NULL, '2026-05-07 16:38:42', '2026-05-07 16:38:42'),
(8, 8, 'PC-08', 'online', NULL, '2026-05-07 16:38:42', '2026-05-07 16:38:42'),
(9, 9, 'PC-09', 'online', NULL, '2026-05-07 16:38:42', '2026-05-07 16:38:42');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_settings`
--

CREATE TABLE `lab_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_settings`
--

INSERT INTO `lab_settings` (`id`, `key`, `value`, `description`, `updated_at`) VALUES
(1, 'lab_name', 'Laboratorium Komputer UKRIDA', 'Nama laboratorium', '2026-06-08 15:15:18'),
(2, 'admin_email', 'admin@ukrida.ac.id', 'Email admin penerima notifikasi', '2026-06-08 15:15:18'),
(3, 'buffer_minutes', '15', 'Waktu buffer antar sesi (menit)', '2026-06-08 15:15:19'),
(4, 'operating_start', '08:00', 'Jam buka laboratorium', '2026-05-07 16:38:42'),
(5, 'operating_end', '22:00', 'Jam tutup laboratorium', '2026-06-08 15:15:18'),
(6, 'operating_days', '1,2,3,4,5,6', 'Hari operasional (1=Senin, 7=Minggu)', '2026-06-08 15:15:19'),
(7, 'max_session_hours', '4', 'Maksimum durasi peminjaman (jam)', '2026-06-08 15:15:19'),
(8, 'session_lifetime', '120', 'Batas waktu sesi login (menit)', '2026-05-07 16:38:42');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2024_01_01_000003_create_teams_table', 1),
(5, '2024_01_01_000004_create_computers_table', 1),
(6, '2024_01_01_000005_create_bookings_table', 1),
(7, '2024_01_01_000006_create_booking_logbooks_table', 1),
(8, '2024_01_01_000007_create_audit_logs_table', 1),
(9, '2024_01_01_000008_create_lab_settings_table', 1),
(10, '2024_01_02_000001_add_room_sharing_to_bookings', 2),
(12, '2026_05_26_000001_rename_email_domain_to_email_in_study_programs', 3),
(13, '2026_05_26_000002_add_gmail_to_users', 3),
(14, '2026_06_03_000001_add_password_to_study_programs', 4),
(15, '2026_06_08_000001_make_audit_auditable_nullable', 5);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('2FlSCpBKriYwilerfPYhiwpyra1A648uGfrj4yQK', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoibUFHRTd3SnZVMlV0SEhEVjhJclAyb29qTlFvV1JDSmxvMlJ1alNUUiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjUyOiJodHRwOi8vbG9jYWxob3N0L1VLUklEQV9MYWJSZXNlcnZlL3B1YmxpYy9ib29raW5nLzIxIjtzOjU6InJvdXRlIjtzOjEyOiJib29raW5nLnNob3ciO31zOjU6ImxvZ2luIjthOjA6e31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO30=', 1780832413),
('b9F734nTqg6KCo62Gv18N1mzeImJUUc9WRiaIWay', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiMEhYam9aamdjTGdaMmpMbDg4eXk0Q25SUW41RGhicktHbzl5WWFEayI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjUwOiJodHRwOi8vbG9jYWxob3N0L1VLUklEQV9MYWJSZXNlcnZlL3B1YmxpYy9jYWxlbmRhciI7czo1OiJyb3V0ZSI7czoxNDoiY2FsZW5kYXIuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjU6ImxvZ2luIjthOjA6e31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1780856554),
('CpXuTtUEIM76M77p4qBjPPblxlZXLXHSpDjQQdDg', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoib0N2Y0NPc1MyVmx2VkdVSVppcTRRRVhxT3hMT21TczRRd1owZFZpYyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjU2OiJodHRwOi8vbG9jYWxob3N0L1VLUklEQV9MYWJSZXNlcnZlL3B1YmxpYy9hZG1pbi9yZXF1ZXN0cyI7czo1OiJyb3V0ZSI7czoyMDoiYWRtaW4ucmVxdWVzdHMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1780833079),
('pkol9s4yjU1p366w0JcuLoIqgPrkk9RzrSQ35irB', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidTl1TU5OM3lxMUtYUm9wNWZjcVdxdHFMcW1Sck5QUXl0WmM2V2h5ciI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvVUtSSURBX0xhYlJlc2VydmUvcHVibGljL2FkbWluL3NldHRpbmdzIjtzOjU6InJvdXRlIjtzOjIwOiJhZG1pbi5zZXR0aW5ncy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1780932239),
('PXeCD8KM79hX8x8HOthLRx1MSqbk647W1HlFWKbI', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTFozcHE1OGpJTlFRSElmR0JtdnJiSzVjbDdGdk9MZURWTDFpMDA2USI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly9sb2NhbGhvc3QvVUtSSURBX0xhYlJlc2VydmUvcHVibGljIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1780928751),
('tQmYAKbFKYTHSM2wwjqgmqFt5L8eM2yqks7ElyRJ', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiR0lWWFVnVmhTMEZZT0pxSzF6YUFMVWloRURodjhnTHVoNzZZaU5RRyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo1NzoiaHR0cDovL2xvY2FsaG9zdC9VS1JJREFfTGFiUmVzZXJ2ZS9wdWJsaWMvYm9va2luZy9oaXN0b3J5Ijt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTc6Imh0dHA6Ly9sb2NhbGhvc3QvVUtSSURBX0xhYlJlc2VydmUvcHVibGljL2Jvb2tpbmcvaGlzdG9yeSI7czo1OiJyb3V0ZSI7czoxNToiYm9va2luZy5oaXN0b3J5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1780854802),
('tzasdmAiheMpquQhYgMQj8PqDrh77s9koACugSUq', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiMm56UUgzQ2dmQldpem1BZTQ0OGsyVXRYNzZmMXVQODM5cGxXcGJJZCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjYwOiJodHRwOi8vbG9jYWxob3N0L1VLUklEQV9MYWJSZXNlcnZlL3B1YmxpYy9hZG1pbi91c2Vycy9jcmVhdGUiO3M6NToicm91dGUiO3M6MTg6ImFkbWluLnVzZXJzLmNyZWF0ZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1780856629),
('y8QXKFxHghohJhEM12uZhpBKBzhaCn9d42RD92XL', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiaGdFUEw0NnZwdTd3enJPMGRZWDdJSXF1V1FJWEFKNFU4aHVmd3ZrQiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjUwOiJodHRwOi8vbG9jYWxob3N0L1VLUklEQV9MYWJSZXNlcnZlL3B1YmxpYy9jYWxlbmRhciI7czo1OiJyb3V0ZSI7czoxNDoiY2FsZW5kYXIuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjU6ImxvZ2luIjthOjA6e31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1780833730),
('ZmA32m0mDLtlJXuJQN1y80ObZLpVPooKc5vxY7hX', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiV0NoeE42VUxoVmVMVjBZUVN5cGVWd3lQUHF4bVdsQTZQWVE1TEkzRyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1780854802);

-- --------------------------------------------------------

--
-- Table structure for table `study_programs`
--

CREATE TABLE `study_programs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `study_programs`
--

INSERT INTO `study_programs` (`id`, `name`, `email`, `password`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Teknik Informatika', 'ti.ukrida@gmail.com', '$2y$12$GnMpRgt362qB8JC4So80x.vNGfbsPB1bDyhA8dkiXKOvlR5Ogk0bi', 1, '2026-05-07 16:38:42', '2026-06-03 16:44:15'),
(2, 'Sistem Informasi', 'si.ukrida@gmail.com', '$2y$12$8.PKcSijoDdW09eyMvO0weQSvmX65DXRlUveq3Z5AaH7PtURdhTBe', 1, '2026-05-07 16:38:42', '2026-06-03 16:44:15'),
(3, 'Teknik Elektro', 'te.ukrida@gmail.com', '$2y$12$tecZBYzU/KiCTtjMvfu4KuNW8VLfsfGE6iIgo5210jelVJxqek006', 1, '2026-05-07 16:38:42', '2026-06-03 16:44:16'),
(4, 'Teknik Industri', 'tk.ukrida@gmail.com', '$2y$12$F7dOv1Hcn6o3fd2NGVbCQuwik.uVsI4t.fjYVYud3R1uXYoXjQOyi', 1, '2026-05-07 16:38:42', '2026-06-03 16:44:16');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `pic_lecturer_id` bigint(20) UNSIGNED NOT NULL,
  `study_program_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_id_number` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `study_program_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `gmail` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','lecturer','team') NOT NULL DEFAULT 'lecturer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `study_program_id`, `name`, `email`, `gmail`, `password`, `role`, `is_active`, `last_login_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Administrator', 'admin@ukrida.ac.id', 'admin.ukrida@gmail.com', '$2y$12$MrjedimeAUsRlY5szvN3QOhBZFWmBR5m9p1d25uYFf.c60jXZyVRK', 'admin', 1, '2026-06-08 14:26:04', NULL, '2026-05-07 16:38:42', '2026-06-08 14:26:04'),
(2, 1, 'Dr. Budi Santoso', 'budi@ti.ukrida.ac.id', NULL, '$2y$12$zetlFn7awvIuLq9Jyt/.POT/M8zR934BRf9458jriV/C3nUPsH2tS', 'lecturer', 1, '2026-06-07 18:20:54', NULL, '2026-05-07 19:42:22', '2026-06-07 18:20:54'),
(3, 1, 'Tim Alpha', 'tim.alpha@ti.ukrida.ac.id', NULL, '$2y$12$Q3kHegXI2rpbitL761e2s.LLdwf8qGZNzPU4s3meXTg7CxF6QhWI6', 'team', 1, '2026-06-07 11:33:30', NULL, '2026-05-07 19:42:22', '2026-06-07 11:33:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`),
  ADD KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  ADD KEY `audit_logs_action_created_at_index` (`action`,`created_at`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bookings_booking_code_unique` (`booking_code`),
  ADD KEY `bookings_user_id_foreign` (`user_id`),
  ADD KEY `bookings_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `bookings_date_start_time_end_time_index` (`date`,`start_time`,`end_time`),
  ADD KEY `bookings_status_date_index` (`status`,`date`);

--
-- Indexes for table `booking_computers`
--
ALTER TABLE `booking_computers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_computers_booking_id_computer_id_unique` (`booking_id`,`computer_id`),
  ADD KEY `booking_computers_computer_id_foreign` (`computer_id`);

--
-- Indexes for table `booking_logbooks`
--
ALTER TABLE `booking_logbooks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_logbooks_booking_id_unique` (`booking_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `computers`
--
ALTER TABLE `computers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `computers_unit_number_unique` (`unit_number`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_settings`
--
ALTER TABLE `lab_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_settings_key_unique` (`key`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `study_programs`
--
ALTER TABLE `study_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `study_programs_email_domain_unique` (`email`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teams_user_id_foreign` (`user_id`),
  ADD KEY `teams_pic_lecturer_id_foreign` (`pic_lecturer_id`),
  ADD KEY `teams_study_program_id_foreign` (`study_program_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_members_team_id_foreign` (`team_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_gmail_unique` (`gmail`),
  ADD KEY `users_study_program_id_foreign` (`study_program_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `booking_computers`
--
ALTER TABLE `booking_computers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `booking_logbooks`
--
ALTER TABLE `booking_logbooks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `computers`
--
ALTER TABLE `computers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_settings`
--
ALTER TABLE `lab_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `study_programs`
--
ALTER TABLE `study_programs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_computers`
--
ALTER TABLE `booking_computers`
  ADD CONSTRAINT `booking_computers_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_computers_computer_id_foreign` FOREIGN KEY (`computer_id`) REFERENCES `computers` (`id`);

--
-- Constraints for table `booking_logbooks`
--
ALTER TABLE `booking_logbooks`
  ADD CONSTRAINT `booking_logbooks_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_pic_lecturer_id_foreign` FOREIGN KEY (`pic_lecturer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `teams_study_program_id_foreign` FOREIGN KEY (`study_program_id`) REFERENCES `study_programs` (`id`),
  ADD CONSTRAINT `teams_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_study_program_id_foreign` FOREIGN KEY (`study_program_id`) REFERENCES `study_programs` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
