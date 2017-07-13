<?php

namespace core;

use core\Response;
use core\Router;
require __DIR__.DS.'helper.php'; //加载全局内置函数
class App {
    public static $nbf = NULL; //存储common对象实例,节省内存
    public static $validate = NULL;//存储validate对象实例
    public static $app_var = []; //存储module controller action等信息
    public static $config = []; //项目配置文件 config.php
    public static $use_router = false; //是否启用路由
    public static $real_url = '';//经过路由转换后的真实的URL
    public static $cur_suffix='';//当前使用的伪后缀
    /*
     * 将全局配置文件加载到$config里
     */

    public static function set_config($cfg_file) {
        if (is_file($cfg_file)) {
            self::$config = require $cfg_file;
            array_change_key_case(self::$config);
        }
    }

//跳转到404
    public static function jump_notFound() {
        if(!headers_sent())
        header("HTTP/1.1 404 Not Found");
        // header("Status: 404 Not Found");
    }

    /*
     * 解析MCA的格式是否符合规范
     */

    public static function parse_mca($mca, $mca_arr) {
        if (is_dir(APPLICATION_PATH . $mca_arr[0])) { //如果模块存在,就马上读取config
                  //读取项目配置文件
            if (is_file(APPLICATION_PATH . $mca_arr[0] . DS . 'config.php')) {
                $config = require APPLICATION_PATH . $mca_arr[0] . DS . 'config.php';
                array_change_key_case($config);
                self::$config = array_merge(self::$config, $config); //覆盖全局设置中的部分内容
            }  
        }
        $num = count($mca_arr);
        switch ($num) {
            //判断模块目录是否存在
            case 0:
                self::jump_notFound();
                throw new \Exception("缺少 模块/控制器/方法 !");
                die;
                break;
            case 1:
                if (!is_dir(APPLICATION_PATH . $mca_arr[0])) {
                    self::jump_notFound();
                    throw new \Exception(" 模块 [$mca_arr[0]] 不存在!");
                    die;
                } else {
                    self::jump_notFound();
                    throw new \Exception('缺少 控制器/方法!');
                    die;
                }
                break;
            case 2:
                self::jump_notFound();
                throw new \Exception('缺少 方法!');
                die;
                break;
            case 3:
            default :
                if (!is_dir(APPLICATION_PATH . $mca_arr[0])) {
                    self::jump_notFound();
                    throw new \Exception("模块 [$mca_arr[0]] 不存在!");
                    die;
                }
                if (!is_file(APPLICATION_PATH . $mca_arr[0] . DS . 'controller' . DS . $mca_arr[1] . EXT)) {
                    self::jump_notFound();
                    throw new \Exception("控制器 [$mca_arr[1]] 不存在!");
                    exit;
                }
                if (empty($mca_arr[2])) {
                    self::jump_notFound();
                    throw new \Exception("缺少 方法 !");
                    exit;
                }
        }
    }

//启动session
    public static function start_session() {
        if (isset(self::$config['session_start'])) {
            if (self::$config['session_start']) {
                nbf()->my_session_start();
            }
        }
    }

    /*
     * 网页开始入口,包括解析url/参数/返回 等
     */

