<?php
namespace core;
/*
Pageslice分页类
有3种模式的表现形式,根据参数自动切换,如:
1) <首页><上一页>和<下一页> 当前第n页
2) <首页><上一页><1><2><3><4><5>...<下一页><尾页>[n] <Go> 共1000页 20000条
3)<首页><上一页><1><2><3><4><5>...<下一页>[n] [<<Go]共1000000页 200000000条

模式1: 由于各种原因无总记录数 $total=-1 的情况;当$total=-1 and $limit=-1 的时候,<下一页>将不能翻页
模式2: 有总记录数且$total >0 and $total<$opt_num的情况
模式3: 有总记录数且$total>=$opt_num 的情况;因为数据库用limit [offset,]num 来截取记录的时候,
offset值越大,性能越差;所以定义了一个$opt_num参数,用来限制用户直接跳转至记录集后面.
 */
class Pageslice{
    private $limit; //每页限制的记录条数
    private $total; //总记录数
    private $opt_num; //模式3的阀值
    private $btns; //页码按钮数量
    private $tag; // 页码的参数名
    private $current; //当前请求的页码数
    private $uri; //当前请求的uri
    private $suffix; //伪后缀;
    private $pages=1; //总分页数
    private $btn_base;//当前按钮前后应该有的按钮数
    private $first; //第一个页码按钮的页码
    private $last; //最后一个页码按钮的页码
    private $div_width = 800;//分页div的宽度
    private $pagediv;//分页div的样式代码
    //定义样式
    //省略号样式
    private $points = "style='margin:0;border-style:none;background:none;padding:5px 0px;color:#808080;float:left;'";
   
    //未选中的页码按钮样式
    private $a_style = "style='box-sizing:border-box;display:block;float:left;margin-right:10px;"
            . "padding:2px 12px;height:30px;line-height:24px;border:1px #cccccc solid;background:#fff;"
            . "text-decoration:none;color:#808080;font-size:12px;'";
    
    // 禁止使用的样式disable
    private $disable_style = "style='box-sizing:border-box;float:left;padding:2px 12px;height:30px;line-height:24px;font-size:12px;"
            . "color:#bbb;border:1px #ccc solid;background:#fcfcfc;margin:0;margin-right:10px;'";
    //选中按钮的样式
    private $select_style = "style='box-sizing:border-box;display:block;float:left;margin-right:10px;"
            . "padding:2px 12px;height:30px;line-height:24px;"
            . "text-decoration:none;font-size:12px;border:1px #cccccc solid;background:#077ee3;color:#fff;'";
    
    private $input_style="style='box-sizing:border-box;display:block;float:left;margin-top:0;margin-left:0;margin-bottom:0;margin-right:10px;text-align:center;"
            . "width:34px;color:blue;height:30px;padding:0px;line-height:24px;font-size:12px;"
            . "border:#cccccc solid 1px;'";

//总分页数和总记录数的样式
    private $total_style ="style='box-sizing:border-box;border-style:none;background:none;font-size:12px;"
            . "margin:0px;height:30px;padding:3px 0px;color:#666;display:block;float:left;line-height:24px;'";
    //按钮鼠标进入和离开时候的事件属性
    private $mouseEvent = "onmouseenter='nbf_mouseenter(this)' onmouseleave='nbf_mouseleave(this)'";
    
    //要执行的鼠标script语句
    private $script ="<script type='text/javascript'>"
                . "function nbf_mouseenter(obj){obj.style.color='#077ee3';obj.style.border='1px #077ee3 solid';}"
                . "function nbf_mouseleave(obj){obj.style.color='#808080';obj.style.border='1px #cccccc solid';}</script>";

