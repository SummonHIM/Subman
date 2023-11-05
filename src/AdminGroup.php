<?php

namespace Subman;

class AdminGroup extends Administrator
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
            case "updateCurrentSub":
                $self->handleUpdateCurrentSub();
                break;
            case "deleteCurrentSub":
                $self->handleDeleteCurrentSub();
                break;
            case "createNewSub":
                $self->handleCreateNewSub();
                break;
            case "updateCurrentAccount":
                $self->handleUpdateCurrentAccount();
                break;
            case "deleteCurrentAccount":
                $self->handleDeleteCurrentAccount();
                break;
            case "createNewAccount":
                $self->handleCreateNewAccount();
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
        if (empty($_POST['gid']) || empty($_POST['name'])) {
            $this->renderGroup("分组名称不得为空。");
            http_response_code(405);
            return;
        }

        $newGid = empty($_POST['newGid']) ? $this->generateUUID() : $_POST['newGid'];
        if (!preg_match($this->uuidPattern, $newGid) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderGroup("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if ($_POST['gid'] != $newGid) {
            if ($this->checkDuplicate('groups', 'gid', $newGid)) {
                $this->renderGroup("分组 UUID 与其他分组重复。");
                http_response_code(405);
                return;
            }
        }

        if (strlen($_POST['name']) > 25) {
            $this->renderGroup("输入的用户名长度超过 25，或用户名不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['sub_hp']) > 255 || strlen($_POST['sub_account']) > 255 || strlen($_POST['sub_aff']) > 255) {
            $this->renderGroup("机场官网网址、机场登录账号或机场邀请码的长度超过 255，或不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['sub_password']) > 64) {
            $this->renderGroup("输入的机场登陆密码长度超过 64，或机场登陆密码不符合储存规范。");
            http_response_code(405);
            return;
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

        if (!preg_match($this->uuidPattern, $_POST['gid'])) {
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
     * 处理更新分组订阅
     */
    private function handleUpdateCurrentSub(): void
    {
        if (empty($_POST['sid']) || empty($_POST['gid']) || empty($_POST['name']) || empty($_POST['url'])) {
            $this->renderGroup("除订阅 UUID、转换目标和转换选项外，其他内容不得为空。");
            http_response_code(405);
            return;
        }

        $newSid = empty($_POST['newSid']) ? $this->generateUUID() : $_POST['newSid'];
        if (!preg_match($this->uuidPattern, $newSid) || !preg_match($this->uuidPattern, $_POST['sid']) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderGroup("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if ($_POST['sid'] != $_POST['newSid']) {
            if ($this->checkDuplicate('group_subscribes', 'sid', $_POST['newSid'])) {
                $this->renderGroup("订阅 UUID 与其他分组重复。");
                http_response_code(405);
                return;
            }
        }

        if (strlen($_POST['url']) > 255 || strlen($_POST['options']) > 255) {
            $this->renderGroup("原始订阅网址、转换订阅网址或转换选项的长度超过 255，或不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['name']) > 25) {
            $this->renderGroup("订阅名称的长度超过 25，或订阅名称不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['target']) > 15) {
            $this->renderGroup("转换目标的长度超过 15，或转换目标不符合储存规范。");
            http_response_code(405);
            return;
        }

        $converter = isset($_POST['converter']) ? 1 : 0;

        try {
            $this->db->updateRow(
                "group_subscribes",
                array(
                    'sid' => $newSid,
                    'name' => $_POST['name'],
                    'url' => $_POST['url'],
                    'converter' => $converter,
                    'target' => $_POST['target'],
                    'options' => $_POST['options']
                ),
                array(
                    'sid' => $_POST['sid'],
                    'gid' => $_POST['gid']
                )
            );
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
     * 处理删除分组订阅
     */
    private function handleDeleteCurrentSub(): void
    {
        if (empty($_POST['sid']) || empty($_POST['gid'])) {
            $this->renderGroup("订阅 UUID 不得为空。");
            http_response_code(405);
            return;
        }

        if (!preg_match($this->uuidPattern, $_POST['sid']) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderGroup("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->deleteRow(
                "group_subscribes",
                array(
                    'sid' => $_POST['sid'],
                    'gid' => $_POST['gid']
                )
            );
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
     * 处理创建新分组订阅
     */
    private function handleCreateNewSub(): void
    {
        if (empty($_POST['gid']) || empty($_POST['name']) || empty($_POST['url'])) {
            $this->renderGroup("除订阅 UUID、转换目标和转换选项外，其他内容不得为空。");
            http_response_code(405);
            return;
        }

        $sid = empty($_POST['sid']) ? $this->generateUUID() : $_POST['sid'];
        if (!preg_match($this->uuidPattern, $sid) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderGroup("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if ($this->checkDuplicate('group_subscribes', 'sid', $sid)) {
            $this->renderGroup("订阅 UUID 与其他分组重复。");
            http_response_code(405);
            return;
        }

        $target = empty($_POST['target']) ? 'clash' : $_POST['target'];
        $options = empty($_POST['options']) ? 'emoji=true&udp=true&new_name=true' : $_POST['options'];

        if (strlen($_POST['url']) > 255 || strlen($_POST['options']) > 255) {
            $this->renderGroup("原始订阅网址、转换订阅网址或转换选项的长度超过 255，或不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['name']) > 25) {
            $this->renderGroup("订阅名称的长度超过 25，或订阅名称不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['target']) > 15) {
            $this->renderGroup("转换目标的长度超过 15，或转换目标不符合储存规范。");
            http_response_code(405);
            return;
        }

        $converter = isset($_POST['converter']) ? 1 : 0;

        try {
            $this->db->insertNewRow(
                "group_subscribes",
                array(
                    'sid' => $sid,
                    'gid' => $_POST['gid'],
                    'name' => $_POST['name'],
                    'url' => $_POST['url'],
                    'converter' => $converter,
                    'target' => $target,
                    'options' => $options
                ),
            );
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
     * 处理更新共享账号
     */
    private function handleUpdateCurrentAccount(): void
    {
        if (empty($_POST['gsid']) || empty($_POST['gid']) || empty($_POST['name']) || empty($_POST['account']) || empty($_POST['password']) || empty($_POST['manage'])) {
            $this->renderGroup("所有可填项均不得为空。");
            http_response_code(405);
            return;
        }

        if (!preg_match($this->uuidPattern, $_POST['gsid']) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderGroup("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['name']) > 50 || strlen($_POST['password']) > 50) {
            $this->renderGroup("账号名称或密码的长度超过 50，或不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['account']) > 255 || strlen($_POST['manage']) > 255) {
            $this->renderGroup("账号或管理账号网址的长度超过 255，或不符合储存规范。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->updateRow(
                "group_share",
                array(
                    'name' => $_POST['name'],
                    'account' => $_POST['account'],
                    'password' => $_POST['password'],
                    'manage' => $_POST['manage']
                ),
                array(
                    'gsid' => $_POST['gsid'],
                    'gid' => $_POST['gid']
                )
            );
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
     * 处理删除共享账号
     */
    private function handleDeleteCurrentAccount(): void
    {
        if (empty($_POST['gsid']) || empty($_POST['gid'])) {
            $this->renderGroup("订阅 UUID（隐藏参数）和共享账号 UUID（隐藏参数）不得为空。");
            http_response_code(405);
            return;
        }

        if (!preg_match($this->uuidPattern, $_POST['gsid']) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderGroup("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->deleteRow(
                "group_share",
                array(
                    'gsid' => $_POST['gsid'],
                    'gid' => $_POST['gid']
                )
            );
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
     * 处理创建新共享账号
     */
    private function handleCreateNewAccount(): void
    {
        if (empty($_POST['gid']) || empty($_POST['name']) || empty($_POST['account']) || empty($_POST['password']) || empty($_POST['manage'])) {
            $this->renderGroup("所有可填项均不得为空。");
            http_response_code(405);
            return;
        }

        $gsid = empty($_POST['gsid']) ? $this->generateUUID() : $_POST['gsid'];
        if (!preg_match($this->uuidPattern, $gsid) || !preg_match($this->uuidPattern, $_POST['gid'])) {
            $this->renderGroup("输入的 UUID 不是有效的 UUID 格式。");
            http_response_code(405);
            return;
        }

        if ($this->checkDuplicate('group_share', 'gsid', $gsid)) {
            $this->renderGroup("订阅 UUID 与其他分组重复。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['name']) > 50 || strlen($_POST['password']) > 50) {
            $this->renderGroup("账号名称或密码的长度超过 50，或不符合储存规范。");
            http_response_code(405);
            return;
        }

        if (strlen($_POST['account']) > 255 || strlen($_POST['manage']) > 255) {
            $this->renderGroup("账号或管理账号网址的长度超过 255，或不符合储存规范。");
            http_response_code(405);
            return;
        }

        try {
            $this->db->insertNewRow(
                "group_share",
                array(
                    'gsid' => $gsid,
                    'gid' => $_POST['gid'],
                    'name' => $_POST['name'],
                    'account' => $_POST['account'],
                    'password' => $_POST['password'],
                    'manage' => $_POST['manage']
                ),
            );
            $this->renderGroup("Success");
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                $this->renderGroup($e->getMessage());
            } else {
                $this->renderGroup("服务器内部错误！保存失败。");
            }
        }
    }
}
