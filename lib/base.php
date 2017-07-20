<?php
if (version_compare(PHP_VERSION, '7.0.0') < 0) {
    echo '<h2>本框架必须在PHP 7.0以上版本使用! 当前版本: ' . PHP_VERSION . "</h2>";
    die;
}
defined('DS') or define('DS', DIRECTORY_SEPARATOR);//目录分隔符
if(!defined('APP_PATH')){
    echo '<h2>请在入口文件里定义 "APP_PATH"</h2>';
    die;  
}
    $app_path = APP_PATH;
if(empty($app_path)){
    echo '<h2> "APP_PATH" 的值不能为空值!</h2>';
    die;    
}else{

    if(DS=='/'){ //linux
     $app_path = str_replace("\\", DS, $app_path);   
    }else{ //win
     $app_path = str_replace("/", DS, $app_path);   
    }    
    
    $path =realpath($app_path);//返回真实目录
   if(false===$path){
    echo "<h2>APP_PATH定义的目录 $app_path 找不到!</h2>";
    die;          
    }
    $app_path = rtrim($path,DS) . DS;
}
define('APPLICATION_PATH', $app_path);//定义应用目录
//定义一些常用常量
defined('EXT') or define('EXT', '.php');//文件后缀
defined('APP_NAME') or define('APP_NAME', basename(APPLICATION_PATH));//应用目录的名称
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APPLICATION_PATH)) . DS);//网站根目录
defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);//lib目录
defined('LIB_PATH') or define('LIB_PATH', __DIR__ . DS);//lib目录
defined('TRAIT_PATH') or define('TRAIT_PATH', ROOT_PATH . 'traits' . DS);//traits的目录
defined('SMARTY_PATH') or define('SMARTY_PATH', LIB_PATH . 'smarty' . DS);//smarty目录
defined('CORE_PATH') or define('CORE_PATH', LIB_PATH . 'core' . DS);//lib/core
defined('EXTEND_PATH') or define('EXTEND_PATH', ROOT_PATH . 'extend' . DS);//扩展目录
defined('CONF_EXT') or define('CONF_EXT', EXT); // 配置文件后缀
defined('PUBLIC_PATH') or define('PUBLIC_PATH',ROOT_PATH.'public'.DS);//public目录
//
//自定义一些错误号,用来处理自定义错误
defined('ERR_ROUTER') or define('ERR_ROUTER',80001);//路由解析错误

// 环境常量
defined('IS_CLI') or define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
defined('IS_WIN') or define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);
//设置时区
//ini_set('date.timezone','Asia/Shanghai');
//date_default_timezone_set("Etc/GMT+8");//这里比林威治标准时间慢8小时 
//date_default_timezone_set("Etc/GMT-8");//这里比林威治标准时间快8小时 
date_default_timezone_set('PRC'); //设置中国时区 

//区分大小写判断文件是否存在
function file_exists_case($filename) {
    if (is_file($filename)) {
        if (strstr(PHP_OS, 'WIN')) {
            if (basename(realpath($filename)) != basename($filename))
                return false;
            else if(dirname(realpath($filename)) != dirname($filename))
                return false;
        }
        return true;
    }
    return false;
}


//创建runtime 目录
if(!file_exists(RUNTIME_PATH))
mkdir(RUNTIME_PATH);
if(!file_exists(RUNTIME_PATH.'cache'))
mkdir(RUNTIME_PATH.'cache');//模板缓冲
if(!file_exists(RUNTIME_PATH.'tpl_c'))
mkdir(RUNTIME_PATH.'tpl_c');//模板编译文件
//注册自动加载类
require CORE_PATH.'Loader'.EXT;
\core\Loader::register();

//注册错误处理类
\core\Error::register();

//加载全局配置
\core\App::set_config(APPLICATION_PATH.'config.php');