    /* @ 构造函数
     * @ $total -- 总记录数
     * @ $limit -- 每页限制显示的记录数
     * @ $opt_num -- 自动切换到(模式3)的总记录数阀值
     * @ $btns -- 页码按钮的数量,不包含[首页/上一页/下一页/尾页/Go]
     * @ $tag -- 附加在URL上的参数:页码的参数名,比如 _pg=10,其中_pg就是tag
     * PS:还有一个隐藏参数,参数名为上述tag+"_total",值是$total的值,比如:
     * _pg_total=100000;这样的好处,除了第一次需要从数据库count(*)统计数据集的记录数total,
     * 之后翻页就不再需要了.
     */
    public function __construct($total=-1,$limit=15,$opt_num=100000,$btns=5,$tag="_pg") {
        $this ->tag = $tag;
        $this ->limit = intval($limit);
        $this ->total = intval(isset($_GET[$this ->tag."_total"])?$_GET[$this ->tag."_total"]:$total);
        $this ->current = intval(isset($_GET[$this ->tag])?$_GET[$this ->tag]:1);
        $this ->opt_num = intval($opt_num);
        $this ->btns = intval($btns);
        $this->suffix = nbf()-> get_suffix();
        $this ->uri = $this -> createUri(0);
        if($this ->total==-1){
            $this ->div_width = 400;
        }
        $this ->pagediv = $this -> get_div($this ->div_width);
        if($this ->limit>0)
        $this ->pages = ceil($this ->total / $this ->limit);
        $this ->btn_base = floor($this->btns/2);
        //第一个按钮页码
        $this ->first = $this ->current - $this ->btn_base;
        //最后一个按钮页码
        $this ->last = $this ->current + $this ->btn_base;
        if($this ->first<1){
          $this ->last = $this ->last + (1-$this ->first);
          $this ->first=1;
        }
        if ($this ->last > $this->pages) {
            $this ->first = $this ->first - ($this ->last - $this->pages);
            $this ->last = $this->pages;
        }
        if ($this ->first < 1)
            $this ->first = 1;
    }
    
    /*
     *  @ 获取当前的URI,并解析
     *  @ 返回值类似 "/shop/index/search?_pg_total=1000&_pg=";只需要附加页码数就构成完整uri
     */
    private function createUri($num){
    $uri = trim(nbf()-> get_real_url());  
    $mca_arr = explode('/', $uri); 
    array_splice($mca_arr, 0, 3);//去掉/模块/控制器/方法,剩下参数
    if (count($mca_arr) == 0) {
        $args = []; //无参数
    } elseif (count($mca_arr) % 2 == 0) {
        //进行键值对的分割
        $keys = [];
        $values = [];
        foreach ($mca_arr as $key => $value) {
            if ($key % 2 == 0) {
                $keys[] = $value;
            } else {
                $values[] = $value;
            }
        }
        $args = array_combine($keys, $values); //组合参数-参数名->值
        } else {
        //参数错误
        throw new \Exception('参数数量不匹配!');
        die;
    }
    unset($args[$this ->tag]); //删除掉以前的页码参数
    unset($args[$this ->tag."_total"]);//将原来的记录总数参数删除掉
    $mca = nbf()-> get_module().'/'.nbf()-> get_controller().'/'.nbf()-> get_action();
    //合并成新的参数数组,并且将_pg放到最后
    $args = array_merge($args,array($this ->tag."_total"=> $this ->total, $this ->tag=>"*nbf"));
    if($num==0){ //保留"*nbf" 字符串供 Go 按钮动态替换
        return nbf()-> makeurl($mca, $args, $this ->suffix);
        }else{
        $args[$this ->tag]=$num;
        return nbf()-> makeurl($mca, $args, $this ->suffix);
      }
    }
    


    /*
     * @ 构建首页按钮的html代码
     */
    private function home(){
        if ($this->current != 1) {
            return "<a class='enable_btn' $this->mouseEvent $this->a_style href='".$this -> createUri(1) ."' title='首页'>首页</a>";
        } else {
            return "<p class='nbf_p_style' $this->disable_style>首页</p>";
        }        
    }
    
    /*
     * @ 构建尾页按钮的html代码
     */
    private function tail(){
        if ($this->current != $this->pages) {
            return "<a class='enable_btn'$this->mouseEvent $this->a_style href='".$this -> createUri($this ->pages) ."' title='尾页'>尾页</a>";
        } else {
            return "<p class='nbf_p_style' $this->disable_style>尾页</p>";
        }        
    }
    
    /*
     * @ 构建上一页按钮的html代码
     */
    private function prev(){
        if ($this->current != 1) {
            return "<a class='enable_btn' $this->mouseEvent $this->a_style href='".$this -> createUri($this ->current-1) ."' title='上一页'>上一页</a>";
        } else {
            return "<p class='nbf_p_style' $this->disable_style>上一页</p>";
        }        
    }    
    
