CREATE TABLE `star_rating` (
  `id` int(10) UNSIGNED NOT NULL,
  `rating_id` varchar(255) NOT NULL,
  `rating_avg` float NOT NULL,
  `total_votes` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `star_rating_ip` (
  `id` int(10) UNSIGNED NOT NULL,
  `rating_id` int(10) UNSIGNED NOT NULL,
  `rating_value` tinyint(2) UNSIGNED NOT NULL,
  `rating_ip` varchar(16) NOT NULL DEFAULT '0.0.0.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `star_rating`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `star_rating_ip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rating_id` (`rating_id`);

ALTER TABLE `star_rating`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `star_rating_ip`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
