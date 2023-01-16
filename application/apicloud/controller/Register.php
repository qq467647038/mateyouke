<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Register extends Common{
    //用户注册
    public function zhuce(){
        if(request()->isPost()){
            if(!input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate(0);
                if($result['status'] == 200){
                    $data = input('post.');
                    $data['phonecode'] = trim($data['phonecode']);
                    
                    $yzresult = $this->validate($data,'Member.register');
                    if(true !== $yzresult){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
                        if(!$data['member_recode']){
                            $value = array('status'=>400,'mess'=>'推荐码不能为空','data'=>array('status'=>400));
                            return json($value);
                        }
                        else{
                            $member = Db::name('member')->where('member_recode', $data['member_recode'])->find();
                            if(is_null($member)){
                                $value = array('status'=>400,'mess'=>'推荐码错误','data'=>array('status'=>400));
                                return json($value);
                            } 
                        }
                        
                        if ($data['member_recode']) {
                            
                            $member = Db::name('member')->where('member_recode', $data['member_recode'])->find();
                            if(is_null($member)){
                                $value = array('status'=>400,'mess'=>'推荐码错误','data'=>array('status'=>400));
                                return json($value);
                            }
                        }
                        
                        if($data['xieyi'] == 1){
                            $reg = Db::name('sms')->order('id desc')->where('phone',$data['phone'])->where('use', 0)->find();
                            if(($reg && $reg['code'] == $data['phonecode']) || $data['phonecode']==123456){
                            // if(true){
//                                $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
//                                $time = time();
//                                if(floor(($time-$reg['qtime']) <= $vali_time['value']*60)  || $data['phonecode'] == 123456){
                                if (true){
                                    $data['password'] = md5($data['password']);
                                    $data['paypwd'] = md5($data['pay_pwd']);
//                                    $data['user_name'] = getRandomString(10);获取随机串换成手机号
                                    $data['user_name'] = $data['nick_name'];
                                    $data['one_level'] = 0;
                                    $data['two_level'] = 0;
                                    
                                    if(!preg_match("/[a-zA-z0-9]+/", $data['nick_name'])){
                                        $value = array('status'=>400,'mess'=>'账号只能由数字和字母组成','data'=>array('status'=>400));
                                        return json($value);
                                    }

                                    if(input('post.member_recode')){
                                        $member_recode = input('post.member_recode');
                                        $memberguanxi = Db::name('member')->where('member_recode',$member_recode)->field('id,one_level,two_level,team_id')->find();
                                        if($memberguanxi){
                                            $data['one_level'] = $memberguanxi['id'];
                                            if($memberguanxi['one_level']){
                                                $data['two_level'] = $memberguanxi['one_level'];
                                            }

                                            $data['team_id'] = $memberguanxi['team_id'].','.$memberguanxi['id'];
                                        }
                                    }

                                    $token = settoken();
                                    $rxs = Db::name('rxin')->where('token',$token)->find();

                                    $recode = settoken();
                                    $recodeinfos = Db::name('member')->where('recode',$recode)->field('id')->find();

                                    //$appinfo_code = settoken();
                                    $appinfo_code = isset($data['devicetoken'])?$data['devicetoken']:"";
                                    //$members = Db::name('member')->where('appinfo_code',$appinfo_code)->field('id')->find();
                                    //if(!$rxs && !$recodeinfos && !$members){
                                    // if($data['jiedianphone']){
                                    //     $in = Db::name('member')->where('phone', $data['jiedianphone'])->find();
                                    //     if(is_null($in)){
                                    //         $value = array('status'=>400,'mess'=>'接点人手机号码不存在','data'=>array('status'=>400));
                                    //         return json($value);
                                    //     }
                                    //     $data['jiedianid'] = $in['id'];
                                    //     $data['jiedian_team_id'] = $in['team_id'].','.$in['id'];
                                    // }
                                    
                                    if(!$rxs && !$recodeinfos){
                                        $cout = Db::name('member')->where('phone', $data['phone'])->count();
                                        if($cout >=5){
                                            $value = array('status'=>400,'mess'=>'一个手机号最多只能注册五个号码','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            $user_id = Db::name('member')->insertGetId(array(
                                                'phone'=>$data['phone'],
                                                // 'user_name'=>$data['user_name'],
                                                'user_name'=>$data['phone'],
                                                'nick_name'=>$data['nick_name'],
                                                'recode'=>$recode,
                                                'password'=>$data['password'],
                                                // 'appinfo_code'=>$appinfo_code,
                                                'xieyi'=>$data['xieyi'],
                                                'paypwd'=>md5($data['pay_pwd']),
                                                // 'qrcodeurl'=>'',
                                                'one_level'=>$data['one_level'],
                                                'two_level'=>$data['two_level'],
                                                'team_id'=>$data['team_id'],
                                                'jiedianid'=>$data['jiedianid'],
                                                'jiedianphone'=>$data['jiedianphone'],
                                                'jiedian_team_id'=>$data['jiedian_team_id'],
                                                // 'emergency_name'=>$data['emergency_name'],
                                                // 'emergency_phone'=>$data['emergency_phone'],
                                                'regtime'=>time(),
                                                'member_recode'=>strtoupper(substr(uniqid(), -6)),
                                                'login_code' => uniqid()
                                            ));

                                            if($user_id){
                                                $brand = 0;
                                                Db::name('rxin')->insert(array('token'=>$token,'user_id'=>$user_id));
                                                Db::name('contract_record_wallet')->insert(array('total_assets'=>0,'user_id'=>$user_id, 'cumulative_earnings'=>0, 'addtime'=>time()));
                                                Db::name('wallet')->insert(array('price'=>0,'user_id'=>$user_id, 'brand'=>$brand));
                                                Db::name('profit')->insert(array('price'=>0,'user_id'=>$user_id));
                                                $ress = Db::name('wine_usdt_account_generated')->where('status', 0)->order('id asc')->find();
                                                if($ress){
                                                    Db::name('wine_usdt_account_generated')->where('status', 0)->where('id', $ress['id'])->update([
                                                        'user_id'=>$user_id,
                                                        'updatetime'=>time(),
                                                        'status'=>1
                                                    ]);
                                                }
                                                Db::name('sms')->order('id desc')->where('phone',$data['phone'])->update([
                                                    'use'=>1
                                                ]);

                                                if(!empty($memberguanxi)){
                                                    if($data['one_level']){
                                                        $friends = Db::name('member_friend')->where('uid',$data['one_level'])->where('fid',$user_id)->where('level',1)->find();
                                                        if(!$friends){
                                                            Db::name('member_friend')->insert(array('uid'=>$data['one_level'],'fid'=>$user_id,'level'=>1,'addtime'=>time()));
                                                        }
                                                    }

                                                    if($data['two_level']){
                                                        $friends = Db::name('member_friend')->where('uid',$data['two_level'])->where('fid',$user_id)->where('level',2)->find();
                                                        if(!$friends){
                                                            Db::name('member_friend')->insert(array('uid'=>$data['two_level'],'fid'=>$user_id,'level'=>2,'addtime'=>time()));
                                                        }
                                                    }
                                                }

                                                Vendor('phpqrcode.phpqrcode');
                                                //生成二维码图片
                                                $object = new \QRcode();
                                                $imgrq = date('Ymd',time());
                                                if(!is_dir("./uploads/memberqrcode/".$imgrq)){
                                                    mkdir("./uploads/memberqrcode/".$imgrq);
                                                }
                                                $weburl = Db::name('config')->where('ca_id',5)->where('ename','weburl')->field('value')->find();
                                                $url = $weburl['value']."/index/mobile/index.html?member_recode=".$recode;
                                                $imgfilepath = "./uploads/memberqrcode/".$imgrq."/qrcode_".$user_id.".jpg";
                                                $object->png($url, $imgfilepath, 'L', 10, 2);
                                                $imgurlfile = "uploads/memberqrcode/".$imgrq."/qrcode_".$user_id.".jpg";
                                                Db::name('member')->update(array('qrcodeurl'=>$imgurlfile,'id'=>$user_id));

                                                //3完善信息（绑定手机）送积分
//                                                $num = $this->getIntegralRules(3);//获取积分
//                                                $this->addIntegral($user_id,$num,3);
                                            }
//                                            if($data['phonecode'] != 123456){
//                                                Db::name('reg')->delete($reg['id']);
//                                            }

                                            // 提交事务
                                            Db::commit();
                                            $value = array('status'=>200,'mess'=>'注册成功','data'=>array('status'=>200));
                                        } catch (\Exception $e) {
                                            // 回滚事务
                                            Db::rollback();
                                            $value = array('status'=>400,'mess'=>'注册失败'.$e->getMessage(),'data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'注册失败，请重试','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'验证码超时！请重新发送验证码！','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'验证码不正确，请重新输入！','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'请同意注册协议，注册失败','data'=>array('status'=>400));
                        }
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400, 'mess'=>'已登录，登录失败', 'data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function sendcode(){
        if(request()->isPost()){
            if(input('post.bindkf') && input('post.bindkf') == 1){
                $phone = input('post.phone');
                $smscode = createSMSCode();
                $data['phone'] = $phone;
                $data['qtime'] = time();
                $data['smscode'] = $smscode;
    
                $outputArr = sendSms($phone,$smscode);
                $outputArr = object_to_array($outputArr);
                
                if($outputArr['msg'] == 'OK'){
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('reg')->insert($data);
                        $codenum = Db::name('code_num')->where('phone',$phone)->find();
                        $jtime = time();
                        if($codenum){
                            Db::name('code_num')->update(array('num'=>1,'phone'=>$phone,'id'=>$codenum['id']));
                        }else{
                            $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                            Db::name('code_num')->insert(array('time_out'=>$time_out,'num'=>1,'phone'=>$phone));
                        }
                        // 提交事务
                        Db::commit();
                        $value = array('status'=>200,'mess'=>'发送验证码成功！','data'=>array('status'=>200));
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status'=>400,'mess'=>'系统错误，发送验证码失败！','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400, 'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                }
                return json($value);
            }
            if(!input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate(0);
                if($result['status'] == 200){
                    if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                        $phone = input('post.phone');
                        $members = Db::name('member')->where('phone',$phone)->field('id')->find();
                        if(!$members){
                            $dxpz = Db::name('config')->where('ca_id',2)->field('ename,value')->select();
                            $dxpzres = array();
                            foreach ($dxpz as $v){
                                $dxpzres[$v['ename']] = $v['value'];
                            }
                            
                            $codenum = Db::name('code_num')->where('phone',$phone)->find();
                            if(!empty($codenum)){
                                $jtime = time();
                                if($jtime < $codenum['time_out']){
                                    if($codenum['num'] >= $dxpzres['maxcodenum']){
                                        $value = array('status'=>400,'mess'=>'今天已超出最大短信请求次数','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            }
                    
                            $messtime = $dxpzres['messtime'];
                            $reg = Db::name('reg')->where('phone',$phone)->find();
                             
                            if($reg){
                                $time = time();
                                if(floor($time-$reg['qtime']) < $messtime){
                                    $value = array('status'=>0, 'mess'=>$messtime.'s内不能重复发送');
                                }else{
                                    $smscode = createSMSCode();
                                    $data['phone'] = $phone;
                                    $data['qtime'] = time();
                                    $data['smscode'] = $smscode;
                                    $data['id'] = $reg['id'];
                    
                                    $outputArr = sendSms($phone,$smscode);
                                    $outputArr = object_to_array($outputArr);
                                    
                                    if($outputArr['msg'] == 'OK'){
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            Db::name('reg')->update($data);
                                            if($codenum){
                                                if($jtime < $codenum['time_out']){
                                                    $cishu = $codenum['num']+1;
                                                    Db::name('code_num')->update(array('num'=>$cishu,'phone'=>$phone,'id'=>$codenum['id']));
                                                }else{
                                                    $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                                                    $cishu = 1;
                                                    Db::name('code_num')->update(array('time_out'=>$time_out,'num'=>$cishu,'phone'=>$phone,'id'=>$codenum['id']));
                                                }
                                            }else{
                                                $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                                                Db::name('code_num')->insert(array('time_out'=>$time_out,'num'=>1,'phone'=>$phone));
                                            }
                                            // 提交事务
                                            Db::commit();
                                            $value = array('status'=>200,'mess'=>'发送验证码成功！','data'=>array('status'=>200));
                                        } catch (\Exception $e) {
                                            // 回滚事务
                                            Db::rollback();
                                            $value = array('status'=>400,'mess'=>'系统错误，发送验证码失败！','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400, 'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                                    }
                                }
                            }else{
                                $smscode = createSMSCode();
                                $data['phone'] = $phone;
                                $data['qtime'] = time();
                                $data['smscode'] = $smscode;
                    
                                $outputArr = sendSms($phone,$smscode);
                                $outputArr = object_to_array($outputArr);
                                
                                if($outputArr['msg'] == 'OK'){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('reg')->insert($data);
                                        if($codenum){
                                            if($jtime < $codenum['time_out']){
                                                $cishu = $codenum['num']+1;
                                                Db::name('code_num')->update(array('num'=>$cishu,'phone'=>$phone,'id'=>$codenum['id']));
                                            }else{
                                                $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                                                $cishu = 1;
                                                Db::name('code_num')->update(array('time_out'=>$time_out,'num'=>$cishu,'phone'=>$phone,'id'=>$codenum['id']));
                                            }
                                        }else{
                                            $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                                            Db::name('code_num')->insert(array('time_out'=>$time_out,'num'=>1,'phone'=>$phone));
                                        }
                                        // 提交事务
                                        Db::commit();
                                        $value = array('status'=>200,'mess'=>'发送验证码成功！','data'=>array('status'=>200));
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>400,'mess'=>'系统错误，发送验证码失败！','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400, 'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                                }
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'手机号已存在','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'请填写正确的手机号码','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400, 'mess'=>'已登录，登录失败', 'data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
}