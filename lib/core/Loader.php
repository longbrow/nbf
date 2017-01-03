<?php
namespace core;
class Loader{
    public static $map = [];


        public static function my_autoload($classname){
        if(false!==$pos= strpos($classname, '\\')){    
        $top_path= substr($classname, 0, $pos);
            if(array_key_exists($top_path, self::$map)){
                $newclsname = str_replace("\\",DS, $classname);
                $newclsname = str_replace($top_path.DS, self::$map[$top_path], $newclsname);
                if(is_file($newclsname.EXT)){
                include $newclsname.EXT;
                return true;
                }elseif(is_file ($newclsname.'.class'.EXT)){//支持一下controller.class.php这种形式的命名
                 include $newclsname.'.class'.EXT;   
                 return true;
                }
            }else{ //到extend里查找
                $newclsname = str_replace("\\",DS, $classname);
                $newclsname = EXTEND_PATH.$newclsname;
                if(is_file($newclsname.EXT)){
                include $newclsname.EXT;
                return true;
                }elseif(is_file ($newclsname.'.class'.EXT)){//支持一下controller.class.php这种形式的命名
                 include $newclsname.'.class'.EXT;   
                 return true;
                }
            }

        }else{ //尝试加载其他文件
                //smarty.class.php第一次加载  ,之后的smarty类都由samrty的autoload函数接管了  
                if(strcasecmp($classname, 'Smarty')==0){
                 include SMARTY_PATH.'Smarty.class'.EXT;
                  return true;   
                }

            if(is_file(__DIR__.DS.$classname.EXT)){
            include __DIR__.DS.$classname.EXT;
            return true;
            }
        }
        
        return false;
    }
    
    public static function register(){
      //注册用来自动加载class的函数
      //spl_autoload_register会把自动加载函数按照注册的先后顺序放到一个队列里;
        //这就保证了可以存在多个自动加载函数,逐个进行加载尝试,直到返回true.
        spl_autoload_register(array(__CLASS__,"my_autoload"),true,true); 
        //预加载一些内置命名空间和路径映射表
        self::$map= [    'core'  => CORE_PATH,
                        APP_NAME   => APPLICATION_PATH,
                        'traits' => TRAIT_PATH,
                    ];
        
    }
    
}
