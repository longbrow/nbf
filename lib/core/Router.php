<?php
namespace core;

class Router{
  public static $url_map = []; //路由配置 
  public static $post_url =[]; //post的路由策略
  public static $get_url = []; //get的路由策略
  public static $put_url = []; //put的路由策略
  public static $delete_url=[]; //delete的路由策略
  public static $makeurl = [];//makeurl时候使用到的匹配规则

//加载路由策略文件  
 public static function load_routerfile($url_file){
   if(is_file($url_file)){
     self::$url_map = require $url_file;
     array_change_key_case(self::$url_map);
     self::dispatch_url_map();
   }
  }
  
  //解析路由
  //通用正则表达式就是数组的key,用之进行比较
  public static function parse_url($mca,$method='GET'){
      switch (strtolower($method)){
          case 'get':
              foreach (self::$get_url as $key=>$value){
              if(1=== preg_match($key, $mca,$matchs)){//通用规则匹配成功,再进行参数匹配
                $real_mca = $value['mca'];
                $mca = ltrim($mca,$matchs[1].'/');//去掉URI,就只剩下实参值了
                if(strlen($mca)>0){ //有可能是无参数的
                    $mca = rtrim($mca,'/');
                $tmp = explode('/', $mca);
                $i = count($tmp);//计算有多少个参数
                //按顺序取出对应的参数名
                $arg_name = array_splice(self::$get_url[$key]['arg_name'], 0,$i);
                //合并成参数名=>值的数组
                $param = array_combine($arg_name, $tmp);
                //再用参数值的正则进行匹配
                foreach ($param as $k1=>$v1){
                    if(isset(self::$get_url[$key][$k1]))
                        if(preg_match(self::$get_url[$key][$k1], $v1)==0){
                            throw new \Exception("参数[ $k1 ]的值[ $v1 ]与路由策略不匹配!",ERR_ROUTER);
                            die;
                        }
                }
                //参数匹配成功,则进行mca+参数的合成
                if(substr($real_mca, -1,1)!='/')
                        $real_mca .='/';
                foreach ($param as $k1=>$v1){
                    $real_mca.=$k1.'/'.$v1.'/';
                }
                return rtrim($real_mca,'/');
                
                }else{
                    return $real_mca;
                }
              }else                  
                  continue;
              }
              break;
          case 'post':
              foreach (self::$post_url as $key=>$value){
              if(1=== preg_match($key, $mca)){//通用规则匹配成功,再进行参数匹配
                $real_mca = $value['mca'];
                $pattern = self::$post_url[$key];
                unset($pattern['mca']);
                if(!is_null($pattern)){
                    foreach ($_POST as $k1=>$v1){//取出post内容来进行参数匹配
                       if(isset($pattern[$k1])){
                           if(0=== preg_match($pattern[$k1], $v1)){
                             throw new \Exception("参数[ $k1 ]的值[ $v1 ]与路由策略不匹配!",ERR_ROUTER);
                             die;                              
                           }
                       }
                    }
                }
                return $real_mca;
              }else                  
                  continue;
              }              
              break;
      }  
      return $mca;
  }
  
