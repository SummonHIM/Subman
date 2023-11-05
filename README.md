# Subman
Subscribe Manager

## 如何使用
1. 向数据库导入位于 `resources/sql/subs.sql` 的脚本。
2. `composer install`
3. 复制 `config.example.php` 到 `config.php`。填入适当的数据。
4. 放入你的网页服务器中即可。

## Nginx
### 无子路径
```
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```
### 有子路径
```
location /subs/ {
    try_files $uri $uri/ /subs/index.php?$query_string;
}
```

## 其他信息
- 测试时使用 PHP 版本为 8.2.11。
- MySQL 数据库可能会对 UUID 类型报错，如果嫌麻烦建议用 MariaDB。

## 感谢
- ChatGPT
