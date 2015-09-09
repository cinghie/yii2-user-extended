ALTER TABLE `profile` 
ADD `firstname` VARCHAR(255) NULL AFTER `name`, 
ADD `lastname` VARCHAR(255) NULL AFTER `firstname`,
ADD `birthday` DATE NOT NULL AFTER `lastname`;