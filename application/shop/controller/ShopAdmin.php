<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class ShopAdmin extends Common{
    public function editpwd(){
        if(request()->isPost()){
            $admin_id = session('shopadmin_id');
            
            if(input('post.old_password')){
                if(input('post.password')){
                    if(input('post.confirm_password')){
                        $old_password = input('post.old_password');
                        $password = input('post.password');
                        $confirm_password = input('post.confirm_password');
            
                        $old_pwd = md5($old_password);
                        $admin_password = Db::name('shop_admin')->where('id',$admin_id)->value('password');
                        if($admin_password == $old_pwd){
                            if(!preg_match("/^[A-Z][a-zA-Z0-9]{5,14}$/", $password)){
                                $value = array('status'=>400,'mess'=>'新密码为大写字母开头，6-15位数字、英文、下划线组成','data'=>array('status'=>400));
                                return json($value);
                            }
                            
                            if($password == $old_password){
                                $value = array('status'=>0,'mess'=>'新密码不能和旧密码相同');
                                return json($value);
                            }
                            
                            if($confirm_password != $password){
                                $value = array('status'=>0,'mess'=>'确认密码不正确');
                                return json($value);
                            }
                            
                            $count = Db::name('shop_admin')->update(array('password'=>md5($password),'id'=>$admin_id));
                            if($count > 0){
                                $value = array('status'=>1,'mess'=>'修改登录密码成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'修改登录密码失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'旧密码错误');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'确认密码不能为空');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'新密码不能为空');
                }
            }else{
                $value = array('status'=>0, 'mess'=>'旧密码不能为空');
            } 
            return json($value);           
        }else{
            return $this->fetch();
        }
    }
    
    //设置支付密码发送短信验证码
    public function addpaypwdcode(){
        if(request()->isPost()){
            $admin_id = session('shopadmin_id');
            
            $phone = Db::name('shop_admin')->where('id',$admin_id)->value('phone');
            
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
            
            $addpwd = Db::name('shop_addpwd')->where('phone',$phone)->find();
             
            if($addpwd){
                $time = time();
                if(floor($time-$addpwd['qtime']) < 60){
                    $value = array('status'=>0, 'mess'=>'60s内不能重复发送');
                }else{
                    $smscode = createSMSCode();
                    $data['phone'] = $phone;
                    $data['qtime'] = time();
                    $data['smscode'] = $smscode;
                    $data['id'] = $addpwd['id'];
            
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('shop_addpwd')->update($data);
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
                }
            }else{
                $smscode = createSMSCode();
                $data['phone'] = $phone;
                $data['qtime'] = time();
                $data['smscode'] = $smscode;
            
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('shop_addpwd')->insert($data);
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
            }
            return json($value);
        }
    }
    
    public function addpaypwd(){
        if(request()->isPost()){
            $admin_id = session('shopadmin_id');
            $phone = Db::name('shop_admin')->where('id',$admin_id)->value('phone');
            
            if(input('post.phonecode')){
                $code = input('post.phonecode');
                $addpwd = Db::name('shop_addpwd')->where('phone',$phone)->find();
                if($addpwd && $addpwd['smscode'] == $code){
                    $time = time();
                    $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                    if($time-$addpwd['qtime'] <= $vali_time['value']*60){
                        $paypwd = input('post.paypwd');
                        if(!$paypwd || !preg_match("/^\\d{6}$/", $paypwd)){
                            $value = array('status'=>0, 'mess'=>'支付密码只能为6位数字组成');
                            return json($value);
                        }
            
                        $confirm_pwd = input('post.confirm_pwd');
                        if(!$confirm_pwd || $confirm_pwd != $paypwd){
                            $value = array('status'=>0, 'mess'=>'确认密码不正确');
                            return json($value);
                        }
                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('shop_admin')->update(array('id'=>$admin_id,'paypwd'=>md5($paypwd)));
                            Db::name('shop_addpwd')->delete($addpwd['id']);
                            // 提交事务
                            Db::commit();
                            $value = array('status'=>1,'mess'=>'设置支付密码成功');
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status'=>0,'mess'=>'设置支付密码失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'验证码超时！请重新发送验证码！');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'手机验证失败！');
                }
            }else{
                $value = array('status'=>0, 'mess'=>'请填写手机验证码');
            }
            return json($value);
        }else{
            $admin_id = session('shopadmin_id');
            $phone = Db::name('shop_admin')->where('id',$admin_id)->value('phone');
            $peizhi = Db::name('config')->where('ename','messtime')->field('value')->find();
            $this->assign('messtime',$peizhi['value']);
            $this->assign('phone',$phone);
            return $this->fetch();
        }
    }
    
    //更换手机号发送短信验证接口
    public function editphonecode(){
        if(request()->isPost()){
            $admin_id = session('shopadmin_id');
            
            if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                $phone = input('post.phone');
                
                $shop_admins = Db::name('shop_admin')->where('phone',$phone)->field('id')->find();
                if(!$shop_admins){
                    
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
            
                    $changephone = Db::name('shop_changephone')->where('phone',$phone)->find();
                     
                    if($changephone){
                        $time = time();
                        if(floor($time-$changephone['qtime']) < 60){
                            $value = array('status'=>0, 'mess'=>'60s内不能重复发送');
                        }else{
                            $smscode = createSMSCode();
                            $data['phone'] = $phone;
                            $data['qtime'] = time();
                            $data['smscode'] = $smscode;
                            $data['id'] = $changephone['id'];
            
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('shop_changephone')->update($data);
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
                        }
                    }else{
                        $smscode = createSMSCode();
                        $data['phone'] = $phone;
                        $data['qtime'] = time();
                        $data['smscode'] = $smscode;
            
                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('shop_changephone')->insert($data);
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
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'手机号已存在');
                }
            }else{
                $value = array('status'=>0,'mess'=>'请填写正确的手机号码');
            }
            return json($value);
        }
    }
    
    public function editphone(){
        if(request()->isPost()){
            $admin_id = session('shopadmin_id');

            if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                $phone = input('post.phone');
                $infos = Db::name('shop_admin')->where('phone',$phone)->field('id')->find();
                if(!$infos){
                    $adminphone = Db::name('shop_admin')->where('id',$admin_id)->value('phone');
                    if($adminphone != $phone){
                        if(input('post.phonecode')){
                            $code = input('post.phonecode');
                            $changephone = Db::name('shop_changephone')->where('phone',$phone)->find();
                            if($changephone && $changephone['smscode'] == $code){
                                $time = time();
                                $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                                if($time-$changephone['qtime'] <= $vali_time['value']*60){
                                    $data = array();
                                    $data['phone'] = $phone;
                                    $data['id'] = $admin_id;
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('shop_admin')->update($data);
                                        Db::name('shop_changephone')->delete($changephone['id']);
                                        // 提交事务
                                        Db::commit();
                                        $value = array('status'=>1,'mess'=>'更换登录手机号成功');
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>0,'mess'=>'更换登录手机号失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'验证码超时！请重新发送验证码！');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'手机验证失败！');
                            }
                        }else{
                            $value = array('status'=>0, 'mess'=>'请填写手机验证码');
                        }
                    }else{
                        $value = array('status'=>0, 'mess'=>'新手机号与旧手机号不能相同');
                    }                    
                }else{
                    $value = array('status'=>0, 'mess'=>'手机号已存在');
                }
            }else{
                $value = array('status'=>0, 'mess'=>'请填写正确的手机号码');
            }
            return json($value);
        }else{
            $admin_id = session('shopadmin_id');
            $peizhi = Db::name('config')->where('ename','messtime')->field('value')->find();
            $this->assign('messtime',$peizhi['value']);
            return $this->fetch();
        }        
    }
}