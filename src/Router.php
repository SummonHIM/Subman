<?php

namespace Subman;

use Subman\Config;
use Subman\Clients;
use Subman\Subscribes;
use Subman\Authentication;

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
                    Administrator::renderAdministrator();
                    break;
                case $baseUrl . '/userconfig':
                    UserConfig::renderUserConfig();
                    break;
                case $baseUrl . '/clients':
                    Clients::renderClients();
                    break;
                    // Begin API route
                case $baseUrl . '/api/subscribe':
                    Subscribes::getSubscribesUrl();
                    break;
                case $baseUrl . '/api.php':
                    Subscribes::getSubscribesUrl();
                    break;
                    // End API route
                default:
                    header("Location: " . $baseUrl . "/");
                    break;
            }
        }
    }
}
