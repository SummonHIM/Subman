<?php

namespace Subman;

use Subman\Config;
use Subman\Database;

class Clients
{
    /**
     * 渲染客户端页面
     */
    public static function renderClients()
    {
        $cfg = new Config();

        if (isset($_SESSION['username'])) {
            $db = new Database();

            $loader = new \Twig\Loader\FilesystemLoader("templates");
            $twig = new \Twig\Environment($loader);

            $imageCDN = empty($cfg->getValue('WebSite', 'ClientImgCDN')) ? $cfg->getValue('WebSite', 'BaseUrl') : $cfg->getValue('WebSite', 'ClientImgCDN');

            $template = $twig->load("clients.twig");
            echo $template->render(array(
                'isAdmin' => $db->getRowbyName("users", 'isadmin', array("uid" => $_SESSION['uid']))['isadmin'],
                'baseUrl' => $cfg->getValue('WebSite', 'BaseUrl'),
                'imageCDN' => $imageCDN,
                'username' => $_SESSION['username'],
                'pageClient' => true
            ));
        } else {
            header("Location: " . $cfg->getValue('WebSite', 'BaseUrl') . "/");
            exit();
        }
    }
}
