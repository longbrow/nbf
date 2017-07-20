<?php
namespace core;
/*
 * 1.路由功能只是为了隐藏真实的URL地址
 * 2.同时也可以缩短url的长度
 * ps: 从实用场景考虑,只需要对可见的URL做判断即可.无需考虑GET POST PUT DELETE 方法
 */
class Router{
  public static $url_map = []; //路由配置 
  public static $get_url=[];//匹配正则的url规则
  public static $makeurl = [];//makeurl时候使用到的匹配规则

//加载路由策略文件  
 public static function load_routerfile($url_file){
   if(file_exists_case($url_file)){
     self::$url_map = require $url_file;
     //array_change_key_case(self::$url_map);
     self::dispatch_url_map();
   }
  }
  
  //解析路由
  //通用正则表达式就是数组的key,用之进行比较
  public static function parse_url($mca){
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

      return $mca;
  }
  
  //将路由规则按照请求方法封装到对应的数组里
  //格式: 'URI/:参数名1:参数名N:[可选参数名1]:[可选参数名N]'=>['mca'=>'admin/index/test','pattern'=>['参数1'=>'正则','参数N'=>'正则']]
  //'user/:name:age:[email]' =>['mca'=>'/admin/index/show','pattern'=>['age'=>'/^\d{2}$/']],
  public static function dispatch_url_map(){
      foreach (self::$url_map as $key=>$value){
          if(strcasecmp($key,'pattern')===0){//取出全局参数正则数组
           $g_pattern = $value;   
          }else{ //处理路由短句
              $arg_pattern = [];//存储参数的正则表达式
              $arg_name =[];//按顺序存储参数名
           if(false!==strpos($key,':')){//:符号代表有参数,需要进一步处理参数
               $arg = explode(':', $key); //短句分割成数组
               //取出第一个元素,即是URI标识.剩下数组元素就是参数列表
               $URI = trim(array_shift($arg),'/');
               $normal_pattern = "($URI)"; //第一个URI的正则表达式       
               foreach ($arg as $v1){
                    if(strpos($v1,'[')!==false){//可选参数
                     $normal_pattern.='([\/]*[^\/]*)'; //可选参数正则表达式,表示可有可无 
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
                            $normal_pattern.='([\/][^\/]+)'; //必选参数正则表达式(/xxx)这种形式
                            if(isset($g_pattern[$arg1]))//取全局参数正则
                                $arg_pattern[$arg1]=$g_pattern[$arg1];
                            if(isset($value['pattern'][$arg1]))//用自定义正则覆盖全局
                                $arg_pattern[$arg1]=$value['pattern'][$arg1];
                        }
                    }                 
               }
              $normal_pattern ='/^'. $normal_pattern . '$/';//组合成通用正则表达式
               //类似于: /^(URI)([\/][^\/]+)([\/][^\/]+)([\/][^\/]+)([\/]*[^\/]*)$/  比如: user/name/age/sex  就符合这个表达式
           }else{ //无参数
                    $URI = trim($key,'/');
                    $normal_pattern = "/^($URI)$/";
           }   

                    self::$get_url[$normal_pattern]=['mca'=>trim($value['mca'],'/'),'arg_name'=>$arg_name];
                    self::$get_url[$normal_pattern] = array_merge(self::$get_url[$normal_pattern],$arg_pattern);
                    //类似于:['mca'=>'admin/index/test','arg_name'=>["name","age","sex"],"name"=>"/正则/","age"=>"/正则/"]


           //makeurl填充数据,将mca去掉左右/,并小写
            //如右格式: 'admin/index/test' =>['pattern'=>'user/:name:age:[email]']  
            $url_mca = trim($value['mca'],'/');
           self::$makeurl[$url_mca]=['pattern'=>$key];
          }
      }
  }
  
  //根据已有的路由规则,生成url地址
  //makeurl数组格式: 'admin/index/test' =>['pattern'=>'user/:name:age:[email]'] 
  public static function makeurl($mca,$arg=[],$suffix=''){
      $new_mca = trim($mca,'/');
      if(array_key_exists($new_mca, self::$makeurl)){//发现mca存在
          if(strpos(self::$makeurl[$new_mca]['pattern'], ':')!==false){//分割规则短句
            $args = explode(':', self::$makeurl[$new_mca]['pattern']); 
            $URI = array_shift($args);
            $new_url = '/'.trim($URI,'/').'/';
            foreach ($args as $value) {
              if(strpos($value, '[')===false){
                 //必选参数
                $param =  isset($arg[trim($value)])?$arg[trim($value)]:'rt_novalue';
                $new_url.= $param .'/'; //跟路由配置里参数不一致,加个默认值
                
              }else{
                  //可选参数
                  $value = trim($value,'[]'); 
                  $param =  isset($arg[trim($value)])?$arg[trim($value)]:NULL;
                  if(!is_null($param))//没有就不加参数
                    $new_url.= $param .'/';
                }
            }
            $new_url = rtrim($new_url,'/').$suffix;
            
          }else{//无参数
            $new_url = '/'.trim(self::$makeurl[$new_mca]['pattern'],'/').$suffix;  
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

