/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- 导出  表 subs.groups 结构
CREATE TABLE IF NOT EXISTS `groups` (
  `gid` uuid NOT NULL DEFAULT uuid(),
  `name` varchar(25) NOT NULL,
  `sub_hp` varchar(255) DEFAULT NULL,
  `sub_account` varchar(255) DEFAULT NULL,
  `sub_password` varchar(64) DEFAULT NULL,
  `sub_aff` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 数据导出被取消选择。

-- 导出  表 subs.groupshare 结构
CREATE TABLE IF NOT EXISTS `groupshare` (
  `gsid` uuid NOT NULL DEFAULT uuid(),
  `gid` uuid NOT NULL DEFAULT uuid(),
  `name` text NOT NULL,
  `account` varchar(255) NOT NULL,
  `password` varchar(50) NOT NULL,
  `manage` varchar(255) NOT NULL,
  PRIMARY KEY (`gsid`) USING BTREE,
  KEY `gid` (`gid`) USING BTREE,
  CONSTRAINT `groupshare_ibfk_1` FOREIGN KEY (`gid`) REFERENCES `groups` (`gid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 数据导出被取消选择。

-- 导出  表 subs.subscribes 结构
CREATE TABLE IF NOT EXISTS `subscribes` (
  `sid` uuid NOT NULL DEFAULT uuid(),
  `gid` uuid NOT NULL DEFAULT uuid(),
  `name` varchar(25) NOT NULL,
  `original_url` varchar(255) NOT NULL,
  `convert_url` varchar(255) NOT NULL,
  `target` char(15) NOT NULL DEFAULT 'clash',
  `options` text NOT NULL DEFAULT 'emoji=true&udp=true&new_name=true',
  PRIMARY KEY (`sid`) USING BTREE,
  KEY `gid` (`gid`) USING BTREE,
  CONSTRAINT `subscribes_ibfk_1` FOREIGN KEY (`gid`) REFERENCES `groups` (`gid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 数据导出被取消选择。

-- 导出  表 subs.users 结构
CREATE TABLE IF NOT EXISTS `users` (
  `uid` uuid NOT NULL DEFAULT uuid(),
  `username` varchar(25) NOT NULL,
  `password` varchar(61) NOT NULL,
  `isadmin` tinyint(1) NOT NULL DEFAULT 0,
  `custom_config` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  CONSTRAINT `conIsAdmin` CHECK (`isadmin` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 数据导出被取消选择。

-- 导出  表 subs.usersubs 结构
CREATE TABLE IF NOT EXISTS `usersubs` (
  `usid` uuid NOT NULL DEFAULT uuid(),
  `uid` uuid NOT NULL DEFAULT uuid(),
  `gid` uuid NOT NULL DEFAULT uuid(),
  `expire` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`usid`) USING BTREE,
  KEY `usersubs_ibfk_uid` (`uid`) USING BTREE,
  KEY `usersubs_ibfk_gid` (`gid`) USING BTREE,
  CONSTRAINT `usersubs_ibfk_gid` FOREIGN KEY (`gid`) REFERENCES `groups` (`gid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `usersubs_ibfk_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 数据导出被取消选择。

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
