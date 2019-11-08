CREATE TABLE `star_rating` (
  `id` int(10) UNSIGNED NOT NULL,
  `rating_id` varchar(20) NOT NULL,
  `rating_avg` float NOT NULL,
  `total_votes` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `star_rating`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `star_rating`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;