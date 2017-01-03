<?php
namespace core;
class Response{
    protected $msg ='<div style="width: 700px;margin-right: auto;margin-left: auto;margin-top: 30px;">'
        . '<h1>欢迎使用菜鸟PHP框架!</h1><h3>Welcome to NBF PHP!</h3>'
                . '<p>&nbsp;&nbsp;我们崇尚原生,不喜欢被各种规则来禁锢!我们不喜欢重复学习,无论PHP还是数据库,有官方手册即可,为什么还要再学一套无法通用的框架语法?</p>'
                . '<p>&nbsp;&nbsp;所以,来吧!NBF(Newbie Framework)让你用已有的知识快速开发项目!</p></div>';
        public $data;
    public function Complete(){   
        if(empty($this->data)){
            echo $this->msg;
            die;
        }
        if(is_array($this->data)){
            echo print_r($this->data,true);
        }else{
            echo $this->data;
        }
    }
}
