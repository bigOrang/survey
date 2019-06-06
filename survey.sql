/*
SQLyog Ultimate v12.5.0 (64 bit)
MySQL - 5.7.18-log : Database - db_mcp_1097
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`db_mcp_1097` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

USE `db_mcp_1097`;

/*Table structure for table `t_survey` */

DROP TABLE IF EXISTS `t_survey`;

CREATE TABLE `t_survey` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `title` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '问卷标题',
  `start_time` timestamp NOT NULL COMMENT '开始时间',
  `end_time` timestamp NOT NULL COMMENT '结束时间',
  `description` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '问卷描述',
  `survey_user` tinyint(1) NOT NULL DEFAULT '1' COMMENT '问卷对象',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态，1-草稿，2-已发布',
  `submit_at` timestamp NOT NULL COMMENT '提交时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问卷调查--主表';

/*Data for the table `t_survey` */

insert  into `t_survey`(`id`,`title`,`start_time`,`end_time`,`description`,`survey_user`,`status`,`submit_at`,`created_at`,`updated_at`,`deleted_at`) values 
(54,'123','2019-05-08 11:26:10','2019-06-07 11:26:12','123',1,2,'2019-05-08 11:26:29','2019-05-08 11:26:30','2019-05-08 11:26:30',NULL);

/*Table structure for table `t_survey_topic` */

DROP TABLE IF EXISTS `t_survey_topic`;

CREATE TABLE `t_survey_topic` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增iD',
  `s_id` int(11) NOT NULL COMMENT '问卷调查主键ID',
  `t_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '问卷题目标题',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '问卷题目类型，1-单选，2-多选，3-简答',
  `is_required` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否必填，1-必须，0-非必须',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问卷调查--题目表';

/*Data for the table `t_survey_topic` */

insert  into `t_survey_topic`(`id`,`s_id`,`t_name`,`type`,`is_required`,`created_at`,`updated_at`,`deleted_at`) values 
(1,54,'122',1,1,'2019-06-06 11:03:43','2019-06-06 11:03:43',NULL);

/*Table structure for table `t_survey_topic_des` */

DROP TABLE IF EXISTS `t_survey_topic_des`;

CREATE TABLE `t_survey_topic_des` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `t_id` int(11) NOT NULL COMMENT '题目表主键',
  `content` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '描述内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问卷调查--题目内容表';

/*Data for the table `t_survey_topic_des` */

insert  into `t_survey_topic_des`(`id`,`t_id`,`content`) values 
(1,1,'12122'),
(2,1,'22');

/*Table structure for table `t_survey_type` */

DROP TABLE IF EXISTS `t_survey_type`;

CREATE TABLE `t_survey_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-显示，0-隐藏',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `t_survey_type` */

insert  into `t_survey_type`(`id`,`name`,`is_show`) values 
(1,'居民',1);

/*Table structure for table `t_survey_user` */

DROP TABLE IF EXISTS `t_survey_user`;

CREATE TABLE `t_survey_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `s_id` int(11) NOT NULL COMMENT '问卷主表自增ID',
  `t_id` int(11) NOT NULL COMMENT '问卷表问题自增ID',
  `td_id` int(11) NOT NULL COMMENT '问卷表答案自增ID',
  `answer` varbinary(100) DEFAULT NULL COMMENT '简答题回答',
  `user_id` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '回答问卷用户',
  `user_name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '回答问卷用户姓名',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '编辑时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问卷结果表';

/*Data for the table `t_survey_user` */

insert  into `t_survey_user`(`id`,`s_id`,`t_id`,`td_id`,`answer`,`user_id`,`user_name`,`created_at`,`updated_at`,`deleted_at`) values 
(1,54,1,2,'','admin','admin','2019-06-06 11:03:55','2019-06-06 11:03:55',NULL);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
