<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class BankCard extends Common{
    public function index(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $cards = Db::name('bank_card')->where('user_id',$user_id)->field('id,name,telephone,card_number,bank_name,province,city,area,branch_name')->find();
                    if($cards){
                        // $cards['card_number'] = formatBankCardNo($cards['card_number']);
                        $card_number = '';
                        // echo substr($cards['card_number'], $i*4, 4);exit;
                        for($i=0; $i<strlen($cards['card_number'])/4; $i++){
                            $card_number .= substr($cards['card_number'], $i*4, 4) . ' ';
                        }
                        $cards['card_number'] = $card_number;
                    }
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$cards);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //添加银行卡
    public function add(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $cards = Db::name('bank_card')->where('user_id',$user_id)->find();
                    if(!$cards){
                        $data = input('post.');
                        $member = Db::name('member')->where('id', $user_id)->find();
                        // if(md5($data['paypwd']) != $member['paypwd']){
                        //     $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                        //     return json($value);
                        // }
                        $reg = Db::name('sms')->order('id desc')->where('phone',$data['telephone'])->find();
                        if(($reg && $reg['code'] == $data['code'])){
                            $yzresult = $this->validate($data,'BankCard');
                            if(true !== $yzresult){
                                $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                            }else{
                                Db::startTrans();
                                try{
                                    Db::name('sms')->order('id desc')->where('phone',$data['telephone'])->update([
                                        'use'=>1
                                    ]);
                                    
                                    $lastId = Db::name('bank_card')->insert(array('name'=>$data['name'],'telephone'=>$data['telephone'],'card_number'=>$data['card_number'],'bank_name'=>$data['bank_name'],'province'=>$data['province'],'city'=>$data['city'],'area'=>$data['area'],'branch_name'=>$data['branch_name'],'user_id'=>$user_id));
                                    if($lastId){
                                        $res = Db::name('member')->where('id', $user_id)->where('true_name', '<>', '')->where('reg_enable_deposit_count', '>', 0)->update([
    									    'reg_enable'=>1,
    									    'reg_enable_deposit_count'=>0
    									]);
									
    									if($res){
    									    // 升级
    								        $this->wineGoodsUpgrade($orders['user_id']);
    									}
                                        
                                        $value = array('status'=>200,'mess'=>'添加银行卡成功','data'=>array('status'=>200));
                                    }else{
                                        $value = array('status'=>400,'mess'=>'添加银行卡失败','data'=>array('status'=>400));
                                    }
                                    
                                    Db::commit();
                                }
                                catch(Exception $e){
                                    
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'添加银行卡失败','data'=>array('status'=>400));
                                }
    
                            }
                        }
                        else{
                            $value = array('status'=>400,'mess'=>'验证码错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'您已绑定银行卡，暂支持绑定一张银行卡','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //删除银行卡
    public function deletecard(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    if(input('post.card_id')){
                        $user_id = $result['user_id'];
                        $card_id = input('post.card_id');
                        $cards = Db::name('bank_card')->where('user_id',$user_id)->where('id',$card_id)->find();
                        if($cards){
                            $count = Db::name('bank_card')->where('id',$card_id)->where('user_id',$user_id)->delete();
                            if($count > 0){
                                $value = array('status'=>200,'mess'=>'解绑成功','data'=>array('status'=>200));
                            }else{
                                $value = array('status'=>400,'mess'=>'解绑失败','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到您的相关银行卡，解绑失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少银行卡信息，解绑失败','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
}