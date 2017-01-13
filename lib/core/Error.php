<?php

namespace core;
use core\App;
class Error {

    public static function register() {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'error_handler']);
        set_exception_handler([__CLASS__, 'exception_handler']);
        register_shutdown_function([__CLASS__, 'shutdown_handler']);
    }

    public static function exception_handler($e) {
        $code = $e->getCode();
        $msg = $e->getMessage();
        $line = $e->getLine();
        $trace = $e->getTraceAsString();
        if(isset(App::$config['debug'])?App::$config['debug']:false)
            self::show_error($code, $e->getFile(), $line, $msg, $trace);
        else
            self::show_err_jump ($code);
    }

    /**
     * Error Handler
     * integer $errno   错误号
     * integer $errstr  错误信息
     * string  $errfile 文件
     * integer $errline 出错行号
     * array    $errcontext
     *
     */
    public static function error_handler($errno, $errstr, $errfile = '', $errline = 0, $errcontext = []) {   
        $code = $errno;
        $msg = htmlspecialchars($errstr);
        $line = $errline;
        $e = new \Exception();
        $trace = $e->getTraceAsString();
        if(isset(App::$config['debug'])?App::$config['debug']:false)
        self::show_error($code, $errfile, $line, $msg, $trace);
        else
            self::show_err_jump ($code);
    }


    public static function shutdown_handler() {
        //只要脚本停止,就会进入这里,所以要判断一下是否是发生致命错误了.只处理致命错误
        if (!is_null($error = error_get_last()) && in_array($error['type'], [E_COMPILE_WARNING,E_CORE_WARNING,E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            
            $e = new \Exception();
            $trace = $e->getTraceAsString();
            if(isset(App::$config['debug'])?App::$config['debug']:false)
            self::show_error($error['type'], $error['file'], $error['line'], $error['message'], $trace);
            else
                self::show_err_jump ($error['type']);
            
        }

        
    }

    //将出错信息打印到网页
    public static function show_error($code, $filepath, $line, $msg, $trace) {
        $file = basename($filepath);
        //只显示错误前后各10行代码
        if (1 <= $start_line = $line - 10) {
            $end_line = $start_line + 19;
        } else {
            $start_line = 1;
            $end_line = 20;
        }
        $i = 0;
        $handle = fopen($filepath, 'r');
        if(!headers_sent())
        header("Content-type:text/html; charset=utf-8");

        echo "<div style='background: #F5F5F5;border:1px solid #ddd'>"
        . "<div style='border:1px solid #ddd'>"
        . "<h2 style='border:1px solid #ddd;color:#D8BFD8;margin-top:0'>NBF Bug Report</h2>"
        . "<p style='font-size: 18px'><span style='color: #FF4500;font-weight:bold;font-size:22px'>[$code] Exception in</span> $file Line $line</p>"
        . "</div><div style='border:1px solid #ddd'><h2 style='margin-top:15px;margin-bottom:15px'>$msg</h2></div>";
        echo "<div style='background: #F5F5F5;border:1px solid #ddd;'><ol start='$start_line' style='border:1px solid #ddd;overflow :auto;padding-left:60px;'>";
        if ($handle) {
            while (!feof($handle)) {
                $i++;
                $buf = fgets($handle);
                if ($i >= $start_line) {
//                      $newbuf = str_replace('\t', str_repeat('&nbsp;',4), $buf);//替换tab
//                      $newbuf = str_replace(' ', '&nbsp;', $buf);//替换空格
                    $buf = htmlspecialchars($buf);
                    if ($i == $line) {
                        echo "<li style='border-left:1px solid #ddd;white-space:nowrap;background: #FA8072;'><pre style='margin:2px;'><code style='font-size:16px;background: #FA8072;'>$buf</code></pre></li>";
                    } else {
                        echo "<li style='border-left:1px solid #ddd;white-space:nowrap;'><pre style='margin:2px;'><code style='font-size:16px;'>$buf</code></pre></li>";
                    }
                    if ($i == $end_line)
                        break;
                }
            }
            fclose($handle);
        }
        echo "</ol></div><h3 style='font-size: 22px;color: #FF4500;margin:0'>Call stack :</h3>";
        echo "<div style='background: #F5F5F5;border:1px solid #ddd;font-size:18px;color:#6A5ACD;overflow :auto'><pre>$trace</pre></div>";
        echo "</div>";
        die;
    }
    
    //生产环境下跳转到一个不显示错误详情的页面上
    public static function show_err_jump($code){
        if(!headers_sent())
        header("Content-type:text/html; charset=utf-8");
        if($code != ERR_ROUTER)
            $notice ="矮油! 访问出错了?!";
        else
            $notice ="矮油! 路由出错了?!";
        $referer = true;
        $jump = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:NULL;
        if(is_null($jump)){
            $jump = isset(App::$config['home'])?App::$config['home']:NULL;
            $jump = '/'.ltrim($jump,'/');//预防用户首页少写了个/
            $referer = false;
        }
        if(!is_null($jump)){
        if($referer)    
        $msg ="秒后,将跳转至上一页!";
        else
        $msg = "秒后,将跳转至首页!";   
        $str = "<div style='width: 666px;margin:50px auto'>"
                . "<h1>$notice</h1><p></p>"
                . "<p style='font-size:24px'>　　　　<span id='sec' style='color: #FF4500; font-size:28px'>5</span> $msg</p>"
                . "<script type='text/javascript'>var i = 5;var intervalid;intervalid = setInterval('fun()', 1000);function fun() "
                . "{if (i == 0) {window.location.href = '$jump';clearInterval(intervalid);}document.getElementById('sec').innerHTML = i;i--;}</script></div>";
        }else{ //无跳转页面,只显示错误即可.
        $str = "<div style='width: 666px;margin:50px auto'>"
                . "<h1>$notice</h1></div>";    
        }
        echo $str;
        die;
    }

}
