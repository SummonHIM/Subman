<?php

namespace Subman;

use Subman\Administrator;
use Subman\Config;

class Router
{
    /**
     * 路由主函数
     */
    public static function route(): void
    {
        $cfg = new Config();
        $baseUrl = $cfg->getValue('WebSite', 'BaseUrl');

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            switch (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
                case $baseUrl . '/login':
                    Authentication::onPostLogin();
                    break;
                case $baseUrl . '/userconfig':
                    UserConfig::onPost();
                    break;
                case $baseUrl . '/admin':
                    Administrator\Administrator::onPost();
                    break;
                case $baseUrl . '/admin/user':
                    Administrator\User::onUserPost();
                    break;
                case $baseUrl . '/admin/group':
                    Administrator\Group::onGroupPost();
                    break;
                case $baseUrl . '/api/logout':
                    Authentication::onPostLogout();
                    break;
                default:
                    http_response_code(404);
                    break;
            }
        }
        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            switch (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
                case $baseUrl . '/':
                    Subscribes::renderSubscribeLists();
                    break;
                case $baseUrl . '/subscribe':
                    Subscribes::renderSubscribe();
                    break;
                case $baseUrl . '/login':
                    Authentication::onRenderLogin();
                    break;
                case $baseUrl . '/admin':
                    Administrator\Administrator::renderMain();
                    break;
                case $baseUrl . '/admin/user':
                    Administrator\Administrator::renderUser();
                    break;
                case $baseUrl . '/admin/group':
                    Administrator\Administrator::renderGroup();
                    break;
                case $baseUrl . '/userconfig':
                    UserConfig::renderUserConfig();
                    break;
                case $baseUrl . '/api/subscribe':
                    Subscribes::getSubscribesUrl();
                    break;
                default:
                    header("Location: " . $baseUrl . "/");
                    break;
            }
        }
    }
}