    public static function Begin() {
        if (isset(self::$config['url_router'])) {
            if (self::$config['url_router']) {
                Router::load_routerfile(APPLICATION_PATH . 'url.php');
                self::$use_router = true;
            }
        }
        $Response = new Response();//定义一个返回对象
        if (isset($_SERVER['HTTP_REFERER']))
            $referer = $_SERVER['HTTP_REFERER'];
        $method = $_SERVER['REQUEST_METHOD']; //get /post /put /delete
        //进行路由解析
        if (strpos($_SERVER['QUERY_STRING'], 's=') === 0) { //rewrite模式!query_string是?s=/admin/index/test....

            $mca = substr($_SERVER['QUERY_STRING'], 2); //去掉's=',?号已经被浏览器去掉了
            $mca = preg_replace("/[=&?]/", "/", $mca);//将&=?这3个字母替换成/ 分隔符,主要是为了兼容url的普通模式
            if(FALSE!=strpos($mca, "//")){
                $mca = preg_replace("/[^\/]+\/\//", "", $mca);//去掉form提交空参数(无值)的情况出现//,例如:/admin/control/action/k1/v1/k2//k3/v3
              }
        }else {//pathinfo模式
            if (strcasecmp(rtrim($_SERVER['PHP_SELF'],'/'), $_SERVER['SCRIPT_NAME']) == 0) { //无参数,根目录
                if(!empty(isset(self::$config['home'])?self::$config['home']:NULL)) //设置了主页就取主页地址
                $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'].'/' . ltrim(self::$config['home'], '/');
                else{
                    $Response->data = 'F5D82E8CDF76291B3BC8515651BB8141';//nbf的md5码,返回内置主页
                    return $Response; //否则跳到默认页.
                }
            }

            $pos = strpos($_SERVER['PHP_SELF'], '/', 1); //去掉/SCRIPT_NAME
            $mca = substr($_SERVER['PHP_SELF'], $pos);
            if (false !== $pos = strpos($mca, '&')) //去掉&的情况
                $mca = substr($mca, 0, $pos);
        }

        //处理一下伪静态后缀
        if (false !== $pos = strrpos($mca, '.')) {
            $suffix = strtolower(substr($mca, $pos + 1)); //取出后缀,并转成小写
            //判断是否是定义过的伪后缀
            foreach (self::$config['suffix'] as &$value) {
                $value = strtolower($value); //将用户定义后缀全转换成小写
            }
            if (in_array($suffix, self::$config['suffix'])){
                $mca = substr($mca, 0, $pos);
                self::$cur_suffix = "." . $suffix; //将当前使用的伪后缀保存下来,加了'.',供分页功能使用
            }
        }
        $mca = trim(strtolower($mca), '/'); //全部转成小写,为了正则的精确匹配,并去掉前后的'/'
        //这里到路由映射数组里进行比对还原
        if (self::$use_router){
            $mca = Router::parse_url($mca, $method);
        }
        self::$real_url = $mca;//将经过路由转换过的真实的request请求保存下来
        $mca = ltrim($mca, '/');
        $mca_arr = explode('/', $mca); //将url分割成数组
        //判断一下m-c-a是否符合要求
        self::parse_mca($mca, $mca_arr);



        $real_mca = array_splice($mca_arr, 0, 3); //提取出前面的模块/控制器/方法,原数组就剩下参数了
        //

           //存储M-C-A留作以后取值
        self::$app_var['module'] = $real_mca[0];
        self::$app_var['controller'] = $real_mca[1];
        self::$app_var['action'] = $real_mca[2];

        //启动session
        self::start_session();
        //加载项目全局函数文件
        if (is_file(APPLICATION_PATH . $real_mca[0] . DS . 'common.php')) {
            require APPLICATION_PATH . $real_mca[0] . DS . 'common.php';
        }

        //读取数据库配置参数,并赋值给相应的数据库模型
        if (is_file(APPLICATION_PATH . $real_mca[0] . DS . 'datebase.php')) {
            $datebase = require APPLICATION_PATH . $real_mca[0] . DS . 'datebase.php';
            array_change_key_case($datebase);//将数据库的key设置成小写
            //可能有多种数据库同时使用的情况,所以要分别处理
            if (array_key_exists("mysql", $datebase)) {//使用了mysql
                if (count($datebase['mysql']) > 0)
                    \core\Mysql::set_datebase($datebase['mysql']);
            }
            if (array_key_exists("mongo", $datebase)) {//使用了mongo
                if (count($datebase['mongo']) > 0)
                    \core\Mongo::set_datebase($datebase['mongo']);
            }
            if (array_key_exists("redis", $datebase)) {//使用了redis
                if (count($datebase['redis']) > 0)
                    \core\Redis::set_datebase($datebase['redis']);
            }
            if (array_key_exists("memcache", $datebase)) {//使用了memcache
                if (count($datebase['memcache']) > 0)
                    \core\Memcache::set_datebase($datebase['memcache']);
            }
        }
        $class = '\\' . APP_NAME . '\\' . $real_mca[0] . '\\controller\\' . $real_mca[1]; //组合类名
        define('TPL_PATH', APPLICATION_PATH . $real_mca[0] . DS . 'view' . DS); //定义模板存放的目录
        define('TPL_DEFAULT_NAME', $real_mca[1] . '.' . $real_mca[2] . '.html'); //模板文件的默认名,控制器.方法.html 形式
        //提取参数,并组合成键值对形式的关联数组
        if (count($mca_arr) == 0) {
            $args = []; //无参数
        } elseif (count($mca_arr) % 2 == 0) {
            //进行键值对的分割
            $keys = [];
            $values = [];
            foreach ($mca_arr as $key => $value) {
                if ($key % 2 == 0) {
                    $keys[] = $value;
                } else {
                    $values[] = $value;
                }
            }
            $args = array_combine($keys, $values); //组合参数-参数名->值
            $_GET = $args; //给$_GET也保留一份键值对的参数
        } else {
            //参数错误
            throw new \InvalidArgumentException(nbf()-> get_module()."/".nbf()-> get_controller()."/". nbf()-> get_action().' 方法的参数不匹配!');
            die;
        }

        $instance = self::invokeClass($class); //调起控制器类的实例
        //执行控制器的方法
        $Response->data = self::invokeMethod($class, $instance, $real_mca[2], $args);




        return $Response; //返回一个对象
    }

