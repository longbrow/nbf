<?php
/*最后修改日期:2016-12-1 15:30
 * 基于mongodb 3.2的php扩展封装的一个基础类,基本保持了mongo指令的原汁原味
 * 只要熟悉了mongodb的指令,这个类用起来就很简单
*/
namespace core;
class Mongo {
    /* mongo_uri 格式:mongodb://[username:password@]host1[:port1][,host2[:port2],...[,hostN[:portN]]][/[database][?options]]
     * 例子: mongodb://myusername:mypassword@example.com/mydatabase
     * 具体参见官方: http://php.net/manual/en/mongodb-driver-manager.construct.php
     */
    public static $datebase = [];//存放mongo的URI连接字符串
    private $dbname = '';//数据库名
    private $collection = '';//集合名
    private $managers = [];//manager管理器组
    private $manager = NULL;//当前连接的数据库管理器
    protected $debug = false;//是否显示数据库错误信息;可在子类里重新赋值
    /*
     * 构造函数,实例化mongodb manager
     */
    public function __construct() {
        //如果配置里只有一个数据库,那么构造的时候就直接创建管理器,无需再使用下面的useConfig方法了
        if(count(self::$datebase)==1){
             $db_identifier=array_keys(self::$datebase);//取出数据库标识符
             $this->manager = new \MongoDB\Driver\Manager(self::$datebase[$db_identifier[0]]);//创建管理器
             $this ->managers[$db_identifier[0]] = $this->manager;
           
        }
        
    }
        /*
     * 选择一个要使用的数据库URI
     * $db_identifier 代表database里设置的数据库标识
     * 返回一个mongo连接后的实例对象本身
     * 如果配置里只有一个数据库,就无需使用该方法
     */
    public function useConfig($db_identifier){
        if(empty($db_identifier)){ //为空,查找默认数据库

              throw new \Exception(" useConfig() 参数不能为空 !" );
              
          }
        
        //判断是否已经建立过连接
         $manager = isset($this->managers[$db_identifier])?$this->managers[$db_identifier]:NULL;
         if($manager){
             $this ->manager = $manager;
             
         }
         else{
            if(!isset(self::$datebase[$db_identifier])){
                throw new \Exception(nbf()->get_module()." 模块下的datebase.php文件的mongo配置里,没有找到 ".$db_identifier .' 的配置信息!' );
            }
             $this ->managers[$db_identifier] = new \MongoDB\Driver\Manager(self::$datebase[$db_identifier]);//创建管理器;
             $this ->manager = $this ->managers[$db_identifier];

         }
         return $this;
    }
    
    /*
     * 设置mongo要操作的命名空间
     * $db_namespace 格式 '数据库名.集合名'
     * 返回当前对象的实例
     */
    public function ns($db_namespace){
        if(empty($db_namespace)){
            throw new \Exception(" useConfig() 参数不能为空 !" );
        }
        if(empty($this ->manager)){ //管理器还没有创建
           throw new \Exception(" 管理器不存在,请先使用 useConfig() 创建管理器!" ); 
        }
        $dbns = trim($db_namespace);
        //将mongo的ns分割,获取数据库名和集合名
        list($this ->dbname, $this ->collection) = explode('.', $dbns);
        return $this;
    }


    /*
     * $filter mongo原生查询语句构成的数组
     * $options mongo原生语句数组,可选
     * return 二维数组
     * 例子:$filter = ['name' => 'tom','sex'=>'man','job'=>'worker','age'=>['$gte'=>18,'$lte'=>22]];//查询基本条件
     *       $options = [ //查询的过滤条件,可选
     *       'projection' => ['_id' => 0],//不用返回的字段赋值0
     *      'limit' => 20,//返回记录条数
     *      'skip' => 10,//跳过多少条记录
     *      'sort' =>['time'=>-1],//按照指定字段排序,可以多个,1是正序,-1倒序
            ];
     * 例子:$db->useConfig('db1')->ns('mydb.test')->find($filter,$options)
     * 更多请参阅官方: http://php.net/manual/en/mongodb-driver-query.construct.php
     */
    public function find($filter=array(),$options=array()){
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
        $namespace = $this->dbname . '.' . $this ->collection;
        if(empty($options))
            $query = new \MongoDB\Driver\Query($filter);
        else
            $query = new \MongoDB\Driver\Query($filter,$options);
        try{
            $cursor = $this->manager->executeQuery($namespace, $query);
            //将返回的object转换为数组形式
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
        } catch (\Exception $e){
           if($this->debug){
               throw new \Exception($e-> getMessage());
            }
            return false;
        }
        
        return $cursor->toArray();
    }
    
