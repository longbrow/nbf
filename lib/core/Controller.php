<?php
namespace core;

class Controller{
    protected $tpl;
    public function __construct() {
        $this->tpl = new \Smarty() ;
        $this->tpl->left_delimiter = '<{'; //定界符
        $this->tpl->right_delimiter = '}>';
        $this->tpl->setTemplateDir(TPL_PATH);//模板存放目录
        $this->tpl->setCompileDir(RUNTIME_PATH.'tpl_c'.DS);//编译后的模板存放目录 
        $this->tpl->setCacheDir(RUNTIME_PATH.'cache'.DS);//模板缓冲目录
        //$this->tpl->setConfigDir(RUNTIME_PATH.'configs'.DS); //模板配置文件存放目录
        $this->tpl->caching = false;//设置Smarty缓存开关功能  
        $this->tpl->cache_lifetime = 60*60; //缓冲保留时间,单位秒,1小时
        //$this->tpl->clearAllCache();//清除所有缓存文件
        //clear_cache('index.tpl');//清除index.tpl的缓存
        //clear_cache('index.tpl',cache_id);//清除指定id的缓存
        
    }
    /*
     * 给模板赋值
     */
    public function assign($tpl_var, $value){
        return $this->tpl->assign($tpl_var, $value);
    }

    /*
     * 输出模板
     */
    public function fetch($tpl_file=''){
       if(empty($tpl_file))
           $tpl_file = TPL_DEFAULT_NAME;
       $cacheID=md5($_SERVER['REQUEST_URI']);//获取当前页URL
       //将内容保存到缓存里,否则display方法直接就输出到页面了
       ob_start();
       ob_implicit_flush(0);
       //给不同参数但同一个方法的页面用不同的cacheID做缓冲
       $this->tpl->display($tpl_file,$cacheID);
       $content = ob_get_clean();//取出缓存
       return $content;
    }
}


