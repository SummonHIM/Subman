<?php

namespace Subman;

use Subman\Config;
use Subman\Database;

class Subscribes
{
    public static function renderSubscribes()
    {
        $cfg = new Config();

        if (!isset($_SESSION['username'])) {
            header("Location: " . $cfg->getValue('WebSite', 'BaseUrl') . "/login");
            exit();
        } else {
            $db = new Database;
            $loader = new \Twig\Loader\FilesystemLoader("templates");
            $twig = new \Twig\Environment($loader);

            // 获取用户信息
            $users = $db->getRowbyName("users", array("uid" => $_SESSION['uid']));

            // 获取并循环 userSubs
            $userSubs = $db->getRowbyName("usersubs", array("uid" => $_SESSION['uid']), true);
            foreach ($userSubs as $i) {
                // 根据 userSubs 中的 gid 查找订阅组信息
                $groups = $db->getRowbyName("groups", array("gid" => $i['gid']));
                // 根据 userSubs 中的 gid 查找订阅链接信息
                $subscribes = $db->getRowbyName("subscribes", array("gid" => $i['gid']), true);
                $groupShare = $db->getRowbyName("groupshare", array("gid" => $i['gid']), true);
                // 检查该订阅是否过期
                if (date('Y-m-d H:i:s') < $i['expire']) {
                    // 若订阅仍未过期，则将过期时间，订阅地址以及共享账号添加至 group 数组内
                    $groups["expire"] = $i['expire'];
                    $groups["subscribes"] = $subscribes;
                    $groups["groupShare"] = $groupShare;
                } else {
                    // 若订阅过期了，则删除部分键值，只保留部分关键信息。
                    unset($groups['sub_name']);
                    unset($groups['sub_hp']);
                    unset($groups['sub_account']);
                    unset($groups['sub_password']);
                    unset($groups['sub_aff']);
                    $groups["expire"] = $i['expire'];
                }
                $renderGroupSubs[] = $groups;
            }

            // 将变量传入 twig
            $twigVar = array(
                'subApiUrl' => $_SERVER['REQUEST_SCHEME'] ?? "http" . '://' . $_SERVER['HTTP_HOST'] . $cfg->getValue('WebSite', 'BaseUrl') . '/api/subscribe',
                'baseUrl' => $cfg->getValue('WebSite', 'BaseUrl'),
                'username' => $_SESSION['username'],
                'uid' => $_SESSION['uid'],
                'isAdmin' => $users['isadmin'],
                'customConfig' => $users['custom_config'],
                'renderGroupSubs' => $renderGroupSubs
            );
            $template = $twig->load("subscribes.twig");
            echo $template->render($twigVar);
        }
    }

    public static function getSubscribesUrl()
    {
        header('Content-Type: text/plain');
        // 若 sub 和 user 不存在则打印错误并返回
        if (!isset($_GET['sub']) || !isset($_GET['user'])) {
            echo json_encode(array(
                'Status' => 'Error',
                'Message' => 'Please provide the parameters sub and user.',
            ));
            return;
        }

        $db = new Database;
        $cfg = new Config;

        $subscribes = $db->getRowbyName("subscribes", array('sid' => $_GET['sub']));
        $groups = $db->getRowbyName("groups", array("gid" => $subscribes['gid']));
        $expire = $db->getRowbyName("usersubs", array('gid' => $subscribes['gid'], 'uid' => $_GET['user']))['expire'];

        // 检查订阅是否已经过期
        if (date('Y-m-d H:i:s') < $expire) {
            // 没有过期则跳转正确的 url
            if (isset($_GET['convert']) && $_GET['convert'] == 'true') {
                // 如果使用改版订阅，则生成 subconverter 链接
                $url = $cfg->getValue('WebSite', 'SubConverterUrl') . "target=clash&url=" . urlencode($subscribes['convert_url']) . "&filename=" . urlencode($groups['name'] . ' 的 ' . $subscribes['name']) . "&" . $subscribes['options'];
                // 如果定义了 config 参数，则将 config 参数也合并进 url 中
                if (isset($_GET['config']) && $_GET["config"])
                    $url .= "&config=" . urlencode($_GET["config"]);
            } else {
                // 否则直接返回原订阅
                $url = $subscribes['original_url'];
            }
            $ret = [
                'Status' => 'Success',
                'Location' => $url,
            ];
            echo json_encode($ret);
            header("Location: $url");
        } else {
            // 过期了则打印过期错误
            echo json_encode(array(
                'Status' => 'Expire',
                'Message' => 'Your subscription has expired, please renew.',
            ));
        }
    }
}
