<?php

namespace Subman\Administrator;

class User extends Administrator
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
            case "saveUserSubs":
                $self->saveUserSubs();
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

        $newUid = empty($_POST['newUid']) ? $this->uuid->generateUUID() : $_POST['newUid'];
        if (!$this->uuid->checkUUID($newUid) || !$this->uuid->checkUUID($_POST['uid'])) {
            $this->renderUser("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if ($_POST['uid'] != $newUid) {
            if ($this->db->checkDuplicate('users', 'uid', $newUid)) {
                $this->renderUser("用户 UUID 与其他用户重复。");
                http_response_code(405);
                return;
            }
        }

        if ($_POST['username'] != $_POST['newUsername']) {
            if ($this->db->checkDuplicate('users', 'uid', $_POST['newUsername'])) {
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

            if ($_POST['uid'] == $_SESSION['uid']) {
                $_SESSION['username'] = $_POST['newUsername'];
                $_SESSION['uid'] = $newUid;
            }

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

        if (!$this->uuid->checkUUID($_POST['uid'])) {
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
     * 处理分组用户Post信息是否符合规范
     */
    private function validateUserSub(string $uid, array $data): void
    {
        if (empty($data['gid']) || empty($data['newGid']) || $data['newGid'] == '添加新分组' || empty($uid))
            throw new \Exception("请选择一个分组。");

        if (!$this->uuid->checkUUID($data['gid']) || !$this->uuid->checkUUID($data['newGid']) || !$this->uuid->checkUUID($uid))
            throw new \Exception("输入的 UUID 不是有效的 UUID 格式。");
    }

    /**
     * 更新现有分组用户
     */
    private function updateUserSub(string $uid, array $data)
    {
        $this->validateUserSub($uid, $data);
        $data['expire'] = empty($data['expire']) ? date("Y-m-d H:i:s", time()) : date("Y-m-d H:i:s", strtotime(str_replace("T", " ", $data['expire'])));

        try {
            $this->db->updateRow(
                "user_groups",
                array(
                    'gid' => $data['newGid'],
                    'expire' => $data['expire']
                ),
                array(
                    'uid' => $uid,
                    'gid' => $data['gid']
                )
            );
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理删除某分组用户
     */
    private function deleteUserSub(string $uid, array $data)
    {
        try {
            $this->db->deleteRow(
                "user_groups",
                array(
                    'uid' => $uid,
                    'gid' => $data['gid']
                )
            );
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理添加新分组用户
     */
    private function createNewUserSub(string $uid, array $data)
    {
        $data['gid'] = $data['newGid'];
        $this->validateUserSub($uid, $data);
        $data['expire'] = empty($data['expire']) ? date("Y-m-d H:i:s", time()) : date("Y-m-d H:i:s", strtotime(str_replace("T", " ", $data['expire'])));

        try {
            $this->db->insertNewRow(
                "user_groups",
                array(
                    'uid' => $uid,
                    'gid' => $data['newGid'],
                    'expire' => $data['expire'],
                ),
            );
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理分组用户逻辑
     */
    private function saveUserSubs(): void
    {
        foreach ($_POST['data'] as $index => $data) {
            if (isset($data['newEmpty'])) {
                try {
                    $this->createNewUserSub($_POST['uid'], $data);
                } catch (\Exception $e) {
                    $this->renderUser("前 " . $index - 1 . " 行数据已保存。创建第 " . $index . " 个新用户分组失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            } else if (isset($data['delete'])) {
                try {
                    $this->deleteUserSub($_POST['uid'], $data);
                } catch (\Exception $e) {
                    $this->renderUser("前 " . $index - 1 . " 行数据已保存。删除名为 " . $data['name'] . " 的用户分组失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            } else {
                try {
                    $this->updateUserSub($_POST['uid'], $data);
                } catch (\Exception $e) {
                    $this->renderUser("前 " . $index - 1 . " 行数据已保存。更新名为 " . $data['name'] . " 的用户分组失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            }
        }

        $this->renderUser("Success");
    }
}
