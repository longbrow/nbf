<?php
/*
 * common类用静态方法定义了一些常用的函数
 */
namespace core;
use core\App;
class Common {
//获取当前module名
public function get_module(){
    $module = App::$app_var['module'];
    return $module;
}
//获取当前控制器名
public function get_controller(){
    $controller = App::$app_var['controller'];
    return $controller;
}
//获取当前方法名
public function get_action(){
    $action = App::$app_var['action'];
    return $action;
}

//获取当前的域名
public function get_domain(){
    if(isset($_SERVER['HTTP_HOST']))
        return $_SERVER['HTTP_HOST'];
    else
        return $_SERVER['SERVER_ADDR'];
}

//获取当前完整的url网址
public function get_url(){
   if(80!=$port = $_SERVER['SERVER_PORT']){
    $url = 'http://'.$this ->get_domain().':'.$port.$_SERVER['REQUEST_URI'];   
   }else{
     $url = 'http://'.$this ->get_domain().$_SERVER['REQUEST_URI'];  
   }
   return $url;
}
//获取user_agent
public function get_user_agent(){
    if(isset($_SERVER['HTTP_USER_AGENT']))
        $useragent = $_SERVER['HTTP_USER_AGENT'];
    else
        $useragent = NULL;
    return $useragent;
}

//获取http请求头里的accept信息
public function get_accept(){
    if(isset($_SERVER['HTTP_ACCEPT']))
        $accept = $_SERVER['HTTP_ACCEPT'];
    else
        $accept = NULL;
    return $accept;
}
//获取http请求方法
public function get_method(){
    return $_SERVER['REQUEST_METHOD'];
}

//----------------cookie操作的系列函数----------------------------
/*
 * 获取指定名称的cookie
 */
public function get_cookie($name){
   $name = $this ->get_module().'_'.$name;//加上'模块名_'做前缀,以区分不同项目 
    if(isset($_COOKIE[$name])){
        $cookie = $_COOKIE[$name];
    }else{
        $cookie = NULL;
    }
    return $cookie;
    
}

//删除指定名称的cookie;此函数必须在其他输出前调用
public function del_cookie($name){
    $name = $this ->get_module().'_'.$name;//加上'模块名_'做前缀,以区分不同项目
    setcookie($name, NULL, time()-3600,'/');
}

//清空cookie;此函数要在其他输出前调用
public function clear_cookie(){
    $prefix = $this ->get_module().'_';//'模块名_'做前缀,以区分不同项目
    foreach($_COOKIE as $key=>$value){
        if(stripos($key,$prefix)===0)
        setcookie($key, NULL, time()-3600,'/');
    }
}

//设置指定名字的cookie;此函数必须在其他输出前调用
public function set_cookie($name,$value='',$expire=0){
    $name = $this ->get_module().'_'.$name;//加上'模块名_'做前缀,以区分不同项目
    setcookie($name,$value,$expire,'/');
}

//----------------以下是session操作的函数------------------------------
//启动session
public function my_session_start(){
    //为了区分项目,要给session名加上'模块名_'的后缀
    $session_name = strtoupper('SessionID_'.$this ->get_module());
    session_start([
    'name'=>$session_name,    
    'cookie_lifetime' => isset(App::$config['session_expire'])?intval(App::$config['session_expire']):1800,
]);
}

//读取session指定的值
public function get_session($name){
    if(isset($_SESSION[$name]))
        return $_SESSION[$name];
    else
        return NULL;
}

//给session设置值
public function set_session($name,$value){
    if(empty($name))
        return false;
    else
        $_SESSION[$name] = $value;
    
    return true;
    
}

//删除指定的session
public function del_session($name){
  if(empty($name) || !isset($_SESSION[$name]))
      return false;
  else
      unset($_SESSION[$name]);
  return true;
}

//清空并释放session
public function clear_session(){
   //清除客户端的cooke里的sessionid
    setcookie(session_name(), NULL, time()-3600,'/');
    //清除服务器上的session文件和变量
    session_unset();
    session_destroy();

}
//根据路由规则生成url
public function makeurl($mca,$arg=[],$suffix=''){
    if(App::$use_router){
        return Router::makeurl($mca, $arg, $suffix);
    }else{
          $new_url = '/'.trim($mca,'/').'/';
          foreach ($arg as $k=>$v){
              $new_url.= $k.'/'.$v.'/';
          }
          return rtrim($new_url,'/').$suffix;
    }
}
}