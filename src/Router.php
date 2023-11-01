<?php

namespace Subman;

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
                    Administrator::onPost();
                    break;
                case $baseUrl . '/admin/user':
                    AdminUser::onUserPost();
                    break;
                case $baseUrl . '/admin/group':
                    AdminGroup::onGroupPost();
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
                    Subscribes::renderSubscribes();
                    break;
                case $baseUrl . '/login':
                    Authentication::onRenderLogin();
                    break;
                case $baseUrl . '/admin':
                    Administrator::renderMain();
                    break;
                case $baseUrl . '/admin/user':
                    Administrator::renderUser();
                    break;
                case $baseUrl . '/admin/group':
                    Administrator::renderGroup();
                    break;
                case $baseUrl . '/userconfig':
                    UserConfig::renderUserConfig();
                    break;
                case $baseUrl . '/clients':
                    Clients::renderClients();
                    break;
                case $baseUrl . '/api/subscribe':
                    Subscribes::getSubscribesUrl();
                    break;
                case $baseUrl . '/api.php':
                    Subscribes::getSubscribesUrl();
                    break;
                default:
                    header("Location: " . $baseUrl . "/");
                    break;
            }
        }
    }
}
