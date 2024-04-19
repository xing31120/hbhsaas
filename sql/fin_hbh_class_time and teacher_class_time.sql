/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50738
 Source Host           : 127.0.0.1:3306
 Source Schema         : finance

 Target Server Type    : MySQL
 Target Server Version : 50738
 File Encoding         : 65001

 Date: 05/12/2023 17:26:44
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for fin_hbh_class_time
-- ----------------------------
DROP TABLE IF EXISTS `fin_hbh_class_time`;
CREATE TABLE `fin_hbh_class_time`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_time` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `end_time` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '上课时间配置表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of fin_hbh_class_time
-- ----------------------------
INSERT INTO `fin_hbh_class_time` VALUES (1, '09:00', '10:00', 1);
INSERT INTO `fin_hbh_class_time` VALUES (2, '10:00', '11:00', 1);
INSERT INTO `fin_hbh_class_time` VALUES (3, '11:00', '12:00', 1);
INSERT INTO `fin_hbh_class_time` VALUES (4, '14:00', '15:00', 1);
INSERT INTO `fin_hbh_class_time` VALUES (5, '15:00', '16:00', 1);
INSERT INTO `fin_hbh_class_time` VALUES (6, '16:00', '17:00', 1);
INSERT INTO `fin_hbh_class_time` VALUES (7, '17:00', '18:00', 1);

-- ----------------------------
-- Table structure for fin_hbh_teacher_class_time
-- ----------------------------
DROP TABLE IF EXISTS `fin_hbh_teacher_class_time`;
CREATE TABLE `fin_hbh_teacher_class_time`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL DEFAULT 0 COMMENT 'shop_id',
  `uid` int(11) NOT NULL DEFAULT 0 COMMENT 'fin_hbh_users表主键',
  `course_id` int(11) NOT NULL COMMENT 'course表主键',
  `class_time_id` int(11) NOT NULL COMMENT 'fin_hbh_class_time表主键',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '教师关联课时表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fin_hbh_teacher_class_time
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
