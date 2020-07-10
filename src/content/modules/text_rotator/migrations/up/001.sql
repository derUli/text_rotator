CREATE TABLE `{prefix}rotating_text` 
  ( 
     `id`        INT NOT NULL auto_increment, 
     `animation` VARCHAR(200) NOT NULL, 
     `separator` VARCHAR(5) NOT NULL, 
     `speed`     INT NOT NULL, 
     `words`     TEXT NOT NULL, 
     PRIMARY KEY (`id`) 
  ) 
engine = innodb 
DEFAULT charset=utf8mb4