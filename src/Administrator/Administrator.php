<?php

namespace Subman\Administrator;

use Subman\UUID;
use Subman\Config;
use Subman\Database;

class Administrator
{
    public $cfg;
    public $db;
    public $twig;
    public $uuid;
    public $isAdmin;

    public function __construct()
    {
        $this->uuid = new UUID();
        $this->cfg = new Config();
        $this->db = new Database();

        $twigLoader = new \Twig\Loader\FilesystemLoader("templates");
        $this->twig = new \Twig\Environment($twigLoader);

        $this->isAdmin = $this->db->getRowbyName("users", 'isadmin', array("uid" => $_SESSION['uid']))['isadmin'];
        if (!isset($_SESSION['username']) || !isset($_SESSION['uid']) || $this->isAdmin != 1) {
            http_response_code(401);
            if ($_SERVER["REQUEST_METHOD"] == "GET")
                header("Location: " . $this->cfg->getValue('WebSite', 'BaseUrl') . "/");
            exit();
        }
    }

    /**
     * 渲染列出所有用户
     * @param string $message 向网页打印错误信息
     */
    private function listUsers(?string $message = null): void
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;

        // 计算要显示的用户范围
        $listPerPage = $this->cfg->getValue('Admin', 'listPerPage');
        $start = ($page - 1) * $listPerPage;
        // $end = $start + $listPerPage;
        $totalUsers = $this->db->getTableCount("users");
        $totalPages = ceil($totalUsers / $listPerPage);

        $users = $this->db->getTable("users", "*", [$start, $listPerPage]);

