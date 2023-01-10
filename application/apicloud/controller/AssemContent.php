<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class AssemContent extends Common{
    
    //获取服务项信息列表信息接口
    public function info(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $infos = Db::name('assem_content')->where('id',1)->find();
                if($infos){
                    $value = array('status'=>200,'mess'=>'获取拼团规则信息成功','data'=>$infos);
                }else{
                    $value = array('status'=>400,'mess'=>'找不到相关拼团规则信息','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
}