extend目录里存放各种用户自定义功能类或通用扩展类;
存储目录格式如下: (子文件夹和类文件名都是自定义)
extend
    |----folder1(文件夹)---classname.php(类文件)
    |----folder2(文件夹)---classname.php(类文件)
    |----folder3(文件夹)---classname.php(类文件)
		
例如: 自定义了一个通用的分页类

extend
    |----mypage(目录)----page.php(分页类文件)
		
page.php的实现
<?php
namespace mypage; //命名空间命名一定要跟类文件的存放目录名完全一致!!

class page{ //类名要跟类文件名一致(去掉.php后缀的文件名)

......//具体实现,省略..

}		

上述类的使用,只需要在控制器或模型里;例如:

use mypage\page;  或者 use mypage\page as 别名;
即可自动加载调用了.

$page = new page(); // or new 别名();