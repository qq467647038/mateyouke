<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Ptdz as PtdzMx;

class Ptdz extends Common{
    
    public function info(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'Ptdz');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $ptdz = new PtdzMx();
                $count = $ptdz->allowField(true)->save($data,array('id'=>1));
                if($count !== false){
                    ys_admin_logs('编辑平台地址','ptdz',$data['id']);
                    $value = array('status'=>1,'mess'=>'保存成功');
                }else{
                    $value = array('status'=>0,'mess'=>'保存失败');
                }
            }
            return json($value);
        }else{
            $dizhis = Db::name('ptdz')->where('id',1)->find();
            $this->assign('dizhis',$dizhis);
            return $this->fetch();
        }
    }
}