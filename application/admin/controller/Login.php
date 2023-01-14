<?php
/*
 * @Descripttion: 
 * @Copyright: ©版权所有
 * @Link: www.s1107.com
 * @Contact: QQ:2487937004
 * @LastEditors: cbing
 * @LastEditTime: 2020-04-22 22:02:27
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Admin as AdminMx;

class Login extends Controller{
    public $phone = '15860049567';
    
    public function aa(){
       session(null);
        if(isset($_COOKIE[session_name()])){
            setcookie(session_name(),'',time()-3600,'/');
        }
        session_destroy();
        $this->redirect('Index/index');
    }
 
    
    
    // 发送验证码
    public function sendSms(){
        if(request()->isPost()) {
            $url = 'http://39.103.164.21:7862/sms';
            if(!$this->phone){
                $value = array('status'=>400,'mess'=>'手机号码不存在','data'=>array('status'=>400));
            }
            else{
                $data = [];

                $rand_num = rand (0, 9).rand (0, 9).rand (0, 9).rand (0, 9).rand (0, 9).rand (0, 9);


                $url  = $url.'?action=send&account=221801&password=jFYcta&mobile='.$this->phone.'&content='.urlencode('【玛特优客】您的验证码是：').$rand_num.'&extno=10690495&rt=json';
                $res = json_decode(file_get_contents($url),true);

                if($res['status'] == 0 && $res['list'][0]['result'] == 0){
//                if(true){
                    $value = array('status'=>200,'mess'=>'发送成功！');

                    Db::name('sms')->insert([
                        'phone'=>$this->phone,
                        'code'=>$rand_num,
                        'addtime'=>time(),
                        'use'=>0
                    ]);

                    //  session($phone, $rand_num);
                    //  var_dump(session($phone));exit;
                }else{
                    $value = array('status'=>400,'mess'=>'发送失败！');
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //管理员登录
    public function index(){
        if(request()->isAjax()){
            // if(input('post.username') && input('post.password') && input('post.phonecode')){
            if(input('post.username') && input('post.password')){
                $verifyCode = input('post.verifyCode');
                // if(!captcha_check($verifyCode)){
                //     $value = array('status'=>400,'mess'=>'图形验证码错误','data'=>array('status'=>400));
                //     return json($value);
                // }
                
                $username = input('post.username');
                $phonecode = input('post.phonecode');
                $token = input('post.token');
                $password = md5(input('post.password'));
                // if(empty($token)){
                //     $sms = Db::name('sms')->where('use', 0)->where('phone', $this->phone)->order('id desc')->field('code')->find();
                //     if(!$sms || $sms['code'] != $phonecode){
                //         $value = array('status'=>400,'mess'=>'验证码错误','data'=>array('status'=>400));
                //         return json($value);
                //     }
                // }
                // else{
                //     if(md5($token) != '35468cf2b0b5cc002bf5cf3f23c6960b'){
                //         $value = array('status'=>400,'mess'=>'验证码错误','data'=>array('status'=>400));
                //         return json($value);
                //     }
                // }
                
                $list = Db::name('admin')->alias('a')->field('a.*,b.rolename,b.pri_id_list')->join('sp_role b','a.roleid = b.id','LEFT')->where(array('a.username' => $username,'a.password' => $password))->find();
                if($list && $list['suo'] != 1){
                    session('adminname',$list['username']);
                    session('admin_id',$list['id']);
                    session('rolename',$list['rolename']);
                    session('shop_id',1); // 自营店铺ID为1
                    $this->getpri($list['pri_id_list']);
                    $data2 = array();
                    $data2['login_ip'] = request()->ip();
                    $data2['login_time'] = time();
                    $data2['id'] = $list['id'];
                    $admin = new AdminMx();
                    
                    Db::startTrans();
                    try{
                        $admin->allowField(true)->save($data2,array('id'=>$data2['id']));
                        
                        $remark = '管理员登录';
                        $res = ys_admin_logs('管理员登录','admin',$list['id'],$remark);
                        if($res == 'false'){
                            throw new Exception('记录失败');
                        }
                        
                        $bool = true;
                        Db::commit();
                    }
                    catch(\Exception $e){
                        $bool = false;
                        Db::rollback();
                    }
                    
                    if($bool){
                        $value = array('status'=>1,'mess'=>'登录成功');
                    }
                    else{
                        $value = array('status'=>0,'mess'=>'登录失败');
                    }
                }elseif($list && $list['suo'] == 1){
                    $value = array('status'=>0,'mess'=>'您的账号已锁定');
                }elseif(!$list){
                    $value = array('status'=>0,'mess'=>'账号或密码错误');
                }
            }else{
                $value = array('status'=>0,'mess'=>'账号或密码或验证码不能为空');
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }
    
    //获取管理员权限
    public function getpri($pri_id_list){
        if($pri_id_list == '*'){
            $menu = Db::name('privilege')->where('pid',0)->where('status',1)->order('sort asc')->select();
            foreach ($menu as $key => $val){
                $menu[$key]['child'] = Db::name('privilege')->where('pid',$val['id'])->where('status',1)->order('sort asc')->select();
            }
            session('menu',$menu);
        }else{
            $menu = Db::name('privilege')->field('id,icon,pri_name,pid,mname,cname,aname,fwname')->where('pid',0)->where('status',1)->where('id','in',$pri_id_list)->select();
            
            foreach($menu as $key => $val){
                $menu[$key]['child'] = Db::name('privilege')->where('pid',$val['id'])->where('id','in',$pri_id_list)->where('status',1)->order('sort asc')->select();
            }
            session('menu',$menu);
        }
    }
    
}