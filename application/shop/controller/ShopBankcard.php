<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class ShopBankcard extends Common{
    
    public function info(){
        if(request()->isPost()){
            $shop_id = session('shopsh_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $result = $this->validate($data,'ShopBankcard');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $cards = Db::name('shop_bankcard')->where('shop_id',$shop_id)->find();
                if($cards){
                    $count = Db::name('shop_bankcard')->update(array(
                        'name'=>$data['name'],
                        'telephone'=>$data['telephone'],
                        'card_number'=>$data['card_number'],
                        'bank_name'=>$data['bank_name'],
                        'province'=>$data['province'],
                        'city'=>$data['city'],
                        'area'=>$data['area'],
                        'branch_name'=>$data['branch_name'],
                        'id'=>$cards['id']
                    ));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('shop_bankcard')->insert(array(
                        'name'=>$data['name'],
                        'telephone'=>$data['telephone'],
                        'card_number'=>$data['card_number'],
                        'bank_name'=>$data['bank_name'],
                        'province'=>$data['province'],
                        'city'=>$data['city'],
                        'area'=>$data['area'],
                        'branch_name'=>$data['branch_name'],
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
            $shop_id = session('shopsh_id');
            $cards = Db::name('shop_bankcard')->where('shop_id',$shop_id)->find();
            $this->assign('cards',$cards);
            return $this->fetch();
        }
    }
    
}