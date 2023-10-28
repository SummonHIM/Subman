<?php
namespace Subman;

use Subman\Router;

final class Application {
    /**
     * 一切从此开始
     */
    public static function run() {
        session_start();
        $ret = Router::route();
        return $ret;
    }
}