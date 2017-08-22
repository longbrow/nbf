<?php
namespace core;
use PDO;
use PDOException;
class Mysql{

    protected static $datebase =[];//可能多个配置
    protected $pdo = [];//将连接后的pdo对象存放到这个数组里
    protected $cur_connect = NULL;//当前使用哪个数据库连接
    public function __construct() {
        //如果配置里只有一个数据库,那么构造的时候就直接建立连接
        if(count(self::$datebase)==1){
             $db_identifier=array_keys(self::$datebase);//取出数据库标识符
             $this->cur_connect = $this->connectDb($db_identifier[0]);//创建pdo实例
             $this ->pdo[$db_identifier[0]] = $this->cur_connect;
           
        }
      
    }
    /*
     * 选择一个要使用的数据库
     * $db_identifier 代表database里设置的数据库标识
     * 返回一个数据库连接后的实例对象本身
     */
    public function useConfig($db_identifier){
        if(empty($db_identifier)){ //为空,查找默认数据库
              if($db_identifier!=0)
              throw new \Exception(" useConfig() 参数不能为空 !" );
              
          }
        
        //判断是否已经建立过连接
         $connect = isset($this->pdo[$db_identifier])?$this->pdo[$db_identifier]:NULL;
         if($connect){
             $this ->cur_connect = $connect;
             
         }
         else{
             $this ->pdo[$db_identifier] = $this ->connectDb($db_identifier);
             $this ->cur_connect = $this ->pdo[$db_identifier];

         }
         return $this;
    }


