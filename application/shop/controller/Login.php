<?php
namespace app\shop\controller;
use think\Controller;
use think\Db;

class Login extends Controller{
    //商家管理员登录
    public function index(){
        if(request()->isPost()){
            if(!session('shopadmin_id')){
                if(input('post.phone') && input('post.password')){
                    $phone = input('post.phone');
                    $password = md5(input('post.password'));
                    $list = Db::name('shop_admin')->where('phone',$phone)->where('password',$password)->field('id,phone,open_status,shop_id')->find();
                    if($list){
                        if($list['open_status'] == 1){
                            if($list['shop_id']){
                                $shops = Db::name('shops')->where('id',$list['shop_id'])->field('id,shop_name,logo,open_status,normal')->find();
                                if($shops && $shops['shop_name'] && $shops['open_status'] == 1){
                                    if($shops['normal'] == 1){
                                        $members = Db::name('member')->where('shop_id',$list['shop_id'])->field('id')->find();
                                        $rxins = Db::name('rxin')->where('user_id',$members['id'])->find();
                                        if($rxins){
                                            session('shopsh_token', $rxins['token']);
                                        }
                                        session('shopadmin_id',$list['id']);
                                        session('shopadmin_phone',$list['phone']);
                                        session('shopsh_id',$list['shop_id']);
                                        session('shopsh_name',$shops['shop_name']);
                                        if($shops['logo']){
                                            session('shopsh_logo',$shops['logo']);
                                        }
                                        Db::name('shop_admin')->update(array(
                                        'login_ip' => request()->ip(),
                                        'login_time' => time(),
                                        'id' => $list['id']
                                        ));
                                        $value = array('status'=>1,'mess'=>'登录成功');
                                    }else{
                                        $value = array('status'=>0,'mess'=>'您的账号已注销，登录失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'账号已关闭，登录失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'登录失败');
                            }
                        }elseif($list['open_status'] == 0){
                            $value = array('status'=>0,'mess'=>'账号已锁定，登录失败');
                        }else{
                            $value = array('status'=>0,'mess'=>'登录失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'账号或密码错误');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'账号或密码不能为空');
                }
            }else{
                $value = array('status'=>0,'mess'=>'您已登录，请退出登录后重试');
            }
            return json($value);
        }else{
            if(!session('shopadmin_id')){
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }
        }
    }
    
    
    //找回密码发送验证码
    public function findpwdcode(){
        if(request()->isPost()){
            if(!session('shopadmin_id')){
                if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                    $phone = input('post.phone');
                    $shop_admins = Db::name('shop_admin')->where('phone',$phone)->field('id')->find();
                    if($shop_admins){
    
    
                        $codenum = Db::name('code_num')->where('phone',$phone)->find();
                        if(!empty($codenum)){
                            $jtime = time();
                            if($jtime < $codenum['time_out']){
                                if($codenum['num'] >= 10){
                                    $value = array('status'=>400,'mess'=>'今天已超出最大请求次数','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
                        }
                         
                        $zhpwd = Db::name('shop_zhpwd')->where('phone',$phone)->find();
                         
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
                                    Db::name('shop_zhpwd')->update($data);
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
                                Db::name('shop_zhpwd')->insert($data);
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
            if(!session('shopadmin_id')){
                if(input('post.phone')){
                    if(input('post.phonecode')){
                        if(input('post.password')){
                            if(input('post.repwd')){
                                $phone = input('post.phone');
                                $code = input('post.phonecode');
                                $password = input('post.password');
                                $repwd = input('post.repwd');
    
                                if(preg_match("/^1[3456789]{1}\\d{9}$/", $phone)){
                                    $zhpwd = Db::name('shop_zhpwd')->where('phone',$phone)->find();
                                    if($zhpwd && $zhpwd['smscode'] == $code){
                                        $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                                        $time = time();
                                        if(floor($time-$zhpwd['qtime']) <= $vali_time['value']*60){
                                            $shop_admins = Db::name('shop_admin')->where('phone',$phone)->field('id,password')->find();
                                            if($shop_admins){
                                                if($repwd != $password){
                                                    $value = array('status'=>0,'mess'=>'确认密码不正确');
                                                    return json($value);
                                                }
    
                                                if(!preg_match("/^[A-Z][a-zA-Z0-9]{5,14}$/", $password)){
                                                    $value = array('status'=>0,'mess'=>'密码以大写字母开头6-15位数字、英文、下划线组成');
                                                    return json($value);
                                                }
    
                                                if(md5($password) == $shop_admins['password']){
                                                    $value = array('status'=>0,'mess'=>'新密码不能与旧密码相同');
                                                    return json($value);
                                                }
    
                                                // 启动事务
                                                Db::startTrans();
                                                try{
                                                    Db::name('shop_admin')->update(array('password'=>md5($password),'id'=>$shop_admins['id']));
                                                    Db::name('shop_zhpwd')->delete($zhpwd['id']);
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
            if(!session('shopadmin_id')){
                $peizhi = Db::name('config')->where('ename','messtime')->field('value')->find();
                $this->assign('messtime',$peizhi['value']);
                return $this->fetch();
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