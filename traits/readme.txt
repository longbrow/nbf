traits目录里存放各种用户自定义功能类或通用扩展类,主要目的是用作多重继承.
存放格式如下: (子文件夹和类文件名都是自定义)
traits
    |----folder1(文件夹)---traitname.php(类文件)
    |----traitname.php(类文件,这里可以不用创建子目录)  
    |
		
例如: 自定义了一个user类

traits
    |----mytrait(目录)----user.php(类文件)
	|----user.php(类文件,这里没用子目录,在命名空间里写明路径即可)
		
user.php的实现
<?php
namespace traits\mytrait; //命名空间命名一定要跟类文件的存放目录名完全一致!!顶层目录一定是traits,后面有几层子目录就写几层
namespace traits; //没用子目录的命名空间就这么写.

trait user{ //类名要跟类文件名一致(去掉.php后缀的文件名)
public function say(){
 return "Hello";
 }
......//其他实现,省略..

}		

上述类的使用,只需要在控制器或模型里,列如:

namespace app\demo;
use traits\user;  或者 use traits\mytrait\user;  //(这里的区别就是一个带目录,一个不带子目录)

class foo extend Controller{
use user; //继承了 user类
public function foo(){
  $this->say(); //直接使用trait user类里的成员方法
}
}