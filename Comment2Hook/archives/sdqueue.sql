CREATE TABLE `sdq_jobs` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`channel` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`job` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`is_done` BOOLEAN NOT NULL DEFAULT 0,
	`payload` TEXT NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` INT(10) UNSIGNED NOT NULL,
	`done_at` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;