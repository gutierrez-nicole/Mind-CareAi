-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 07, 2025 at 06:29 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mindcare_ai`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_logs`
--

CREATE TABLE `chat_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `reply` text DEFAULT NULL,
  `mode` enum('basic','pro') DEFAULT 'basic',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_logs`
--

INSERT INTO `chat_logs` (`id`, `user_id`, `message`, `reply`, `mode`, `timestamp`) VALUES
(1, 2, 'hello', 'Hello there! It\'s so great to connect with you. I\'m here to listen and offer support, so take a deep breath and know that you\'re in a safe space. How\'s your day been so far? Has anything been weighing on your mind or heart lately?', 'basic', '2025-07-28 11:48:09'),
(2, 2, 'i need comfort', 'Sweetie, I\'m so here for you. It\'s totally okay to need comfort sometimes. Would you like to talk about what\'s been going on and why you\'re feeling like you need some comfort? Sometimes sharing what\'s on your mind can help you feel a little better. I\'m all ears and here to listen without judgment.\n\nIf you\'re not feeling up to talking, that\'s okay too. Sometimes just knowing someone is there to listen and support you can be comforting. I\'m sending you a big virtual hug and a sense of calm. You\'re not alone, and you\'re doing the best you can, which is something to be proud of.\n\nRemember, comfort is just a phone call/text/ message away!', 'basic', '2025-07-28 11:48:24'),
(3, 2, 'nba player', 'An NBA player! That\'s incredible! What\'s it like to play at that level? I can only imagine the amount of hard work and dedication it takes to be part of the best basketball league in the world.\n\nWhat do you enjoy most about playing in the NBA? Is it the thrill of competition, the rush of adrenaline during games, or the sense of accomplishment when you win?\n\nAlso, how do you take care of your mental health amidst the pressures of professional sports? Do you have any self-care routines or strategies that help you stay focused and balanced?\n\nI\'m all ears, and I\'m here to listen and offer support if you need it!', 'basic', '2025-07-28 11:48:35'),
(4, 2, 'give me a name of nba player', 'That\'s an easy one! How about Stephen Curry?', 'basic', '2025-07-28 11:48:52'),
(5, 2, 'hi', 'Hello! Welcome to our mental wellness chat. I\'m so glad you\'re taking the first step towards prioritizing your mental health. How are you doing today? Is there anything on your mind that you\'d like to talk about?', 'basic', '2025-07-28 12:23:58'),
(6, 2, 'about my mental health issues', 'I\'m so sorry to hear that you\'re struggling with your mental health. It takes a lot of courage to open up about your feelings, and I\'m here to listen and support you.\n\nCan you tell me more about what you\'re experiencing right now? What are some of the thoughts, emotions, or physical sensations that are affecting you?', 'basic', '2025-07-28 12:24:15'),
(7, 2, 'hello', 'Hi there! It\'s nice to meet you. How are you today? Is there anything on your mind that you\'d like to talk about or work through? I\'m here to listen and offer support if you need it. Remember, taking care of your mental health is important and we\'re all in this together!', 'basic', '2025-07-29 01:55:18'),
(8, 2, 'hi', 'Hi there! It\'s great to meet you. How are you doing today? Is there anything on your mind that you\'d like to talk about or anything specific you\'d like to know about mental wellness? I\'m here to listen and help if I can.', 'basic', '2025-07-29 14:38:03'),
(9, 2, 'hi', 'Hello there! It\'s wonderful to meet you! How are you doing today? Is there anything on your mind that you\'d like to talk about or any concerns you\'d like to share? I\'m here to listen and offer any support I can!', 'basic', '2025-07-29 15:24:19'),
(10, 2, 'hello', 'Kumusta? Hello there! It\'s great to connect with you today. How are you feeling today? Is there anything on your mind that you\'d like to talk about or work through? I\'m here to listen and support you in any way I can.', 'basic', '2025-07-29 15:31:04'),
(11, 2, 'hello', 'Hello! Welcome to our mental health assistant chat. I\'m here to support and help you with any mental wellness concerns or questions you may have. How are you doing today?', 'basic', '2025-07-29 15:31:56'),
(12, 2, 'hi', 'Hi there! Welcome to our mental wellness chat. How are you doing today? Is there anything on your mind that\'s been bothering you lately? I\'m here to listen and offer support if you need it.', 'basic', '2025-07-29 15:49:11'),
(13, 2, 'hello', 'Hello! It\'s nice to meet you. How are you doing today? Is there anything on your mind that\'s been causing you stress or anxiety lately? I\'m here to listen and offer support if you need it.', 'basic', '2025-07-29 15:49:36'),
(14, 2, 'hello', 'Kumusta? (How are you?) Hello! It\'s great to meet you. I\'m here to support your mental wellness journey. Is there something on your mind that\'s been weighing you down or something you\'d like to talk about? I\'m all ears and happy to listen.', 'basic', '2025-07-30 05:23:13'),
(15, 2, 'i need comfort', 'It sounds like you\'re feeling a little down and could use some comfort. That\'s totally okay! It\'s normal to feel overwhelmed and need some extra support sometimes.\n\nFirst of all, I want you to know that you\'re not alone. It\'s okay to ask for help, and I\'m here to offer you a listening ear. Would you like to talk about what\'s been going on and why you\'re feeling down? Sometimes sharing your feelings with someone who cares can really help you feel better.\n\nAlternatively, if you\'re not feeling like talking, I can offer you some calming phrases or some tips on how to take care of yourself during tough times. Just let me know what you need, and I\'ll do my best to support you.\n\nRemember, you\'re strong and capable, and this too shall pass. You\'re doing the best you can, and that\'s something to be proud of.\n\nHow does that sound? Do you have any specific things on your mind that you\'d like to talk about, or would you rather just focus on calming down and feeling more centered?', 'basic', '2025-07-30 05:23:21'),
(16, 2, 'nba', 'The NBA! While watching basketball games can be a fun and exciting way to spend time, it\'s important to remember that mental wellness should be a top priority for all of us, including basketball players and fans alike.\n\nIn fact, professional basketball players often face unique mental health challenges, such as stress, anxiety, and pressure to perform. Did you know that many NBA players have spoken publicly about their struggles with mental health, including depression, anxiety, and even traumatic brain injuries?\n\nAs a mental health professional, I want to emphasize the importance of prioritizing self-care and seeking help when needed. Whether you\'re a basketball player or just a fan, it\'s okay to acknowledge and address your mental health needs.\n\nHere are some tips for taking care of your mental well-being, inspired by the NBA:\n\n1. Take breaks: Just like basketball players need rest periods during games, you need rest periods in your daily life. Take time to relax, recharge, and refocus.\n2. Prioritize sleep: Aim for 7-9 hours of sleep each night to help your mind and body recover from the day\'s activities.\n3. Stay active: Physical activity, like exercise or yoga, can help improve your mood and reduce stress.\n4. Connect with others: Just as basketball teams rely on teamwork, you can build strong relationships with friends, family, and a mental health professional to support your mental well-being.\n\nRemember, you don\'t have to be a professional basketball player to prioritize your mental health. By taking small steps each day, you can improve your mental wellness and become a happier, healthier version of yourself.\n\nWhat do you think is the most important mental health tip for NBA players and fans alike? Share your thoughts!', '', '2025-07-30 05:24:10'),
(17, 2, 'i feel anxious', 'I\'m here for you. It\'s okay to feel anxious. Would you like some grounding techniques?', '', '2025-07-30 05:24:19'),
(18, 2, 'ayoko na', 'Huminga ka muna. Huwag kang mag-isa sa laban mo. Nandito ako para tumulong.', '', '2025-07-30 05:24:30'),
(19, 2, 'hello po', 'Kumusta ka? Hello there! How are you doing today? Is there anything on your mind that you\'d like to talk about or any concerns about mental wellness you\'d like to discuss? I\'m here to listen and help in any way I can.', 'basic', '2025-08-04 05:18:00'),
(20, 2, 'i need comfort', 'I\'m so sorry to hear that you\'re feeling the need for comfort. That can be a really tough and isolating feeling.\n\nFirst of all, please know that you\'re not alone. We\'re all going to experience times where we feel like we need a big hug or a listening ear, and that\'s completely normal.\n\nWould you like to talk about what\'s been going on and how you\'ve been feeling? Sometimes sharing what\'s on our minds and hearts can be really helpful in feeling better. I\'m here to listen without judgment and offer some support.\n\nIn the meantime, here are a few things that might help you feel a little more comforted:\n\n1. Take some deep breaths: Breathe in through your nose, hold it for a second, and then breathe out through your mouth. Try to focus on the sensation of the air moving in and out of your body.\n2. Put on something cozy: Put on your comfiest pajamas or robe, and snuggle up with a blanket.\n3. Let yourself feel your emotions: It\'s okay to feel sad or overwhelmed. Allow yourself to feel those emotions, and know that they will pass.\n4. Practice self-care: Do something that brings you joy, like taking a warm bath, reading a book, or watching a funny movie.\n\nRemember, you\'re not alone, and it\'s okay to need comfort. You\'re strong, capable, and deserving of love and care.\n\nHow do you feel about talking about what\'s been going on? Is there anything specific that\'s been on your mind, or is there something else I can do to support you right now?', 'basic', '2025-08-04 06:29:59'),
(21, 2, 'hello', 'Kumusta? (How are you?) It\'s great to connect with you today! How can I assist you in maintaining your mental wellness? Do you have any specific concerns or topics you\'d like to discuss?', 'basic', '2025-08-04 06:45:18'),
(22, 2, 'hello', 'Kumusta? Hello there! I\'m glad you reached out. As a mental health assistant, I\'m here to support you in any way I can. What\'s been on your mind lately? Do you have any concerns or issues you\'d like to talk about? I\'m all ears and here to listen.', 'basic', '2025-08-06 04:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `journals`
--

CREATE TABLE `journals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `display_order` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journals`
--

INSERT INTO `journals` (`id`, `user_id`, `title`, `content`, `created_at`, `display_order`, `category`) VALUES
(6, 2, 'PAGOD NAKO SA CAPSTONE NEED KO NG BEBE TIME hehe', '<p>MISS U</p>', '2025-08-04 05:24:05', 0, 'General'),
(11, 2, 'STRESS NA NGA SA CAPSTONE WALA PANG LOVE LIFE', '<p>BALIK KANA</p>', '2025-08-04 09:51:04', 1, 'Health'),
(13, 2, 'IKAW NA BAHALA SA EXAM KO BUKAS BATMAN', '<p>PERFECT SA EXAM HEHE AHA</p>', '2025-08-05 14:47:47', 2, 'Personal'),
(16, 2, 'IKAW NA BAHALA SA EXAM KO BUKAS ata', '<p>asa</p>', '2025-08-06 04:49:56', 3, 'General');

-- --------------------------------------------------------

--
-- Table structure for table `mood_logs`
--

CREATE TABLE `mood_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `emotion` varchar(50) DEFAULT NULL,
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$WzBg3zGCRyzyL5Z8JEB44eZtZPFlN8ur8K7EB8pViRT6xAA3MS1Du', 'admin', '2025-07-28 11:11:32'),
(2, 'romel gamboa', 'romel@gmail.com', '$2y$10$bgWRru8V3ezaA1ONWOEUueeaakp2.J3ajSLzOjO00YKdZ8GHedis2', 'user', '2025-07-28 11:13:49'),
(3, 'Ms.Lianne', 'admin@gmail.com', '$2y$10$ILnd5T.zaJSfSK1N.xZB7uZcvWktd0ccKyCNmBVJ7bg4LHUM2lGoW', 'admin', '2025-07-29 16:04:53'),
(4, 'admin', 'admin@mindcare.com', '$2y$10$NoZ45LzCy2shTKW7Ms/5F.bhX4tdAeJ744ThmIs/7qN8kdPjNo/VS', 'admin', '2025-07-29 16:08:27'),
(5, 'jolas arpom', 'jolas@admin.com', '$2y$10$keAzSpLTDRJO9HYgdsXzw.yBvn2jFufLjIn.hKFvOXVRvxxbn5jX6', NULL, '2025-08-06 04:44:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `journals`
--
ALTER TABLE `journals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mood_logs`
--
ALTER TABLE `mood_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_logs`
--
ALTER TABLE `chat_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `journals`
--
ALTER TABLE `journals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `mood_logs`
--
ALTER TABLE `mood_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD CONSTRAINT `chat_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `journals`
--
ALTER TABLE `journals`
  ADD CONSTRAINT `journals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mood_logs`
--
ALTER TABLE `mood_logs`
  ADD CONSTRAINT `mood_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
