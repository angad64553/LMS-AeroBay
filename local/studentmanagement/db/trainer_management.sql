CREATE TABLE IF NOT EXISTS `mdl_trainer_school_map` (
  `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
  `trainerid` BIGINT(10) NOT NULL,
  `schoolid` BIGINT(10) NOT NULL,
  `gradeid` BIGINT(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_traschmap_traschgra_uix` (`trainerid`, `schoolid`, `gradeid`),
  KEY `mdl_traschmap_tra_ix` (`trainerid`),
  KEY `mdl_traschmap_sch_ix` (`schoolid`),
  KEY `mdl_traschmap_gra_ix` (`gradeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mdl_trainer_course_map` (
  `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
  `trainerid` BIGINT(10) NOT NULL,
  `courseid` BIGINT(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_tracomap_tracou_uix` (`trainerid`, `courseid`),
  KEY `mdl_tracomap_tra_ix` (`trainerid`),
  KEY `mdl_tracomap_cou_ix` (`courseid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Run these ALTER statements if you already created trainer_school_map before grade selection was added.
ALTER TABLE `mdl_trainer_school_map`
  ADD COLUMN `gradeid` BIGINT(10) NOT NULL AFTER `schoolid`;

ALTER TABLE `mdl_trainer_school_map`
  DROP INDEX `mdl_traschmap_trasch_uix`;

ALTER TABLE `mdl_trainer_school_map`
  ADD UNIQUE KEY `mdl_traschmap_traschgra_uix` (`trainerid`, `schoolid`, `gradeid`);

ALTER TABLE `mdl_trainer_school_map`
  ADD KEY `mdl_traschmap_gra_ix` (`gradeid`);
