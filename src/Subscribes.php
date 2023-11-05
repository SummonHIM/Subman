<?php

namespace Subman;

use Subman\Config;
use Subman\Database;

class Subscribes
{
    /**
     * 渲染订阅页面
     */
    public static function renderSubscribes()
    {
        $cfg = new Config();

        if (isset($_SESSION['username'])) {
            $db = new Database;
            $loader = new \Twig\Loader\FilesystemLoader("templates");
            $twig = new \Twig\Environment($loader);

            // 获取用户信息
            $users = $db->getRowbyName("users", 'isadmin, custom_config', array("uid" => $_SESSION['uid']));

            // 获取并循环 userSubs
            $userSubs = $db->getRowbyName("user_subscribes", '*', array("uid" => $_SESSION['uid']), true);
            $renderGroupSubs = [];
            foreach ($userSubs as $i) {
                // 根据 userSubs 中的 gid 查找订阅组信息
                $groups = $db->getRowbyName("groups", '*', array("gid" => $i['gid']));
                // 检查该订阅是否过期
                if (date('Y-m-d H:i:s') < $i['expire']) {
                    // 若订阅仍未过期，则将过期时间，订阅地址以及共享账号添加至 group 数组内
                    $groups["expire"] = $i['expire'];
                    $groups["subscribes"] = $db->getRowbyNameOrder("group_subscribes", 'sid, gid, name, converter, target', array("gid" => $i['gid']), array("orderlist" => "ASC"), true);
                    $groups["share"] = $db->getRowbyName("group_share", 'name, account, password, manage', array("gid" => $i['gid']), true);
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

            $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? "http";

            // 将变量传入 twig
            $twigVar = array(
                'subApiUrl' => $requestScheme . '://' . $_SERVER['HTTP_HOST'] . $cfg->getValue('WebSite', 'BaseUrl') . '/api/subscribe',
                'baseUrl' => $cfg->getValue('WebSite', 'BaseUrl'),
                'username' => $_SESSION['username'],
                'uid' => $_SESSION['uid'],
                'isAdmin' => $users['isadmin'],
                'customConfig' => $users['custom_config'],
                'renderGroupSubs' => $renderGroupSubs
            );
            $template = $twig->load("subscribes.twig");
            echo $template->render($twigVar);
        } else {
            header("Location: " . $cfg->getValue('WebSite', 'BaseUrl') . "/login");
            exit();
        }
    }

    /**
     * 响应获取订阅API
     */
    public static function getSubscribesUrl()
    {
        // 若 sub 和 user 不存在则打印错误并返回
        if (!isset($_GET['sub']) || !isset($_GET['user'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(array(
                'Status' => 'Error',
                'Message' => 'Please provide the parameters sub and user.',
            ));
            return;
        }

        $db = new Database;
        $cfg = new Config;

        $subscribes = $db->getRowbyName("group_subscribes", "*", array('sid' => $_GET['sub']));
        if (empty($subscribes)) {
            header('Content-Type: application/json');
            http_response_code(405);
            echo json_encode(array(
                'Status' => 'Error',
                'Message' => 'This subscribe ID does not contain any subscribe information.',
            ));
            return;
        }

        $groups = $db->getRowbyName("groups", "name", array("gid" => $subscribes['gid']));
        $expire = $db->getRowbyName("user_subscribes", "expire", array('gid' => $subscribes['gid'], 'uid' => $_GET['user']))['expire'];
        if (empty($expire)) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(array(
                'Status' => 'Error',
                'Message' => 'The user does not have access to this subscription.',
            ));
            return;
        }

        header('Content-Type: text/plain');

        // 检查订阅是否已经过期
        if (date('Y-m-d H:i:s') < $expire) {
            // 没有过期则跳转正确的 url
            if ($subscribes['converter'] == 1) {
                // 如果使用改版订阅，则生成 subconverter 链接
                $url = $cfg->getValue('WebSite', 'SubConverterUrl') . "target=" . $subscribes["target"] . "&url=" . urlencode($subscribes['url']) . "&filename=" . urlencode($groups['name'] . ' ' . $subscribes['name']) . "&" . $subscribes['options'];
                // 如果定义了 config 参数，则将 config 参数也合并进 url 中
                if (!empty($_GET['config']))
                    $url .= "&config=" . urlencode($_GET["config"]);
            } else {
                // 否则直接返回原订阅
                $url = $subscribes['url'];
            }
            echo json_encode(array(
                'Status' => 'Success',
                'Location' => $url,
            ));
            header("Location: $url");
        } else {
            // 过期了则返回带过期信息的配置
            switch ($subscribes["target"]) {
                case 'clash':
                    echo "proxies:\n";
                    echo "  - {name: 订阅已过期, type: ss, cipher: aes-128-gcm, server: dns.google, port: 4433, password: expire}\n";
                    echo "proxy-groups:\n";
                    echo "  - {name: 注意, type: select, proxies: [订阅已过期]}\n";
                    break;
                default:
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(array(
                        'Status' => 'Error',
                        'Message' => 'Your subscription has expired.',
                    ));
                    break;
            }
        }
    }
}
