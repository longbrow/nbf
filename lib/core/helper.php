<?php
//调用nbf内置的常用函数类common
function nbf(){
    //先判断common实例是否已经存在
    $nbf = isset(core\App::$nbf)?core\App::$nbf:NULL;
    if($nbf){
        return $nbf;
    }else{
    $nbf = new core\Common();
    core\App::$nbf = $nbf;
    return $nbf;
    }
}

function validate(){
     //先判断validate实例是否已经存在
    $validate = isset(core\App::$validate)?core\App::$validate:NULL;
    if($validate){
        return $validate;
    }else{
    $validate = new core\Validate();
    core\App::$validate = $validate;
    return $validate;
    }   
}