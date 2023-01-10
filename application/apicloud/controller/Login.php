<?php
namespace app\apicloud\controller;
use app\apicloud\model\Gongyong as GongyongMx;
use app\common\service\WxLoginService;
use app\common\service\MemberService;
use EasyWeChat\Factory;
use think\Db;

class Login extends Common{

    public function login(){
        return $this->fetch();
    }

    /**
     * 公众号微信登录
     *
     * @return void
     */
    public function comWxLogin(){
        $code = input('code','');
        $url = input('url','');
        // Db::table('sp_test')->insert(['value'=>$url]);
        $service = new WxLoginService();
        if($code == ''){

            // 获取code
            $res = $service->getOpenId('',$url);
            return returnJson(201,'获取成功',$res);

        }else{

            $data = $service->getOpenId($code);
//            if((!isset($data['openid']) || empty($data['openid'])))
//            {
//                return returnJson(400,'登录失败');
//            }

            // 登录
            $memberService = new MemberService();
            $userInfo = $memberService->comWxLogin($data);

            if(empty($userInfo) || empty($userInfo['token'])){
                return returnJson(400,'登录失败');
            }
            return returnJson(200,'登录成功',$userInfo);
            
        }
    }

    /**
     * 授权
     *
     * @return void
     */
    public function authWx(){
        $code = input('code','');
        $url = input('url','');
        $service = new WxLoginService();
        if($code == ''){

            // 获取code
            $res = $service->getOpenId('',$url);
            return returnJson(201,'获取成功',$res);

        }else{

            $data = $service->getOpenId($code);
            return returnJson(200,'获取成功',$data);
            // 登录
            // $memberService = new MemberService();
            // $userInfo = $memberService->comWxLogin($data);
            // if($userInfo == ''){
            //     return returnJson(400,'登录失败');
            // }
            // return returnJson(200,'登录成功',$userInfo);
            
        }
    }

    /**
     * 重定向
     */
    public function locaWxUrl()
    {
        // 跳转
        Header("Location:http://soldier.cxy365.com/admin");
    }

    /**
     * 授权登录
     */
    public function authWxLogin()
    {
        $data = input('data');
        $memberService = new MemberService();
        $userInfo = $memberService->comWxLogin($data);
        if($userInfo == ''){
            return returnJson(400,'登录失败');
        }
        return returnJson(200,'登录成功',$userInfo);
    }