    /*
     * @ 构建下一页按钮的html代码
     */
    private function next(){
        if ($this->current != $this->pages && $this ->limit>0) {
            return "<a class='enable_btn'$this->mouseEvent $this->a_style href='".$this -> createUri($this ->current +1) ."' title='下一页'>下一页</a>";
        } else {
            return"<p class='nbf_p_style' $this->disable_style>下一页</p>";
        }        
    }

    /*
     * @ 构建跳转输入框的html代码
     */
    private function jump_input(){
    return '<input class="page_num" type="text" value="'.$this->current. "\" ".$this ->input_style .'>';        
    }

    /*
     * @ 构建go按钮的html代码
     */
    private function Go(){
    if($this ->total< $this ->opt_num)
    return "<a class= 'go_jump'$this->select_style href='javascript:;' onclick=\"javascript:var page=(this.previousSibling.value>$this->pages)?$this->pages:this.previousSibling.value;var URL='$this->uri';var r = /^\+?[0-9][0-9]*$/;if(r.test(page)){var pg=parseInt(page);if(pg==0){pg=1;}var newurl=URL.replace('*nbf',pg);location=newurl;}else{var newurl=URL.replace('*nbf','1');location=newurl;}\" title='Go'>Go</a>";        
    else

    return "<a class= 'go_jump' $this->select_style href='javascript:;' onclick=\"javascript:var page=(this.previousSibling.value>$this->last)?$this->last:this.previousSibling.value;var URL='$this->uri';var r = /^\+?[0-9][0-9]*$/;if(r.test(page)){var pg=parseInt(page);if(pg==0){pg=1;}var newurl=URL.replace('*nbf',pg);location=newurl;}else{var newurl=URL.replace('*nbf','1');location=newurl;}\" title='往前跳转'>Go</a>";
    }

    /*
     * @ 构建页码按钮的html代码
     */
    private function page_btn(){
        $btn_html="";

        
        if ($this ->first > 1) {
            $btn_html.="<p class='nbf_p_style' $this->points>...</p>";
        }
        for ($i = $this ->first; $i <= $this ->last; $i++) {
            if ($i == $this->current) {
                $btn_html.="<a class='disable_btn' $this->select_style href='javascript:;' title='第" . $i . "页' >$i</a>";
            } else {
                $btn_html.="<a class='enable_btn' $this->mouseEvent $this->a_style href='".$this -> createUri($i) ."' title='第" . $i . "页'>$i</a>";
            }
        }
        if ($this ->last < $this->pages) {
            $btn_html.="<p class='nbf_p_style'  $this->points>...</p>";
        }
        return $btn_html;
    }
    
    /*
     *  显示总页数和总记录数的html代码
     */
    private function total_num(){
        return "<p class='nbf_p_style' $this->total_style >共<span style='color:red;'> " . $this->pages .
                " </span>页<span style='color:red;'> " . $this->total . " </span>条数据</p>";        
    }
    
    
    /*
     *  模式1的情况下显示当前第n页
     */
    private function current_num(){
        return "<p class='nbf_p_style' $this->total_style >当前第<b style='color:red;'> " . $this->current .
                " </b>页</p>";        
    }    
    
    /*
     * 获取div的html代码,其代码缺少尾部</div>
     * 本函数的目的,是为了修改div的宽度
     */
    private function get_div($width){
     //分页 div 样式
    $div ="style='width:".$width."px;margin-left:auto;margin-right:auto;height:40px;padding:20px 0px;'";    
    return $div;
    }


    /*
     * @ 构建整个分页div的html代码
     */
    public function render(){
       if($this->total==0) return ""; 
       $page = "<div class='nbf_pageslice' $this->pagediv>"; 
       $page.= $this -> home();
       $page.= $this -> prev();
       if($this ->total>0){
       $page.= $this -> page_btn();
       }
       
       $page.= $this -> next();
       
       if($this ->total>0 && $this ->total<$this ->opt_num){    
       $page.= $this -> tail();
       }
       
       if($this ->total>0){
       $page.= $this -> jump_input();
       $page.= $this -> Go();
       $page.= $this -> total_num();
       }else{//模式1
       $page.= $this -> current_num();    
       }
       $page.= $this ->script;
       $page.="<div style='clear:both'></div></div>";
       return $page;
    }    
}


