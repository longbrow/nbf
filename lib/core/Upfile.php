<?php
namespace core;
use finfo;
class Upfile {
    protected $size = 2000000; //单个文件大小字节
    protected $mime =[]; //上传文件的mime类型,比如["image/jpeg","application/x-zip-compressed"]
    protected $ext = []; //上传文件后缀,比如 ["gif","png","jpeg","zip"]
    protected $saveDir = ROOT_PATH."public";//上传文件的保存路径
    protected $info =array("fail"=>array(),"success"=>array());//上传完成后的返回值

    /*
     * @设置上传文件的最大字节数,不得超过PHP.ini的设置数
     */
    public function setMaxSize($size=2000000){
        $this ->size = $size;
    }
    
    /*
     * 转换数组值为小写字符串
     */
    private static function case_str(&$value,$key){
        $value = strtolower($value);
    }


    /*
     * @设置允许上传的文件MIME类型
     */
    public function setMimeType($mime=[]){
        if(! empty($mime)){
        array_walk($mime, "self::case_str");
        }
        $this ->mime = $mime;
    }
    
    /*
     * @设置允许上传的文件的后缀名
     */
    public function setExt($ext=[]){
        if(! empty($ext)){
        array_walk($ext, "self::case_str");
        }
        $this ->ext = $ext;
    }
    
    /*
     * 设置上传文件的存储路径
     */
    public function setSaveDir($dir){
        $this ->saveDir = $dir;
    }
    
    /*
     * 检查mime
     */
    protected function checkMime($mime){
     if(empty($this ->mime)){
         return TRUE;
     }else{
         return in_array(strtolower($mime), $this ->mime);
     }   
    }
    
    /*
     * 检查ext
     */
    protected function checkExt($ext){
     if(empty($this ->ext)){
         return TRUE;
     }else{
        return in_array(strtolower($ext), $this ->ext);

     }   
    }
    
    /*
     * @获取指定文件的MIME
     */
    protected function getMime($path){
     $finfo = new finfo(FILEINFO_MIME_TYPE);
     return $finfo->file($path);
    }


    /*
     * 检查文件大小
     */
    protected function checkSize($size){
     if($this ->size >=$size) 
         return TRUE;
     else
         return FALSE;
        
    }

    /*
     * 合成文件名
     * @prefix 前缀,一般是前端提交的input的name值
     * @path 上传文件的临时文件路径
     * @ext 上传文件的后缀
     */
    protected function createFileName($prefix,$path,$ext){
        $tmp = md5_file($path);
        return empty($ext)?$prefix."_".$tmp:$prefix."_".$tmp.".".$ext;
        
    }

