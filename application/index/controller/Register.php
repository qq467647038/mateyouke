<?php
namespace app\index\controller;
use think\Controller;
use think\Db;

class Register extends Common{
    //用户注册
    public function index(){
        if(request()->isPost()){
            if(!session('user_id')){
                $data = input('post.');
                $result = $this->validate($data,'Member');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    if($data['xieyi'] == 1){
                        $reg = Db::name('reg')->where('phone',$data['phone'])->find();
                        if($reg && $reg['smscode'] == $data['phonecode']){
                            $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                            $time = time();
                            if(floor($time-$reg['qtime']) <= $vali_time['value']*60){
                                $data['password'] = md5($data['password']);
                                
                                $token = settoken();
                                $rxs = Db::name('rxin')->where('token',$token)->find();
                                
                                $recode = settoken();
                                $recodeinfos = Db::name('member')->where('recode',$recode)->field('id')->find();
                                
                                $appinfo_code = settoken();
                                $members = Db::name('member')->where('appinfo_code',$appinfo_code)->field('id')->find();
                                
                                if(!$rxs && !$recodeinfos && !$members){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        $user_id = Db::name('member')->insertGetId(array(
                                            'phone'=>$data['phone'],
                                            'recode'=>$recode,
                                            'password'=>$data['password'],
                                            'appinfo_code'=>$appinfo_code,
                                            'xieyi'=>$data['xieyi'],
                                            'regtime'=>time()
                                        ));
                                
                                        if($user_id){
                                            Db::name('rxin')->insert(array('token'=>$token,'user_id'=>$user_id));
                                            Db::name('wallet')->insert(array('price'=>0,'user_id'=>$user_id));
                                            Db::name('profit')->insert(array('price'=>0,'user_id'=>$user_id));
                                        }
                                
                                        Db::name('reg')->delete($reg['id']);
                                        // 提交事务
                                        Db::commit();
                                        $value = array('status'=>1,'mess'=>'注册成功');
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>0,'mess'=>'注册失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'注册失败，请重试');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'验证码超时！请重新发送验证码！');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'验证码不正确，请重新输入！');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请同意注册协议，注册失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'您已登录，请退出登录后重试');
            }
            return json($value);
        }else{
            if(!session('user_id')){
                $peizhi = Db::name('config')->where('ename','messtime')->field('value')->find();
                $this->assign('messtime',$peizhi['value']);
                return $this->fetch('register');
            }else{
                $this->redirect('index/index');
            }
        }
    }
    
    //验证用户手机号唯一性
    public function checkPhone(){
        if(request()->isAjax()){
            if(input('post.phone')){
                $username = Db::name('member')->where(array('phone' => input('post.phone')))->find();
                if($username){
                    echo 'false';
                }else{
                    echo 'true';
                }
            }else{
                echo 'false';
            }
        }
    }
    
    
    public function sendcode(){
        if(request()->isPost()){
            if(!session('user_id')){
                if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                    $phone = input('post.phone');
                    $members = Db::name('member')->where('phone',$phone)->field('id')->find();
                    if(!$members){
                        $codenum = Db::name('code_num')->where('phone',$phone)->find();
                        if(!empty($codenum)){
                            $jtime = time();
                            if($jtime < $codenum['time_out']){
                                if($codenum['num'] >= 10){
                                    $value = array('status'=>0,'mess'=>'今天已超出最大请求次数');
                                    return json($value);
                                }
                            }
                        }
                        
                        $reg = Db::name('reg')->where('phone',$phone)->find();
                         
                        if($reg){
                            $time = time();
                            if(floor($time-$reg['qtime']) < 60){
                                $value = array('status'=>0, 'mess'=>'60s内不能重复发送');
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
                                    $value = array('status'=>1, 'mess'=>'发送验证码成功！'.$smscode);
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>0, 'mess'=>'系统错误，发送验证码失败！');
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
                                sendSms($phone,$smscode);
                                // 提交事务
                                Db::commit();
                                $value = array('status'=>1, 'mess'=>'发送验证码成功！'.$smscode);
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0, 'mess'=>'系统错误，发送验证码失败！');
                            }
                            }else{
                                $value = array('status'=>400, 'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                            }

                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'手机号已存在');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请填写正确的手机号码');
                }
            }else{
                $value = array('status'=>0,'mess'=>'您已登录，请退出登录后重试');
            }
            return json($value);
        }
    }
    
}