  //将路由规则按照请求方法封装到对应的数组里
  //格式: '通用正则表达式'=>['mca'=>'admin/index/test','arg_name'=>['参数1','参数2','参数N'],'参数1'=>'正则','参数N'=>'正则']
  public static function dispatch_url_map(){
      foreach (self::$url_map as $key=>$value){
          if(strcasecmp($key,'pattern')===0){//取出全局参数正则数组
           $g_pattern = $value;   
          }else{ //处理路由短句
              $method = isset($value['method'])?$value['method']:'GET';
              $method = strtolower($method);
              $arg_pattern = [];//存储参数的正则表达式
              $arg_name =[];//按顺序存储参数名
           if(false!==strpos($key,':')){//需要进一步处理参数
               $arg = explode(':', $key);
               //取出第一个元素,即是URI标识.剩下数组元素就是参数列表
               $URI = trim(array_shift($arg),'/');
               $normal_pattern = "($URI)"; //第一个URI的正则表达式       
               foreach ($arg as $v1){
                    if(strpos($v1,'[')!==false){//可选参数
                        if($method == 'get')//post put delete 参数不会出现在url上
                            $normal_pattern.='([\/]*[^\/]*)'; //可选参数正则表达式 
                     $arg1=trim($v1,'[]');
                     array_push($arg_name,$arg1);//将参数名按顺序保存
                     if(isset($g_pattern[$arg1]))//取全局参数正则
                         $arg_pattern[$arg1]=$g_pattern[$arg1];
                     if(isset($value['pattern'][$arg1]))//用自定义正则覆盖全局
                         $arg_pattern[$arg1]=$value['pattern'][$arg1];
                    } else{//必选参数
                        if(strlen($v1)>0){
                            $arg1 = trim($v1);
                            array_push($arg_name,$arg1);//将参数名按顺序保存
                            if($method == 'get')
                                $normal_pattern.='([\/][^\/]+)'; //必选参数正则表达式
                            if(isset($g_pattern[$arg1]))//取全局参数正则
                                $arg_pattern[$arg1]=$g_pattern[$arg1];
                            if(isset($value['pattern'][$arg1]))//用自定义正则覆盖全局
                                $arg_pattern[$arg1]=$value['pattern'][$arg1];
                        }
                    }                 
               }
              $normal_pattern ='/^'. $normal_pattern . '$/';//组合成通用正则表达式
               
           }else{ //无参数
                    $URI = trim($key,'/');
                    $normal_pattern = "/^($URI)$/";
           }   
            //分发到对应的数据里
               switch ($method){
                   case 'get':
                    self::$get_url[$normal_pattern]=['mca'=>trim($value['mca'],'/'),'arg_name'=>$arg_name];
                    self::$get_url[$normal_pattern] = array_merge(self::$get_url[$normal_pattern],$arg_pattern);
                    break;
                    case 'post':
                    self::$post_url[$normal_pattern]=['mca'=>$value['mca']];
                    self::$post_url[$normal_pattern] = array_merge(self::$post_url[$normal_pattern],$arg_pattern);
                    break;
                   case 'put':
                    self::$put_url[$normal_pattern]=['mca'=>$value['mca']];
                    self::$put_url[$normal_pattern] = array_merge(self::$put_url[$normal_pattern],$arg_pattern);
                    break;
                    case 'delete':
                    self::$delete_url[$normal_pattern]=['mca'=>$value['mca']];
                    self::$delete_url[$normal_pattern] = array_merge(self::$delete_url[$normal_pattern],$arg_pattern);
                    break;                
               }

           //makeurl填充数据,将mca去掉左右/,并小写
            //如右格式: 'admin/index/test' =>['pattern'=>'user/:name:age:[email]','method'=>'get' ]  
            $case_mca = strtolower(trim($value['mca'],'/'));
           self::$makeurl[$case_mca]=['method'=>$method,'pattern'=>$key];
          }
      }
  }
  
  //根据已有的路由规则,生成url地址
  //makeurl数组格式: 'admin/index/test' =>['pattern'=>'user/:name:age:[email]','method'=>'get' ] 
  public static function makeurl($mca,$arg=[],$suffix=''){
      $new_mca = strtolower(trim($mca,'/'));
      if(array_key_exists($new_mca, self::$makeurl)){//发现mca存在
          $method = self::$makeurl[$new_mca]['method'];//路由请求形式 get post put delete
          if(strpos(self::$makeurl[$new_mca]['pattern'], ':')!==false){//分割规则短句
            $args = explode(':', self::$makeurl[$new_mca]['pattern']); 
            $URI = array_shift($args);
            $new_url = '/'.trim($URI,'/').'/';
            foreach ($args as $value) {
              if(strpos($value, '[')===false){
                 //必选参数
                if(strcasecmp($method, 'get')===0){//只有get才需要把参数写到url里  
                $param =  isset($arg[trim($value)])?$arg[trim($value)]:'rt_novalue';
                $new_url.= $param .'/'; //跟路由配置里参数不一致,加个默认值
                }
              }else{
                  //可选参数
                  $value = trim($value,'[]');
                   if(strcasecmp($method, 'get')===0){//只有get才需要把参数写到url里
                  $param =  isset($arg[trim($value)])?$arg[trim($value)]:NULL;
                  if(!is_null($param))//没有就不加参数
                    $new_url.= $param .'/';
              }  }
            }
            $new_url = rtrim($new_url,'/').$suffix;
            
          }else{//无参数
            $new_url = '/'.trim(self::$makeurl[$new_mca],'/').$suffix;  
          }
      }else{
          //路由规则里没有,就生成正常的pathinfo地址
          $new_url = '/'.trim($mca,'/').'/';
          foreach ($arg as $k=>$v){
              $new_url.= $k.'/'.$v.'/';
          }
           $new_url = rtrim($new_url,'/').$suffix;
      }
      
      return $new_url;
  }
  
}