    /**
     * 调用反射执行类的方法 支持参数绑定
     * @access public
     * @param string $classname 类名
     * @param sting $instance 类实例
     * @param string $method 方法
     * @param array        $vars   变量
     * @return mixed
     */
    public static function invokeMethod($classname, $instance, $method, $vars = []) {
        $reflect = new \ReflectionMethod($classname, $method);

        $args = self::createParams($reflect, $vars);


        return $reflect->invokeArgs($instance, $args);
    }

    /**
     * 调用反射执行类的实例化
     * @access public
     * @param string    $class 类名
     * @param array     $vars  变量
     * @return mixed
     */
    public static function invokeClass($class, $vars = []) {
        $reflect = new \ReflectionClass($class);
        $constructor = $reflect->getConstructor();
        if ($constructor) {
            $args = self::createParams($constructor, $vars);
        } else {
            $args = [];
        }
        return $reflect->newInstanceArgs($args);
    }

    /**
     * 获取参数
     * @access public
     * @$vars array 参数传值
     * @param \ReflectionMethod|\ReflectionFunction $reflect 反射类
     * @return array
     */
    private static function createParams($reflect, $vars = []) {
        $args = [];
        // 判断数组类型 数字数组时按顺序绑定参数
        reset($vars);
        $type = key($vars) === 0 ? 1 : 0; //判断是否有参数名
        if ($reflect->getNumberOfParameters() > 0) {
            $params = $reflect->getParameters();
            foreach ($params as $param) {
                $name = $param->getName();
                if (1 == $type && !empty($vars)) {//没有参数名,就按顺序取值
                    $args[] = array_shift($vars);
                } elseif (0 == $type && isset($vars[$name])) {//有参数名
                    $args[] = $vars[$name];
                } elseif ($param->isDefaultValueAvailable()) {//没有实参,就取默认值
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new \InvalidArgumentException(nbf()-> get_module()."/".nbf()-> get_controller()."/". nbf()-> get_action().' 方法的参数不匹配!');
                }
            }
        }
        return $args;
    }

}
