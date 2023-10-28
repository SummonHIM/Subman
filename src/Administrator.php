<?php

namespace Subman;

use Subman\Config;
use Subman\Database;

class Administrator {
    /**
     * 渲染后台管理页面
     */
    public static function renderAdministrator()
    {
        $db = new Database();
        $cfg = new Config();
        
        if (!isset($_SESSION['username']) || $db->getRowbyName("users", array("uid" => $_SESSION['uid']))['isadmin'] != 1) {
            header("Location: " . $cfg->getValue('WebSite', 'BaseUrl') . "/");
            exit();
        } else {
            $loader = new \Twig\Loader\FilesystemLoader("templates");
            $twig = new \Twig\Environment($loader);

            $template = $twig->load("administrator.twig");
            echo $template->render(array(
                'isAdmin' => $db->getRowbyName("users", array("uid" => $_SESSION['uid']))['isadmin'],
                'baseUrl' => $cfg->getValue('WebSite', 'BaseUrl'),
                'username' => $_SESSION['username'],
            ));
        }
    }
}