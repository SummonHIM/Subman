<?php

namespace Subman;

use Subman\Config;
use Subman\Database;
use Subman\Authentication;

class UserConfig
{
    public $cfg;
    public $db;

    public function __construct()
    {
        $this->cfg = new Config();
        $this->db = new Database();
    }

    private function handleChangePasswd()
    {
        if (empty($_POST['oldPasswd']) || empty($_POST['newPasswd']) || empty($_POST['confirmPasswd'])) {
            $this->renderUserConfig("旧密码、新密码和确认新密码输入框不得为空。");
            http_response_code(405);
            return;
        }

        $authentication = new Authentication();
        if ($authentication->login($_SESSION['username'], $_POST['oldPasswd'])['execStatus'] == 'Failed') {
            $this->renderUserConfig("旧密码不正确。");
            http_response_code(401);
            return;
        }

        if ($_POST['newPasswd'] !== $_POST['confirmPasswd']) {
            $this->renderUserConfig("新密码与确认新密码不匹配。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['newPasswd']) < 8 || !preg_match('/[A-Z]/', $_POST['newPasswd']) || !preg_match('/[a-z]/', $_POST['newPasswd']) || !preg_match('/\d/', $_POST['newPasswd'])) {
            $this->renderUserConfig("密码必须包含至少一个大写字母、一个小写字母和一个数字，且长度不少于8个字符。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->updateRow("users", array('password' => password_hash($_POST['newPasswd'], PASSWORD_DEFAULT)), array('uid' => $_SESSION['uid']));
            $this->renderUserConfig("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderUserConfig($e->getMessage());
            } else {
                $this->renderUserConfig("服务器内部错误！保存失败。");
            }
        }
    }

    private function handlechangeCustomConfig()
    {
        if (strlen($_POST['customConfigUrl']) > 255) {
            $this->renderUserConfig("输入的自定义远程配置长度超过 255，或不符合储存规范。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->updateRow("users", array('custom_config' => $_POST['customConfigUrl']), array('uid' => $_SESSION['uid']));
            $this->renderUserConfig("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderUserConfig($e->getMessage());
            } else {
                $this->renderUserConfig("服务器内部错误！保存失败。");
            }
        }
    }

    private function handlechangeUserInfo()
    {
        $uuid_pattern = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';
        if (!preg_match($uuid_pattern, $_POST['uid'])) {
            $this->renderUserConfig("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['username']) > 25) {
            $this->renderUserConfig("输入的用户名长度超过 25，或用户名不符合储存规范。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->updateRow("users", array('uid' => $_POST['uid'], 'username' => $_POST['username']), array('uid' => $_SESSION['uid']));
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['uid'] = $_POST['uid'];
            $this->renderUserConfig("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderUserConfig($e->getMessage());
            } else {
                $this->renderUserConfig("服务器内部错误！保存失败。");
            }
        }
    }

    public static function onPost()
    {
        $self = new self();
        switch ($_POST['type']) {
            case "changePasswd":
                $self->handleChangePasswd();
                break;
            case "changeCustomConfig":
                $self->handlechangeCustomConfig();
                break;
            case "changeUserInfo":
                $self->handlechangeUserInfo();
                break;
            default:
                header("Location: " . $self->cfg->getValue('WebSite', 'BaseUrl') . "/userconfig");
                break;
        }
    }

    public static function renderUserConfig(?string $result = null)
    {
        $self = new self();

        if (!isset($_SESSION['username'])) {
            header("Location: " . $self->cfg->getValue('WebSite', 'BaseUrl') . "/");
            exit();
        } else {
            $db = new Database();
            $user = $db->getRowbyName("users", array("uid" => $_SESSION['uid']));

            $loader = new \Twig\Loader\FilesystemLoader("templates");
            $twig = new \Twig\Environment($loader);

            $template = $twig->load("userConfig.twig");
            echo $template->render(array(
                'customConfigUrl' => $user['custom_config'],
                'isAdmin' => $user['isadmin'],
                'baseUrl' => $self->cfg->getValue('WebSite', 'BaseUrl'),
                'username' => $_SESSION['username'],
                'uid' => $_SESSION['uid'],
                'result' => $result
            ));
        }
    }
}
