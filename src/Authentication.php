<?php

namespace Subman;

use Subman\Config;
use Subman\Database;

class Authentication
{
    public $cfg;
    public $db;
    public $uuid;

    public function __construct()
    {
        $this->cfg = new Config();
        $this->db = new Database();
        $this->uuid = new UUID();
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
                $retMsg = "用户名或密码错误！";
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
     * 检查保持登录的 Session ID
     * @param string $sessionID Session ID
     */
    private function checkSession(string $sessionID): void
    {
        $data = $this->db->getRowbyName("user_sessions", "uid, expire", array('session_id' => $sessionID));

        if (date('Y-m-d H:i:s') < $data['expire']) {
            $newExpire = strtotime('+1 month', time());

            $this->db->updateRow(
                "user_sessions",
                array('expire' => date("Y-m-d H:i:s", $newExpire)),
                array(
                    'session_id' => $sessionID,
                    'uid' => $data['uid']
                )
            );

            setcookie("sm_session", $sessionID, $newExpire, "/");

            $_SESSION['uid'] = $data['uid'];
            $_SESSION['username'] = $this->db->getRowbyName("users", "username", array("uid" => $data['uid']))['username'];
        } else {
            setcookie("sm_session", "", time() - 3600, "/");
        }

        if (!empty($data['uid'])) {
            $this->deleteExpireSession($data['uid']);
        }
    }

    /**
     * 创建保持登录 Session
     * @param string $uid 用户 ID
     */
    private function createSession(string $uid): void
    {
        if (empty($uid) || !$this->uuid->checkUUID($uid))
            return;

        $newSessionID = $this->uuid->generateUUID();
        $newExpire = strtotime('+1 month', time());

        try {
            $this->db->insertNewRow(
                "user_sessions",
                array(
                    'session_id' => $newSessionID,
                    'uid' => $uid,
                    'expire' => date("Y-m-d H:i:s", $newExpire),
                ),
            );

            setcookie("sm_session", $newSessionID, $newExpire, "/");
        } catch (\PDOException) {
            return;
        }
    }

    /**
     * 删除过期 Session
     * @param string $uid 用户 ID
     */
    private function deleteExpireSession(string $uid): void
    {
        $data = $this->db->getRowbyName("user_sessions", "*", array('uid' => $uid), true);
        foreach ($data as $i) {
            if (date('Y-m-d H:i:s') > $i['expire']) {
                $this->db->deleteRow(
                    "user_sessions",
                    array(
                        'session_id' => $i['session_id'],
                        'uid' => $i['uid']
                    )
                );
            }
        }
    }

    /**
     * 渲染登录页面
     * @param string $loginResult 登录结果，默认 "Success"。twig 逻辑中 "Success" 会隐藏错误提示框。
     */
    public static function onRenderLogin(string $loginResult = "Success"): void
    {
        $self = new self();
        $baseUrl = $self->cfg->getValue('WebSite', 'BaseUrl');

        if (!empty($_COOKIE['sm_session']))
            $self->checkSession($_COOKIE['sm_session']);

        if (isset($_SESSION['username']) || isset($_SESSION['uid'])) {
            header("Location: " . $baseUrl . "/");
        } else {
            $loader = new \Twig\Loader\FilesystemLoader("templates");
            $twig = new \Twig\Environment($loader);
            $template = $twig->load("login.twig");
            echo $template->render([
                "loginResult" => $loginResult,
                'baseUrl' => $baseUrl
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

        if (!isset($_POST['formLoginUsername']) && !isset($_POST['formLoginPasswd']))
            return;

        $loginResult = $self->login($_POST['formLoginUsername'], $_POST['formLoginPasswd']);
        if ($loginResult['message'] == "Success") {
            $_SESSION['username'] = $loginResult['username'];
            $_SESSION['uid'] = $loginResult['uid'];
        } else {
            $self->onRenderLogin($loginResult['message']);
            return;
        }

        if (isset($_POST['keepLogin']))
            $self->createSession($loginResult['uid']);

        if (!empty($loginResult['uid']))
            $self->deleteExpireSession($loginResult['uid']);

        header("Location: " . $baseUrl . "/");
    }

    /**
     * Post 登出时的处理函数
     */
    public static function onPostLogout(): void
    {
        $self = new self();
        $baseUrl = $self->cfg->getValue('WebSite', 'BaseUrl');

        setcookie("sm_session", "", time() - 3600, "/");
        $self->db->deleteRow(
            "user_sessions",
            array(
                'session_id' => $_COOKIE['sm_session'],
                'uid' => $_SESSION['uid']
            )
        );

        session_destroy();

        header("Location: " . $baseUrl . "/");
    }
}
