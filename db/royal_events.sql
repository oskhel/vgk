CREATE TABLE `royal_events`
(
    `id`          varchar(64) NOT NULL,
    `title`       text,
    `participant` varchar(255) DEFAULT NULL,
    `location`    varchar(255) DEFAULT NULL,
    `date`        date         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;