    /*
     * 建立数据库连接
     * 返回PDO实例
     */
    protected function connectDb($db_identifier){
            if(!isset(self::$datebase[$db_identifier])){
                throw new \Exception(nbf()->get_module()." 模块下的datebase.php文件mysql配置里,没有找到 ".$db_identifier .' 的配置信息!' );
            }
            $dsn = "mysql:host=".self::$datebase[$db_identifier]['host'].";port=".self::$datebase[$db_identifier]['port'].";dbname=".self::$datebase[$db_identifier]['dbname'];
            $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ".self::$datebase[$db_identifier]['charset'],
            PDO::ATTR_PERSISTENT => FALSE,//TRUE为持久连接,缓存连接,加快速度,但有问题,不建议
                //持久连接需要到mysql里去配置一下,将wait_timeout=7200 或更长的值
                //否则有可能因为超时断开长连接,导致出错
            ); 
            $pdo = new PDO($dsn, self::$datebase[$db_identifier]['username'], self::$datebase[$db_identifier]['password'], $options); 
            $pdo-> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
    }


    /*
     * 执行一条sql语句,一般用作insert update delete,查询请用query
     * 返回: 正确执行返回受影响的记录条数. 错误返回false;
     *
     * $sql SQL语句
     *$ret = $db->exec('INSERT INTO user (name,age,sex) VALUES ("mary",40,"女")');//只配置了一个数据库,就无需useConfig
     *$ret = $db->useDB('db1')->exec('DELETE from user where name ="mary"');//有多个数据库的时候,必须指明使用哪个
     *$ret = $db->useDB('db2')->exec('update user set age = 39 where name ="mary");
     */
    public function exec($sql){
      if(empty($sql))
          return false;
      if($this->cur_connect){
          try{
          return $this->cur_connect->exec($sql);
          }catch (PDOException $e){
            throw new PDOException($e-> getMessage()."<br/>语句:" . $sql);   
          }
      }
      else{
          if(count(self::$datebase)>1){
          throw new \Exception("存在多个数据库!请用 useConfig()方法,选择当前要使用哪个数据库!" );
          }else{
           throw new \Exception(" 数据库未配置或配置错误,请到 ".nbf()->get_module()." 模块目录下的datebase.php里mysql部分进行正确设置 !" );   
          }
      }
    }
    
    //用预处理语句的方式处理查询,防止sql注入
    //使用方法:(sql语句里的参数名要跟bind数组里的键名一一对应,参数名必须用 ':'符号开头的形式)
    // $sql = "delete from tb_name where gender=:sex and score=:grade";
    // $bind = array(":sex"=>"男",":grade"=>80);自增字段值是NULL,比如":id"=>NULL
    // $db->safe_exec($sql,$bind);
    //正确返回影响记录的条数.
    public function safe_exec($sql,$bind=[]){
      if(empty($sql))
          return false;
     
      if($this->cur_connect){
          try{
          $pdostatement = $this->cur_connect-> prepare($sql);
          if($pdostatement===FALSE)
              return FALSE;
          //处理参数绑定
          if(count($bind)>0){
              foreach ($bind as $key=>$value){
                  if(is_int($value) || is_bool($value))
                      $ret_bind =$pdostatement-> bindValue ($key, $value, PDO::PARAM_INT);
                  else
                      $ret_bind =$pdostatement-> bindValue ($key, $value);
                  if(!$ret_bind){
                    throw new \Exception( "绑定参数 ".$key ." 时发生错误!" );  
                  }
              } 
          }
          $ret =$pdostatement-> execute();
          if($ret===FALSE)
              return FALSE;
          else
              return $pdostatement-> rowCount();//返回受到影响的记录条数
          }
          catch (PDOException $e){
             throw new PDOException($e-> getMessage()."<br/>参数:".json_encode($bind));
          }
      }
      else{
          if(count(self::$datebase)>1){
          throw new \Exception("存在多个数据库!请用 useConfig()方法,选择当前要使用哪个数据库!" );
          }else{
           throw new \Exception(" 数据库未配置或配置错误,请到 ".nbf()->get_module()." 模块目录下的datebase.php里mysql部分进行正确设置 !" );   
          }
      }
    }
    
    //用预处理语句的方式处理查询,防止sql注入
   //使用方法:(sql语句里的参数名要跟bind数组里的键名一一对应,参数名必须用 ':'符号开头的形式)
    //$sql = select * from tb_name where gender=:sex and score>:grade
    // $bind = array(":sex"=>"男",":grade"=>80);自增字段值是NULL,比如":id"=>NULL
    // $db->safe_query($sql,$bind);
    //返回二维数组,每个子数组(关联数组,字段名=>值 的形式)都是一行记录
    public function safe_query($sql,$bind=[]){
          if(empty($sql))          
          return false; 
          if($this->cur_connect){
          try{    
          $pdostatement = $this->cur_connect-> prepare($sql);
          if($pdostatement===FALSE)
              return FALSE;
          //处理参数绑定
          if(count($bind)>0){
              foreach ($bind as $key=>$value){
                  if(is_int($value) || is_bool($value))
                      $ret_bind =$pdostatement-> bindValue ($key, $value, PDO::PARAM_INT);
                  else
                      $ret_bind =$pdostatement-> bindValue ($key, $value);
                  if(!$ret_bind){
                    throw new \Exception( "绑定参数 ".$key ." 时发生错误!" );  
                  }
              } 
          }
          $ret =$pdostatement-> execute();
          if($ret===FALSE)
              return FALSE;
          return $pdostatement->fetchAll(PDO::FETCH_ASSOC);
          }
          catch(PDOException $e){
           throw new PDOException($e-> getMessage()."<br/>参数:".json_encode($bind));   
          }
      }else{
         if(count(self::$datebase)>1){
          throw new \Exception("存在多个数据库!请用 useConfig()方法,选择当前要使用哪个数据库!" );
          }else{
           throw new \Exception(" 数据库未配置或配置错误,请到 ".nbf()->get_module()." 模块目录下的datebase.php里的mysql部分进行正确设置 !" );   
          }
      }
    }




    /*
     * 封装过的查询语句
     * 返回键值对关联形式的二维数组
     * 
     * $sql SQL语句
     * $db->query('select name from user');//配置里只有一个数据库
     * $db->useDB('db1')->query('select name from user');//有多个数据库的时候,必须指明使用哪个
     */
    public function query($sql){
      if(empty($sql))          
          return false;  
      if($this->cur_connect){
          try{
          $pdostatement = $this->cur_connect->query($sql,PDO::FETCH_ASSOC);
          }catch(PDOException $e){
            throw new PDOException($e-> getMessage()."<br/>语句:" . $sql);  
          }
      }else{
         if(count(self::$datebase)>1){
          throw new \Exception("存在多个数据库!请用 useConfig()方法,选择当前要使用哪个数据库!" );
          }else{
           throw new \Exception(" 数据库未配置或配置错误,请到 ".nbf()->get_module()." 模块目录下的datebase.php里的mysql部分进行正确设置 !" );   
          }
      }
         $result = array();
         if(false!==$pdostatement){
         foreach ($pdostatement as $value) { //将返回的数据封装到一个二维数组里
           $result[]=$value;  
            }
         }
               
        return $result;
    }
    
    /*
     * 原生query语句
     */
    public function original_query($statement){
             if($this->cur_connect){
                 return $this->cur_connect->query($statement);
         
         }
         
      
    }

    /*
     * 从配置里读取数据库参数,参数是二维数组
     */
    public static function set_datebase($array){
       foreach($array as $key=>$value){
           if(count($value)<6) //mysql数据库的配置参数需要6个,不可以少
               continue;
           else
               self::$datebase[$key]=$value;
       } 
    }
    
    /*
     * 执行一个sql文件里的所有指令.每个命令用分号';'做结束标记,注释每行请用'#'开头
     * $dsn - 连接指定的mysql服务端;格式$dsn = "mysql:host=127.0.0.1;port=3306;dbname=testdb";
     * ps:如果没有指明数据库,那么在文件里要有 USE Db_name 来选中数据库,否则之后的语句都执行失败
     * $username和$password 对应连接数据库的用户名和密码
     * $sql_file 记录sql指令的文件,指令顺序要搞好.比如先有数据库,才能建表,有表,才能操作记录
     * 返回:void; (所以上述指令里不应该有查询指令)
     */
    public static function run_sql_file($dsn,$username,$password,$sql_file){
       $pdo = new PDO($dsn, $username, $password);
       $content='';
       if(false!==$file = fopen($sql_file, 'r')){
           while(!feof($file)){
               $line = trim(fgets($file));
               if(strpos($line,'#')===0 || $line=='')//注释语句或空行
                    continue;
               else
               $content.=$line;
           }
           fclose($file);
       }
       $sql_arr= explode(';', $content);
       foreach ($sql_arr as $sql){
           if(!empty($sql))
           $ret = $pdo->exec($sql);
       }
       
    }
}


