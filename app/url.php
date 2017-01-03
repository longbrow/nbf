<?php
/*
 * 框架下所有的项目都共用此路由;
 * 所以路由短句必须是唯一的
 */
return [
    'pattern'=>['name'=>'/\w+/',],//全局的参数正则,如果路由语句里有同名参数,以语句里正则优先
    //admin模块的路由如下:
    //语句规则: 
    //'路由短句'由自定义 URI标识/:参数名1:参数名2:参数名N构成,其中可选参数用'[]'包含;
    //可选参数按照语法规则必然是靠右排列,参数顺序是有序的,必须要跟方法里参数顺序一致,参数名也必须一样
    //规则写法:
    //mca : 代表 模块/控制器/方法,因为路由是以根目录为基准的,所以必须全部填写;必填项
    //pattern: 各参数要应用的正则规则,可选项
    //method: request的请求方法,可选项,默认是GET
    'user/:name:age:[email]' =>['mca'=>'/admin/index/show','pattern'=>['age'=>'/^\d{2}$/'],'method'=>'GET'],
    '12345/'=>['mca'=>'admin/index/test'],
    'xx/:arg1:[arg2]'=>['mca'=>'admin/index/test2'],
    'post/:name:[age]'=>['mca'=>'admin/index/getpost','pattern'=>['age'=>'/^\d{2}$/'],'method'=>'post'],
    //demo模块的路由如下:
    
];

