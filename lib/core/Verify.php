<?php
namespace core;
class Verify{
    protected $png = LIB_PATH.'res'.DS.'image'.DS.'v1.png';//背景图片
    protected $ttf = LIB_PATH.'res'.DS.'ttf'.DS.'en.ttf';//ttf字库文件
    
    protected $fontSize = 20;//字体大小
    protected $imgWidth = 150;//图片宽度
    protected $imgHeight = 50;//图片高度
    protected $text_red = 255;//文字颜色RGB
    protected $text_green = 0;//文字颜色RGB
    protected $text_blue = 0;//文字颜色RGB
    protected $back_red = 255;//背景颜色RGB
    protected $back_green = 255;//背景颜色RGB
    protected $back_blue = 255;//背景颜色RGB
    
    protected $en_text =['1','2','3','4','5','6','7','8','9',
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N',
        'P','Q','R','S','T','U','V','W','X','Y','Z'];
    protected $zh_text =['我','是','忠','诚','过','秋','天','人','鬼',
        '数','器','车','别','看','出','做','吧','谁','想','去','到',
        '发','才','来','苦','日','好','胡','大','春','开','要','用'];
    protected $text_length = 4;//字符长度
    protected $use_zh = false;//是否使用中文验证码
    protected $left = 20;
    protected $bottom = 35;

    

    //设置背景图片
    public function set_backPng($filepath){
        $this ->png = $filepath;
    }

    //设置背景图片
    public function set_backColor($red,$green,$blue){
        $this ->back_red = $red;
        $this ->back_blue = $blue;
        $this ->back_green = $green;
    }
    //设置是否用中文做验证码
    public function set_Zh($zh = false){
        $this ->use_zh = $zh;
        if($zh)
            $this ->ttf = LIB_PATH.'res'.DS.'ttf'.DS.'zh.ttf';
        else
           $this ->ttf = LIB_PATH.'res'.DS.'ttf'.DS.'en.ttf'; 
    }
    
    //设置文字起始位置,文字起始左边距和距离底部的位置
    public function set_textPos($left,$bottom){
        $this ->left =$left;
        $this ->bottom = $bottom;
    }


    //设置字体大小
    public function set_fontSize($size){
        $this->fontSize = $size;
    }

    //设置验证码长度
    public function set_textLength($len){
        $this ->text_length = $len;
    }

    //设置图片大小
    public function set_imgSize($width,$height){
        $this ->imgHeight = $height;
        $this ->imgWidth = $width;
    }
    
    //
    
    //设置文字颜色,RGB
    public function set_textColor($red,$green,$blue){
        $this ->text_red = $red;
        $this ->text_blue = $blue;
        $this ->text_green = $green;
    }
    
    //用指定png图片做背景来生成验证码
    public function createFromPng(){
        $srcImg = imagecreatefrompng($this ->png);  
        $srcWidth = imagesx($srcImg);  
        $srcHeight = imagesy($srcImg); 
        //创建新图  
        $newImg = imagecreatetruecolor($this ->imgWidth, $this ->imgHeight);  
        //分配颜色 + alpha，将颜色填充到新图上  
        $alpha = imagecolorallocatealpha($newImg, 0, 0, 0, 127);  
        imagefill($newImg, 0, 0, $alpha);  

        //将源图拷贝到新图上，并设置在保存 PNG 图像时保存完整的 alpha 通道信息  
        imagecopyresampled($newImg, $srcImg, 0, 0, 0, 0, $this ->imgWidth, $this ->imgHeight, $srcWidth, $srcHeight);  
        imagesavealpha($newImg, true); 
        //生成随机验证码
        $verifyCode = '';
        if($this ->use_zh){
        $text_count = count($this ->zh_text)-1;
        $list = $this ->zh_text;
        }
        else {
        $text_count = count($this ->en_text)-1;
        $list = $this ->en_text;
        }   
        for ( $i=0; $i < $this ->text_length; $i++ ){
              $randnum = mt_rand(0, $text_count);
              $verifyCode .= $list[$randnum];           //取出字符，组合成为我们要的验证码字符
        }
        //文字颜色
        $textColor = imagecolorallocate($newImg, $this ->text_red, $this ->text_green, $this ->text_blue);
        //写入文字
        imagettftext($newImg, $this ->fontSize,0, $this ->left, $this ->bottom,$textColor,$this->ttf,$verifyCode);
        //记录验证码和时间到session里
        if(nbf()-> is_session_started()==FALSE)
            nbf()-> my_session_start ();
        nbf()-> set_session('verifyCode', $verifyCode);
        nbf()-> set_session('verifyTime', time());
        //将图片输出到缓存里
        ob_start();
        imagepng($newImg);
        $content = ob_get_clean();
        imagedestroy($newImg);
        header("Content-type: image/png");//设置http响应头
        return $content;
        
        
        
    }
    
