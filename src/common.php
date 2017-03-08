<?php
// +----------------------------------------------------------------------
// | thinkphp5 Addons [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.zzstudio.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Byron Sampson <xiaobo.sun@qq.com>
// +----------------------------------------------------------------------

use think\Hook;
use think\Config;
use think\Loader;

// 插件目录
define('ADDON_PATH', ROOT_PATH . 'addons' . DS);

// 定义路由
\think\Route::any('addons/:v/:addon/:controller/:action', "\\think\\addons\\Route@execute");

// 如果插件目录不存在则创建
if (!is_dir(ADDON_PATH)) {
    // @mkdir(ADDON_PATH, 0777, true);
    recurse_copy(__DIR__.'/../addons/',ADDON_PATH);
}

// 注册类的根命名空间
\think\Loader::addNamespace('addons', ADDON_PATH);

// 闭包初始化行为
Hook::add('action_begin', function () {
    // 获取系统配置
    $data = \think\Config::get('app_debug') ? [] : cache('hooks');
    $addons = (array)Config::get('addons');
    if (empty($data)) {
        // 初始化钩子
        foreach ($addons as $key => $values) {

            if (is_string($values)) {
                $values = explode(',', $values);
            } else {
                $values = (array)$values;
            }
            
            $addons[$key] = array_filter(array_map('get_addon_class', $values));

            \think\Hook::add($key, $addons[$key]);
        }
        cache('hooks', $addons);
    } else {
        Hook::import($data, false);
    }
});


/**
 * 处理插件钩子
 * @param string $hook 钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook, $params = [])
{
    \think\Hook::listen($hook, $params);
}

/**
 * 获取插件类的类名
 * @param $name 插件名
 * @param string $type 返回命名空间类型
 * @param string $class 当前类名
 * @return string
 */
function get_addon_class($name, $type = 'hook', $class = null)
{
    
    $name = \think\Loader::parseName($name);

    // 处理多级控制器情况
    if (!is_null($class) && strpos($class, '.')) {

        $class = explode('.', $class);
        foreach ($class as $key => $cls) {
            $class[$key] = \think\Loader::parseName($cls, 1);
        }
        $class = implode('\\', $class);
    } else {
        $class = \think\Loader::parseName(is_null($class) ? $name : $class, 1);
    }

    switch ($type) {
        case 'controller':
            $namespace = "\\addons\\". $name . "\\controller\\" . $class;
            break;
        default:
            $namespace = "\\addons\\". $name;
    }

    return class_exists($namespace) ? $namespace : '';
}

/**
 * 获取插件类的配置文件数组
 * @param string $name 插件名
 * @return array
 */
function get_addon_config($name)
{
    $class = get_addon_class($name);
    if (class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    } else {
        return [];
    }
}

/**
 * 插件显示内容里生成访问插件的url
 * @param $url
 * @param array $param
 * @return bool|string
 * @param bool|string $suffix 生成的URL后缀
 * @param bool|string $domain 域名
 */
function addon_url($url, $param = [], $suffix = true, $domain = false)
{

    $url = parse_url($url);
    $case = config('url_convert');

    $v = $case ? Loader::parseName($url['scheme']) : $url['scheme'];
    $addons = $case ? Loader::parseName($url['host']) : $url['host'];
    $path = explode('/', $url['path']);
    $controller = $case ? Loader::parseName($path[1]) : $path[1];
    $action = trim($case ? strtolower($path[2]) : $path[2], '/');

    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    // 生成插件链接新规则
    $actions = "{$addons}/{$controller}/{$action}";

    return url("/addons/{$v}/{$actions}", $param, $suffix, $domain);
}

function recurse_copy($src,$dst) { // 原目录，复制到的目录
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}