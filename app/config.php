<?php
/*
 * 全局配置,在所有module中生效.其中部分配置可以在module的下的config里去覆盖替换.
 * 标注为*全局*的配置项,在模块下配置是无效的.
 */
return [
    //******以下配置是全局生效的,模块下同名配置无效******
    'home'  =>'demo/index/index',//默认首页,*全局*,格式: 模块/控制器/方法.例如 admin/index/home
    'suffix'    =>['htm','Html','json','jsonp','do','shtml','xml'],//支持的伪静态后缀,*全局*
    'url_router'    =>true,//是否启用url路由功能,*全局*
    
    //******以下配置可以覆盖替换全局的同名配置项,可复制到模块下修改配置值******
    'debug' => true, //开启debug模式,可覆盖,如要关闭,建议到指定的模块下去关闭调试模式
    'session_start' => true, //session是否自动开启,可覆盖
    'session_expire' => 1000,//session过期时间,单位秒,可覆盖
    
];

