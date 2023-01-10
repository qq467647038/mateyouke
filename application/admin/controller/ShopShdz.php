<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ShopShdz extends Common{
    
    public function info(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $result = $this->validate($data,'ShopShdz');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $dizhis = Db::name('shop_shdz')->where('shop_id',$shop_id)->find();
                if($dizhis){
                    $count = Db::name('shop_shdz')->update(array(
                        'name'=>$data['name'],
                        'telephone'=>$data['telephone'],
                        'province'=>$data['province'],
                        'city'=>$data['city'],
                        'area'=>$data['area'],
                        'address'=>$data['address'],
                        'id'=>$dizhis['id']
                    ));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('shop_shdz')->insert(array(
                        'name'=>$data['name'],
                        'telephone'=>$data['telephone'],
                        'province'=>$data['province'],
                        'city'=>$data['city'],
                        'area'=>$data['area'],
                        'address'=>$data['address'],
                        'shop_id'=>$data['shop_id']
                    ));
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }
            return json($value);
        }else{
            $shop_id = session('shop_id');
            $dizhis = Db::name('shop_shdz')->where('shop_id',$shop_id)->find();
            $this->assign('dizhis',$dizhis);
            return $this->fetch();
        }
    }
}