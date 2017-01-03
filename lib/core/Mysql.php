<?php
namespace core;
use \PDO;
class Mysql{

    protected static $datebase =[];//可能多个配置
    protected $pdo = [];//可能多个连接
    protected $one_db = false;//只有一个数据库
    public function __construct() {
        //如果配置里只有一个数据库,那么构造的时候就直接建立连接
        if(count(self::$datebase)==1){
            $this->one_db = true;
             $db_identifier=array_keys(self::$datebase);//取出数据库标识符
             $this->pdo[0] = $this->connectDb($db_identifier[0]);//创建pdo实例
           
        }
      
    }
    
    /*
     * 建立数据库连接
     * 返回PDO实例
     */
    protected function connectDb($db_identifier){
            $dsn = "mysql:host=".self::$datebase[$db_identifier]['host'].";port=".self::$datebase[$db_identifier]['port'].";dbname=".self::$datebase[$db_identifier]['dbname'];
            $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ".self::$datebase[$db_identifier]['charset'],
            PDO::ATTR_PERSISTENT => true,//持久连接,缓存连接,加快速度
            ); 

            $pdo = new PDO($dsn, self::$datebase[$db_identifier]['username'], self::$datebase[$db_identifier]['password'], $options); 
            return $pdo;
    }


    /*
     * 执行一条sql语句,一般用作insert update delete,查询请用query
     * 返回: 正确执行返回受影响的记录条数. 错误返回false;
     * $db_identifier, 数据库标识符(database.php配置里填写的那个标识符)
     * $sql SQL语句
     *$ret = $this->exec('INSERT INTO user (name,age,sex) VALUES ("mary",40,"女")','db1');
     *$ret = $this->exec('DELETE from user where name ="mary"','db1');
     *$ret = $this->exec('update user set age = 39 where name ="mary"','db1');
     */
    public function exec($sql,$db_identifier=null){
      if(empty($sql))
          return false;
      if($this->one_db){
          $num = $this->pdo[0]->exec($sql);
      }
      else if(!empty($db_identifier) && isset($this->pdo[$db_identifier]) ){ //已经建立过连接
         $num = $this->pdo[$db_identifier]->exec($sql);
      }
      else //尚未建立连接
      {
            if(empty($db_identifier)){
                throw new \Exception('exec 缺少参数: $db_identifier');
                return false; //没有找到该数据库的配置               
            }
            if(array_key_exists($db_identifier, self::$datebase)){
            $this->pdo[$db_identifier] = $this->connectDb($db_identifier);
            $num = $this->pdo[$db_identifier]->exec($sql);
            }
            else{
                throw new \Exception('配置文件里没有找到 '.$db_identifier.' 的配置信息!');
                return false; //没有找到该数据库的配置
            }
      }
      return $num;
    }
    
    
    /*
     * 封装过的查询语句
     * 返回键值对关联形式的二维数组
     * $db_identifier, 数据库标识符(database.php配置里填写的那个标识符)
     * $sql SQL语句
     * $this->query('select name from user','db2');
     */
    public function query($sql,$db_identifier=null){
      if(empty($sql))          
          return false;  
      if($this->one_db){
          $pdostatement = $this->pdo[0]->query($sql,PDO::FETCH_ASSOC);
      }
      else if(!empty($db_identifier) && isset($this->pdo[$db_identifier]) ){ //已经建立过连接
         $pdostatement = $this->pdo[$db_identifier]->query($sql,PDO::FETCH_ASSOC);
      }
      else //尚未建立连接
      {
            if(empty($db_identifier)){
                throw new \Exception('query 缺少参数: $db_identifier');
                return false; //没有找到该数据库的配置               
            }
            if(array_key_exists($db_identifier, self::$datebase)){
            $this->pdo[$db_identifier] = $this->connectDb($db_identifier);
            $pdostatement = $this->pdo[$db_identifier]->query($sql,PDO::FETCH_ASSOC);
            }
            else{
                throw new \Exception('配置文件里没有找到 '.$db_identifier.' 的配置信息!');
                return false; //没有找到该数据库的配置
            }
      }
         if(false!==$pdostatement){
         foreach ($pdostatement as $value) {
           $result[]=$value;  
            }
         }else
             return false;
               
        return $result;
    }
    
    /*
     * 原生query语句
     */
    public function original_query($statement){
             if($this->one_db){
                 return $this->pdo[0]->query($statement);
         
         }
         
      
    }

    /*
     * 从配置里读取数据库参数,参数是二维数组
     */
    public static function set_datebase($array){
       foreach($array as $key=>$value){
           if(count($value)<6)
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


