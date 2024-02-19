<?php

namespace Subman;

use Subman\Config;
use Subman\Database;

class Subscribes
{
    /**
     * 检查两个 string 最后一个字符与第一个字符是否为中日韩等文
     * @param string $first 第一个字符串
     * @param string $last 第二个字符串
     * @return bool 若相等则 true 若不相等则 false
     */
    function isStrNeedSpace(string $first, string $last): bool
    {
        // 正则表达式
        $patternFirst = '/[\p{Bopomofo}\p{Han}\p{Hiragana}\p{Katakana}]$/u';
        $patternLast = '/^[\p{Bopomofo}\p{Han}\p{Hiragana}\p{Katakana}]/u';

        // 比较区块代码是否相同
        return (preg_match($patternFirst, $first) && preg_match($patternLast, $last));
    }

    /**
     * 渲染订阅页面
     */
    public static function renderSubscribe()
    {
        $self = new self();
        $cfg = new Config();

        if (isset($_SESSION['uid']) && isset($_SESSION['username']) && isset($_GET['gid'])) {
            $db = new Database;
            $loader = new \Twig\Loader\FilesystemLoader("templates");
            $twig = new \Twig\Environment($loader);

            // 获取用户信息
            $users = $db->getRowbyName("users", 'isadmin, custom_config', array("uid" => $_SESSION['uid']));
            // 获取过期时间
            $expire = $db->getRowbyName("user_groups", 'expire', array("uid" => $_SESSION['uid'], "gid" => $_GET['gid']))['expire'];
            // 初始化 group
            $group = [];

            // 检查该订阅是否过期
            if (date('Y-m-d H:i:s') < $expire) {
                // 根据 gid 查找分组信息
                $group = $db->getRowbyName("groups", '*', array("gid" => $_GET['gid']));
                $group["subscribes"] = $db->getRowbyNameOrder("group_subscribes", 'sid, gid, name, converter', array("gid" => $_GET['gid']), array("orderlist" => "ASC"), true);
                foreach ($group["subscribes"] as &$subscribe) {
                    $subscribe["suggestion_name"] = $group["name"] . ($self->isStrNeedSpace($group["name"], $subscribe["name"]) ? '' : ' ') . $subscribe["name"];
                }
                $group["share"] = $db->getRowbyName("group_share", 'name, account, password, manage', array("gid" => $_GET['gid']), true);
                foreach ($group["share"] as &$share) {
                    $share["suggestion_name"] = $group["name"] . ($self->isStrNeedSpace($group["name"], $share["name"]) ? '' : ' ') . $share["name"];
                }
            }

            // 将变量传入 twig
            $twigVar = array(
                'subApiUrl' => ($_SERVER['REQUEST_SCHEME'] ?? "http") . '://' . $_SERVER['HTTP_HOST'] . $cfg->getValue('WebSite', 'BaseUrl') . '/api/subscribe',
                'baseUrl' => $cfg->getValue('WebSite', 'BaseUrl'),
                'username' => $_SESSION['username'],
                'uid' => $_SESSION['uid'],
                'isAdmin' => $users['isadmin'],
                'customConfig' => $users['custom_config'],
                'expire' => $expire,
                'group' => $group
            );
            $template = $twig->load("subscribes.twig");
            echo $template->render($twigVar);
        } else {
            header("Location: " . $cfg->getValue('WebSite', 'BaseUrl') . "/login");
            exit();
        }
    }

    /**
     * 渲染订阅列表
     */
    public static function renderSubscribeLists()
    {
        $cfg = new Config();

        if (isset($_SESSION['uid']) && isset($_SESSION['username'])) {
            $db = new Database;
            $loader = new \Twig\Loader\FilesystemLoader("templates");
            $twig = new \Twig\Environment($loader);

            $users = $db->getRowbyName("users", 'isadmin', array("uid" => $_SESSION['uid']));
            $userSubs = $db->getRowbyName("user_groups", '*', array("uid" => $_SESSION['uid']), true);
            $renderGroupSubs = [];
            foreach ($userSubs as $i) {
                $group = $db->getRowbyName("groups", 'gid, name', array("gid" => $i['gid']));
                $group['expire'] = $i['expire'];
                $renderGroupSubs[] = $group;
            }

            // 将变量传入 twig
            $twigVar = array(
                'baseUrl' => $cfg->getValue('WebSite', 'BaseUrl'),
                'username' => $_SESSION['username'],
                'isAdmin' => $users['isadmin'],
                'renderGroupSubs' => $renderGroupSubs
            );
            $template = $twig->load("subscribesList.twig");
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
        header('Content-Type: application/json');

        // 若 sub 和 user 不存在则打印错误并返回
        if (!isset($_GET['sub']) || !isset($_GET['user'])) {
            http_response_code(401);
            echo json_encode(array(
                'Status' => 'Error',
                'Message' => 'Please provide the parameters sub and user.',
            ));
            return;
        }

        $self = new self();
        $db = new Database;
        $cfg = new Config;

        $subscribes = $db->getRowbyName("group_subscribes", "*", array('sid' => $_GET['sub']));
        if (empty($subscribes)) {
            http_response_code(405);
            echo json_encode(array(
                'Status' => 'Error',
                'Message' => 'This subscribe ID does not contain any subscribe information.',
            ));
            return;
        }

        $groups = $db->getRowbyName("groups", "name", array("gid" => $subscribes['gid']));
        $expire = $db->getRowbyName("user_groups", "expire", array('gid' => $subscribes['gid'], 'uid' => $_GET['user']))['expire'];
        if (empty($expire)) {
            http_response_code(401);
            echo json_encode(array(
                'Status' => 'Error',
                'Message' => 'The user does not have access to this subscription.',
            ));
            return;
        }

        // 检查订阅是否已经过期
        if (date('Y-m-d H:i:s') > $expire) {
            $url = $cfg->getValue('WebSite', 'SubConverterUrl') . "target=" . (isset($_GET['target']) ? $_GET['target'] : 'auto') . "&url=" . urlencode("ss://YWVzLTI1Ni1jZmI6RXhwaXJl@dns.google:14514#订阅已过期");
            echo json_encode(array(
                'Status' => 'Expire',
                'Message' => 'Your subscription has expired.',
                'Location' => $url,
            ));
            header("Location: $url");
            return;
        }

        // 没有过期则跳转正确的 url
        if ($subscribes['converter'] == 0 || $_GET['original'] == 'true') {
            // 否则直接返回原订阅
            $url = $subscribes['url'];
        } else {
            // 生成文件名
            $suggestionName = $groups['name'] . ($self->isStrNeedSpace($groups['name'], $subscribes['name']) ? '' : ' ') . $subscribes['name'];

            // 如果使用改版订阅，则生成 subconverter 链接
            $url = $cfg->getValue('WebSite', 'SubConverterUrl') . "target=" . (isset($_GET['target']) ? $_GET['target'] : 'auto') . "&url=" . urlencode($subscribes['url']) . "&filename=" . urlencode($suggestionName) . "&" . $subscribes['converter_options'];
            // 如果定义了 config 参数，则将 config 参数也合并进 url 中
            if (!empty($_GET['config']))
                $url .= "&config=" . urlencode($_GET["config"]);
        }
        echo json_encode(array(
            'Status' => 'Success',
            'Location' => $url,
        ));
        header("Location: $url");
    }
}
