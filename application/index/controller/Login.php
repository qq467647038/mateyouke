<?php
namespace app\index\controller;
use think\Controller;
use think\Db;

class Login extends Common{
    //用户登录
    public function index(){
        if(request()->isPost()){
            // $data = input();
            // dump($data);die;
            if(!session('user_id')){
                if (input('post.type') == 2){
                    if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
//                        var_dump(1);die;
                        if(input('post.password')){
                            $phone = input('post.phone');
                            $pwd = input('post.password');
                            $password = md5(input('post.password'));

                            $members = Db::name('member')->where('phone',$phone)->where('password',$password)->field('id,user_name,phone,checked')->find();
                            if($members && $members['checked'] == 1){
                                session('user_id',$members['id']);
                                session('user_phone',$members['phone']);
                                if($members['user_name']){
                                    session('user_name',$members['user_name']);
                                }
                                if(cookie('goods_url')){
                                    $value = array('status'=>1,'mess'=>'登录成功','goods_url'=>cookie('goods_url'));
                                }else{
                                    $value = array('status'=>2,'mess'=>'登录成功');
                                }
                            }else{
                                if($members && $members['checked'] == 0){
                                    $value = array('status'=>0,'mess'=>'您的账号已锁定，请联系网站管理员');
                                }else{
                                    $value = array('status'=>0,'mess'=>'手机号或密码错误');
                                }
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请填写登录密码');
                        }
                    }else{
                        $value = array('status'=>0,'message'=>'请填写正确的手机号码');
                    }
                }elseif(input('post.type') == 1){
                    if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                        if(input('post.sms_captcha')){
                            $phone = input('post.phone');
                            $sms_code = input('post.sms_captcha');
                            $reg = Db::name('zhpwd')->where('phone',$phone)->find();
                            if($reg && $reg['smscode'] == $sms_code){
                                $members = Db::name('member')->where('phone',$phone)->field('id,user_name,phone,checked')->find();
                                if($members && $members['checked'] == 1){
                                    session('user_id',$members['id']);
                                    session('user_phone',$members['phone']);
                                    if($members['user_name']){
                                        session('user_name',$members['user_name']);
                                    }
                                    if(cookie('goods_url')){
                                        $value = array('status'=>1,'mess'=>'登录成功','goods_url'=>cookie('goods_url'));
                                    }else{
                                        $value = array('status'=>2,'mess'=>'登录成功');
                                    }
                                }else{
                                    if($members && $members['checked'] == 0){
                                        $value = array('status'=>0,'mess'=>'您的账号已锁定，请联系网站管理员');
                                    }else{
                                        $value = array('status'=>0,'mess'=>'手机号或密码错误');
                                    }
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'请填写正确的验证码');
                            }

                        }else{
                            $value = array('status'=>0,'mess'=>'请输入验证码');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请填写正确的手机号码');
                    }
                }

            }else{
                $value = array('status'=>0,'mess'=>'您已登录，请退出登录后重试');
            }
            return json($value);
        }else{
            if(!session('user_id')){
                return $this->fetch('login');
            }else{
                $this->redirect('index/index');
            }
        }
    }
    
    
    //找回密码发送验证码
    public function findpwdcode(){
        if(request()->isPost()){
            if(!session('user_id')){
                if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                    $phone = input('post.phone');
                    $members = Db::name('member')->where('phone',$phone)->field('id')->find();
                    if($members){
                        
                        
                        $codenum = Db::name('code_num')->where('phone',$phone)->find();
                        if(!empty($codenum)){
                            $jtime = time();
                            if($jtime < $codenum['time_out']){
                                if($codenum['num'] >= 100){
                                    $value = array('status'=>400,'mess'=>'今天已超出最大请求次数','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
                        }
                         
                        $zhpwd = Db::name('zhpwd')->where('phone',$phone)->find();
                         
                        if($zhpwd){
                            $time = time();
                            if(floor($time-$zhpwd['qtime']) < 60){
                                $value = array('status'=>0, 'mess'=>'60s内不能重复发送');
                            }else{
                                $smscode = createSMSCode();
                                $data['phone'] = $phone;
                                $data['qtime'] = time();
                                $data['smscode'] = $smscode;
                                $data['id'] = $zhpwd['id'];
    
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('zhpwd')->update($data);
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
                                    $value = array('status'=>1,'mess'=>'发送验证码成功！'.$smscode);
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>0,'mess'=>'系统错误，发送验证码失败！');
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
                                Db::name('zhpwd')->insert($data);
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
                                $value = array('status'=>1,'mess'=>'发送验证码成功！'.$smscode);
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0,'mess'=>'系统错误，发送验证码失败！');
                            }
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'手机号不存在');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请填写正确的手机号码');
                }
            }else{
                $value = array('status'=>0,'mess'=>'您已登录，操作失败');
            }
            return json($value);
        }
    }
    
    
    //找回密码
    public function findpwd(){
        if(request()->isPost()){
            if(!session('user_id')){
                if(input('post.phone')){
                    if(input('post.phonecode')){
                        if(input('post.password')){
                            if(input('post.repwd')){
                                $phone = input('post.phone');
                                $code = input('post.phonecode');
                                $password = input('post.password');
                                $repwd = input('post.repwd');
    
                                if(preg_match("/^1[3456789]{1}\\d{9}$/", $phone)){
                                    $zhpwd = Db::name('zhpwd')->where('phone',$phone)->find();
                                    if($zhpwd && $zhpwd['smscode'] == $code){
                                        $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                                        $time = time();
                                        if(floor($time-$zhpwd['qtime']) <= $vali_time['value']*60){
                                            $members = Db::name('member')->where('phone',$phone)->field('id,password')->find();
                                            if($members){
                                                if($repwd != $password){
                                                    $value = array('status'=>0,'mess'=>'确认密码不正确');
                                                    return json($value);
                                                }
    
                                                if(!preg_match("/^[A-Z][a-zA-Z0-9]{5,14}$/", $password)){
                                                    $value = array('status'=>0,'mess'=>'密码以大写字母开头6-15位数字、英文、下划线组成');
                                                    return json($value);
                                                }
    
                                                if(md5($password) == $members['password']){
                                                    $value = array('status'=>0,'mess'=>'新密码不能与旧密码相同');
                                                    return json($value);
                                                }
    
                                                // 启动事务
                                                Db::startTrans();
                                                try{
                                                    Db::name('member')->update(array('password'=>md5($password),'id'=>$members['id']));
                                                    Db::name('zhpwd')->delete($zhpwd['id']);
                                                    // 提交事务
                                                    Db::commit();
                                                    $value = array('status'=>1,'mess'=>'重置密码成功');
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                    $value = array('status'=>0,'mess'=>'重置密码失败');
                                                }
                                            }else{
                                                $value = array('status'=>0,'mess'=>'手机号不存在！');
                                            }
                                        }else{
                                            $value = array('status'=>0,'mess'=>'验证码超时！请重新发送验证码！');
                                        }
                                    }else{
                                        $value = array('status'=>0,'mess'=>'验证码不正确，请重新输入！');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'请填写正确的手机号码');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'缺少确认密码');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'缺少新密码参数');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'缺少短信验证码');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'缺少手机号');
                }
            }else{
                $value = array('status'=>0,'mess'=>'您已登录，操作失败');
            }
            return json($value);
        }else{
            if(!session('user_id')){
                $peizhi = Db::name('config')->where('ename','messtime')->field('value')->find();
                $this->assign('messtime',$peizhi['value']);
                return $this->fetch('findpwds');
            }else{
                $this->redirect('index/index');
            }
        }
    }
    
    public function loginout(){
        session(null);
        if(isset($_COOKIE[session_name()])){
            setcookie(session_name(),'',time()-3600,'/');
        }
        session_destroy();
        $this->redirect('login/index');
    }
    
}