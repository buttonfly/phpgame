/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.5.28-log : Database - dbGame
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`dbGame` /*!40100 DEFAULT CHARACTER SET utf8 */;

/*Table structure for table `user` */

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `userid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL,
  `registertime` int(11) DEFAULT NULL,
  `state` tinyint(4) unsigned DEFAULT '1',
  `lastlogip` varchar(15) DEFAULT NULL,
  `lastlogtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

/*Data for the table `user` */

insert  into `user`(`userid`,`username`,`password`,`registertime`,`state`,`lastlogip`,`lastlogtime`) values (1,'aaa','4e46e97687102de3f75afd9e940a4161',NULL,1,'localhost',2013),(2,'bbb','4e46e97687102de3f75afd9e940a4161',NULL,1,'localhost',2013),(3,'ccc','4e46e97687102de3f75afd9e940a4161',NULL,1,'localhost',2013),(4,'eee','4e46e97687102de3f75afd9e940a4161',NULL,1,'localhost',2013),(5,'fff','4e46e97687102de3f75afd9e940a4161',NULL,1,'localhost',2013),(6,'ggg','4e46e97687102de3f75afd9e940a4161',NULL,1,'localhost',2012),(7,'qqq','4e46e97687102de3f75afd9e940a4161',NULL,1,'localhost',2012);

/*Table structure for table `user_login` */

DROP TABLE IF EXISTS `user_login`;

CREATE TABLE `user_login` (
  `userid` int(11) NOT NULL,
  `key` varchar(50) NOT NULL,
  `sip` varchar(15) NOT NULL DEFAULT '',
  `cip` varchar(15) NOT NULL,
  `port` varchar(6) NOT NULL DEFAULT '0',
  `roleid` int(11) NOT NULL DEFAULT '0',
  `loginflag` enum('0','1') NOT NULL DEFAULT '1',
  `lineid` smallint(6) unsigned NOT NULL,
  `alogintime` int(11) NOT NULL,
  `slogintime` int(11) NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `user_login` */

insert  into `user_login`(`userid`,`key`,`sip`,`cip`,`port`,`roleid`,`loginflag`,`lineid`,`alogintime`,`slogintime`) values (2,'4d7e50e1e00b959b5e93876fb0428d66','localhost','localhost','6666',20,'1',0,2013,2013),(3,'5fb5d3c8fbfadc12c25ffc4093a64ed8','localhost','localhost','6666',21,'1',0,2013,2013),(4,'bcbdcc8e518c19fad060ff1735f302ab','localhost','localhost','6666',22,'1',0,2013,2013),(5,'d6cde94e00ff2fe7cdbfa292147c0618','localhost','localhost','6666',23,'1',0,2013,2013),(6,'9b61da3025cef1ec8ac9565326021b0e','localhost','localhost','6666',24,'1',0,2012,2012);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
