<?php
/*
 * Redis::REDIS_STRING - String
Redis::REDIS_SET - Set
Redis::REDIS_LIST - List
Redis::REDIS_ZSET - Sorted set
Redis::REDIS_HASH - Hash
Redis::REDIS_NOT_FOUND - Not found / other
 */
namespace core;
class RedisClt extends \Redis{
    protected static $datebase =[];//可能多个配置
    protected $ONE = TRUE;//只有一个redis
    public function __construct() {
               //如果配置里只有一个redis库,那么构造的时候就直接建立连接
        if(count(self::$datebase)==1){
             $db_identifier=array_keys(self::$datebase);//取出数据库标识符
             $result =$this->connect($db_identifier[0]);
        if(!$result){
            throw new \Exception( "Redis: ".$db_identifier ."连接失败!");  
          }
             $this ->ONE = TRUE;
           
        } else{
            $this ->ONE = FALSE;
        }
    }
    

    //根据配置来连接redis服务端
    public function connect($db_identifier){
            if(!isset(self::$datebase[$db_identifier])){
                throw new \Exception(nbf()->get_module()." 模块下的datebase.php文件redis配置里,没有找到 ".$db_identifier .' 的配置信息!' );
            }
            //
            if(empty(self::$datebase[$db_identifier]["host"])){
                throw new \Exception(nbf()->get_module()." 模块下的datebase.php文件redis配置里,没有找到 ".$db_identifier .' 下的host配置信息!' ); 
            }else{
                $host = self::$datebase[$db_identifier]["host"];
            }
            //
            if(empty(self::$datebase[$db_identifier]["port"])){
                $port = 6379;
            }else{
                $port = self::$datebase[$db_identifier]["port"];
            }
            //
            if(!empty(self::$datebase[$db_identifier]["timeout"])){
                $timeout = self::$datebase[$db_identifier]["timeout"];
            }else{
                $timeout = 2; //2秒超时
            }
            //
            if(empty(self::$datebase[$db_identifier]["password"])){
                $password = "";
            }else{
                $password = self::$datebase[$db_identifier]["password"];
            }
            //
            $connect_OK = TRUE;
            $auth_OK = TRUE;
            //pconnect可复用连接,性能高
               $connect_OK = $this ->pconnect($host,$port,$timeout);
               if(! empty($password)){
                   $auth_OK=$this->auth($password);
               }
           
            
            return ($connect_OK and $auth_OK)?TRUE:FALSE;

    }



    /*
     * 选择一个要使用的数据库
     * $db_identifier 代表database里设置的rds标识
     * 返回一个redis连接后的实例对象本身
     */
    public function usedb($db_identifier){
        if(empty($db_identifier)){ //为空,查找默认数据库
              if($db_identifier!=0)
              throw new \Exception(" usedb() 参数不能为空 !" );
              
          }
          
          if($this ->ONE and isset(self::$datebase[$db_identifier]))
              return $this;
          //共用一个实例的时候,只能重新连接
          $result =$this ->connect($db_identifier);
          if(!$result){
            throw new \Exception( "Redis: ".$db_identifier ."连接失败!请检查host或port或password");  
          }
          return $this;
    }





        /*
     * 从配置里读取数据库参数,参数是二维数组
     */
    public static function set_datebase($array){
       foreach($array as $key=>$value){
           if(count($value)<2) //redis的配置参数需要2个,不可少host和port
               continue;
           else
               self::$datebase[$key]=$value;
       } 
    }
    
    
    
}