    /*
     * 获取上传结果
     */
    public function getinfo(){
        foreach ($_FILES as $k => $v){
            //有一种可能是用name[]数组形式提交的
            if(is_array($v['name'])){
                foreach ($v['name'] as $sub_k => $sub_v){
                if(! isset($v['error'][$sub_k]) || is_array($v['error'][$sub_k])){
                    $err = array($k.'_'.$sub_k =>"Invalid parameters");
                    $this ->info['fail']= array_merge($this ->info['fail'],$err);
                    continue;
                }   
                //检查错误类型
                    switch ($v['error'][$sub_k]) {
                    case UPLOAD_ERR_OK:
                        break;
                    case UPLOAD_ERR_NO_FILE: 
                        $err = array($k.'_'.$sub_k =>"No file sent");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);
                        continue;
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE: 
                        $err = array($k.'_'.$sub_k =>$v['name'][$sub_k].": Exceeded filesize limit");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);
                        continue;           
                        break;
                    default:
                        $err = array($k.'_'.$sub_k =>"Unknown errors");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;
                }
                    //不是通过post上传的文件
                    if(!is_uploaded_file($v['tmp_name'][$sub_k])){ 
                        $err = array($k.'_'.$sub_k =>"file is not posted");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;                        
                    }
                    
                //检查文件尺寸是否超过预设大小
                    if(!$this -> checkSize($v['size'][$sub_k])){
                        $err = array($k.'_'.$sub_k =>$v['name'][$sub_k].": file size too big");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;                    
                }
                    //检查mime
                    $mime = $this -> getMime($v['tmp_name'][$sub_k]);
                    if(!$this -> checkMime($mime)){
                        $err = array($k.'_'.$sub_k =>$v['name'][$sub_k].": file MIME not allowed");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;                         
                    }                
                
                    //获取后缀名
                    $ext_arr = explode('.', $v['name'][$sub_k]);
                    if(count($ext_arr)<2){ //没有后缀
                        $ext="";
                    }else{
                        $ext = $ext_arr[count($ext_arr)-1];
                    }
                    //检查后缀类型是否被允许
                    if(! $this -> checkExt($ext)){ 
                        $err = array($k.'_'.$sub_k =>$v['name'][$sub_k].": file ext not allowed");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;                        
                    }


                    //构建要移动的文件名
                    $newName = $this -> createFileName($k.'_'.$sub_k, $v['tmp_name'][$sub_k], $ext);
                    //判断文件是否已经存在
                    $newPath = rtrim(realpath($this ->saveDir),DS).DS . $newName;
                    if(!is_file($newPath)){
                        //文件已经存在,无需拷贝
                       if (!move_uploaded_file($v['tmp_name'][$sub_k],$newPath)){
                        $err = array($k.'_'.$sub_k =>"file move fail");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err); 
                        continue;
                       }  
                    }
                    //将成功信息记录到info数组里
                        $success = array($k.'_'.$sub_k =>$newName);
                        $this ->info['success']= array_merge($this ->info['success'],$success);                    
                }  
                
                
            }else{ //单独提交的
                
                if(! isset($v['error']) || is_array($v['error'])){
                    $err = array($k =>"Invalid parameters");
                    $this ->info['fail']= array_merge($this ->info['fail'],$err);
                    continue;
                }   
                //检查错误类型
                    switch ($v['error']) {
                    case UPLOAD_ERR_OK:
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $err = array($k =>"No file sent");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);
                        continue;
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $err = array($k =>"Exceeded filesize limit");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);
                        continue;           
                        break;
                    default:
                        $err = array($k =>"Unknown errors");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;
                }
                    //不是通过post上传的文件
                    if(!is_uploaded_file($v['tmp_name'])){
                        $err = array($k =>"file is not posted");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;                        
                    }
                    
                //检查文件尺寸是否超过预设大小
                    if(!$this -> checkSize($v['size'])){ 
                        $err = array($k =>$v['name'].": file size too big");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;                    
                }
                    //检查mime
                    $mime = $this -> getMime($v['tmp_name']);
                    if(!$this -> checkMime($mime)){   
                        $err = array($k =>$v['name'].": file MIME not allowed");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;                         
                    }                
                
                    //获取后缀名
                    $ext_arr = explode('.', $v['name']);
                    if(count($ext_arr)<2){ //没有后缀
                        $ext="";
                    }else{
                        $ext = $ext_arr[count($ext_arr)-1];
                    }
                    //检查后缀类型是否被允许
                    if(! $this -> checkExt($ext)){   
                        $err = array($k =>$v['name'].": file ext not allowed");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err);                        
                        continue;                        
                    }


                    //构建要移动的文件名
                    $newName = $this -> createFileName($k, $v['tmp_name'], $ext);
                    //判断文件是否已经存在
                    $newPath = rtrim(realpath($this ->saveDir),DS).DS . $newName;
                    if(!is_file($newPath)){
                        //文件已经存在,无需拷贝
                       if (!move_uploaded_file($v['tmp_name'],$newPath)){
                        $err = array($k =>"file move fail");
                        $this ->info['fail']= array_merge($this ->info['fail'],$err); 
                        continue;
                       }  
                    }
                    //将成功信息记录到info数组里
                        $success = array($k =>$newName);
                        $this ->info['success']= array_merge($this ->info['success'],$success);
            }
            
        } 
        
        return $this ->info;
        
    }
}


