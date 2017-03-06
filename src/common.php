<?php
use think\Hook;
use think\Config;
use think\Loader;

// 插件目录
define('ADDON_PATH', ROOT_PATH . 'addons' . DS);

dump(11);
// 如果插件目录不存在则创建
if (!is_dir(ADDON_PATH)) {
    @mkdir(ADDON_PATH, 0777, true);
}


