        $template = $this->twig->load("administrator/listUsers.twig");
        echo $template->render(array(
            'baseUrl' => $this->cfg->getValue('WebSite', 'BaseUrl'),
            'username' => $_SESSION['username'],
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'isAdmin' => $this->isAdmin,
            'users' => $users,
            'type' => "users",
            'execMessage' => $message
        ));
    }

    /**
     * 渲染列出所有分组
     * @param string $message 向网页打印错误信息
     */
    private function listGroups(?string $message = null): void
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;

        // 计算要显示的用户范围
        $listPerPage = $this->cfg->getValue('Admin', 'listPerPage');
        $start = ($page - 1) * $listPerPage;
        // $end = $start + $listPerPage;
        $totalUsers = $this->db->getTableCount("groups");
        $totalPages = ceil($totalUsers / $listPerPage);

        $groups = $this->db->getTable("groups", "*", [$start, $listPerPage]);

        $template = $this->twig->load("administrator/listGroups.twig");
        echo $template->render(array(
            'baseUrl' => $this->cfg->getValue('WebSite', 'BaseUrl'),
            'username' => $_SESSION['username'],
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'isAdmin' => $this->isAdmin,
            'groups' => $groups,
            'type' => "groups",
            'execMessage' => $message
        ));
    }

    /**
     * 处理 Get 操作
     * @param string $message 向网页打印错误信息
     */
    public static function renderMain(?string $message = null): void
    {
        $self = new Self();

        switch (isset($_GET['type']) ? $_GET['type'] : "users") {
            case 'users':
                $self->listUsers($message);
                break;
            case 'groups':
                $self->listGroups($message);
                break;
            default:
                $self->listUsers($message);
                break;
        }
    }

    /**
     * 渲染编辑用户
     * @param string $message 向网页打印错误信息
     */
    public static function renderUser(?string $message = null): void
    {
        $self = new Self();

        $editingUID = isset($_GET['uid']) ? $_GET['uid'] : "users";
        $user = $self->db->getRowbyName("users", "*", array("uid" => $editingUID));
        if (empty($user)) {
            header("Location: " . $self->cfg->getValue('WebSite', 'BaseUrl') . "/admin");
            return;
        }
        $userSubs = $self->db->getRowbyName("user_groups", '*', array("uid" => $editingUID), true);
        foreach ($self->db->getTable("groups", "gid, name") as $iGroup) {
            $groupNames[$iGroup['gid']] = $iGroup['name'];
        }

        $template = $self->twig->load("administrator/user.twig");
        echo $template->render(array(
            'baseUrl' => $self->cfg->getValue('WebSite', 'BaseUrl'),
            'username' => $_SESSION['username'],
            'isAdmin' => $self->isAdmin,
            'type' => "users",
            'navRet' => true,
            'user' => $user,
            'userSubs' => $userSubs,
            'groupNames' => $groupNames,
            'execMessage' => $message
        ));
    }

    /**
     * 渲染编辑分组
     * @param string $message 向网页打印错误信息
     */
    public static function renderGroup(?string $message = null): void
    {
        $self = new Self();

        $group = $self->db->getRowbyName("groups", "*", array("gid" => $_GET['gid']));
        if (empty($group)) {
            header("Location: " . $self->cfg->getValue('WebSite', 'BaseUrl') . "/admin");
            return;
        }
        $subscribes = $self->db->getRowbyNameOrder("group_subscribes", '*', array("gid" => $_GET['gid']), array("orderlist" => "ASC"), true);
        $groupShare = $self->db->getRowbyName("group_share", '*', array("gid" => $_GET['gid']), true);
        $userSubs = $self->db->getRowbyName("user_groups", '*', array("gid" => $_GET['gid']), true);
        foreach ($self->db->getTable("users", "uid, username") as $iUser) {
            $userNames[$iUser['uid']] = $iUser['username'];
        }

        $template = $self->twig->load("administrator/group.twig");
        echo $template->render(array(
            'baseUrl' => $self->cfg->getValue('WebSite', 'BaseUrl'),
            'username' => $_SESSION['username'],
            'isAdmin' => $self->isAdmin,
            'type' => "groups",
            'navRet' => true,
            'group' => $group,
            'subscribes' => $subscribes,
            'groupShare' => $groupShare,
            'execMessage' => $message,
            'userSubs' => $userSubs,
            'userNames' => $userNames
        ));
    }

    /**
     * 处理 Post 操作
     */
    public static function onPost(): void
    {
        $self = new self();
        switch ($_POST['type']) {
            case "createNewUser":
                $self->handleCreateNewUser();
                break;
            case "createNewGroup":
                $self->handleCreateNewGroup();
                break;
            default:
                header("Location: " . $self->cfg->getValue('WebSite', 'BaseUrl') . "/");
                break;
        }
    }

    /**
     * 处理创建用户操作
     */
    private function handleCreateNewUser(): void
    {
        if (empty($_POST['username']) || empty($_POST['password'])) {
            $this->renderMain("用户名和密码不得为空。");
            http_response_code(405);
            return;
        }

        $uid = empty($_POST['uid']) ? $this->uuid->generateUUID() : $_POST['uid'];
        if (!$this->uuid->checkUUID($uid)) {
            $this->renderMain("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if ($this->db->checkDuplicate('users', 'uid', $uid)) {
            $this->renderMain("用户 UUID 与其他用户重复。");
            http_response_code(405);
            return;
        }

        if ($this->db->checkDuplicate('users', 'username', $_POST['username'])) {
            $this->renderMain("用户名与其他用户重复。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['password']) < 8 || !preg_match('/[A-Z]/', $_POST['password']) || !preg_match('/[a-z]/', $_POST['password']) || !preg_match('/\d/', $_POST['password'])) {
            $this->renderMain("密码必须包含至少一个大写字母、一个小写字母和一个数字，且长度不少于8个字符。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->insertNewRow(
                "users",
                array(
                    'uid' => $uid,
                    'username' => $_POST['username'],
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
                ),
            );
            $this->renderMain("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderMain($e->getMessage());
            } else {
                $this->renderMain("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理创建分组操作
     */
    private function handleCreateNewGroup(): void
    {
        if (empty($_POST['name'])) {
            $this->renderMain("分组名称不得为空。");
            http_response_code(405);
            return;
        }

        $gid = empty($_POST['gid']) ? $this->uuid->generateUUID() : $_POST['gid'];
        if (!$this->uuid->checkUUID($gid)) {
            $this->renderMain("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if ($this->db->checkDuplicate('groups', 'gid', $gid)) {
            $this->renderMain("分组 UUID 与其他分组重复。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->insertNewRow(
                "groups",
                array(
                    'gid' => $gid,
                    'name' => $_POST['name'],
                ),
            );
            $this->renderMain("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderMain($e->getMessage());
            } else {
                $this->renderMain("服务器内部错误！保存失败。");
            }
        }
    }
}