    //用户账号密码登录
    public function denglu(){
        if(request()->isPost()){
            if(1){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate(0);
                if($result['status'] == 200){
                    if(input('post.nick_name') && input('post.password')){
                        $phone = input('post.nick_name');
//                        if(preg_match("/^1[3456789]{1}\\d{9}$/", $phone)){
                        if(true){
                            $password = md5(input('post.password'));
                            $members = Db::name('member')->where('nick_name',$phone)->where('delete', 0)->where('password',$password)->field('id,appinfo_code,checked,shop_id,pid,one_level')->find();
                            // var_dump($members);exit;
                            if($members && $members['checked'] == 1){
                                // $new_token = settoken();
                                // Db::name('rxin')->where('user_id',$members['id'])->update(array('token'=>$new_token));
                                // $members['token'] = $new_token;
                                //九盼写 先注释
//                                if($members['one_level'] == 0 ){
//                                    $parent['one_level'] = input('post.shareid');
//                                    Db::name('member')->where('id',$members['id'])->update($parent);
//                                }
                                // bandPid($members['id'],(int)trim(input('post.shareid')));
                                $rxs = Db::name('rxin')->where('user_id',$members['id'])->field('token')->find();
                                $members['token'] = $rxs['token'];
                                $members['role'] = getUserRole($members['id']);

                                if($members['pid'] >0 ){
                                    //$shop =  $mysql->findone('sp_member', '', ['id' => $members['pid']]);
                                    $shop =  Db::name('member')->where('id',$members['pid'])->find();
                                    $members['serviceShopId'] = $shop['shop_id'];
                                }
                                //登录成功，更改用户的设备token
                                if(input('post.devicetoken') && input('post.devicetoken') != $members['appinfo_code']){   //如果有新的设备token进来，记录此token值
                                    Db::name('member')->update(array('id'=>$members['id'],'appinfo_code'=>input('post.devicetoken')));
                                }
								//登录送积分
								// $num = $this->getIntegralRules(1);//获取登录积分
								// $this->addIntegral($members['id'],$num,1);								
								$uniqid = uniqid();
								$members['login_code'] = $uniqid;
								Db::name('member')->update(array('id'=>$members['id'],'login_code'=>$uniqid));
                                $value = array('status'=>200,'mess'=>'登录成功','data'=>$members);
                            }elseif($members && $members['checked'] != 1){
                                $value = array('status'=>400,'mess'=>'账号已冻结，请联系平台管理员','data'=>array('status'=>400));
                            }else{
                                $value = array('status'=>400,'mess'=>'账号或密码错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'账号格式不正确','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'账号或密码错误','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'已登录，登录失败','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //登录发送短信验证码
    public function sendcode(){
        if(request()->isPost()){
            if(!input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate(0);
                if($result['status'] == 200){
                    if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                        $phone = input('post.phone');
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
                        $denglu = Db::name('denglu')->where('phone',$phone)->find();
                         
                        if($denglu){
                            $time = time();
                            if(floor($time-$denglu['qtime']) < $messtime){
                                $value = array('status'=>0, 'mess'=>$messtime.'s内不能重复发送');
                            }else{
                                $smscode = createSMSCode();
                                $data['phone'] = $phone;
                                $data['qtime'] = time();
                                $data['smscode'] = $smscode;
                                $data['id'] = $denglu['id'];
                        
                                $outputArr = sendSms($phone,$smscode);
                                $outputArr = object_to_array($outputArr);
                                if($outputArr['msg'] == 'OK'){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('denglu')->update($data);
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
                                    Db::name('denglu')->insert($data);
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
                        $value = array('status'=>4000,'mess'=>'请填写正确的手机号码','data'=>array('status'=>400));
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

    //短信验证码登录
    public function duanxinlogin(){
        if(request()->isPost()){
            if(!input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate(0);
                if($result['status'] == 200){
                    if(input('post.phone')){
                        if(input('post.phonecode')){
                            $phone = input('post.phone');
                            $code = input('post.phonecode');
                            
                            if(preg_match("/^1[3456789]{1}\\d{9}$/", $phone)){
                                $denglu = Db::name('denglu')->where('phone',$phone)->find();
                                if($denglu && $denglu['smscode'] == $code){
                                    $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                                    $time = time();
                                    if(floor($time-$denglu['qtime']) <= $vali_time['value']*60){
                                        $members = Db::name('member')->where('phone',$phone)->field('id,appinfo_code,checked')->find();
                                        if($members){
                                            if($members && $members['checked'] == 1){
                                                // $new_token = settoken();
                                                // Db::name('rxin')->where('user_id',$members['id'])->update(array('token'=>$new_token));
                                                // $members['token'] = $new_token;
                                                $rxs = Db::name('rxin')->where('user_id',$members['id'])->field('token')->find();
                                                $members['token'] = $rxs['token'];
                                                unset($members['id']);
                                                $value = array('status'=>200,'mess'=>'登录成功','data'=>$members);
                                            }elseif($members && $members['checked'] != 1){
                                                $value = array('status'=>400,'mess'=>'账号已关闭，请联系平台管理员','data'=>array('status'=>400));
                                            }else{
                                                $value = array('status'=>400,'mess'=>'账号或密码错误','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $password = createSMSCode();
                                            
                                            $password = md5($password);
                                            
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
                                                        'phone'=>$phone,
                                                        'password'=>$password,
                                                        'recode'=>$recode,
                                                        'appinfo_code'=>$appinfo_code,
                                                        'xieyi'=>1,
                                                        'regtime'=>time()
                                                    ));
                                            
                                                    if($user_id){
                                                        Db::name('rxin')->insert(array('token'=>$token,'user_id'=>$user_id));
                                                        Db::name('wallet')->insert(array('price'=>0,'user_id'=>$user_id));
                                                        Db::name('profit')->insert(array('price'=>0,'user_id'=>$user_id));
                                                    }
                                            
                                                    // 提交事务
                                                    Db::commit();
                                                    $canshu = array('token'=>$token);
                                                    $value = array('status'=>200,'mess'=>'登录成功','data'=>$canshu);
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                    $value = array('status'=>400,'mess'=>'登录失败','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'登录失败，请重试','data'=>array('status'=>400));
                                            }
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'验证码超时！请重新发送验证码！','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'验证码不正确，请重新输入！','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'请填写正确的手机号码','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少短信验证码','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少账号','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'已登录，登录失败','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    public function openThirdLogin(){
            if(request()->isPost()){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate(0);
				if($result['status'] == 200){
                    //$open = $this->webconfig['thirdlogin'];
					$opens = $this->getConfigInfo(145);
					$open = $opens['value'];
                    if($open =="开启"){
                        //$value = array('status'=>200,'mess'=>'已开启','data'=>array('open'=>true));
						$value = array('status'=>200,'data'=>array('open'=>true));
                    }else{
                        //$value = array('status'=>400,'mess'=>'已关闭','data'=>array('open'=>false));
						$value = array('status'=>200,'data'=>array('open'=>false));
                    }
                    
                }else{
                    $value = $result;
                }
            }else{
               $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('open'=>false));
            }
            return json($value);
    }
    //处理第三方登录
    public function sfdenglu(){
        if(request()->isPost()){
//            if(!input('post.token')){
            if(1){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate(0);
                if($result['status'] == 200){
                    if(input('post.oauth')){
                        if(input('post.oauth') == 'weixin' || input('post.oauth') == 'mini' || input('post.oauth') == 'portal'){
                            if(input('post.openid')){
                                if(input('post.nick_name')){
                                    $oauth = 1;
                                    $postOauth = input('post.oauth');
                                    $openid = input('post.openid');
                                    $nick_name = input('post.nick_name');
                                    $unionid = input('post.unionid');
                                    $head_pric = input('post.head_pic','');
                                    $one_level = (int)trim(input('post.shareid'));
                                    if(input('post.iconurl')){
                                        $headimgurl = input('post.iconurl');
                                    }else{
                                        $headimgurl = '';
                                    }
                                    // $po = json_encode(input('post.'));
                                    // $ge = json_encode(input('param.'));
                                    // Db::name('wxtest')->insert(array('con'=>$ge));
                                     
                    
                                    if(input('post.uniongender') && in_array(input('post.uniongender'), array(1,2))){
                                        $sex = input('post.uniongender');
                                    }else{
                                        $sex = 0;
                                    }
                                    // if($postOauth == 'mini'){
                                    //     $members = Db::name('member')->where('xcx_openid',$openid)->find();
                                    // }else{
                                    //     $members = Db::name('member')->where('openid',$openid)->find();
                                    // }
                                    if($unionid != ''){
                                        $members = Db::name('member')->where('unionid',$unionid)->find();
                                    }else{
                                        if($postOauth == 'mini'){
                                            $members = Db::name('member')->where('xcx_openid',$openid)->find();
                                        }elseif($postOauth == 'portal'){
                                            $members = Db::name('member')->where('portal_openid',$openid)->find();
                                        }else{
                                            $members = Db::name('member')->where('openid',$openid)->find();
                                        }
                                    }
                                    if($members){
                                        if(empty($members['unionid'])){
                                            $memberData['unionid'] = $unionid;
                                            Db::name('member')->where('id',$members['id'])->update($memberData);
                                        }
                                        if($postOauth == 'mini'){
                                            if(empty($members['xcx_openid'])){
                                                $memberData['xcx_openid'] = $openid;
                                                Db::name('member')->where('id',$members['id'])->update($memberData);
                                            }
                                        }
                                        if($postOauth == 'portal'){
                                            if(empty($members['portal_openid'])){
                                                $memberData['portal_openid'] = $openid;
                                                Db::name('member')->where('id',$members['id'])->update($memberData);
                                            }
                                        }
                                        bandPid($members['id'],$one_level);

                                        //九盼写的 先注释
//                                        if($members['one_level'] == 0){
//                                            $parent['one_level'] = $one_level;
//                                            Db::name('member')->where('id',$members['id'])->update($parent);
//                                        }
                                        // $new_token = settoken();
                                        //登录成功，更改用户的设备token
                                        if(input('post.devicetoken') && input('post.devicetoken') != $members['appinfo_code']){   //如果有新的设备token进来，记录此token值
                                            Db::name('member')->update(array('id'=>$members['id'],'appinfo_code'=>input('post.devicetoken')));
                                        }
                                        $rxres = Db::name('rxin')->where('user_id',$members['id'])->find();
                                        $canshu = array('token'=>$rxres['token'],'user_id'=>$members['id'],'shop_id'=>$members['shop_id'],'pid'=>$members['pid'],'role'=>getUserRole($members['id']));
                                        $value = array('status'=>200,'mess'=>'登录成功','data'=>$canshu);
                                    }else{
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
                                                if($postOauth == 'mini'){

                                                    $user_id = Db::name('member')->insertGetId(array(
                                                        //'user_name'=>$nick_name,
                                                        'user_name'=>$this->userTextEncode($nick_name),
                                                        'recode'=>$recode,
                                                        'appinfo_code'=>$appinfo_code,
                                                        'headimgurl'=>$head_pric,
                                                        'sex'=>$sex,
                                                        'oauth'=>$oauth,
                                                        'xcx_openid'=> $openid,
                                                        'unionid' => $unionid,
    //                                                    'one_level' => $one_level,
                                                        'xieyi'=>1,
                                                        'regtime'=>time()
                                                    ));

                                                }elseif($postOauth == 'portal'){

                                                    $user_id = Db::name('member')->insertGetId(array(
                                                        //'user_name'=>$nick_name,
                                                        'user_name'=>$this->userTextEncode($nick_name),
                                                        'recode'=>$recode,
                                                        'appinfo_code'=>$appinfo_code,
                                                        'headimgurl'=>$head_pric,
                                                        'sex'=>$sex,
                                                        'oauth'=>$oauth,
                                                        'portal_openid'=> $openid,
                                                        'unionid' => $unionid,
    //                                                    'one_level' => $one_level,
                                                        'xieyi'=>1,
                                                        'regtime'=>time()
                                                    ));

                                                }else{

                                                    $user_id = Db::name('member')->insertGetId(array(
                                                        //'user_name'=>$nick_name,
                                                        'user_name'=>$this->userTextEncode($nick_name),
                                                        'recode'=>$recode,
                                                        'appinfo_code'=>$appinfo_code,
                                                        'headimgurl'=>$headimgurl,
                                                        'sex'=>$sex,
                                                        'oauth'=>$oauth,
                                                        'openid'=>$openid,
                                                        'unionid' => $unionid,
    //                                                    'one_level' => $one_level,
                                                        'xieyi'=>1,
                                                        'regtime'=>time()
                                                    ));
                                                }
                                            
                                                if($user_id){
                                                    Db::name('rxin')->insert(array('token'=>$token,'user_id'=>$user_id));
                                                    Db::name('wallet')->insert(array('price'=>0,'user_id'=>$user_id));
                                                    Db::name('profit')->insert(array('price'=>0,'user_id'=>$user_id));
                                                    bandPid($user_id,$one_level);
                                                }

                                                // 提交事务
                                                Db::commit();
                                                $canshu = array('token'=>$token);
                                                $members = Db::name('member')->field('id,appinfo_code,checked,shop_id,pid')->find($user_id);

                                                $canshu = array('token'=>$token,'user_id'=>$members['id'],'shop_id'=>$members['shop_id'],'pid'=>$members['pid'],'role'=>getUserRole($members['id']));
                                                $value = array('status'=>200,'mess'=>'登录成功','data'=>$canshu);
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                                $value = array('status'=>400,'mess'=>'登录失败','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'登录失败，请重试','data'=>array('status'=>400));
                                        }
                                    }
                                }else{
                                    $value = array('status'=>400, 'mess'=>'缺少用户昵称','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400, 'mess'=>'缺少第三方登录用户唯一标识，登录失败','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400, 'mess'=>'登录类型错误，登录失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400, 'mess'=>'缺少登录类型，登录失败','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'已登录，登录失败1','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function getAccessToken(){
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx3a697e9630da7dbd&secret=eb446b0ae21c893dc2cc0e474447fbcf";
                $token = https_request($url);
                return $token['access_token'];            
    }

    public function getWxUserInfo(){
        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx3a697e9630da7dbd&secret=eb446b0ae21c893dc2cc0e474447fbcf";
        // $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxdc0a2e3f8fa61d0f&secret=623311f72bb5f062400409ac56a12118";
        $token = https_request($tokenUrl);
        $token = json_decode($token,true);
        // dump($token);die;
        $userInfoUrl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$token['access_token']."&openid=oQeeE52lAwlHniHJ437sOoTf3V-o&lang=zh_CN";
        $userInfo = https_request($userInfoUrl);
        return $userInfo;            
    }

    public function getWechatMiniProgramOpenid(){
        // 验证api_token
        $res = $this->checkToken(0);
        if($res['status'] == 400){  return json($res);  }

        $code = input('post.code');
        $weChatApp = Factory::miniProgram($this->wechatConfig);
        $res = $weChatApp->auth->session($code);
        if(!empty($res['openid'])){
            return datamsg(WIN, '获取成功', $res);
        }else{
            datamsg(LOSE,'获取失败');
        }
    }

    /**
     * 门户小程序
     */
    public function getPortalMiniOpenid(){
        // 验证api_token
        $res = $this->checkToken(0);
        if($res['status'] == 400){  return json($res);  }

        $code = input('post.code');
        $array = [
            'app_id' => 'wx03eb78d0613ef338',
            'secret' => '8aaf2d9e921ab5b8ba13bf4de337e45d',
            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ]
        ];

        $weChatApp = Factory::miniProgram($array);
        $res = $weChatApp->auth->session($code);

        if(!empty($res['openid'])){
            return datamsg(WIN, '获取成功', $res);
        }else{
            datamsg(LOSE,'获取失败');
        }
    }

    /***
     * 增加注册用户手机信息
     */
    public function addMemberMobile(){
        $data = [];
        if(input('post.token')){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                $data['user_id'] = $result['user_id'];
                $testdata = Db::name('member_extends')->where('user_id',$data['user_id'])->find();
                if(empty($testdata)){
                    $data['brand'] = input('post.brand');
                    $data['model'] = input('post.model');
                    $data['version'] = input('post.version');
                    $data['system'] = input('post.system');
                    $data['platform'] = input('post.platform');
                    $data['created'] = date('Y-m-d H:i:s',time());
                    Db::name('member_extends')->insert($data);
                }
                $value = array('status'=>200,'mess'=>'操作成功','data'=>[]);
            }else{
                $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));   
            }
            return json($value);
        }else{
            $value = array('status'=>400,'mess'=>'操作失败，缺少参数','data'=>array('status'=>400)); 
        }
        
    }

}