<?php
/**
 * 数据库配置
 */
// PDO 数据库类型
$cfg['Database']['Type'] = "mysql";
// PDO 数据库编码
$cfg['Database']['Charset'] = "utf8mb4";
// PDO 数据库主机名
$cfg['Database']['Host'] = "localhost";
// PDO 数据库用户名
$cfg['Database']['Username'] = "username";
// PDO 数据库密码
$cfg['Database']['Password'] = "password";
// PDO 数据库库名
$cfg['Database']['Name'] = "subs";

/**
 * 本站设置
 */
// 主目录设置，用于子目录环境运行
$cfg['WebSite']['BaseUrl'] = "";
// 启用调试模式
$cfg['WebSite']['Debug'] = false;
// 自定义 SubConverter 服务器
$cfg['WebSite']['SubConverterUrl'] = "https://sub.xeton.dev/sub?";