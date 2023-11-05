<?php

namespace Subman;

class AdminUser extends Administrator
{
    /**
     * 处理 Post 操作
     */
    public static function onUserPost(): void
    {
        $self = new self();
        switch ($_POST['type']) {
            case "updateUserConfig":
                $self->handleUpdateUserConfig();
                break;
            case "deleteUser":
                $self->handleDeleteUser();
                break;
            case "updateCurrentSub":
                $self->handleUpdateCurrentSub();
                break;
            case "deleteCurrentSub":
                $self->handleDeleteCurrentSub();
                break;
            case "createNewSub":
                $self->handleCreateNewSub();
                break;
            default:
                header("Location: " . $self->cfg->getValue('WebSite', 'BaseUrl') . "/");
                break;
        }
    }

    /**
     * 处理更新用户设置
     */
    private function handleUpdateUserConfig(): void
    {
        if (empty($_POST['uid']) || empty($_POST['newUsername']) || empty($_POST['username'])) {
            $this->renderUser("用户名不得为空。");
            http_response_code(405);
            return;
        }

        $newUid = empty($_POST['newUid']) ? $this->generateUUID() : $_POST['newUid'];
        if (!preg_match($this->uuidPattern, $newUid) || !preg_match($this->uuidPattern, $_POST['uid'])) {
            $this->renderUser("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if ($_POST['uid'] != $newUid) {
            if ($this->checkDuplicate('users', 'uid', $newUid)) {
                $this->renderUser("用户 UUID 与其他用户重复。");
                http_response_code(405);
                return;
            }
        }

        if ($_POST['username'] != $_POST['newUsername']) {
            if ($this->checkDuplicate('users', 'uid', $_POST['newUsername'])) {
                $this->renderUser("用户名与其他用户重复。");
                http_response_code(405);
                return;
            }
        }

        if (strlen($_POST['newUsername']) > 25) {
            $this->renderUser("输入的用户名长度超过 25，或用户名不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['customConfigUrl']) > 255) {
            $this->renderUser("输入的自定义远程配置长度超过 255，或不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (!empty($_POST['newPasswd'])) {
            if (strlen($_POST['newPasswd']) < 8 || !preg_match('/[A-Z]/', $_POST['newPasswd']) || !preg_match('/[a-z]/', $_POST['newPasswd']) || !preg_match('/\d/', $_POST['newPasswd'])) {
                $this->renderUser("密码必须包含至少一个大写字母、一个小写字母和一个数字，且长度不少于8个字符。");
                http_response_code(405);
                return;
            }
        }

        $isAdmin = isset($_POST['isadmin']) ? 1 : 0;
        $updateRow = array(
            'uid' => $newUid,
            'username' => $_POST['newUsername'],
            'custom_config' => $_POST['customConfigUrl'],
            'isadmin' => $isAdmin
        );
        if (!empty($_POST['newPasswd']))
            $updateRow['password'] = password_hash($_POST['newPasswd'], PASSWORD_DEFAULT);

        try {
            $this->db->updateRow(
                "users",
                $updateRow,
                array('uid' => $_POST['uid'])
            );
            if ($_POST['uid'] != $newUid)
                $this->renderMain("Success");
            else
                $this->renderUser("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderUser($e->getMessage());
            } else {
                $this->renderUser("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理删除用户
     */
    private function handleDeleteUser(): void
    {
        if (empty($_POST['uid'])) {
            $this->renderUser("用户 UUID 不得为空。");
            http_response_code(405);
            return;
        }

        if (!preg_match($this->uuidPattern, $_POST['uid'])) {
            $this->renderUser("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->deleteRow(
                "users",
                array(
                    'uid' => $_POST['uid']
                )
            );
            $this->renderMain("用户已删除！");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderUser($e->getMessage());
            } else {
                $this->renderUser("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理更新用户现有分组
     */
    private function handleUpdateCurrentSub(): void
    {
        if (empty($_POST['uid']) || empty($_POST['gid'])) {
            $this->renderUser("用户 UUID 和分组 UUID 不得为空。");
            http_response_code(405);
            return;
        }

        if (!preg_match($this->uuidPattern, $_POST['uid']) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderUser("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        $expire = !empty($_POST['expire']) ? date("Y-m-d H:i:s", strtotime(str_replace("T", " ", $_POST['expire']))) : date("Y-m-d H:i:s", time());

        try {
            $this->db->updateRow(
                "user_subscribes",
                array('expire' => $expire),
                array(
                    'uid' => $_POST['uid'],
                    'gid' => $_POST['gid']
                )
            );
            $this->renderUser("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderUser($e->getMessage());
            } else {
                $this->renderUser("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理删除用户现有分组
     */
    private function handleDeleteCurrentSub(): void
    {
        if (empty($_POST['uid']) || empty($_POST['gid'])) {
            $this->renderUser("用户 UUID 和分组 UUID 不得为空。");
            http_response_code(405);
            return;
        }

        if (!preg_match($this->uuidPattern, $_POST['uid']) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderUser("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->deleteRow(
                "user_subscribes",
                array(
                    'uid' => $_POST['uid'],
                    'gid' => $_POST['gid']
                )
            );
            $this->renderUser("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderUser($e->getMessage());
            } else {
                $this->renderUser("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理添加新分组
     */
    private function handleCreateNewSub(): void
    {
        if (empty($_POST['uid']) || empty($_POST['gid'])) {
            $this->renderUser("用户 UUID 和分组 UUID 不得为空。");
            http_response_code(405);
            return;
        }

        if (!preg_match($this->uuidPattern, $_POST['uid']) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderUser("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        $expire = !empty($_POST['expire']) ? date("Y-m-d H:i:s", strtotime(str_replace("T", " ", $_POST['expire']))) : date("Y-m-d H:i:s", time());

        try {
            $this->db->insertNewRow(
                "user_subscribes",
                array(
                    'uid' => $_POST['uid'],
                    'gid' => $_POST['gid'],
                    'expire' => $expire,
                ),
            );
            $this->renderUser("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderUser($e->getMessage());
            } else {
                $this->renderUser("服务器内部错误！保存失败。");
            }
        }
    }
}