    /* 
     * 统计数据记录 
     * 返回 integer 数量
     * 例子: $where=[];//全部   $where=['age'=>['$gte'=>18,'$lte'=>90],'sex'=>'man'];//按条件查
     * count($where)
     */
    
    public function count($filter=array(),$db='',$collection=''){
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
        return $this->cmd('count', $filter, $this->dbname, $this ->collection);


    }
    
    /*
     *  distinct 指令 字段内容去重;返回一维数组
     * 例子: $field='job'; 
     * distinct([],'mydb','userinfo',$field);返回不同的工作种类
     */
    
        public function distinct($filter=array(),$field=''){
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
        return $this->cmd('distinct', $filter, $this ->dbname, $this ->collection,$field);


    }
    /*
     *  group 分组指令,支持多个字段分组,多字段之间用','分隔
     * 返回 二维数组,包含每组的数量count统计
     * 例子: $field='job,sex';
     * group([],'mydb','userinfo',$field);
     */
    
        public function group($filter=array(),$field=''){
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
        return $this->cmd('group', $filter, $this ->dbname, $this ->collection,$field);


    }
    
    /*
     * 执行mongodb原生命令
     */
    protected function cmd($command,$query,$db,$collection,$field=''){
        
        if($command=='count')
        {
            $cmd =[
                'count'=>$collection,
                empty($query)?:'query'=>$query,
            ];
            $num = 1;
            
        }else if($command=='distinct')
        {
            
            $cmd =[
                'distinct'=>$collection,
                empty($query)?:'query'=>$query,
                'key' => $field,
            ];
            
            $num = 2;
            
        }else if($command=='group')
        {   //多个字段
            if(strpos($field, ',')){
              $arr= explode(',', $field);  
              foreach ($arr as $v){
                  $fieldarr[$v]=1;
              }
            }else{
                $fieldarr[$field]=1;
            }
            $cmd = [ 'group'=>
                ['ns'=>$collection,
                'key'=>$fieldarr,
                '$reduce'=>"function (obj, prev) { prev.count++ ;}",
                empty($query)?:'cond'=>$query,    
                'initial'=>['count'=>0],
                
            ]];
            $num = 3;
        }else
            return false;
        try{
        $mongoCmd = new \MongoDB\Driver\Command($cmd);
        
        $cursor = $this->manager->executeCommand($db, $mongoCmd); 
        //将返回的object转换为数组形式
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
         } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return false;
        }
        switch ($num)
        {
            case 1:
                $val = $cursor->toArray();
                $result = $val[0]['n'];
                break;
            case 2:
                $val = $cursor->toArray();
                $result = $val[0]['values'];
                break;
            case 3:
                $val = $cursor->toArray();
                $result = $val[0]['retval'];
                break;
            default :
                $result = false;
        }
        return $result;

       
    }
    /*
     * 本函数会自动创建一个名为'id'的自增字段,从1开始计数,依次累加1
     * 例子: $where=['name'=>'tom','age'=>19,'sex'=>'man','job'='student'];
     * $db->useConfig('db1')->ns('mydb.test')->autoid_insert($where);//虽然没有指定id字段,但会自动创建一个id字段
     */
    public function autoid_insert($bulk=array()){
        if(empty($bulk))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
            $namespace = $this ->dbname . '.' . $this ->collection;
            $id= $this->mk_autoId($this ->dbname, $this ->collection);
            if($id===false) return 0;
            $where = ['id'=> $id];
            $bulk = array_merge($where,$bulk);
        $newbulk = new \MongoDB\Driver\BulkWrite();
        try{        
        $newbulk->insert($bulk);
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        $result = $this->manager->executeBulkWrite($namespace, $newbulk, $writeConcern);
        } catch (\Exception $e){
            if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        }
        
        return $result->getInsertedCount();
        
    }
    /*
     * 批量插入带自增id的记录
     * 例子: $where[0]=['name'=>'tom','age'=>19,'sex'=>'man','job'='student'];
     * $where[1]=['name'=>'mike','age'=>20,'sex'=>'man','job'='student'];
     * $where[2]=['name'=>'ina','age'=>18,'sex'=>'female','job'='officer'];
     * $db->useConfig('db1')->ns('mydb.test')->autoid_insertAll($where);会将上述3条记录一次插入,并且每条记录自动添加一个自增型id
     */
        public function autoid_insertAll($bulk=array()){
        if(empty($bulk))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
            $namespace = $this ->dbname . '.' . $this ->collection;
        $newbulk = new \MongoDB\Driver\BulkWrite();
        //处理多条
        try{        
        foreach ($bulk as $itemBulk){
             $id= $this->mk_autoId($this ->dbname, $this ->collection);
            if($id===false)
                continue;
            $where = ['id'=> $id];
            $itemBulk = array_merge($where,$itemBulk);
        $newbulk->insert($itemBulk);
        }
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        $result = $this->manager->executeBulkWrite($namespace, $newbulk, $writeConcern);
        } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        }
        
        return $result->getInsertedCount();
    }
    
    
    /*
     * insert 插入一条数据 返回插入成功的条数
     * 例子: $where=['name'=>'tom','age'=>19,'sex'=>'man','job'='student'];
     * $db->useConfig('db1')->ns('mydb.test')->insert($where);
     */
    public function insert($bulk=array()){
        if(empty($bulk))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
            $namespace = $this ->dbname . '.' . $this ->collection;
        $newbulk = new \MongoDB\Driver\BulkWrite();
        try{        
        $newbulk->insert($bulk);
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        $result = $this->manager->executeBulkWrite($namespace, $newbulk, $writeConcern);
        } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        }
        
        return $result->getInsertedCount();
        
    }
    
    /*
     * insertAll插入多条记录,参数是二维数组
     * 例子: $where[0]=['name'=>'tom','age'=>19,'sex'=>'man','job'='student'];
     * $where[1]=['name'=>'mike','age'=>20,'sex'=>'man','job'='student'];
     * $where[2]=['name'=>'ina','age'=>18,'sex'=>'female','job'='officer'];
     * $db->useConfig('db1')->ns('mydb.test')->insertAll($where);会将上述3条记录一次插入
     */
    public function insertAll($bulk=array()){
        if(empty($bulk))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
            $namespace = $this ->dbname . '.' . $this ->collection;
        $newbulk = new \MongoDB\Driver\BulkWrite();
        //处理多条
        try{        
        foreach ($bulk as $itemBulk)
        $newbulk->insert($itemBulk);
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        $result = $this->manager->executeBulkWrite($namespace, $newbulk, $writeConcern);
        } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        }
        
        return $result->getInsertedCount();
    }
    
    /*
     * update 更新记录 返回更新的数量,参数bulk是二维数组( array|object $filter , array|object $newObj [, array $updateOptions ] )
     * 例子: $bulk=[['name'=>new \MongoDB\BSON\Regex('tom','gi')],['$set'=>['age'=>99]],['multi' => true, 'upsert' => false]];
     * $db->useConfig('db1')->ns('mydb.test')->update($bulk);
     * multi表示是否将所有匹配条件的记录都更新,true是全部更新,默认是false;
     * upsert表示如果没有找到匹配的记录,是否新增一条,true是新增,默认是false;如果新增,只是$set条件里的东西会被写入,没有其他字段,会造成记录不完整,用的时候注意.
     * 详见http://php.net/manual/en/mongodb-driver-bulkwrite.update.php
     */
    public function update($bulk=array()){
        if(empty($bulk))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
            $namespace = $this ->dbname . '.' . $this ->collection;
        $newbulk = new \MongoDB\Driver\BulkWrite();
        $i=count($bulk);
        try{        
        if($i==2)
        $newbulk->update($bulk[0],$bulk[1]);
        else if($i==3)
        $newbulk->update($bulk[0],$bulk[1],$bulk[2]);
        else
            return 0; //参数错误,参数最多3个,最少2个
            
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        $result = $this->manager->executeBulkWrite($namespace, $newbulk, $writeConcern);
        } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        }
        
        return $result->getModifiedCount();        
    }
    /*
     * updateAll 处理多条更新条件 ,参数是多维数组,返回更新成功的数量
    * 例子: $bulk[0]=[['name'=>new \MongoDB\BSON\Regex('tom','gi')],['$set'=>['age'=>20]],['multi' => true, 'upsert' => false]];
     * $bulk[1]=[['name'=>new \MongoDB\BSON\Regex('ina','gi')],['$set'=>['age'=>18]],['multi' => true, 'upsert' => false]];
     * $bulk[2]=[['sex'=>'man'],['$set'=>['job'=>'student']],['multi' => true, 'upsert' => false]];
     * $db->useConfig('db1')->ns('mydb.test')->updateAll($bulk); 符合上述条件的都会被更新
     * multi表示是否将所有匹配条件的记录都更新,true是全部更新,默认是false;
     * upsert表示如果没有找到匹配的记录,是否新增一条,true是新增,默认是false;如果新增,只是$set条件里的东西会被写入,没有其他字段,会造成记录不完整,用的时候注意.
     */
        public function updateAll($bulk=array()){
        if(empty($bulk))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
            $namespace = $this ->dbname . '.' . $this ->collection;
        $newbulk = new \MongoDB\Driver\BulkWrite();
        //处理多条
        try{        
        foreach ($bulk as $itemBulk)
        {
             $i=count($itemBulk);
             if($i==2)
             $newbulk->update($itemBulk[0],$itemBulk[1]);
             else if($i==3)
             $newbulk->update($itemBulk[0],$itemBulk[1],$itemBulk[2]);
             else
                 continue; //参数错误,参数最多3个,最少2个
        
        }
        if(0==$newbulk->count()) 
            return 0;
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        $result = $this->manager->executeBulkWrite($namespace, $newbulk, $writeConcern);
        } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        }
        
        return $result->getModifiedCount();        
    }
    
    /*
     * remove 删除记录 返回删除的数量,参数bulk是二维数组( array|object $filter [, array $deleteOptions ] )
     * 例子:$bulk =[['name'=>new \MongoDB\BSON\Regex('tom','i')],['limit'=>false]]//名字包含tom的记录都会被删除
     * 详见http://php.net/manual/en/mongodb-driver-bulkwrite.delete.php
     * $db->useConfig('db1')->ns('mydb.test')->remove($bulk);
     */
    public function remove($bulk=array()){
        if(empty($bulk))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
        $namespace = $this ->dbname . '.' . $this ->collection;
        $newbulk = new \MongoDB\Driver\BulkWrite();
        $i=count($bulk);
        try{        
        if($i==1)
        $newbulk->delete($bulk[0]);
        else if($i==2)
        $newbulk->delete($bulk[0],$bulk[1]);
        else
            return 0; //参数错误,参数最多2个,最少1个
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        $result = $this->manager->executeBulkWrite($namespace, $newbulk, $writeConcern);
        } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        }
        
        return $result->getDeletedCount();            
    }
    
    /*
     * removeAll 多语句删除记录 返回删除的数量,是对remove的扩展
     * 例子:$bulk[0] =[['name'=>new \MongoDB\BSON\Regex('tom','i')],['limit'=>false]];//名字包含tom的记录都会被删除
     * $bulk[1] =[['age'=>['$gte'=>50,'$lte'=>70]]];//年龄>=50 and <=70
     * $bulk[2] =[['job'=>'student']];//职业是学生的
     * $db->useConfig('db1')->ns('mydb.test')->removeAll($bulk);//符合条件的都会被一次性删除
     */
    public function removeAll($bulk=array()){
        if(empty($bulk))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
        $namespace = $this ->dbname . '.' . $this ->collection;
        $newbulk = new \MongoDB\Driver\BulkWrite();
        //处理多条
        try{        
        foreach ($bulk as $itemBulk)
        {
             $i=count($itemBulk);
             if($i==1)
             $newbulk->delete($itemBulk[0]);
             else if($i==2)
             $newbulk->delete($itemBulk[0],$itemBulk[1]);
             else
                 continue; //参数错误,参数最多2个,最少1个
        
        }
        if(0==$newbulk->count()) 
            return 0;
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        $result = $this->manager->executeBulkWrite($namespace, $newbulk, $writeConcern);
        } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        }
        
        return $result->getDeletedCount();              
    }    
    
    /*
     * createIndex 创建索引,索引可以是多条,返回0失败,1成功
    例子:$indexs=[
            [ 'key'=>['name'=>1],
               'name'=>'userinfo_name_unique',
                'unique'=>true,  
             ],      
        ];
     * $db->useConfig('db1')->ns('mydb.test')->createIndexs($indexs);//给test集合的name字段创建唯一索引,索引名为userinfo_name_unique
     * 详见官方文档:https://docs.mongodb.com/manual/reference/command/createIndexes/
     */
    public function createIndexs($indexs=array()){
        if(empty($indexs))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
        
        $cmd = [
            'createIndexes'=> $this ->collection,
            'indexes'=>$indexs,
        ];
        try{
       $mongoCmd = new \MongoDB\Driver\Command($cmd);
        
        $cursor = $this->manager->executeCommand($this ->dbname, $mongoCmd); 
        
        //将返回的object转换为数组形式
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
         } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        } 
       
        $val = $cursor->toArray();
        return $val[0]['ok'];
        
    }
    
    /*
     * dropIndex 删除索引,返回0失败,1成功
     * 例子:$db->useConfig('db1')->ns('mydb.test')->dropIndex('userinfo_name_unique');//删除集合test上名为userinfo_name_unique的索引
     * 详见官方:https://docs.mongodb.com/manual/reference/command/dropIndexes/
     */
    public function dropIndex($index_name=''){
         if(empty($index_name))
            return 0;
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }
        
        $cmd = [
            'dropIndexes'=> $this ->collection,
            'index'=>$index_name,
        ];
       try{ 
       $mongoCmd = new \MongoDB\Driver\Command($cmd);
        
        $cursor = $this->manager->executeCommand($this ->dbname, $mongoCmd); 
        
        //将返回的object转换为数组形式
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
         } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        } 
       
        $val = $cursor->toArray();
        return $val[0]['ok'];
    }
    
    /*
     * dropCollection 删除集合 返回0失败,1成功
     * 例子: $db->useConfig('db1')->ns('mydb.test')->dropCollection()
     */
    public function dropCollection(){
        if(empty($this->dbname) || empty($this ->collection)){
            throw new Exception('请使用ns()方法设置mongo的命名空间,参数格式: "数据库名.集合名" ');
        }

        $cmd = [
            'drop'=> $this ->collection,
        ];
       try{ 
       $mongoCmd = new \MongoDB\Driver\Command($cmd);
        
        $cursor = $this->manager->executeCommand($this ->dbname, $mongoCmd); 
        
        //将返回的object转换为数组形式
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
         } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return 0;
        } 
       
        $val = $cursor->toArray();
        return $val[0]['ok'];       
    }
    
    /*
     * 该函数用来做自增id使用的
     * 会自动创建一个以 本集合名_autoId 命名的新集合,专门用来记录最后一条记录的自增id
     * 返回值 int 最新的id计数
     */
    private function mk_autoId($db='',$collection=''){
       if(empty($db))
            $db= $this->dbname;
       if(empty($collection))
           $collection = $this->collection;
           $collection.='_autoId';
        $cmd = [
            'findAndModify'=> $collection,
            'query'=>[],
            'remove'=>false,
            'update'=>['$inc'=>['last_id'=>1]],
            'new'=>true,
            'fields'=>['_id'=>0],
            'upsert'=>true,
        ];
       try{ 
       $mongoCmd = new \MongoDB\Driver\Command($cmd);
        
        $cursor = $this->manager->executeCommand($db, $mongoCmd); 
        
        //将返回的object转换为数组形式
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
         } catch (\Exception $e){
           if($this->debug){
                throw new \Exception($e-> getMessage());
            }
            return false;
        } 
       
        $val = $cursor->toArray();
        return $val[0]['value']['last_id'];
       
    }
    
    /*
     * 从配置里读取数据库参数,参数是一维数组
     */
    public static function set_datebase($array){
       foreach($array as $key=>$value){
           if(stripos($value,'mongodb://')===false) //mongo的URI
               continue;
           else
               self::$datebase[$key]=$value;
       } 
    }    
}



