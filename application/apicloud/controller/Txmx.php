<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use app\common\model\BankCard as BankCardModel;
use think\Db;

class Txmx extends Common{
    //提现获取钱包及银行卡信息
    public function index(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $wallets = Db::name('wallet')->where('user_id',$user_id)->field('id,price,agent_profit')->find();
                    if($wallets){
                        $cards = Db::name('bank_card')->where('user_id',$user_id)->field('id,name,telephone,card_number,bank_name,province,city,area,branch_name')->find();
                        // if($cards){
                        // 不验证是否有银行卡
                        if(true){
                            // $paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                            // 取消支付密码
                            $zhifupwd = 1;
                            // if($paypwd){
                            //     $zhifupwd = 1;
                            // }else{
                            //     $zhifupwd = 0;
                            // }
                            
                            $webconfig = $this->webconfig;
                            $tixianjine = $webconfig['tixianjine'];
                            $tixiancishu = $webconfig['tixiancishu'];
                            $value = array('status'=>200,'mess'=>'获取信息成功','data'=>array('cards'=>$cards,'wallets'=>$wallets,'zhifupwd'=>$zhifupwd,'tixianjine'=>$tixianjine,'tixiancishu'=>$tixiancishu));
                        }else{
                            $value = array('status'=>400,'mess'=>'请先绑定银行卡','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'获取信息失败','data'=>array('status'=>400));
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
    
    public function tixian(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                    // 取消密码
                    // if($paypwd){
                    if(true){
                        // if(input('post.pay_password')){
                        if(input('post.')){
                            $param = input('post.');
                            if(empty($param['iphone']))
                            {
                                $value = array('status'=>400,'mess'=>'手机号码不能为空','data'=>array('status'=>400));
                                return json($value);
                            }
//                            $param['telephone'] = trim($param['iphone']);

                            // 添加银行卡
                            $cards = Db::name('bank_card')->where('user_id',$user_id)->find();
                            $param['user_id'] = $user_id;
                            if(!$cards){
                                // halt($param);
                                try {
                                    BankCardModel::add($param);
                                } catch (\Throwable $th) {
                                    $value = array('status'=>400,'mess'=>'银行卡创建失败','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                            }else{
                                if($cards['card_number'] != $param['card_number'] || $param['iphone'] != $cards['telephone']){
                                    BankCardModel::updateData($param);
                                    $cards = Db::name('bank_card')->where('card_number',$param['card_number'])->find();
                                }
                            }

                            if(input('post.price')){
                                // $pay_password = input('post.pay_password');
                                $price = input('post.price');
                                // 取消支付密码
                                // if(preg_match("/^\\d{6}$/", $pay_password)){
                                if($price){
                                    // if($paypwd == md5($pay_password)){
                                    if(true){
                                        if(preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $price)){
                                            $webconfig = $this->webconfig;
                                            if($price >= $webconfig['tixianjine']){
                                                $wallets = Db::name('wallet')->where('user_id',$user_id)->find();

                                                $type = input('post.type');
                                                if($type == 1)
                                                {
                                                    // 账户余额
                                                    if($wallets['price'] >= $price){
                                                        $txmxnum = Db::name('withdraw')->where('user_id',$user_id)->whereTime('time', 'month')->count();
                                                        if($txmxnum < $webconfig['tixiancishu']){
                                                            $cards = Db::name('bank_card')->where('user_id',$user_id)->find();
                                                            if($cards){
                                                                $tx_number = 'TX'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                                $txmxs = Db::name('withdraw')->where('tx_number',$tx_number)->find();
                                                                if(!$txmxs){
                                                                    $shengshiqu = $cards['province'].$cards['city'].$cards['area'];
                                                                    // 启动事务
                                                                    Db::startTrans();
                                                                    try{
                                                                        Db::name('withdraw')->insert(array('type'=>1,'tx_number'=>$tx_number,'price'=>$price,'time'=>time(),'user_id'=>$user_id,'card_number'=>$cards['card_number'],'zs_name'=>$cards['name'],'bank_name'=>$cards['bank_name'],'shengshiqu'=>$shengshiqu,'branch_name'=>$cards['branch_name'],'telephone'=>$cards['telephone']));
                                                                        Db::name('wallet')->where('id',$wallets['id'])->setDec('price', $price);
                                                                        // 提交事务
                                                                        Db::commit();
                                                                        $value = array('status'=>200,'mess'=>'提现申请成功，我们将尽快处理','data'=>array('status'=>200));
                                                                    } catch (\Exception $e) {
                                                                        // 回滚事务
                                                                        Db::rollback();
                                                                        $value = array('status'=>400,'mess'=>'申请提现失败','data'=>array('status'=>400));
                                                                    }
                                                                }else{
                                                                    $value = array('status'=>400,'mess'=>'申请提现失败，请重试','data'=>array('status'=>400));
                                                                }
                                                            }else{
                                                                $value = array('status'=>400,'mess'=>'请先绑定银行卡！','data'=>array('status'=>400));
                                                            }
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'每月最多提现'.$webconfig['tixiancishu'].'次','data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'您的钱包余额不足，提现失败','data'=>array('status'=>400));
                                                    }
                                                }
                                                elseif($type == 2)
                                                {
                                                    // 代理提现规则
                                                    $agent_withdrawal_rule = Db::name('travel_agent_withdrawal')->where('id', 1)->find();
                                                    if($agent_withdrawal_rule && $agent_withdrawal_rule['open'] == 1){
                                                        if($agent_withdrawal_rule['withdrawal_day'] != date('d')){
                                                            $value = array('status'=>400,'mess'=>'代理余额提现只能在每月的'.$agent_withdrawal_rule['withdrawal_day'].'号，申请提现','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }

                                                    // 代理余额
                                                    if($wallets['agent_profit'] >= $price){
                                                        $txmxnum = Db::name('withdraw')->where('user_id',$user_id)->whereTime('time', 'month')->count();
                                                        if($txmxnum < $webconfig['tixiancishu']){
                                                            $cards = Db::name('bank_card')->where('user_id',$user_id)->find();
                                                            if($cards){
                                                                $tx_number = 'TX'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                                $txmxs = Db::name('withdraw')->where('tx_number',$tx_number)->find();
                                                                if(!$txmxs){
                                                                    $shengshiqu = $cards['province'].$cards['city'].$cards['area'];
                                                                    // 启动事务
                                                                    Db::startTrans();
                                                                    try{
                                                                        Db::name('withdraw')->insert(array('type'=>2, 'tx_number'=>$tx_number,'price'=>$price,'time'=>time(),'user_id'=>$user_id,'card_number'=>$cards['card_number'],'zs_name'=>$cards['name'],'bank_name'=>$cards['bank_name'],'shengshiqu'=>$shengshiqu,'branch_name'=>$cards['branch_name'],'telephone'=>$cards['telephone']));
                                                                        Db::name('wallet')->where('id',$wallets['id'])->setDec('agent_profit', $price);
                                                                        // 提交事务
                                                                        Db::commit();
                                                                        $value = array('status'=>200,'mess'=>'提现申请成功，我们将尽快处理','data'=>array('status'=>200));
                                                                    } catch (\Exception $e) {
                                                                        // 回滚事务
                                                                        Db::rollback();
                                                                        $value = array('status'=>400,'mess'=>'申请提现失败','data'=>array('status'=>400));
                                                                    }
                                                                }else{
                                                                    $value = array('status'=>400,'mess'=>'申请提现失败，请重试','data'=>array('status'=>400));
                                                                }
                                                            }else{
                                                                $value = array('status'=>400,'mess'=>'请先绑定银行卡！','data'=>array('status'=>400));
                                                            }
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'每月最多提现'.$webconfig['tixiancishu'].'次','data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'您的代理余额不足，提现失败','data'=>array('status'=>400));
                                                    }
                                                }
                                                else
                                                {
                                                    $value = array('status'=>400,'mess'=>'提现类型错误，请联系客服','data'=>array('status'=>400));
                                                }







                                            }else{
                                                $value = array('status'=>400,'mess'=>'每次最少提现'.$webconfig['tixianjine'].'元','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'提现金额格式错误','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'请填写提现金额','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'请填写支付密码','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'请先设置支付密码','data'=>array('status'=>400));
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
    
    //获取提现列表
    public function getlist(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $user_id = $result['user_id'];
                        $webconfig = $this->webconfig;
                        $perpage = 10;
                        $offset = (input('post.page')-1)*$perpage;

                        $type = input('post.type');
                        $count = Db::name('withdraw')->where('user_id',$user_id)->where('type', $type)->count();
                        $txmxres = Db::name('withdraw')->where('user_id',$user_id)->where('type', $type)->order('time desc')->field('id,price,time,checked,complete')->limit($offset,$perpage)->select();
                        foreach ($txmxres as $k => $v){
                            $txmxres[$k]['time'] = date('Y/m/d H:i:s',$v['time']);
                        }
                        $value = array('status'=>200,'mess'=>'获取提现记录成功','data'=>$txmxres, 'page_num'=>ceil($count/$perpage));
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页数','data'=>array('status'=>400));
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
    
    public function txinfo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.tx_id')){
                        $tx_id = input('post.tx_id');
                        $txs = Db::name('withdraw')->where('id',$tx_id)->where('user_id',$user_id)->field('id,tx_number,price,time,checked,complete,card_number,zs_name,bank_name,branch_name,remarks,wtime')->find();
                        if($txs){
                            $txs['time'] = date('Y/m/d H:i:s',$txs['time']);
                            $value = array('status'=>200,'mess'=>'获取提现详细成功','data'=>$txs);
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关提现记录','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少提现记录参数','data'=>array('status'=>400));
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