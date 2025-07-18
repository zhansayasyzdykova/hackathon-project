-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2025 at 05:46 PM
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
-- Database: `db_users`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `commentID` int(11) NOT NULL,
  `postID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`commentID`, `postID`, `userID`, `comment`, `created_at`) VALUES
(1, 6, 2, 'ыаваыав', '2025-07-17 15:35:13'),
(2, 6, 2, 'класно достар', '2025-07-17 15:35:20'),
(3, 5, 2, 'кацыйф', '2025-07-17 15:35:30'),
(4, 6, 1, 'темаа', '2025-07-17 15:35:46'),
(5, 5, 2, 'длдордлр', '2025-07-17 15:37:08');

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `friendID` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `friends`
--

INSERT INTO `friends` (`id`, `userID`, `friendID`, `created_at`) VALUES
(1, 1, 2, '2025-07-17 14:56:02'),
(2, 2, 1, '2025-07-17 14:56:02');

-- --------------------------------------------------------

--
-- Table structure for table `friend_requests`
--

CREATE TABLE `friend_requests` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `friend_requests`
--

INSERT INTO `friend_requests` (`id`, `sender_id`, `receiver_id`, `status`, `created_at`) VALUES
(1, 2, 1, 'accepted', '2025-07-17 11:46:18'),
(2, 1, 2, 'accepted', '2025-07-17 14:52:24');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `postID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `event_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`postID`, `userID`, `name`, `category`, `description`, `image`, `location`, `likes`, `created_at`, `updated_at`, `event_date`) VALUES
(3, 1, 'Zhansaya', '', 'lklhlj', '6878dbbe9e927_a-shadow-of-a-boy-carrying-the-camera-with-red-sky-behind.webp', ',mdksd', 4, '2025-07-17 16:17:18', '2025-07-17 19:50:31', NULL),
(4, 1, 'MUSIC FESTIVAL', 'Music', 'Hello, this is a festival', '687911d835447_download (1).jpg', 'NU ASTANA', 1, '2025-07-17 20:08:08', '2025-07-17 20:08:13', NULL),
(5, 2, 'BLOGPOST', 'Education', 'this is for gen z??', '687912131412d_download.jpg', 'my house', 0, '2025-07-17 20:09:07', '2025-07-17 20:09:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `post_attendance`
--

CREATE TABLE `post_attendance` (
  `id` int(11) NOT NULL,
  `postID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `status` enum('going','not_going') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_attendance`
--

INSERT INTO `post_attendance` (`id`, `postID`, `userID`, `status`) VALUES
(1, 2, 1, 'going'),
(2, 1, 1, 'not_going'),
(3, 3, 1, 'going'),
(4, 4, 1, 'going'),
(5, 3, 2, 'going'),
(6, 4, 2, 'going'),
(7, 5, 2, 'going');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `username`, `firstName`, `lastName`, `email`, `password`) VALUES
(1, 'zwsaya', 'Zhansaya', 'Syzdykova', 'zhansayasyzd@gmail.com', '$2y$10$CpKHepeiglVd300rRTl6c.JcKh3h8a83C7mvy2xH01xh8lzWIO7/.'),
(2, 'sayuwa', 'saya', 's', 'zhans@gmail.com', '$2y$10$k/PdrrRXrS4RUpPhXLx6yedkqLak0W3ItnehAFycme08KlErxEIva');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`commentID`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pair` (`userID`,`friendID`);

--
-- Indexes for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request` (`sender_id`,`receiver_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`postID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `post_attendance`
--
ALTER TABLE `post_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`postID`,`userID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `commentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `friend_requests`
--
ALTER TABLE `friend_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `postID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `post_attendance`
--
ALTER TABLE `post_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
