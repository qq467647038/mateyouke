<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Safesz extends Common{
    //安全设置
    public function shezhi(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    $yzresult = $this->validate($data,'Member.shezhi');
                    if(true !== $yzresult){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
                        $safesz = Db::name('safesz')->where('phone',$data['phone'])->find();
                        if($safesz && $safesz['smscode'] == $data['phonecode']){
                            $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                            $time = time();
                            if(floor($time-$safesz['qtime']) <= $vali_time['value']*60){
                                $data['password'] = md5($data['password']);
                        
                                $count = Db::name('member')->where('id',$user_id)->update(array(
                                    'phone'=>$data['phone'],
                                    'password'=>$data['password'],
                                    'id'=>$user_id
                                ));
                        
                                if($count > 0){
                                    $value = array('status'=>200,'mess'=>'设置成功','data'=>array('status'=>200));
                                }else{
                                    $value = array('status'=>400,'mess'=>'设置失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'验证码超时！请重新发送验证码！','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'验证码不正确，请重新输入！','data'=>array('status'=>400));
                        }
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
    
    //安全设置发送短信验证码
    public function sendcode(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
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
                            $safesz = Db::name('safesz')->where('phone',$phone)->find();
                             
                            if($safesz){
                                $time = time();
                                if(floor($time-$safesz['qtime']) < $messtime){
                                    $value = array('status'=>0, 'mess'=>$messtime.'s内不能重复发送');
                                }else{
                                    $smscode = createSMSCode();
                                    $data['phone'] = $phone;
                                    $data['qtime'] = time();
                                    $data['smscode'] = $smscode;
                                    $data['id'] = $safesz['id'];
    
                                    $outputArr = sendSms($phone,$smscode);
                                    $outputArr = object_to_array($outputArr);
    
                                    if($outputArr['msg'] == 'OK'){
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            Db::name('safesz')->update($data);
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
                                        Db::name('safesz')->insert($data);
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
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
}