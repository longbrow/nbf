extendĿ¼���Ÿ����û��Զ��幦�����ͨ����չ��;
�洢Ŀ¼��ʽ����: (���ļ��к����ļ��������Զ���)
extend
    |----folder1(�ļ���)---classname.php(���ļ�)
    |----folder2(�ļ���)---classname.php(���ļ�)
    |----folder3(�ļ���)---classname.php(���ļ�)
		
����: �Զ�����һ��ͨ�õķ�ҳ��

extend
    |----mypage(Ŀ¼)----page.php(��ҳ���ļ�)
		
page.php��ʵ��
<?php
namespace mypage; //�����ռ�����һ��Ҫ�����ļ��Ĵ��Ŀ¼����ȫһ��!!

class page{ //����Ҫ�����ļ���һ��(ȥ��.php��׺���ļ���)

......//����ʵ��,ʡ��..

}		

�������ʹ��,ֻ��Ҫ�ڿ�������ģ����;����:

use mypage\page;  ���� use mypage\page as ����;
�����Զ����ص�����.

$page = new page(); // or new ����();