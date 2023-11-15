-- 每一个小时清理已过期的 Session，可选功能。

DELIMITER //

CREATE EVENT IF NOT EXISTS `clean_expire_session`
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    DELETE FROM `user_sessions` WHERE `expire` < NOW();
END //

DELIMITER ;

SHOW EVENTS;

SHOW VARIABLES LIKE 'event_scheduler';
SET GLOBAL event_scheduler = ON;