       //生成验证码图片并打印到浏览器
    public function createPng($transparent=true){
        //创建新图  
        $newImg = imagecreatetruecolor($this ->imgWidth, $this ->imgHeight);  
        // 分配颜色
        $red = imagecolorallocate($newImg, 255, 120, 0);
        $yellow = imagecolorallocate($newImg, 255, 255, 0);
        $blue = imagecolorallocate($newImg, 0, 128, 255);
        $background = imagecolorallocate($newImg, $this ->back_red, $this ->back_green, $this ->back_blue);
        imagefill($newImg,0,0,$background);
        //生成干扰线条
        for($i=0;$i<10;$i++){
        imagearc($newImg, mt_rand($i, $this ->imgWidth), mt_rand($i, $this ->imgHeight), mt_rand($i, $this ->imgWidth/2), mt_rand($i, $this ->imgHeight/2), mt_rand($i, $this ->imgWidth), mt_rand($i, $this ->imgHeight), $blue);
        imageline($newImg, rand(0,$this ->imgWidth) , rand(0,$this ->imgHeight), rand(0,$this ->imgWidth) , rand(0,$this ->imgHeight), $red);    //加入线条状干扰素
        imageline($newImg, rand(0,$this ->imgWidth) , rand(0,$this ->imgHeight), rand(0,$this ->imgWidth) , rand(0,$this ->imgHeight), $yellow);    //加入线条状干扰素
        }
        //生成干扰像素
        for($i=0;$i<100;$i++)  //加入干扰象素
        {
             imagesetpixel($newImg, rand(0,$this ->imgWidth) , rand(0,$this ->imgHeight) , $red);    //加入点状干扰素
             imagesetpixel($newImg, rand(0,$this ->imgWidth) , rand(0,$this ->imgHeight) , $yellow);
             imagesetpixel($newImg, rand(0,$this ->imgWidth) , rand(0,$this ->imgHeight) , $blue);
        }
        if($transparent)
        ImageColorTransparent($newImg, $background);//底色透明

        //生成随机验证码
        $verifyCode = '';
        if($this ->use_zh){
        $text_count = count($this ->zh_text)-1;
        $list = $this ->zh_text;
        }
        else {
        $text_count = count($this ->en_text)-1;
        $list = $this ->en_text;
        }   
        for ( $i=0; $i < $this ->text_length; $i++ ){
              $randnum = mt_rand(0, $text_count);
              $verifyCode .= $list[$randnum];           //取出字符，组合成为我们要的验证码字符
        }
        //文字颜色
        $textColor = imagecolorallocate($newImg, $this ->text_red, $this ->text_green, $this ->text_blue);
        //写入文字
        imagettftext($newImg, $this ->fontSize,0, $this ->left, $this ->bottom,$textColor,$this->ttf,$verifyCode);
        //记录验证码和时间到session里
        if(nbf()-> is_session_started()==FALSE)
            nbf()-> my_session_start ();
        nbf()-> set_session('verifyCode', $verifyCode);
        nbf()-> set_session('verifyTime', time());
        
        //将图片输出到缓存里
        ob_start();
        imagepng($newImg);
        $content = ob_get_clean();
        imagedestroy($newImg);
        header("Content-type: image/png");//设置http响应头
        return $content;
        
        
        
    } 
    
    //检查验证码是否正确
    //$code 收到用户提交的验证码字符串 string
    //$expire 验证码过期时间,单位秒
    //一旦session里记录的验证码距今超过这个时间间隔,则认为失效.
    public function checkCode($code,$expire=30){
      if(nbf()-> is_session_started()==FALSE)
      nbf()-> my_session_start ();  
      $oldcode = isset($_SESSION['verifyCode'])? $_SESSION['verifyCode']:NULL;
      $oldtime = isset($_SESSION['verifyTime'])? $_SESSION['verifyTime']:NULL;
      if($oldcode && $oldtime){
          if(strcasecmp($oldcode, $code)!==0 || (time()-$oldtime)>$expire)
              return false;
          else
              return true;
      }
      return false;
    }
    
}
