<?php
/*
 * 控制器和模板使用的一个小例子,可删除
 */
namespace app\demo\controller;
use core\Controller;
class index extends Controller{
    
    public function index(){
//        $this->assign("content", '模板输出测试');
//        $this->assign("title", '模板标题');
//        return $this -> fetch();//如果存在index.index.html模板页
        return;//返回空,则显示内置默认页
    }
}