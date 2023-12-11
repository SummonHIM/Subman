<?php

namespace Subman\Administrator;

class Group extends Administrator
{
    /**
     * 处理 Post 操作
     */
    public static function onGroupPost(): void
    {
        $self = new self();
        switch ($_POST['type']) {
            case "updateGroupConfig":
                $self->handleUpdateGroupConfig();
                break;
            case "deleteGroup":
                $self->handleDeleteGroup();
                break;
            case "saveSubscribes":
                $self->saveSubscribes();
                break;
            case "saveShare":
                $self->saveShare();
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
     * 处理更新分组设置
     */
    private function handleUpdateGroupConfig(): void
    {
        $newGid = empty($_POST['newGid']) ? $this->uuid->generateUUID() : $_POST['newGid'];

        // 检查是否存在空值
        $requiredFields = ['gid', 'name'];
        // 检查长度和规范
        $lengthChecks = [
            'name' => 25,
            'sub_hp' => 255,
            'sub_account' => 255,
            'sub_aff' => 255,
            'sub_password' => 64,
        ];
        // 检查 UUID 格式
        $uuidFields = ['newGid', 'gid'];
        $itemName = [
            'gid' => '分组 UUID',
            'newGid' => '新分组 UUID',
            'name' => '分组名称',
            'sub_hp' => '机场官网网址',
            'sub_account' => '机场登录账号',
            'sub_password' => '机场登录密码',
            'sub_aff' => '机场邀请码',
        ];

        // 检查是否存在空值
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $this->renderGroup($itemName[$field] . "不得为空。");
                http_response_code(405);
                return;
            }
        }

        // 检查 UUID 格式
        foreach ($uuidFields as $field) {
            $value = empty($_POST[$field]) ? $this->uuid->generateUUID() : $_POST[$field];
            if (!$this->uuid->checkUUID($value)) {
                $this->renderGroup("输入的 " . $itemName[$field] . " 不是有效的 UUID 格式。");
                http_response_code(405);
                return;
            }
        }

        // 检查分组 UUID 重复
        if ($_POST['gid'] != $_POST['newGid'] && $this->db->checkDuplicate('groups', 'gid', $_POST['newGid'])) {
            $this->renderGroup("分组 UUID 与其他分组重复。");
            http_response_code(405);
            return;
        }

        // 检查长度和规范
        foreach ($lengthChecks as $field => $maxLength) {
            if (strlen($_POST[$field]) > $maxLength) {
                $this->renderGroup($itemName[$field] . "的长度超过 " . $maxLength . "，或不符合储存规范。");
                http_response_code(405);
                return;
            }
        }

        try {
            $this->db->updateRow(
                "groups",
                array(
                    'gid' => $newGid,
                    'name' => $_POST['name'],
                    'sub_hp' => $_POST['sub_hp'],
                    'sub_account' => $_POST['sub_account'],
                    'sub_password' => $_POST['sub_password'],
                    'sub_aff' => $_POST['sub_aff'],
                ),
                array('gid' => $_POST['gid'])
            );
            if ($_POST['gid'] != $newGid)
                $this->renderMain("Success");
            else
                $this->renderGroup("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderGroup($e->getMessage());
            } else {
                $this->renderGroup("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理删除分组
     */
    private function handleDeleteGroup(): void
    {
        if (empty($_POST['gid'])) {
            $this->renderGroup("分组 UUID 不得为空。");
            http_response_code(405);
            return;
        }

        if (!$this->uuid->checkUUID($_POST['gid'])) {
            $this->renderGroup("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->deleteRow(
                "groups",
                array(
                    'gid' => $_POST['gid']
                )
            );
            $this->renderMain("分组已删除！");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderGroup($e->getMessage());
            } else {
                $this->renderGroup("服务器内部错误！保存失败。");
            }
        }
    }

    /**
     * 处理分组订阅Post信息是否符合规范
     */
    private function validateSubscribe($data): void
    {
        // 检查是否有空值
        $requiredFields = ['sid', 'newSid', 'name', 'url'];
        // 检查 UUID 格式
        $uuidFields = ['sid', 'newSid'];
        // 检查字符串长度和规范
        $lengthChecks = [
            'url' => 255,
            'options' => 255,
            'name' => 25,
            'target' => 15,
        ];
        $itemName = [
            'url' => '订阅网址',
            'options' => '转换选项',
            'name' => '订阅名称',
            'target' => '转换目标',
        ];

        // 检查是否有空值
        foreach ($requiredFields as $field) {
            if (empty($data[$field]))
                throw new \Exception("除订阅 UUID、排序、转换目标和转换选项外，其他内容不得为空。");
        }

        // 检查 UUID 格式
        foreach ($uuidFields as $field) {
            if (!$this->uuid->checkUUID($data[$field]))
                throw new \Exception("输入的 UUID 不是有效的 UUID 格式。");
        }

        // 检查订阅 UUID 重复
        if ($data['sid'] != $data['newSid'] && $this->db->checkDuplicate('group_subscribes', 'sid', $data['newSid']))
            throw new \Exception("订阅 UUID 与其他分组重复。");

        // 检查字符串长度和规范
        foreach ($lengthChecks as $field => $maxLength) {
            if (isset($data[$field]) && strlen($data[$field]) > $maxLength)
                throw new \Exception($itemName[$field] . "的长度超过 " . $maxLength . "，或不符合储存规范。");
        }

        // 检查排序范围
        if ($data['orderlist'] < 0 || $data['orderlist'] > 255)
            throw new \Exception("排序不能超过 255 或小于 0。");
    }

    /**
     * 保存分组订阅
     */
    private function updateSubscribe(string $gid, array $data): void
    {
        $data['newSid'] = empty($data['newSid']) ? $this->uuid->generateUUID() : $data['newSid'];
        $data['orderlist'] = empty($data['orderlist']) ? 1 : $data['orderlist'];
        $data['converter'] = isset($data['converter']) ? 1 : 0;

        try {
            $this->validateSubscribe($data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        try {
            $this->db->updateRow(
                "group_subscribes",
                array(
                    'sid' => $data['newSid'],
                    'orderlist' => $data['orderlist'],
                    'name' => $data['name'],
                    'url' => $data['url'],
                    'converter' => $data['converter'],
                    'target' => $data['target'],
                    'options' => $data['options']
                ),
                array(
                    'sid' => $data['sid'],
                    'gid' => $gid
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
     * 删除分组订阅
     */
    private function deleteSubscribe(string $gid, array $data): void
    {
        try {
            $this->validateSubscribe($data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        try {
            $this->db->deleteRow(
                "group_subscribes",
                array(
                    'sid' => $data['sid'],
                    'gid' => $gid
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
     * 创建新分组订阅
     */
    private function createNewSubscribe(string $gid, array $data): void
    {
        $data['newSid'] = empty($data['newSid']) ? $this->uuid->generateUUID() : $data['newSid'];
        $data['sid'] = $data['newSid'];
        $data['orderlist'] = empty($data['orderlist']) ? 1 : $data['orderlist'];
        $data['converter'] = isset($data['converter']) ? 1 : 0;

        try {
            $this->validateSubscribe($data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        try {
            $this->db->insertNewRow(
                "group_subscribes",
                array(
                    'sid' => $data['sid'],
                    'gid' => $gid,
                    'orderlist' => $data['orderlist'],
                    'name' => $data['name'],
                    'url' => $data['url'],
                    'converter' => $data['converter'],
                    'target' => $data['target'],
                    'options' => $data['options']
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
     * 处理分组订阅逻辑
     */
    private function saveSubscribes(): void
    {
        if (empty($_POST['gid'])) {
            $this->renderGroup("隐藏数据丢失。请刷新网页（不重复提交数据）后重试。");
            http_response_code(405);
            return;
        }

        foreach ($_POST['data'] as $index => $data) {
            if (isset($data['newEmpty'])) {
                try {
                    $this->createNewSubscribe($_POST['gid'], $data);
                } catch (\Exception $e) {
                    $this->renderGroup("前 " . $index - 1 . " 行数据已保存。创建第 " . $index . " 个新分组订阅失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            } else if (isset($data['delete'])) {
                try {
                    $this->deleteSubscribe($_POST['gid'], $data);
                } catch (\Exception $e) {
                    $this->renderGroup("前 " . $index - 1 . " 行数据已保存。删除 UUID 为 " . $data['sid'] . " 的分组订阅失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            } else {
                try {
                    $this->updateSubscribe($_POST['gid'], $data);
                } catch (\Exception $e) {
                    $this->renderGroup("前 " . $index - 1 . " 行数据已保存。更新 UUID 为 " . $data['sid'] . " 的分组订阅失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            }
        }

        $this->renderGroup("Success");
    }

    /**
     * 处理分组共享账号Post信息是否符合规范
     */
    private function validateShare($data): void
    {
        $fieldsEmpty = ['gsid', 'name', 'account', 'password', 'manage'];
        $fieldsCheckUUID = ['gsid'];
        $lengthChecks = [
            'name' => 50,
            'password' => 50,
            'account' => 255,
            'manage' => 255,
        ];
        $itemName = [
            'name' => '账号名称',
            'account' => '账号',
            'password' => '密码',
            'manage' => '管理账号网址',
        ];

        foreach ($fieldsEmpty as $field) {
            if (empty($data[$field])) {
                throw new \Exception("所有可填项均不得为空。");
            }
        }

        foreach ($fieldsCheckUUID as $field) {
            if (!$this->uuid->checkUUID($data[$field])) {
                throw new \Exception("输入的 UUID 不是有效的 UUID 格式。");
            }
        }

        foreach ($lengthChecks as $field => $maxLength) {
            if (strlen($data[$field]) > $maxLength) {
                throw new \Exception($itemName[$field] . "的长度超过 " . $maxLength . " ，或不符合储存规范。");
            }
        }
    }

    /**
     * 更新共享账号
     */
    private function updateShare(string $gid, array $data): void
    {
        try {
            $this->validateShare($data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        try {
            $this->db->updateRow(
                "group_share",
                array(
                    'name' => $data['name'],
                    'account' => $data['account'],
                    'password' => $data['password'],
                    'manage' => $data['manage']
                ),
                array(
                    'gsid' => $data['gsid'],
                    'gid' => $gid
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
     * 删除共享账号
     */
    private function deleteShare(string $gid, array $data): void
    {
        try {
            $this->validateShare($data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        try {
            $this->db->deleteRow(
                "group_share",
                array(
                    'gsid' => $data['gsid'],
                    'gid' => $gid
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
     * 创建新共享账号
     */
    private function createNewShare(string $gid, array $data): void
    {
        $data['gsid'] = empty($data['gsid']) ? $this->uuid->generateUUID() : $data['gsid'];

        if ($this->db->checkDuplicate('group_share', 'gsid', $data['gsid']))
            throw new \Exception("共享账号 UUID 与其他重复。请尝试重新保存。");

        try {
            $this->validateShare($data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        try {
            $this->db->insertNewRow(
                "group_share",
                array(
                    'gsid' => $data['gsid'],
                    'gid' => $gid,
                    'name' => $data['name'],
                    'account' => $data['account'],
                    'password' => $data['password'],
                    'manage' => $data['manage']
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
     * 处理分组共享账号逻辑
     */
    private function saveShare(): void
    {
        if (empty($_POST['gid'])) {
            $this->renderGroup("隐藏数据丢失。请刷新网页（不重复提交数据）后重试。");
            http_response_code(405);
            return;
        }

        foreach ($_POST['data'] as $index => $data) {
            if (empty($data['gsid'])) {
                try {
                    $this->createNewShare($_POST['gid'], $data);
                } catch (\Exception $e) {
                    $this->renderGroup("前 " . $index - 1 . " 行数据已保存。创建第 " . $index . " 个新分组共享账号失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            } else if (isset($data['delete'])) {
                try {
                    $this->deleteShare($_POST['gid'], $data);
                } catch (\Exception $e) {
                    $this->renderGroup("前 " . $index - 1 . " 行数据已保存。删除名为 " . $data['name'] . " 的分组共享账号失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            } else {
                try {
                    $this->updateShare($_POST['gid'], $data);
                } catch (\Exception $e) {
                    $this->renderGroup("前 " . $index - 1 . " 行数据已保存。更新名为 " . $data['name'] . " 的分组共享账号失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            }
        }

        $this->renderGroup("Success");
    }

    /**
     * 处理分组用户Post信息是否符合规范
     */
    private function validateUserSub(string $gid, array $data): void
    {
        if (empty($data['uid']) || empty($data['newUid']) || $data['newUid'] == '添加新用户' || empty($gid))
            throw new \Exception("请选择一名用户。");

        if (!$this->uuid->checkUUID($data['uid']) || !$this->uuid->checkUUID($data['newUid']) || !$this->uuid->checkUUID($gid))
            throw new \Exception("输入的 UUID 不是有效的 UUID 格式。");
    }

    /**
     * 更新现有分组用户
     */
    private function updateUserSub(string $gid, array $data)
    {
        $this->validateUserSub($gid, $data);
        $data['expire'] = empty($data['expire']) ? date("Y-m-d H:i:s", time()) : date("Y-m-d H:i:s", strtotime(str_replace("T", " ", $data['expire'])));

        try {
            $this->db->updateRow(
                "user_groups",
                array(
                    'uid' => $data['newUid'],
                    'expire' => $data['expire']
                ),
                array(
                    'uid' => $data['uid'],
                    'gid' => $gid
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
    private function deleteUserSub(string $gid, array $data)
    {
        try {
            $this->db->deleteRow(
                "user_groups",
                array(
                    'uid' => $data['uid'],
                    'gid' => $gid
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
    private function createNewUserSub(string $gid, array $data)
    {
        $data['uid'] = $data['newUid'];
        $this->validateUserSub($gid, $data);
        $data['expire'] = empty($data['expire']) ? date("Y-m-d H:i:s", time()) : date("Y-m-d H:i:s", strtotime(str_replace("T", " ", $data['expire'])));

        try {
            $this->db->insertNewRow(
                "user_groups",
                array(
                    'uid' => $data['newUid'],
                    'gid' => $gid,
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
                    $this->createNewUserSub($_POST['gid'], $data);
                } catch (\Exception $e) {
                    $this->renderGroup("前 " . $index - 1 . " 行数据已保存。创建第 " . $index . " 个新分组用户失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            } else if (isset($data['delete'])) {
                try {
                    $this->deleteUserSub($_POST['gid'], $data);
                } catch (\Exception $e) {
                    $this->renderGroup("前 " . $index - 1 . " 行数据已保存。删除名为 " . $data['name'] . " 的分组用户失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            } else {
                try {
                    $this->updateUserSub($_POST['gid'], $data);
                } catch (\Exception $e) {
                    $this->renderGroup("前 " . $index - 1 . " 行数据已保存。更新名为 " . $data['name'] . " 的分组用户失败：" . $e->getMessage());
                    http_response_code(405);
                    return;
                }
            }
        }

        $this->renderGroup("Success");
    }
};
