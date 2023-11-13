<?php

namespace Subman;

use Subman\Config;
use Subman\Database;

class Authentication
{
    public $cfg;
    public $db;

    public function __construct()
    {
        $this->cfg = new Config();
        $this->db = new Database();
    }

    /**
     * 主登录逻辑
     * @param string $username 用户名
     * @param string $password 密码
     * @return 返回数组，其中包括: execStatus
     */
    public function login(string $username, string $password): array
    {
        try {
            $row = $this->db->getRowbyName("users", "uid, username, password", array("username" => $username));

            if ($row) {
                $hashed_password = $row['password'];

                if (password_verify($password, $hashed_password)) {
                    $execStatus = "Success";
                    $retMsg = "Success";
                } else {
                    $execStatus = "Failed";
                    $retMsg = "用户名或密码错误！";
                }
            } else {
                $execStatus = "Failed";
                $retMsg = "用户名或密码错误！"; //Username not exist
            }
        } catch (\PDOException $e) {
            $execStatus = "Failed";
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $retMsg = $e->getMessage();
            } else {
                $retMsg = "服务器内部错误！";
            }
        }

        $ret = array(
            'execStatus' => $execStatus,
            'message' => $retMsg,
        );

        if ($row) {
            $ret['username'] = $row['username'];
            $ret['uid'] = $row['uid'];
        }

        return $ret;
    }

    /**
     * 渲染登录页面
     * @param string $loginResult 登录结果，默认 "Success"。twig 逻辑中 "Success" 会隐藏错误提示框。
     */
    public static function onRenderLogin(string $loginResult = "Success"): void
    {
        $cfg = new Config();
        $baseUrl = $cfg->getValue('WebSite', 'BaseUrl');

        if (isset($_SESSION['username']) || isset($_SESSION['uid'])) {
            header("Location: " . $baseUrl . "/");
        } else {
            $loader = new \Twig\Loader\FilesystemLoader("templates");
            $twig = new \Twig\Environment($loader);
            $template = $twig->load("login.twig");
            echo $template->render([
                "loginResult" => $loginResult,
                'baseUrl' => $cfg->getValue('WebSite', 'BaseUrl')
            ]);
        }
    }

    /**
     * Post 登录时的处理函数
     */
    public static function onPostLogin(): void
    {
        $self = new self();
        $baseUrl = $self->cfg->getValue('WebSite', 'BaseUrl');

        if (isset($_POST['formLoginUsername']) && isset($_POST['formLoginPasswd'])) {
            $loginResult = $self->login($_POST['formLoginUsername'], $_POST['formLoginPasswd']);
            if ($loginResult['message'] == "Success") {
                $_SESSION['username'] = $loginResult['username'];
                $_SESSION['uid'] = $loginResult['uid'];
                header("Location: " . $baseUrl . "/");
            } else {
                $self->onRenderLogin($loginResult['message']);
            }
        }
    }

    /**
     * Post 登出时的处理函数
     */
    public static function onPostLogout(): void
    {
        $cfg = new Config();
        $baseUrl = $cfg->getValue('WebSite', 'BaseUrl');
        session_destroy();
        header("Location: " . $baseUrl . "/");
    }
}
