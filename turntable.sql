/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50635
 Source Host           : localhost
 Source Database       : turntable

 Target Server Type    : MySQL
 Target Server Version : 50635
 File Encoding         : utf-8

 Date: 07/06/2019 20:33:56 PM
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `ac_turntable_game`
-- ----------------------------
DROP TABLE IF EXISTS `ac_turntable_game`;
CREATE TABLE `ac_turntable_game` (
  `turntable_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '大转盘id',
  `shop_id` int(11) NOT NULL COMMENT '商户id',
  `title` varchar(255) NOT NULL COMMENT '大转盘名称',
  `description` text NOT NULL COMMENT '大转盘介绍',
  `num_by_one` int(11) NOT NULL COMMENT '单个用户一天可转次数',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `updated` int(11) NOT NULL COMMENT '更新时间',
  `listorder` int(11) NOT NULL COMMENT '排序顺序（倒序）',
  `status` tinyint(1) NOT NULL COMMENT '状态。0禁用，1正常',
  `start_date` int(11) NOT NULL COMMENT '转盘开始时间',
  `end_date` int(11) NOT NULL COMMENT '转盘结束时间',
  `frequncy` tinyint(1) NOT NULL DEFAULT '1' COMMENT '频率。关联num_by_one。1是每天，2是活动期内',
  PRIMARY KEY (`turntable_id`) USING BTREE,
  KEY `商户id` (`shop_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='大转盘表';

-- ----------------------------
--  Records of `ac_turntable_game`
-- ----------------------------
BEGIN;
INSERT INTO `ac_turntable_game` VALUES ('1', '1', '大转盘', '这是一个大转盘', '3', '0', '0', '1', '1', '1561392000', '1564455600', '1');
COMMIT;

-- ----------------------------
--  Table structure for `ac_turntable_game_log`
-- ----------------------------
DROP TABLE IF EXISTS `ac_turntable_game_log`;
CREATE TABLE `ac_turntable_game_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL COMMENT '商户id',
  `turntable_id` int(11) NOT NULL COMMENT '大转盘id',
  `prize_id` int(11) NOT NULL COMMENT '奖品id',
  `userid` int(11) NOT NULL COMMENT '用户id',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否有奖。0：没奖，1：有奖',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态（1：显示 0：不显示）',
  `created` int(11) unsigned NOT NULL COMMENT '时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `union` (`userid`,`created`) USING BTREE,
  KEY `turntable_id` (`turntable_id`) USING BTREE,
  KEY `prize_id` (`prize_id`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=323 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='大转盘日志表';

-- ----------------------------
--  Table structure for `ac_turntable_game_prize`
-- ----------------------------
DROP TABLE IF EXISTS `ac_turntable_game_prize`;
CREATE TABLE `ac_turntable_game_prize` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '奖品id',
  `turntable_id` int(11) NOT NULL COMMENT '大转盘id',
  `prize_name` varchar(255) NOT NULL COMMENT '奖品名称',
  `img_url` varchar(255) NOT NULL COMMENT '图片地址',
  `num` int(11) unsigned NOT NULL COMMENT '奖品数量',
  `probability` float(5,2) NOT NULL COMMENT '中奖概率',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '奖品状态（0：下架 1：正常）',
  `type` tinyint(1) NOT NULL COMMENT '是否有奖。0：没奖，1：有奖',
  `listorder` int(11) NOT NULL COMMENT '排序顺序（倒序）',
  `created` int(11) NOT NULL COMMENT '新增时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `turntable_id` (`turntable_id`) USING BTREE,
  KEY `type` (`type`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='大转盘活动表';

-- ----------------------------
--  Records of `ac_turntable_game_prize`
-- ----------------------------
BEGIN;
INSERT INTO `ac_turntable_game_prize` VALUES ('1', '1', '一等奖', '', '10', '1.00', '1', '1', '1', '0'), ('2', '1', '二等奖', '', '10', '1.00', '1', '1', '1', '0'), ('3', '1', '三等奖', '', '2', '1.00', '1', '1', '1', '0'), ('4', '1', '谢谢惠顾', '', '9896', '69.00', '1', '0', '1', '0'), ('5', '1', '哇特等奖', 'img_url', '1', '1.00', '1', '1', '1', '2019');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
