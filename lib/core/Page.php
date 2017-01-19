<?php
namespace core;

class Page {

    private $Total_records;          //总记录数
    private $Page_limit;           //一页显示的记录数
    private $Page_current;      //当前页码,可以css装饰
    private $Total_pages;     //总分页数
    private $Page_first;              //起头页数
    private $Page_last;             //结尾页数
    //定义样式
    //省略号
    private $points = "style='margin:0;border-style:none;background:none;padding:4px 0px;color:#808080;float:left;'";
    //page div
    private $pagediv ="style='width:800px;margin-left:auto;margin-right:auto;height:40px;padding:20px 0px;'";
    //a style
    private $a_style = "style='display:block;float:left;margin-right:10px;"
            . "padding:2px 12px;height:24px;border:1px #cccccc solid;background:#fff;"
            . "text-decoration:none;color:#808080;font-size:12px;line-height:24px;'";
    
    
    private $p_style = "style='float:left;padding:2px 12px;font-size:12px;height:24px;"
            . "line-height:24px;color:#bbb;border:1px #ccc solid;background:#fcfcfc;margin:0;margin-right:8px;'";
    
    private $a_current = "style='display:block;float:left;margin-right:10px;"
            . "padding:2px 12px;height:24px;#cccccc solid;"
            . "text-decoration:none;font-size:12px;line-height:24px;border:none;background:#077ee3;color:#fff;'";
    
    private $p_remark ="style='border-style:none;background:none;font-size:12px;height:24px;line-height:24px;"
            . "margin:0px;padding:4px 0px;color:#666;display:block;float:left;'";
    private $mouseEvent = "onmouseenter='nbf_mouseenter(this)' onmouseleave='nbf_mouseleave(this)'";
    
    private $url = '';//还原后的mca
    private $suffix='';//伪后缀
    private $paging_tag = 'pgid';//分页形参


    // private $Url;            //获取当前的url,用来替换参数的模板
    /*
     * $Tags
     * 页面显示的格式，显示链接的页数为2*$Tags+1。
     * 如$Tags=2那么页面上显示就是[首页] [上页] 1 2 3 4 5 [下页] [尾页] 
     */
    private $Tags;

    public function __construct($Total_records = 1, $Page_limit = 1, $Page_current = 1,  $Tags = 2) {
        $this ->url = trim(nbf()-> get_real_url());
        $this->suffix = nbf()-> get_suffix();
        $this ->paging_tag = nbf()-> get_paging_tag();
        $this->Total_records = $this->numeric($Total_records);
        $this->Page_limit = $this->numeric($Page_limit);
        $this->Page_current = $this->numeric($Page_current);
        $this->Total_pages = ceil($this->Total_records / $this->Page_limit);
        if ($this->Total_records < 0)
            $this->Total_records = 0;
        if ($this->Page_current < 1)
            $this->Page_current = 1;
        if ($this->Total_pages < 1)
            $this->Total_pages = 1;
        if ($this->Page_current > $this->Total_pages)
            $this->Page_current = $this->Total_pages;
       
        $this->Page_first = $this->Page_current - $Tags;
        $this->Page_last = $this->Page_current + $Tags;
        if ($this->Page_first < 1) {
            $this->Page_last = $this->Page_last + (1 - $this->Page_first);
            $this->Page_first = 1;
        }
        if ($this->Page_last > $this->Total_pages) {
            $this->Page_first = $this->Page_first - ($this->Page_last - $this->Total_pages);
            $this->Page_last = $this->Total_pages;
        }
        if ($this->Page_first < 1)
            $this->Page_first = 1;
    }

    //检测是否为数字
    private function numeric($num) {
        if (strlen($num)) {
            if (!preg_match("/^[0-9]+$/", $num)) {
                $num = 1;
            } else {
                $num = substr($num, 0, 11);
            }
        } else {
            $num = 1;
        }
        return $num;
    }
    
    //替换url里的页码
    private function replace($page){
     if(false===stripos($this ->url, $this ->paging_tag)){
         //没有找到page分页标识,直接附加一个
         $url = $this ->url . '/' . $this ->paging_tag . '/' .$page;
 
     }else{
         $pattern = "/".$this ->paging_tag ."\/\\d{1,}/i";
         $replace = $this ->paging_tag . "/$page";
         $url = preg_replace($pattern, $replace, $this ->url);
     }
        $mca_arr = explode('/', $url); 
         array_splice($mca_arr, 0, 3);//去掉mca,剩下参数
                 //提取参数,并组合成键值对形式的关联数组
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
        $mca = nbf()-> get_module().'/'.nbf()-> get_controller().'/'.nbf()-> get_action();
        return nbf()-> makeurl($mca, $args, $this ->suffix);
    }

        //首页
    private function Home() {
        if ($this->Page_current != 1) {
            return "<a  $this->mouseEvent $this->a_style href='".$this -> replace(1) ."' title='首页'>首页</a>";
        } else {
            return "<p $this->p_style>首页</p>";
        }
    }

    //上一页
    private function Prev() {
        if ($this->Page_current != 1) {
            return "<a $this->mouseEvent $this->a_style href='".$this -> replace($this ->Page_current-1) ."' title='上一页'>上一页</a>";
        } else {
            return "<p $this->p_style>上一页</p>";
        }
    }

    //下一页
    private function Next() {
        if ($this->Page_current != $this->Total_pages) {
            return "<a $this->mouseEvent $this->a_style href='".$this -> replace($this ->Page_current +1) ."' title='下一页'>下一页</a>";
        } else {
            return"<p $this->p_style>下一页</p>";
        }
    }

    //尾页
    private function Last() {
        if ($this->Page_current != $this->Total_pages) {
            return "<a $this->mouseEvent $this->a_style href='".$this -> replace($this ->Total_pages) ."' title='尾页'>尾页</a>";
        } else {
            return "<p $this->p_style>尾页</p>";
        }
    }

    //输出
    public function get_PageCode() {
        //记录数不足一页,则不显示
        if($this->Total_records<=$this->Page_limit) return "";
        $str = "<div id='nbf_page' $this->pagediv>";
        $str.=$this->Home();
        $str.=$this->Prev();
        if ($this->Page_first > 1) {
            $str.="<p class='pageEllipsis' $this->points>...</p>";
        }
        for ($i = $this->Page_first; $i <= $this->Page_last; $i++) {
            if ($i == $this->Page_current) {
                $str.="<a $this->a_current href='".$this -> replace($i) ."' title='第" . $i . "页' >$i</a>";
            } else {
                $str.="<a  $this->mouseEvent $this->a_style href='".$this -> replace($i) ."' title='第" . $i . "页'>$i</a>";
            }
        }
        if ($this->Page_last < $this->Total_pages) {
            $str.="<p class='pageEllipsis'  $this->points>...</p>";
        }
        $str.=$this->Next();
        $str.=$this->Last();
        $str.="<p $this->p_remark >共<b style='color:red;'> " . $this->Total_pages .
                " </b>页<b style='color:red;'> " . $this->Total_records . " </b>条数据</p>";
        $str.="<script type='text/javascript'>"
                . "function nbf_mouseenter(obj){obj.style.color='#077ee3';obj.style.border='1px #077ee3 solid';}"
                . "function nbf_mouseleave(obj){obj.style.color='#808080';obj.style.border='1px #cccccc solid';}</script></div>";
        return $str;
    }


}
