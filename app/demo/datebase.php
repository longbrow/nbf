<?php
/*
 * 日常项目中可能会有多种数据库同时使用的情况,请按照下面的格式进行参数设置
 */
return [
    //定义mysql数据库的参数
    'mysql'=>[
        //如果有多个数据库,分别用不同的标识符来标识.这个标识符(比如下面的db1,db2)自己可以自定义,不可重复
        'db1'=>['host'=>'127.0.0.1','port'=>5918,"dbname"=>'testdb',"username"=>'root',"password"=>'',"charset"=>'utf8'],
        'db2'=>['host'=>'127.0.0.1','port'=>5918,"dbname"=>'testdb',"username"=>'root',"password"=>'',"charset"=>'utf8']
        
    ],
    'mongo'=>[
        
    ],
    'redis'=>[
        
    ],
    'memcache'=>[
        
    ],
];

