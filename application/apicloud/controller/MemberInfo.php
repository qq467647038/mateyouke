<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use EasyWeChat\Kernel\Exceptions\Exception;
use app\apicloud\controller\TencentSms as TecentSms;
use TencentCloud\Common\Credential;
use think\Cache;
use think\Db;

class MemberInfo extends Common{
    public function siteNotice(){
        $info = Db::name('site_notice')->order('id desc')->limit(10)->select();
        
        $value = array('status'=>200,'mess'=>'获取成功','data'=>$info);
        return json($value);
    }
    
    public function myEarning(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $info = Db::name('contract_record_wallet')->where('user_id', $user_id)->find();
                    
                    $info['jinpaijilucount'] = Db::name('wine_order_buyer_contract')->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 'in', [1, 2])
                    ->where('wob.top_stop', 0)
                    ->where('wob.transfer', 0)
                    ->count();
                    
                    
                    
                    $list1 = Db::name('wine_order_saler_contract')->alias('wos')->where('wos.delete', 0)->where('sale_id', $user_id)
                        ->where('wos.status', 'in', [0])
                        ->count();
                        
                    // 已封仓
                    $list2 = Db::name('wine_order_buyer_contract')->alias('wob')->where('wob.delete', 0)->where('wob.buy_id', $user_id)->where('wob.transfer', 1)->where('wob.transfer_wine_contract_day_id', '>', 0)
                        ->where('wob.status', 'in', [2])
                        ->count();
                    
                    // 待确认
                    $list3 = Db::name('wine_order_buyer_contract')->alias('wob')
                        ->where('wob.delete', 0)
                        // ->where('wob.pay_status', 1)
                        ->where('wob.confirm_exchange', null)
                        ->where('wob.status', 'in', [1])
                        ->where('wob.sale_id', $user_id)
                        ->count();
                    $showTime = time()-24*60*60;
                    $list = Db::name('wine_order_saler_contract')->where('wos.delete', 0)->alias('wos')->where('sale_id', $user_id)
                    // ->where('wos.status', 'in', [$post['status'], 4])
                    ->where('wos.status', 'in', 2)
                    ->where('wos.addtime', '>=', $showTime)
                    ->count();
                    // $info['zhuanrangjilucount'] = $list1+$list2+$list3+$list;
                    $info['zhuanrangjilucount'] = $list3;
                        
                    $value = array('status'=>200,'mess'=>'获取成功！','data'=>$info);
                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function verifyPageStatus(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    
                    $zenren_frozen = Db::name('member')->where('id', $user_id)->value('zenren_frozen');
                    if($zenren_frozen!=0){
                        $value = array('status'=>400,'mess'=>'账号未激活','data'=>array('status'=>400));
                    }
                    else{
                        $value = array('status'=>200,'mess'=>'正常','data'=>array('status'=>200));
                    }
                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function rechargeUsdt(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $bank = Db::name('wine_usdt_account_generated')->where('user_id', $user_id)->field('address')->find();
                    if(!is_null($bank)){
                        $usdt_qrcode = $this->h5_code_water_logo('usdt_recharge_'.$user_id.'.png', $bank['address']);
                        $value = array('status'=>200,'mess'=>'获取成功！','data'=>$usdt_qrcode);
                    }
                    else{
                        $value = array('status'=>400,'mess'=>'未分配充值地址！','data'=>array('status'=>400));
                    }
                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function h5_code_water_logo($name,$path)
    {
    //带LOGO
        Vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();//实例化二维码类
        $url=$path;//网址或者是文本内容
        $level=3;
        $size=6;
        $pathname = "./uploads/usdt_qrcode";
        if(!is_dir($pathname)) { //若目录不存在则创建之
            mkdir($pathname);
        }

        $ad = $pathname .'/'. $name;
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);
        // echo $ad;exit;
        // $image = \think\Image::open($ad);
        // $image->water($logo, \think\Image::WATER_CENTER)->save($ad);

        $filePath = $this->getImageUrl('/uploads/usdt_qrcode/'.$name);
        return $filePath;
    }

    //获取二维码
    private function getImageUrl($imgurl){
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        if(empty($imgurl)){
            return $http_type . $_SERVER['HTTP_HOST'] . '/template/mobile/new2/static/course/images/default.png';
        }else{
            return $http_type . $_SERVER['HTTP_HOST'] . $imgurl;
        }

    }
    
    public function withdrawFuel(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $post = input('post.');
                    
                    if(!in_array($post['index'], [1,2,3])){
                        $value = array('status'=>400,'mess'=>'提现类型错误！','data'=>array('status'=>400));
                        return json($value);
                    }

                    $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
                    if(is_null($wallet_info)){
                        $value = array('status'=>400,'mess'=>'提现失败！','data'=>array('status'=>400));
                    }
                    else{
                        if($wallet_info['point_ticket']<$post['num'] || $post['num']<=0){
                            $value = array('status'=>400,'mess'=>'提现的金额有误！','data'=>array('status'=>400));
                        }
                        else{
                            if($post['index']==1){
                                $ss = Db::name('zfb_card')->where('user_id', $user_id)->find();
                                if(is_null($ss)){
                                    $value = array('status'=>400,'mess'=>'请先绑定支付宝','data'=>array('status'=>400));
                                    return json($value);
                                }
                                else{
                                    $qrcode = $ss['qrcode'];
                                    $telephone = $ss['telephone'];
                                    $name = $ss['name'];
                                    $card_number = '';
                                    $bank_name = '';
                                }
                            }
                            else if($post['index']==2){
                                $ss = Db::name('wx_card')->where('user_id', $user_id)->find();
                                if(is_null($ss)){
                                    $value = array('status'=>400,'mess'=>'请先绑定微信','data'=>array('status'=>400));
                                    return json($value);
                                }
                                else{
                                    $qrcode = $ss['qrcode'];
                                    $telephone = $ss['telephone'];
                                    $name = $ss['name'];
                                    $card_number = '';
                                    $bank_name = '';
                                }
                            }
                            else if($post['index']==3){
                                $ss = Db::name('bank_card')->where('user_id', $user_id)->find();
                                if(is_null($ss)){
                                    $value = array('status'=>400,'mess'=>'请先绑定银行卡','data'=>array('status'=>400));
                                    return json($value);
                                }
                                else{
                                    $qrcode = '';
                                    $telephone = $ss['telephone'];
                                    $name = $ss['name'];
                                    $card_number = $ss['card_number'];
                                    $bank_name = $ss['bank_name'];
                                }
                            }
                            
                            Db::startTrans();
                            try{
                                $res = Db::name('wallet')->where('user_id', $user_id)->setDec('point_ticket', $post['num']);
                                if(!$res)throw new Exception('提现申请失败');
                                
                                 $detail = [
                                    'de_type' => 2,
                                    'zc_type' => 130,
                                    'before_price'=> $wallet_info['point_ticket'],
                                    'price' => $post['num'],
                                    'after_price'=> $wallet_info['point_ticket']-$post['num'],
                                    'user_id' => $user_id,
                                    'wat_id' => $wallet_info['id'],
                                    'time' => time()
                                 ];
                                $res = $this->addDetail($detail);
//                                 $res = Db::name('detail')->insert($detail);
                                 if(!$res)throw new Exception('提现申请失败');
                                 
                                 $insertData = [
                                    'address' => '',
                                    'num' => isset($post['num']) ? $post['num'] : 0,
                                    'qrcode' => $qrcode,
                                    'name' => $name,
                                    'bank_name' => $bank_name,
                                    'card_number' => $card_number,
                                    'phone' => $telephone,
                                    'addtime' => time(),
                                    'type'=>$post['index']
                                 ];
                                 $res = Db::name('fuel_withdraw_way')->insert($insertData);
                                 if(!$res)throw new Exception('提现申请失败');
                                 
                                $value = array('status'=>200,'mess'=>'提现申请成功！','data'=>array('status'=>200));
                                Db::commit();
                            }
                            catch(Exception $e){
                                $value = array('status'=>400,'mess'=>'提现申请失败！','data'=>array('status'=>400));
                                Db::rollback();
                            }
                        }
                    }
                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function rechargeBank(){
        $bank = Db::name('bank_card')->where('user_id', 1)->find();
        
        $value = array('status'=>200,'mess'=>'获取成功','data'=>$bank);
        return json($value);
    }
    
    public function transactionUsdtDetail(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    // $user_id = 8243;

                    $info = Db::name('wine_usdt_account_generated')->where('user_id', $user_id)->field('address')->where('status', 1)->find();
                    if(!is_null($info) && $info['address']){
                        $address = $info['address'];
                        // $address = 'TRt6MrAPR3wHNocdpDjF3n9sBFPPRnUR1D';
                        $contract_address = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
                        $apiurl = 'https://api.trongrid.io/v1/accounts/'.$address.'/transactions/trc20?only_confirmed=true&limit=20&contract_address='.$contract_address.'&order_by=block_timestamp,desc';
                        
                        $data = json_decode(file_get_contents($apiurl), true);
                        
                        if(isset($data['success']) && $data['success']==true){
                            $data = $data['data'];
                            $time = time();
                            
                            if(count($data) > 0){
                                $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
                                $wallet_id = $wallet_info['id'];
                                $usdt_to_fuel = Db::name('config')->where('ename', 'usdt_to_fuel')->value('value');
                                for($i=0; $i<count($data); $i++){
                                    $transaction_id = $data[$i]['transaction_id']; // 交易唯一值
                                    $to = $data[$i]['to']; // 客户地址
                                    $symbol = $data[$i]['token_info']['symbol']; //USDT类型
                                    $value = $data[$i]['value'] / pow(10, $data[$i]['token_info']['decimals']); // 金额
                                    
                                    // 举例这个。to=自己  symbol=USDT   那么就可以上分了
                                    if($to==$address && $symbol=='USDT'){
                                        $count = Db::name('wine_usdt_account_recharge')->where('transaction_id', $transaction_id)->count();
                                        if($count == 0){
                                            Db::startTrans();
                                            try{
                                                $transactionData = [
                                                    'transaction_id'=>$transaction_id,
                                                    'symbol'=>$symbol,
                                                    'amount'=>$value,
                                                    'addtime'=>$time,
                                                    'user_id'=>$user_id
                                                ];
                                                $res = Db::name('wine_usdt_account_recharge')->insert($transactionData);
                                                if(!$res)throw new Exception('失败');
                                                
                                                $res = Db::name('wallet')->where('user_id', $user_id)->setInc('price', $value*$usdt_to_fuel);
                                                if(!$res)throw new Exception('失败');
                                                
                                                $detail = [
                                                    'de_type' => 1,
                                                    'sr_type' => 100,
                                                    'before_price'=> $wallet_info['price'],
                                                    'price' => $value*$usdt_to_fuel,
                                                    'after_price'=> $wallet_info['price']+($value*$usdt_to_fuel),
                                                    'user_id' => $user_id,
                                                    'wat_id' => $wallet_id,
                                                    'time' => $time
                                                ];

                                                $res = $this->addDetail($detail);
//                                                $res = Db::name('detail')->insert($detail);
                                                if(!$res)throw new Exception('失败');
                                                
                                                Db::commit();
                                            }
                                            catch(Exception $e){
                                                Db::rollback();
                                            }
                                        }
                                    }
                                    
                                }
                            }
                        }
                    }

                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        // return json($value);
    }
    
    public function withdrawCommission(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $post = input('post.');
                    $num = $post['num'];
                    $memberInfo = Db::name('member')->where('id', $user_id)->find();
                    
                    $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
    
                    if($num<100 || $num%100!=0){
                        $value = array('status'=>400,'mess'=>'请输入100的倍数','data'=>array('status'=>400));
                        return json($value);
                    }
                    
                    if($num<0 || $num>$wallet_info['point_ticket']){
                        $value = array('status'=>400,'mess'=>'你输入的提现数量有误','data'=>array('status'=>400));
                    }
                    else{
                        Db::startTrans();
                        try{
                            $res = Db::name('wallet')->where('user_id', $user_id)->dec('point_ticket', $num)->update();
                            if(!$res)throw new Exception('扣除失败4');
                            
                             $detail_commission = [
                                'de_type' => 2,
                                'zc_type' => 120,
                                'before_price'=> $wallet_info['point_ticket'],
                                'price' => $num,
                                'after_price'=> $wallet_info['point_ticket']-$num,
                                'user_id' => $user_id,
                                'wat_id' => $wallet_info['id'],
                                'time' => time()
                             ];

                            $res = $this->addDetail($detail_commission);
                             if(!$res)throw new Exception('扣除失败1');
                            
                            $value = array('status'=>200,'mess'=>'提现成功！','data'=>array('status'=>200));
                            Db::commit();
                        }
                        catch(Exception $e){
                            $value = array('status'=>400,'mess'=>'提现失败', 'data'=>array('status'=>400));
                            Db::rollback();
                        }
                    }
                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function wineDuihuan(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];

                    $list = Db::name('wine_to_inkind')->where('user_id', $user_id)->order('addtime desc')->select();

                    foreach ($list as $k=>$v)
                    {
                        $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                        $list[$k]['status'] = $v['status']==0 ? '待发货' : '已发货';
                    }

                    $value = array('status'=>200,'mess'=>'获取成功！','data'=>$list);
                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    // 激活保证金
    public function reg_enable(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];

                    $amount = Db::name('config')->where('ename', 'enable_deposit_amount')->value('value');
                    $info = Db::name('reg_enable_amount')->where('user_id', $user_id)->where('status', 0)->find();
                    if(is_null($info)){
                        $insertData = [
                            'user_id'=>$user_id,
                            'amount'=>$amount,
                            'addtime'=>time(),
                            'odd'=>uniqid()
                        ];
                        
                        $res = Db::name('reg_enable_amount')->insert($insertData);
                        if(!$res){
                            $info = [];
                        }
                        else{
                            $info = $insertData;
                            $info['status'] = 0;
                            $info['pay_type'] = 0;
                        }
                    }
                    else{
                        $res = Db::name('reg_enable_amount')->where('id', $info['id'])->where('status', 0)->update([
                            'amount' => $amount,
                            'updatetime'=>time()
                        ]);
                        if($res){
                            $info['amount'] = $amount;
                        }
                    }

                    $value = array('status'=>200,'mess'=>'激活中...！','data'=>$info);
                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    // 发送短信
    public function sendSMS($phone = ''){
        if(request()->isPost()) {
            $url = 'http://39.103.164.21:7862/sms';
            if(!$phone){
                $value = array('status'=>400,'mess'=>'手机号码不存在','data'=>array('status'=>400));
            }
            else{
                $data = [];

                $rand_num = rand (0, 9).rand (0, 9).rand (0, 9).rand (0, 9).rand (0, 9).rand (0, 9);


                $url  = $url.'?action=send&account=221801&password=jFYcta&mobile='.$phone.'&content='.urlencode('【玛特优客】您的验证码是：').$rand_num.'&extno=10690495&rt=json';
                $res = json_decode(file_get_contents($url),true);

                if($res['status'] == 0 && $res['list'][0]['result'] == 0){
//                if(true){
                    $value = array('status'=>200,'mess'=>'发送成功！');

                    Db::name('sms')->insert([
                        'phone'=>$phone,
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

    public function smstest(){
        $cred = new Credential("AKIDQpAQafdUErNm4EHC0e7SehdQ5c0GMK9q", "brzynU2QflhVHtNiuyrY4QdjgUAh6MTR");
        $sms = new TecentSms($cred,"ap-guangzhou");
        $sms->send(133888866666,666);
    }
    // 权益卡
    public function rightsCard()
    {
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];

                    $list = Db::name('vip_rights_card')->alias('vrc')
                                    ->field('vrc.*,m.user_name buy_username,mm.user_name use_username')
                                    ->join('member m', 'm.id=vrc.user_id', 'left')
                                    ->join('member mm', 'mm.id=vrc.use_user_id', 'left')
                                    ->where('vrc.user_id', $user_id)
                                    ->whereOr('vrc.use_user_id', $user_id)
                                    ->order('addtime desc')
                                    ->select();
                    foreach ($list as $k=>$v)
                    {
                        $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                        if($v['addtime'] == $v['uptime'])
                        {
                            $list[$k]['uptime'] = 0;
                        }
                        else
                        {
                            $list[$k]['uptime'] = date('Y-m-d H:i:s', $v['uptime']);
                        }
                    }

                    $is_vip = Db::name('member')->where('id', $user_id)->value('is_vip');
                    $value = array('status'=>200,'mess'=>'获取成功！','data'=>['list'=>$list, 'is_vip_user'=>$is_vip]);
                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    // 激活权益卡
    public function getTokenData()
    {
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];

                    $card_no = input('post.card_no');
                    $state = true;

                    $info = Db::name('vip_rights_card')->where('card_no', $card_no)->find();
                    if(is_null($info))
                    {
                        $state = false;
                        $value = array('status'=>400,'mess'=>'权益卡不存在','data'=>array('status'=>400));
                    }

                    if($info['token'])
                    {
                        $state = false;
                        $value = array('status'=>400,'mess'=>'权益卡已激活,请勿重新激活','data'=>array('status'=>400));
                    }

                    if($info['use'] == 1)
                    {
                        $state = false;
                        $value = array('status'=>400,'mess'=>'已使用的权益卡不能操作','data'=>array('status'=>400));
                    }

                    if ($state == true)
                    {
                        $token_data = 'TOKEN'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

                        $res = Db::name('vip_rights_card')
                                    ->where('card_no', $card_no)
                                    ->where('use', 0)
                                    ->whereNull('token')
                                    ->update(['token'=>$token_data, 'activetime'=>time()]);
                        if($res)
                        {
                            $value = array('status'=>200,'mess'=>'激活成功！');
                        }
                        else
                        {
                            $value = array('status'=>400,'mess'=>'激活失败');
                        }
                    }

                }
                else{
                    $value = $result;
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    // 绑定权益卡
    public function bindRightsCard()
    {
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $userInfo = Db::name('member')->where('id', $user_id)->find();
                    $token = input('post.token_code');
//                    $json_profile = input('post.profile');
//                    $profile = json_decode($json_profile, true);
                    $state = true;

//                    $count = Db::name('vip_rights_card')->where('phone', $profile['phone'])->count();
//                    if($count>0){
//                        $state = false;
//                        $value = array('status'=>400,'mess'=>'该手机号已绑定过权益卡');
//                    }

                    $info = Db::name('vip_rights_card')->where('token', $token)->find();
                    if(is_null($info))
                    {
                        $state = false;
                        $value = array('status'=>400,'mess'=>'该权益卡不存在');
                    }

                    if($info['use'] == 1)
                    {
                        $state = false;
                        $value = array('status'=>400,'mess'=>'该权益卡已被使用');
                    }

                    if($state == true)
                    {
                        // 开始事务
                        Db::startTrans();
                        try{
                            if($userInfo['is_vip'] == 1)
                            {
                                throw new Exception('你已经是权益卡用户，无需再绑定权益卡');
                            }

                            // 开始绑定权益卡
                            $data['use_user_id'] = $user_id;
                            $data['use'] = 1;
//                            $data['phone'] = $profile['phone'];
//                            $data['name'] = $profile['name'];
//                            $data['idcard'] = $profile['idcard'];
                            $data['uptime'] = time();
                            $res = Db::name('vip_rights_card')->where('token', $token)->where('use', 0)->update($data);
                            if(!$res)
                            {
                                throw new Exception('绑定权益卡失败');
                            }

                            $order_id = $info['order_id'];
//                            $res = Db::name('order')->where('id', $order_id)->update([
//                                'contacts'=>$profile['name'],
//                                'telephone'=>$profile['phone'],
//                                'profile'=>$json_profile
//                            ]);
//                            if(!$res)
//                            {
//                                throw new Exception('订单更新失败');
//                            }

                            if($userInfo['is_vip'] == 0){
                                $update = Db::name('member')->where('id',$user_id)->update(['is_vip'=>1]);

                                if($update){
                                    // 代理等级验证升级...
                                    uplevel_agent($user_id, $order_id);
                                }
                                else
                                {
                                    throw new Exception('您绑定权益卡失败');
                                }
                            }
                            // 提交事务
                            Db::commit();
                            $value = array('status'=>200,'mess'=>'绑定权益卡成功');
                        }
                        catch (\Exception $e)
                        {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status'=>400,'mess'=>$e->getMessage());
                        }
                    }
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    // 验证权益卡token是否存在
    public function checkToken()
    {
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $token = input('post.token_code');
                    $state = true;
                    if(empty($token))
                    {
                        $state = false;
                        $value = array('status'=>400,'mess'=>'激活码不能为空');
                    }
                    $info = Db::name('vip_rights_card')->where('token', $token)->find();
                    if(is_null($info))
                    {
                        $state = false;
                        $value = array('status'=>400,'mess'=>'该权益卡不存在');
                    }

                    if($info['use'] == 1)
                    {
                        $state = false;
                        $value = array('status'=>400,'mess'=>'该权益卡已被使用');
                    }

                    if($state == true)
                    {
                        $value = array('status'=>200,'mess'=>'权益卡正常');
                    }
                }
            }
        }
        else
        {
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    //读取用户资料
    public function readprofile(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    bandPid($user_id,(int)trim(input('post.shareid')));
                    $members = Db::name('member')->where('id',$user_id)->field('id,user_name,phone,true_name,idcard,password,headimgurl,integral,sex,birth,email,oauth,agent_type,false_agent_type,login_code, emergency_phone, emergency_name, vip_time, reg_enable, qiandan, team_id, jiedian_team_id, checked, zenren_frozen,team_id,jiedian_team_id, nick_name')->find();
                    $members['enable_deposit_amount'] = Db::name('config')->where('ename', 'enable_deposit_amount')->value('value');
// var_dump($member['agent_type']);exit;
                    if($members){
                        $wallets = Db::name('wallet')->where('user_id',$user_id)->field('price, credit_value, buy_ticket, total_stock, brand, manager_reward, fuel, klg, commission, point, zkj, point_ticket, point_credit, ticket_burn')->find();
                        $members['price'] = $wallets['price'];
                        $members['credit_value'] = $wallets['credit_value'];
                        $members['buy_ticket'] = $wallets['buy_ticket'];
                        $members['total_stock'] = $wallets['total_stock'];
                        $members['manager_reward'] = $wallets['manager_reward'];
                        $members['commission'] = $wallets['commission'];
                        $members['phone'] = substr($members['phone'], 0, 3).'****'.substr($members['phone'], 7);
                        $members['klg'] = $wallets['klg'];
                        $members['brand'] = $wallets['brand'];
                        $members['price'] = $wallets['price'];
                        $members['point'] = $wallets['point'];
                        $members['point_ticket'] = $wallets['point_ticket'];
                        $members['point_credit'] = $wallets['point_credit'];
                        $members['ticket_burn'] = $wallets['ticket_burn'];
                        $members['zkj'] = $wallets['zkj'];
                        $coupon_num = Db::name('member_coupon')
                            ->alias('a')
                            ->join('sp_coupon b','a.coupon_id = b.id','INNER')
                            ->join('sp_shops c','a.shop_id = c.id','INNER') 
                            ->where('a.user_id',$user_id)
                            ->where('a.is_sy',0)
                            ->where('b.onsale',1)
                            ->where('c.open_status',1)
                            ->where('start_time','<',time())
                            ->where('end_time','>',time())
                            ->count();

                        $members['coupon_num'] = $coupon_num;
                        $collgoods_count = Db::name('coll_goods')->where('user_id',$user_id)->count();
                        $collshops_count = Db::name('coll_shops')->where('user_id',$user_id)->count();
//                        $members['coll_num'] = $collgoods_count+$collshops_count;
                        $members['coll_num'] = $collgoods_count;//暂时去除收藏的店铺
                        $webconfig = $this->webconfig;
                        // if($webconfig)
                        // if($members['headimgurl'] && !$members['oauth']){
                        //     $members['headimgurl'] = $webconfig['weburl'].'/'.$members['headimgurl'];
                        // }

						if(strpos($members['headimgurl'],'http') !== false){
							$members['headimgurl'] = $members['headimgurl'];
						}else{
							if(strpos($members['headimgurl'],'uploads/') !== false){
							    $members['headimgurl'] = $members['headimgurl'] ? $this->webconfig['weburl']."/".$members['headimgurl'] : "";
							}else{
								$domain = config('tengxunyun')['cos_domain'];
								$members['headimgurl'] = $members['headimgurl'] ? $domain."/".$members['cover'] : "";
							}
						}

						$memberLevelInfo = $this->getMemberLevelInfo($members['integral']);
                        $members['rank'] = $memberLevelInfo['sort'];
                        $members['rank_name'] = $memberLevelInfo['level_name'];
                        $nextMemberLevelInfo = Db::name('member_level')->where('sort','gt',$memberLevelInfo['sort'])->order('sort ASC')->fetchSql()->find();
                        //此处分母为null 导致 结果为INF 暂时不去研究这段代码背后的逻辑
                        $members['rank_percent'] = round($members['integral']/$nextMemberLevelInfo['points_min'],2)*100;
                        $members['next_rank_integral'] = $nextMemberLevelInfo['points_min'];
                        $members['privilege'] = array(
                            'returns' => 1,
                            'customerService' =>1,
                            'freeEvaluation' =>1,
                            'identification' => 1,
                            'holidayGift' => 1,
                            'preemptive' =>1,
                            'offlineActivity' =>1
                        );
                        
                        if($members['phone'] && $members['password']){
                            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
                            if(!$applyinfos){
                                $members['rz_zt'] = 1;
                            }else{
                                $members['rz_zt'] = 2;
                            }
                        }else{
                            $members['rz_zt'] = 4;
                        }
						//平台消息
						$pt_msg = Db::name('notification')->where('status',1)->count();
						//客服消息
						$kf_msg = Db::name('chat_message')->where('fromid',input('post.token'))->whereOr('toid',input('post.token'))->count();
                        
						$members['msg_num'] = $pt_msg+$kf_msg;
						
						
						
						//订单数 1:待支付 2:待发货 3:待收货 4:待评价 5:退款/售后 6:全部
						
						//待付款
						$where1 = array('a.user_id'=>$user_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
						$sort1 = array('a.addtime'=>'desc','a.id'=>'desc');
						$num1 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where1)->order($sort1)->count();
						$members['pay_num'] = $num1;
						//待发货
						$where2 = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
						$sort2 = array('a.pay_time'=>'desc','a.id'=>'desc');
						$num2 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where2)->order($sort2)->count();
						$members['send_num'] = $num2;
						//待收货
						$where3 = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0,'a.is_show'=>1);
						$sort3 = array('a.fh_time'=>'desc','a.id'=>'desc');
						$num3 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where3)->order($sort3)->count();
						$members['shou_num'] = $num3;
						//待评价
						$where4 = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>0,'a.is_show'=>1);
						$sort4 = array('a.coll_time'=>'desc','a.id'=>'desc');
						$num4 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where4)->order($sort4)->count();
						$members['ping_num'] = $num4;
						
						//退换货
                        $where5 = array('user_id'=>$user_id,'apply_status'=>1);
                        //$where5 = array('user_id'=>$user_id);
						$sort5 = array('apply_time'=>'desc');
						$num5 = Db::name('th_apply')->where($where5)->order($sort5)->count();
						$num6 = Db::name('th_apply')->where(array('user_id'=>$user_id,'apply_status'=>0))->order($sort5)->count();
						
						
						//评价
						/*
						$where0 = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>1,'a.is_show'=>1);
						$sort0 = array('a.coll_time'=>'desc','a.id'=>'desc');
						$num0 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where0)->order($sort0)->count();
						$members['myping_num'] = $num0+$num4;
						*/
					   //$members['huan_num'] = $num5;
						$members['huan_num'] = $num5+$num6;
						
						//购物车数量
						$shopcar_num = Db::name('cart')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.num,a.shop_id,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->order('a.add_time desc')->count();
                        
						$members['shopcar_num']  = $shopcar_num;
						
						//我的会员是否开启
						$sta = $this->getConfigInfo(168);
						
						$members['user_center_state'] = $sta['value'] == '开启' ? 1 : 0;
						
                        unset($members['password']);

                        // 课程聊天消息未读数
                        $school_message_count = Db::name('school_message')->where('to_user_id', $user_id)->where('isview', 0)->where('kind', 'chat')->count();
                        // 社群未读数
                        $community_message_count = Db::name('community_message')->where('to_user_id', $user_id)->where('isview', 0)->count();
                        $members['unread_count'] = $school_message_count+$community_message_count;

                        $members['buy_count'] = Db::name('wine_order_buyer')->where('buy_id', $user_id)->where('delete', 0)->where('status', 1)->where('pay_status', 0)->count();
                        // $members['sale_count'] = Db::name('wine_order_saler')->where('sale_id', $user_id)->where('delete', 0)->where('status', 'in', [0,1])->count();
                        $members['sale_count'] = Db::name('wine_order_buyer')->where('sale_id', $user_id)->where('delete', 0)->where('pay_status', 1)->where('status', 1)->count();
                        // var_dump(Db::name('wine_order_saler')->where('sale_id', $user_id)->where('delete', 0)->where('status', 'in', [0, 1])->select());exit;
                        if($members['false_agent_type'] > $members['agent_type']){
                            $members['agent_type'] = $members['false_agent_type'];
                        }
                        $level_name = Db::name('wine_level')->where('id', $members['agent_type'])->value('level_name');
                        
                        $members['level_name'] = $level_name ? $level_name : '经销商';
                        
                        $wine_order_buyer_count = Db::name('wine_order_buyer')->where('buy_id', $members['id'])->count();
                        if($wine_order_buyer_count==0){
                            $members['new'] = 1;
                        }
                        else{
                            $members['new'] = 0;
                        }
                        
                        if($members['vip_time'] > time()){
                            $members['vip'] = 1;
                        }
                        else{
                            $members['vip'] = 0;
                        }
                        
                        $day3 = strtotime(date('Y-m-d', strtotime('-3 day')));
                        $today = strtotime('today')-1;
                        $count = Db::name('wine_order_record')->where('addtime', '>=', $day3)->where('addtime', '<=', $today)->where('buy_id', $user_id)->count();
                        $applyVip = Db::name('wine_apply_vip')->where('date', date('Y-m-d'))->where('user_id', $user_id)->count();
                        if($count >=6 && $applyVip==0 && $members['vip']==0){
                            $members['apply_vip'] = 1;
                        }
                        else{
                            $members['apply_vip'] = 0;
                        }

                        $bank_list = Db::name('bank_name')->column('name');
                        $members['klg_to_legoufen'] = Db::name('config')->where('ename', 'klg_to_legoufen')->value('value');
                        
                        $config_commission = Db::name('config')->where('ename', 'in', ['commission_to_fuel', 'commission_to_point'])->column('ename, value');
                        $members['commission_text'] = '佣金提现规则：'.$config_commission['commission_to_fuel'].'%进入余额钱包，'.$config_commission['commission_to_point'].'%进入积分商城用于积分商城的消费';
                        
                        $members['bank'] = Db::name('bank_card')->where('user_id', $user_id)->find();
                        $members['wx'] = Db::name('wx_card')->where('user_id', $user_id)->find();
                        $members['zfb'] = Db::name('zfb_card')->where('user_id', $user_id)->find();
                        // $total_team_id = array_merge($members['jiedian_team_id'], $members['team_id']);
                        $total_team_id = Db::name('member')
                         ->where('team_id', 'like', '%,'.$user_id)->whereOr('team_id', 'like', '%,'.$user_id.',%')
                         ->whereOr('jiedian_team_id', 'like', '%,'.$user_id)->whereOr('jiedian_team_id', 'like', '%,'.$user_id.',%')
                         ->column('id');
                        // var_dump($members);exit;
                        // 未付款
                        $members['unpaid'] = Db::name('wine_order_buyer')->where('buy_id', 'in', $total_team_id)->where('pay_status', 0)->where('status', 1)->where('delete', 0)->count();
                        
                        // 待确认
                        $members['pendingConfirm'] = Db::name('wine_order_buyer')->where('sale_id', 'in', $total_team_id)->where('pay_status', 1)->where('status', 1)->where('delete', 0)->count();
                        
                        // 未寄售
                        // $members['unConsignment'] = Db::name('wine_order_saler')->where('sale_id', 'in', $total_team_id)->where('status', 0)->where('delete', 0)->count();
                        $members['unConsignment'] = Db::name('wine_order_buyer')->where('buy_id', 'in', $total_team_id)->where('status', 2)->where('delete', 0)->where('day', 0)->count();
                        
                        // 今日总业绩
                        $members['performance'] = Db::name('wine_order_buyer')->where('buy_id', 'in', $total_team_id)->where('pay_status', 1)->where('delete', 0)->where('addtime', '>=', strtotime('today'))->sum('buy_amount');

                        $value = array('status'=>200,'mess'=>'获取用户资料成功！','data'=>$members, 'bank_list'=>$bank_list);
                    }else{
                        $value = array('status'=>400,'mess'=>'信息有误,获取失败','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'请先登录','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        $res = unserialize(str_replace(array('NAN;','INF;'),'0;',serialize($value)));
        return json($res);
    }
    
    // 未付款
    public function unpaid(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $input = input();
                    $user_id = $result['user_id'];
                    
                    $total_team_id = Db::name('member')
                     ->where('team_id', 'like', '%,'.$user_id)->whereOr('team_id', 'like', '%,'.$user_id.',%')
                     ->whereOr('jiedian_team_id', 'like', '%,'.$user_id)->whereOr('jiedian_team_id', 'like', '%,'.$user_id.',%')
                     ->column('id');
                    $list = Db::name('wine_order_buyer')->alias('wob')
                            ->join('member m', 'wob.buy_id = m.id', 'left')
                            ->join('wine_deal_area wda', 'wda.id = wob.wine_deal_area_id', 'left')
                            ->field('wob.*, m.true_name, m.user_name, m.phone, wda.desc, wob.addtime')
                            ->where('wob.buy_id', 'in', $total_team_id)->where('wob.pay_status', 0)->where('wob.status', 1)->where('wob.delete', 0)->select();
                    
                    foreach($list as &$v){
                        $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                    }
                    
                    $value = array('status'=>200,'mess'=>'获取成功！','data'=>$list);
                }else{
                    // $value = $result;
                    $value = array('status'=>300,'mess'=>'身份验证失败.','data'=>array('status'=>300));
                }
            }else{
                $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    // 待确认
    public function pendingConfirm(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $input = input();
                    $user_id = $result['user_id'];
                    
                    $total_team_id = Db::name('member')
                     ->where('team_id', 'like', '%,'.$user_id)->whereOr('team_id', 'like', '%,'.$user_id.',%')
                     ->whereOr('jiedian_team_id', 'like', '%,'.$user_id)->whereOr('jiedian_team_id', 'like', '%,'.$user_id.',%')
                     ->column('id');
                    $list = Db::name('wine_order_buyer')->alias('wob')
                            ->join('member m', 'wob.sale_id = m.id', 'left')
                            ->join('wine_deal_area wda', 'wda.id = wob.wine_deal_area_id', 'left')
                            ->field('wob.*, m.true_name, m.user_name, m.phone, wda.desc, wob.paytime')
                            ->where('wob.sale_id', 'in', $total_team_id)->where('wob.pay_status', 1)->where('wob.status', 1)->where('wob.delete', 0)->select();
                    
                    foreach($list as &$v){
                        $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                        $v['paytime'] = date('Y-m-d H:i:s', $v['paytime']);
                    }
                    
                    $value = array('status'=>200,'mess'=>'获取成功！','data'=>$list);
                }else{
                    // $value = $result;
                    $value = array('status'=>300,'mess'=>'身份验证失败.','data'=>array('status'=>300));
                }
            }else{
                $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    // 未寄售
    public function unConsignment(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $input = input();
                    $user_id = $result['user_id'];
                    
                    $total_team_id = Db::name('member')
                     ->where('team_id', 'like', '%,'.$user_id)->whereOr('team_id', 'like', '%,'.$user_id.',%')
                     ->whereOr('jiedian_team_id', 'like', '%,'.$user_id)->whereOr('jiedian_team_id', 'like', '%,'.$user_id.',%')
                     ->column('id');
                    // $list = Db::name('wine_order_saler')->alias('wos')
                    //         ->join('member m', 'wos.sale_id = m.id', 'left')
                    //         ->join('wine_order_buyer wob', 'wob.odd = wos.odd', 'left')
                    //         ->join('wine_deal_area wda', 'wda.id = wos.wine_deal_area_id', 'left')
                    //         ->field('wos.*, m.true_name, m.user_name, m.phone, wda.desc, wob.confirm_exchange')
                    //         ->where('wos.sale_id', 'in', $total_team_id)->where('wos.status', 0)->where('wos.delete', 0)->select();
                    $list = Db::name('wine_order_buyer')->alias('wob')
                            ->join('member m', 'wob.buy_id = m.id', 'left')
                            ->join('wine_deal_area wda', 'wda.id = wob.wine_deal_area_id', 'left')
                            ->field('wob.*, m.true_name, m.user_name, m.phone, wda.desc')
                            ->where('wob.buy_id', 'in', $total_team_id)->where('wob.status', 2)->where('wob.delete', 0)->where('wob.day', 0)->select();
                    
                    foreach($list as &$v){
                        $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                        $v['confirm_exchange'] = date('Y-m-d H:i:s', $v['confirm_exchange']);
                    }
                    
                    $value = array('status'=>200,'mess'=>'获取成功！','data'=>$list);
                }else{
                    // $value = $result;
                    $value = array('status'=>300,'mess'=>'身份验证失败.','data'=>array('status'=>300));
                }
            }else{
                $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    // 今日总业绩
    public function performance(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $input = input();
                    $user_id = $result['user_id'];
                    
                    $total_team_id = Db::name('member')
                     ->where('team_id', 'like', '%,'.$user_id)->whereOr('team_id', 'like', '%,'.$user_id.',%')
                     ->whereOr('jiedian_team_id', 'like', '%,'.$user_id)->whereOr('jiedian_team_id', 'like', '%,'.$user_id.',%')
                     ->column('id');
        
                    $toyeji = Db::name('wine_order_buyer')->alias('wob')
                            ->join('member m', 'wob.buy_id = m.id', 'left')
                            ->field('wob.*, m.true_name, m.user_name, m.phone')
                            ->where('wob.buy_id', 'in', $total_team_id)->where('wob.pay_status', 1)->sum('buy_amount');
        
                    // $list = Db::name('wine_order_buyer')->alias('wob')
                    //         ->join('member m', 'wob.buy_id = m.id', 'left')
                    //         ->field('wob.*, m.true_name, m.user_name, m.phone')
                    //         ->where('wob.buy_id', 'in', $total_team_id)->where('wob.pay_status', 1)->where('wob.delete', 0)->where('wob.addtime', '>=', strtotime('today'))->select();
                    $list = Db::name('wine_order_buyer')->alias('wob')
                            ->join('member m', 'wob.buy_id = m.id', 'left')
                            ->field('wob.*, m.true_name, m.user_name, m.phone, sum(buy_amount) total_buy_amount, wob.date')
                            ->where('wob.buy_id', 'in', $total_team_id)->where('wob.pay_status', 1)->where('date', '<>', '')->order('wob.date desc')->group('wob.date')->select();
                    foreach($list as &$v){
                        $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                    }
                    
                    $value = array('status'=>200,'mess'=>'获取成功！','data'=>$list, 'toyeji'=>$toyeji);
                }else{
                    // $value = $result;
                    $value = array('status'=>300,'mess'=>'身份验证失败.','data'=>array('status'=>300));
                }
            }else{
                $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //设置个人基本资料
    public function editprofile(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    // $yzresult = $this->validate($data,'Member.edit');
                    // if(true !== $yzresult){
                    if(false){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
//                        $repic = Db::name('member')->where('id',$user_id)->value('headimgurl');
//
//                        $file = request()->file('image');
//                        if($file){
//
//                            $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'member_pic');
//                            if($info){
//                                $original = 'uploads/member_pic/'.$info->getSaveName();
//                                //$headimgurl = $original;   //图片存储路径返回查看测试
//                                $image = \think\Image::open('./'.$original);
//                                $image->thumb(300, 300)->save('./'.$original,null,90);
//                                $headimgurl = $original;
//                            }else{
//                                $value = array('status'=>400,'mess'=>$file->getError(),'data'=>array('status'=>400));
//                                return json($value);
//                            }
//                        }
                        
                        $datainfo = array();
                        if(!empty($data['user_name'])){
                            $datainfo['user_name'] = $data['user_name'];
                        }
                        
                        if(!empty($data['sex'])){
                            $datainfo['sex'] = $data['sex'];
                        }
                        
                        if(!empty($data['birth'])){
                            $datainfo['birth'] = strtotime($data['birth']);
                        }
                        
                        if(!empty($data['email'])){
                            $datainfo['email'] = $data['email'];
                        }
                        
                        if(!empty($data['headimgurl'])){
                            $datainfo['headimgurl'] = $data['headimgurl'];
                        }

                        if(!empty($data['emergency_name'])){
                            $datainfo['emergency_name'] = $data['emergency_name'];
                        }
                        
                        if(!empty($data['emergency_phone'])){
                            $datainfo['emergency_phone'] = $data['emergency_phone'];
                        }
                    
                        if(!empty($data['true_name'])){
                            $datainfo['true_name'] = $data['true_name'];
                        }
                        
                        if(!empty($data['idcard'])){
                            $datainfo['idcard'] = $data['idcard'];
                        }
                        
                        $datainfo['id'] = $user_id;
                        // 启动事务
                        Db::startTrans();
                        try{
                            // $arr = [
                            //     'idcard'=>$data['idcard'],
                            //     'name'=>$data['true_name'],
                            //     'showapi_appid'=>'1058276'
                            // ];
                            // ksort($arr);
                            // $str = '';
                            // foreach ($arr as $k=>$v){
                            //     $str = $k.$v;
                            // }
                            // $showapi_sign = md5($str.'72894e48e5be4fd0a6a964bc56bdaa28');
                            // $arr['showapi_sign'] = $showapi_sign;
                            // $str = '';
                            // foreach ($arr as $k=>$v){
                            //     $str .= $k.'='.$v.'&';
                            // }
                            // $str = trim($str, '&');
   
                            // $apiurl = 'https://route.showapi.com/1072-1?'.http_build_query($arr);
                            if(isset($data['idcard']) || isset($data['true_name'])){
                                $info = Db::name('member')->where('id', $user_id)->find();
                                if($info['idcard'] || $info['true_name']){
                                    unset($data['idcard']);
                                    unset($data['true_name']);
                                }
                                else{
                                    $arr = [
                                        'idcard'=>$data['idcard'],
                                        'name'=>$data['true_name'],
                                        'showapi_appid'=>'1058276.0',
                                        'showapi_timestamp'=>date('YmdHis')
                                    ];
                                    ksort($arr);
                                    $str = '';
                                    foreach ($arr as $k=>$v){
                                        $str .= $k.$v;
                                    }
                                    $arr['showapi_sign'] = md5($str.'72894e48e5be4fd0a6a964bc56bdaa28');
                                    $res = json_decode(file_get_contents('https://route.showapi.com/1072-1?'.http_build_query($arr)), true);
                                    if(isset($res['showapi_res_body']['ret_code']) && $res['showapi_res_body']['ret_code']==0){
                                        
                                    }
                                    else{
                                        throw new Exception('身份不匹配');
                                    }
                                }
                            }
                            // var_dump($data);exit;
                            $res = Db::name('member')->update($datainfo);
                            if($res){
							    $count1 = Db::name('wx_card')->where('user_id', $user_id)->count();
							    $count2 = Db::name('zfb_card')->where('user_id', $user_id)->count();
							    $count3 = Db::name('bank_card')->where('user_id', $user_id)->count();
							    if($count1 || $count2 || $count3){
									$res = Db::name('member')->where('id', $user_id)->where('reg_enable_deposit_count', '>', 0)->update([
									    'reg_enable'=>1,
									    'reg_enable_deposit_count'=>0
									]);
									
									if($res){
									    // 升级
								        $this->wineGoodsUpgrade($orders['user_id']);
									}
							    }
                            }
//                            if(!empty($headimgurl)){
//                                if(!empty($repic) && file_exists('./'.$repic)){
//                                    @unlink('./'.$repic);
//                                }
//
//								//4完善信息（上传头像）
//								$num = $this->getIntegralRules(4);//获取积分
//								$this->addIntegral($user_id,$num,4);
//                            }
                            // 提交事务
                            Db::commit();
                            $value = array('status'=>200, 'mess'=>'设置个人基本资料成功', 'data'=>array('status'=>200),'info'=>$datainfo);
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status'=>400, 'mess'=>'设置个人基本资料失败', 'data'=>array('status'=>400));
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
    
    //上传用户头像
    /*public function update_head(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $file = request()->file('image');
                    if($file){
                        $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'member_pic');
                        if($info){
                            $original = 'uploads/member_pic/'.$info->getSaveName();
                            $image = \think\Image::open('./'.$original);
                            $image->thumb(160, 160)->save('./'.$original);
                            $headimgurl = Db::name('member')->where('id',$user_id)->value('headimgurl');
                            $count = Db::name('member')->update(array('headimgurl'=>$original,'id'=>$user_id));
                            if($count > 0){
                                if($headimgurl && file_exists('./'.$headimgurl)){
                                    @unlink('./'.$headimgurl);
                                }
                                $webconfig = $this->webconfig;
                                $value = array('status'=>200,'mess'=>'上传成功','data'=>array('headimgurl'=>$webconfig['weburl'].'/'.$original));
                            }else{
                                $value = array('status'=>400,'mess'=>'上传失败','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>$file->getError(),'data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'文件不存在','data'=>array('status'=>400));
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
    }*/
    
    
    //找回密码发送验证码
    public function findBackPwSms(){
        if(request()->isPost()){
            if(!input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate(0);
                if($result['status'] == 200){
                    if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                        $phone = input('post.phone');
                        $members = Db::name('member')->where('phone',$phone)->field('id')->find();
                        if($members){
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
                                        $value = array('status'=>400,'mess'=>'今天已超出最大请求次数','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            }
                             
                            $messtime = $dxpzres['messtime'];
                             
                            $zhpwd = Db::name('sms')->where('phone',$phone)->find();
                             
                            if($zhpwd){
                                $time = time();
                                if(floor($time-$zhpwd['qtime']) < $messtime){
                                    $value = array('status'=>400, 'mess'=>$messtime.'s内不能重复发送','data'=>array('status'=>400));
                                }else{
                                    $smscode = createSMSCode();
                                    $data['phone'] = $phone;
                                    $data['qtime'] = time();
                                    $data['smscode'] = $smscode;
                                    $data['id'] = $zhpwd['id'];
                                    $data['type'] = 2;
                                    
                                    $outputArr = sendSms($phone,$smscode);
                                    $outputArr = object_to_array($outputArr);
                                    if($outputArr['msg'] == 'OK'){
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            Db::name('sms')->update($data);
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
                                        $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                                    }
                                }
                            }else{
                                $smscode = createSMSCode();
                                $data['phone'] = $phone;
                                $data['qtime'] = time();
                                $data['smscode'] = $smscode;
                                $data['type'] = 1;

                                $outputArr = sendSms($phone,$smscode);
                                $outputArr = object_to_array($outputArr);
                                if($outputArr['msg'] == 'OK'){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('sms')->insert($data);
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
                                    $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                                }
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'手机号不存在','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'请填写正确的手机号码','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'已登录，发送验证码失败','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //找回密码
    public function findBackPwd(){
        if(request()->isPost()){
            if(!input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate(0);
                if($result['status'] == 200){
                    if(input('post.phone')){
                        if(input('post.code')){
                            if(input('post.password')){
                                // if(input('post.confirm_password')){
                                    $phone = input('post.phone');
                                    $code = input('post.code');
                                    $password = input('post.password');
                                    $confirm_password = input('post.confirm_password');
                                    if($password != $confirm_password){
                                        $value = array('status'=>400,'mess'=>'两次输入的密码不一致','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                    
                                    // dump($phone);
                                    if(preg_match("/^1[3456789]{1}\\d{9}$/", $phone)){
                                        $zhpwd = Db::name('sms')->where(['phone'=>$phone])->order('id desc')->find();
                                        // dump($zhpwd);die;
                                        if($zhpwd && $zhpwd['code'] == $code){
                                            // $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                                            $time = time();
                                            // if(floor($time-$zhpwd['qtime']) <= $vali_time['value']*60){
                                            if(true){
                                                $members = Db::name('member')->where('phone',$phone)->field('id,password')->find();
                                                if($members){
                                                    // if($confirm_password != $password){
                                                    //     $value = array('status'=>400,'mess'=>'确认密码不正确','data'=>array('status'=>400));
                                                    //     return json($value);
                                                    // }
                                    
                                                    if(!preg_match("/^[0-9a-zA-Z]{6,15}$/", $password)){
                                                        $value = array('status'=>400,'mess'=>'密码为6-15位数字、英文、下划线组成','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                    
                                                    // if(md5($password) == $members['password']){
                                                    //     $value = array('status'=>400,'mess'=>'新密码不能与旧密码相同','data'=>array('status'=>400));
                                                    //     return json($value);
                                                    // }
                                    
                                                    // 启动事务
                                                    Db::startTrans();
                                                    try{
                                                        Db::name('member')->update(array('password'=>md5($password),'id'=>$members['id']));
                                                        // Db::name('zhpwd')->delete($zhpwd['id']);
                                                        // 提交事务
                                                        Db::commit();
                                                        $value = array('status'=>200,'mess'=>'重置密码成功','data'=>array('status'=>200));
                                                    } catch (\Exception $e) {
                                                        // 回滚事务
                                                        Db::rollback();
                                                        $value = array('status'=>400,'mess'=>'重置密码失败','data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'手机号不存在！','data'=>array('status'=>400));
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
                                // }else{
                                //     $value = array('status'=>400,'mess'=>'缺少确认密码参数','data'=>array('status'=>400));
                                // }
                            }else{
                                $value = array('status'=>400,'mess'=>'缺少新密码参数','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少短信验证码参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少手机号参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'已登录，请求失败','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //修改密码
    public function editpwd(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    // echo $user_id;die;
                    $phone = Db::name('member')->where('id',$user_id)->value('phone');
                    $old_password = input('post.old_pwd');
                    $password = input('post.new_pwd');
                    $code = input('post.code');
                    $confirm_password = input('post.confirm_password');


                    // if(empty($phone)){
                    //     $value = array('status'=>400,'mess'=>'请输入手机号码！','data'=>array('status'=>400));
                    //     return json($value);
                    // }
//                    if(empty($code)){
//                        $value = array('status'=>400, 'mess'=>'请输入验证码','data'=>array('status'=>400));
//                        return json($value);
//                    }
                    if(empty($old_password)){
                        $value = array('status'=>400, 'mess'=>'请输入旧密码','data'=>array('status'=>400));
                        return json($value);
                    }
                    if(empty($password)){
                        $value = array('status'=>400, 'mess'=>'请输入新密码','data'=>array('status'=>400));
                        return json($value);
                    }
                   

                    

                    // $editPwdSms = Db::name('sms')->where(['phone'=>$phone,'type'=>1])->find();
                    // dump($editPwdSms);die;
                    // dump($code);
//                    if($editPwdSms && $editPwdSms['smscode'] == $code){
                    if (true){
                        $time = time();
                        $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
//                        if($time-$editPwdSms['qtime'] <= $vali_time['value']*60){
                        if (true){
                                $old_pwd = md5($old_password);
                                $member_password = Db::name('member')->where('id', $user_id)->value('password');
                                // echo $old_pwd.'______';
                                // echo $member_password;die;
                                if (!$member_password) {
                                    $value = array('status'=>400,'mess'=>'请先设置密码，修改失败','data'=>array('status'=>400));
                                    return json($value);
                                } else {
                                    if ($member_password != $old_pwd) {
                                        $value = array('status'=>400,'mess'=>'旧密码错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                                
                                if (!preg_match("/^[0-9a-zA-Z]{6,15}$/", $password)) {
                                    $value = array('status'=>400,'mess'=>'新密码只能为6-15位数字、英文、下划线组成','data'=>array('status'=>400));
                                    return json($value);
                                }
                                if ($password == $old_password) {
                                    $value = array('status'=>400,'mess'=>'新密码不能和旧密码相同','data'=>array('status'=>400));
                                    return json($value);
                                }
                                // if($confirm_password != $password){
                                //     $value = array('status'=>400,'mess'=>'确认密码不正确','data'=>array('status'=>400));
                                //     return json($value);
                                // }
                                
                                $count = Db::name('member')->update(array('password'=>md5($password),'id'=>$user_id));
                                if ($count > 0) {
                                    $value = array('status'=>200,'mess'=>'修改登录密码成功','data'=>array('status'=>200));
                                } else {
                                    $value = array('status'=>400,'mess'=>'修改登录密码失败','data'=>array('status'=>400));
                                }
                        }else{
                            $value = array('status'=>400,'mess'=>'验证码超时！请重新发送验证码！','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'验证码错误！','data'=>array('status'=>400));
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

     //重置密码发送短信
     public function editPwdSms(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $members = Db::name('member')->where('id',$user_id)->field('phone')->find();
                    if($members['phone']){
                        $phone = $members['phone'];
                        $dxpz = Db::name('config')->where('ca_id',2)->field('ename,value')->select();
                        $dxpzres = array();
                        foreach ($dxpz as $v){
                            $dxpzres[$v['ename']] = $v['value'];
                        }
                         
                        $codenum = Db::name('code_num')->where('phone',$phone)->find();
                        // dump($dxpzres);die;
                        if($codenum){
                            $jtime = time();
                            if($jtime < $codenum['time_out']){
                                if($codenum['num'] >= $dxpzres['maxcodenum']){
                                    $value = array('status'=>400,'mess'=>'今天已超出最大请求次数','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
                        }
                         
                        $messtime = $dxpzres['messtime'];

                        $czpaypwd = Db::name('sms')->where('phone',$phone)->find();
                        // dump($czpaypwd);die;
                        if($czpaypwd){
                            $time = time();
                            if($time-$czpaypwd['qtime'] < $messtime){
                                $value = array('status'=>400,'mess'=>$messtime.'s内不能重复发送','data'=>array('status'=>400));
                            }else{
                                $smscode = createSMSCode();
                                $data['phone'] = $phone;
                                $data['qtime'] = time();
                                $data['smscode'] = $smscode;
                                $data['id'] = $czpaypwd['id'];
                                $data['type'] = 1;
                                 
                                $outputArr = sendSms($phone,$smscode);
                                $outputArr = object_to_array($outputArr);
                                
                                if($outputArr['msg'] == 'OK'){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('sms')->update($data);
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
                                    $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                                }
                            }
                        }else{
                            $smscode = createSMSCode();
                            $data['phone'] = $phone;
                            $data['qtime'] = time();
                            $data['smscode'] = $smscode;
                            $data['type'] = 1; // 1:修改登录密码
                        
                            $outputArr = sendSms($phone,$smscode);
                            $outputArr = object_to_array($outputArr);
                            
                            if($outputArr['msg'] == 'OK'){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('sms')->insert($data);
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
                                $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                            }
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'请选设置手机号码','data'=>array('status'=>400));
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
    
    //修改支付密码
    public function editpaypwd(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.old_pwd')){
                        if(input('post.paypwd')){
                            if(input('post.confirm_pwd')){
                                $old_pwd = input('post.old_pwd');
                                $paypwd = input('post.paypwd');
                                $confirm_pwd = input('post.confirm_pwd');
                                
                                $member_paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                                if(!$member_paypwd){
                                    $value = array('status'=>400,'mess'=>'请先设置支付密码，修改失败','data'=>array('status'=>400));
                                }else{
                                    if($member_paypwd != md5($old_pwd)){
                                        $value = array('status'=>400,'mess'=>'旧支付密码错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                                
                                if(!preg_match("/^\\d{6}$/", $paypwd)){
                                    $value = array('status'=>400,'mess'=>'支付密码只能为6位数字组成','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                if($paypwd == $old_pwd){
                                    $value = array('status'=>400,'mess'=>'新支付密码不能和旧支付密码相同','data'=>array('status'=>400));
                                    return json($value);
                                }
 
                                if($confirm_pwd != $paypwd){
                                    $value = array('status'=>400,'mess'=>'确认密码不正确','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                $count = Db::name('member')->update(array('paypwd'=>md5($paypwd),'id'=>$user_id));
                                if($count > 0){
                                    $value = array('status'=>200,'mess'=>'修改支付密码成功','data'=>array('status'=>200));
                                }else{
                                    $value = array('status'=>400,'mess'=>'修改支付密码失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'确认密码不能为空','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'新支付密码不能为空','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'旧支付密码不能为空','data'=>array('status'=>400));
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
    
    //获取用户手机号
    public function huoquphone(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $phone = Db::name('member')->where('id',$user_id)->value('phone');
                    if($phone){
                        $value = array('status'=>200,'mess'=>'获取用户手机号成功','data'=>array('phone'=>$phone));
                    }else{
                        $value = array('status'=>400,'mess'=>'获取用户手机号失败','data'=>array('status'=>400));
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
    
    //判断用户支付密码设置与否
    public function pdpaypwd(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                    if($paypwd){
                        $zhifupwd = 1;
                    }else{
                        $zhifupwd = 0;
                    }
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>array('zhifupwd'=>$zhifupwd));
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
    
    //设置支付密码发送短信验证码
    public function szpaypwdcode(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $members = Db::name('member')->where('id',$user_id)->field('phone,paypwd')->find();
                    if(!$members['paypwd']){
                        $phone = $members['phone'];
                        
                        $dxpz = Db::name('config')->where('ca_id',2)->field('ename,value')->select();
                        $dxpzres = array();
                        foreach ($dxpz as $v){
                            $dxpzres[$v['ename']] = $v['value'];
                        }
                         
                        $codenum = Db::name('code_num')->where('phone',$phone)->find();
                        if($codenum){
                            $jtime = time();
                            if($jtime < $codenum['time_out']){
                                if($codenum['num'] >= $dxpzres['maxcodenum']){
                                    $value = array('status'=>400,'mess'=>'今天已超出最大请求次数','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
                        }
                         
                        $messtime = $dxpzres['messtime'];

                        $szpaypwd = Db::name('szpaypwd')->where('phone',$phone)->find();
                        
                        if($szpaypwd){
                            $time = time();
                            if($time-$szpaypwd['qtime'] < $messtime){
                                $value = array('status'=>400,'mess'=>$messtime.'s内不能重复发送','data'=>array('status'=>400));
                            }else{
                                $smscode = createSMSCode();
                                $data['phone'] = $phone;
                                $data['qtime'] = time();
                                $data['smscode'] = $smscode;
                                $data['id'] = $szpaypwd['id'];
                                 
                                $outputArr = sendSms($phone,$smscode);
                                $outputArr = object_to_array($outputArr);
                                
                                if($outputArr['msg'] == 'OK'){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('szpaypwd')->update($data);
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
                                    $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
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
                                    Db::name('szpaypwd')->insert($data);
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
                                $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                            }
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'支付密码已存在，设置失败','data'=>array('status'=>400));
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
    
    //设置支付密码
    public function szpaypwd(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.phonecode')){
                        if(input('post.paypwd')){
                            if(input('post.confirm_pwd')){
                                $code = input('post.phonecode');
                                $paypwd = input('post.paypwd');
                                $confirm_pwd = input('post.confirm_pwd');
    
                                $members = Db::name('member')->where('id',$user_id)->field('phone,paypwd')->find();
                                if(!$members['paypwd']){
                                    $phone = $members['phone'];
                                    
                                    $szpaypwd = Db::name('szpaypwd')->where('phone',$phone)->find();
                                    if($szpaypwd && $szpaypwd['smscode'] == $code){
                                        $time = time();
                                        $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                                        if($time-$szpaypwd['qtime'] <= $vali_time['value']*60){
                                            if(!preg_match("/^\\d{6}$/", $paypwd)){
                                                $value = array('status'=>400,'mess'=>'支付密码只能为6位数字组成','data'=>array('status'=>400));
                                                return json($value);
                                            }

                                            if($confirm_pwd != $paypwd){
                                                $value = array('status'=>400,'mess'=>'确认密码不正确','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                    
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                Db::name('member')->update(array('paypwd'=>md5($paypwd),'id'=>$user_id));
                                                Db::name('szpaypwd')->delete($szpaypwd['id']);
                                                // 提交事务
                                                Db::commit();
                                                $value = array('status'=>200,'mess'=>'重置支付密码成功','data'=>array('status'=>200));
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                                $value = array('status'=>400,'mess'=>'重置支付密码失败','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'验证码超时！请重新发送验证码！','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'手机验证失败！','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'已存在支付密码，设置失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'缺少确认密码','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少新支付密码','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少手机验证码','data'=>array('status'=>400));
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
    
    //重置支付密码发送短信
    public function czpaypwdcode(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $members = Db::name('member')->where('id',$user_id)->field('phone,paypwd')->find();
                    if($members['paypwd']){
                        $phone = $members['phone'];
                        $dxpz = Db::name('config')->where('ca_id',2)->field('ename,value')->select();
                        $dxpzres = array();
                        foreach ($dxpz as $v){
                            $dxpzres[$v['ename']] = $v['value'];
                        }
                         
                        $codenum = Db::name('code_num')->where('phone',$phone)->find();
                        if($codenum){
                            $jtime = time();
                            if($jtime < $codenum['time_out']){
                                if($codenum['num'] >= $dxpzres['maxcodenum']){
                                    $value = array('status'=>400,'mess'=>'今天已超出最大请求次数','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
                        }
                         
                        $messtime = $dxpzres['messtime'];

                        $czpaypwd = Db::name('czpaypwd')->where('phone',$phone)->find();
                        
                        if($czpaypwd){
                            $time = time();
                            if($time-$czpaypwd['qtime'] < $messtime){
                                $value = array('status'=>400,'mess'=>$messtime.'s内不能重复发送','data'=>array('status'=>400));
                            }else{
                                $smscode = createSMSCode();
                                $data['phone'] = $phone;
                                $data['qtime'] = time();
                                $data['smscode'] = $smscode;
                                $data['id'] = $czpaypwd['id'];
                                 
                                $outputArr = sendSms($phone,$smscode);
                                $outputArr = object_to_array($outputArr);
                                
                                if($outputArr['msg'] == 'OK'){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('czpaypwd')->update($data);
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
                                    $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
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
                                    Db::name('czpaypwd')->insert($data);
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
                                $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                            }
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'请选设置支付密码','data'=>array('status'=>400));
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

    //重置支付密码
    public function resetpaypwd(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.phonecode')){
                        if(input('post.paypwd')){
                            if(input('post.confirm_pwd')){
                                $code = input('post.phonecode');
                                $paypwd = input('post.paypwd');
                                $confirm_pwd = input('post.confirm_pwd');
                                
                                $members = Db::name('member')->where('id',$user_id)->field('phone,paypwd')->find();
                                if($members['paypwd']){
                                    $phone = $members['phone'];
                                    
                                    // $czpaypwd = Db::name('czpaypwd')->where('phone',$phone)->find();
                                    $czpaypwd = Db::name('sms')->where('phone',$phone)->where('use', 0)->order('id desc')->find();
                                    if($czpaypwd && $czpaypwd['code'] == $code){
                                        $time = time();
                                        // $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                                        // if($time-$czpaypwd['qtime'] <= $vali_time['value']*60){
                                        if(true){
                                            if(!preg_match("/^\\d{6}$/", $paypwd)){
                                                $value = array('status'=>400,'mess'=>'支付密码只能为6位数字组成','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                    
                                            if(md5($paypwd) == $members['paypwd']){
                                                $value = array('status'=>400,'mess'=>'新支付密码不能与旧支付密码相同','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                    
                                            if($confirm_pwd != $paypwd){
                                                $value = array('status'=>400,'mess'=>'确认密码不正确','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                    
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                Db::name('member')->update(array('paypwd'=>md5($paypwd),'id'=>$user_id));
                                                // Db::name('czpaypwd')->delete($czpaypwd['id']);
                                                Db::name('sms')->where('id', $czpaypwd['id'])->update([
                                                    'use' => 1
                                                ]);
                                                // 提交事务
                                                Db::commit();
                                                $value = array('status'=>200,'mess'=>'重置支付密码成功','data'=>array('status'=>200));
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                                $value = array('status'=>400,'mess'=>'重置支付密码失败','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'验证码超时！请重新发送验证码！','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'手机验证失败！','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'请先设置支付密码','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'缺少确认密码','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少新支付密码','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少手机验证码','data'=>array('status'=>400));
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
    
    //更换手机号码发送短信
    public function editphonecode(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                        $phone = input('post.phone');
                        
                        $phonearr = Db::name('member')->where('phone',$phone)->find();
                        if(!$phonearr){
                            $dxpz = Db::name('config')->where('ca_id',2)->field('ename,value')->select();
                            $dxpzres = array();
                            foreach ($dxpz as $v){
                                $dxpzres[$v['ename']] = $v['value'];
                            }
                            $codenum = Db::name('code_num')->where('phone',$phone)->find();
                            if($codenum){
                                $jtime = time();
                                if($jtime < $codenum['time_out']){
                                    if($codenum['num'] >= $dxpzres['maxcodenum']){
                                        $value = array('status'=>400,'mess'=>'今天已超出最大请求次数','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            }
                            
                            $messtime = $dxpzres['messtime'];

                            $changephone = Db::name('changephone')->where('phone',$phone)->find();
                             
                            if($changephone){
                                $time = time();
                                if($time-$changephone['qtime'] < $messtime){
                                    $value = array('status'=>400,'mess'=>$messtime.'s内不能重复发送','data'=>array('status'=>400));
                                }else{
                                    $smscode = createSMSCode();
                                    $data['phone'] = $phone;
                                    $data['qtime'] = time();
                                    $data['smscode'] = $smscode;
                                    $data['id'] = $changephone['id'];
                                     
                                    $outputArr = sendSms($phone,$smscode);
                                    $outputArr = object_to_array($outputArr);
                                    
                                    if($outputArr['msg'] == 'OK'){
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            Db::name('changephone')->update($data);
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
                                        $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
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
                                        Db::name('changephone')->insert($data);
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
                                    $value = array('status'=>400,'mess'=>'发送验证码失败！','data'=>array('status'=>400));
                                }
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'手机号已存在，请更换后重试','data'=>array('status'=>400));
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
    
    //更换手机号码
    public function editphone(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.phone') && preg_match("/^1[3456789]{1}\\d{9}$/", input('post.phone'))){
                        if(input('post.phonecode')){
                            $phone = input('post.phone');
                            $code = input('post.phonecode');
                            $userphone = Db::name('member')->where('id',$user_id)->value('phone');
                            if($userphone != $phone){
                                $changephone = Db::name('changephone')->where('phone',$phone)->find();
                                if($changephone && $changephone['smscode'] == $code){
                                    $time = time();
                                    $vali_time = Db::name('config')->where('ca_id',2)->where('ename','mess_vali_time')->field('value')->find();
                                    if($time-$changephone['qtime'] <= $vali_time['value']*60){
                                        $memberinfo = Db::name('member')->where('phone',$phone)->find();
                                        if(!$memberinfo){
                                            $data['phone'] = $phone;
                                            $data['id'] = $user_id;
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                Db::name('member')->update($data);
                                                Db::name('changephone')->delete($changephone['id']);
                                                // 提交事务
                                                Db::commit();
                                                $value = array('status'=>200,'mess'=>'更换绑定手机号成功','data'=>array('status'=>200));
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                                $value = array('status'=>400,'mess'=>'更换绑定手机号失败','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'手机号已存在，更换失败','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'验证码超时！请重新发送验证码！','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'手机验证失败！','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400, 'mess'=>'新手机号与旧手机号不能相同','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'请填写手机验证码','data'=>array('status'=>400));
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

    /**
     * 我的积分明细
     * @param
     * @return object
     * @author:Damow
     */
    public function getIntegralList(){
        !isset($this->data['page'])?$page=1:$page=$this->data['page'];
        if (Cache::get('user_id') == 0){
            if(input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    Cache::set('user_id',$user_id);
                }
            }
        }

        $list   = db('member_integral')->where(['user_id'=>Cache::get('user_id')])->order('id desc')->page($page,PAGE)->select();

        count($list)<1 && datamsg(WIN,'暂无更多数据','arr');

        foreach ($list as $k=>$v){
            $list[$k]['addtime']   = date('Y-m-d H:i:s',$v['addtime']);
            $list[$k]['log'] = $this->getIntegralTitle($v['type']);
            $list[$k]['class'] = $v['class'] == 0 ? '奖励+' : '消费-';
        }
        datamsg(WIN,'成功',$list);
    }

    public function pointTransferAccount(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    
                    if($data['num']<10 || $data['num']%10!=0){
                        $value = array('status'=>400,'mess'=>'请输入10的倍数','data'=>array('status'=>400));
                        return json($value);
                    }
                    
                    $yzresult = $this->validate($data,'Member.pointTransferAccount');
                    if(true !== $yzresult){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
                        $receive_member = Db::name('member')->where('nick_name', $data['nick_name'])->find();
                        $send_member = Db::name('member')->where('id', $user_id)->find();
                        if (is_null($receive_member)){
                            $value = array('status'=>400,'mess'=>'昵称不存在','data'=>array('status'=>400));
                            return json($value);
                        }

                        if($send_member['nick_name'] == $receive_member['nick_name']){
                        // if(false){
                            $value = array('status'=>400,'mess'=>'转账不能转给自己','data'=>array('status'=>400));
                        }
                        else{
                            // if($receive_member['jiedian_team_id']){
                            //     $receive_member['team_id'] = $receive_member['jiedian_team_id'];
                            // }
                            // $receive_member['team_id'] = array_merge($receive_member['team_id'], $receive_member['jiedian_team_id']);
                            $receive_team_id = explode(',', $receive_member['team_id']);
                            if(in_array($send_member['id'], $receive_team_id) ){
                            // if(true){
                                $receive_wallet = Db::name('wallet')->where('user_id', $receive_member['id'])->find();
                                $send_wallet = Db::name('wallet')->where('user_id', $send_member['id'])->find();

                                if ($send_wallet['ticket_burn'] < $data['num']){
                                    $value = array('status'=>400,'mess'=>'门票不足以转账','data'=>array('status'=>400));
                                }
                                else{
                                    // if($send_member['paypwd'] == md5($data['paypwd'])){
                                    if(true){
                                        Db::startTrans();
                                        try{
                                            Db::name('wallet')->where('id', $send_wallet['id'])->setDec('ticket_burn', $data['num']);
                                            Db::name('wallet')->where('id', $receive_wallet['id'])->setInc('ticket_burn', $data['num']);

                                            $time = time();
                                            $send_detail = [
                                                'before_price'=> $send_wallet['ticket_burn'],
                                                'price' => $data['num'],
                                                'after_price'=> $send_wallet['ticket_burn']-$data['num'],
                                                'de_type' => 2,
                                                'zc_type' => 125,
                                                'user_id' => $send_member['id'],
                                                'target_id' => $receive_member['id'],
                                                'wat_id' => $send_wallet['id'],
                                                'time' => $time
                                            ];
                                            $receive_detail = [
                                                'before_price'=> $receive_wallet['ticket_burn'],
                                                'price' => $data['num'],
                                                'after_price'=> $receive_wallet['ticket_burn']+$data['num'],
                                                'de_type' => 1,
                                                'sr_type' => 128,
                                                'user_id' => $receive_member['id'],
                                                'target_id' => $send_member['id'],
                                                'wat_id' => $receive_wallet['id'],
                                                'time' => $time
                                            ];
                                            // $detail['de_type'] = 2;
                                            // $detail['zc_type'] = 5;
                                            // $detail['user_id'] = $send_member['id'];

                                            $this->addDetail($send_detail);
//                                            Db::name('detail')->insert($send_detail);
                                            $this->addDetail($receive_detail);
//                                            Db::name('detail')->insert($receive_detail);
                                            Db::commit();
                                            $value = array('status'=>200,'mess'=>'转账成功','data'=>array('status'=>200));
                                        }
                                        catch (Exception $e){
                                            Db::rollback();
                                            $value = array('status'=>400,'mess'=>'转账失败','data'=>array('status'=>400));
                                        }
                                    }
                                    else{
                                        $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                    }
                                }
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'转账只能转给自己的下属团队','data'=>array('status'=>400));
                            }
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

    public function brandTransferAccount(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    
                    if($data['num']<10 || $data['num']%10!=0){
                        $value = array('status'=>400,'mess'=>'请输入10的倍数','data'=>array('status'=>400));
                        return json($value);
                    }
                    
                    $yzresult = $this->validate($data,'Member.brandTransferAccount');
                    if(true !== $yzresult){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
                        $receive_member = Db::name('member')->where('phone', $data['phone'])->find();
                        $send_member = Db::name('member')->where('id', $user_id)->find();
                        if (is_null($receive_member)){
                            $value = array('status'=>400,'mess'=>'当前用户异常','data'=>array('status'=>400));
                            return json($value);
                        }

                        if($send_member['phone'] == $receive_member['phone']){
                        // if(false){
                            $value = array('status'=>400,'mess'=>'转账不能转给自己','data'=>array('status'=>400));
                        }
                        else{
                            if($receive_member['jiedian_team_id']){
                                $receive_member['team_id'] = $receive_member['jiedian_team_id'];
                            }
                            // $receive_member['team_id'] = array_merge($receive_member['team_id'], $receive_member['jiedian_team_id']);
                            $receive_team_id = explode(',', $receive_member['team_id']);
                            if(in_array($send_member['id'], $receive_team_id) ){
                            // if(true){
                                $receive_wallet = Db::name('wallet')->where('user_id', $receive_member['id'])->find();
                                $send_wallet = Db::name('wallet')->where('user_id', $send_member['id'])->find();

                                if ($send_wallet['price'] < $data['num']){
                                    $value = array('status'=>400,'mess'=>'账户余额不足以转账','data'=>array('status'=>400));
                                }
                                else{
                                    // if($send_member['paypwd'] == md5($data['paypwd'])){
                                    if(true){
                                        Db::startTrans();
                                        try{
                                            Db::name('wallet')->where('id', $send_wallet['id'])->setDec('price', $data['num']);
                                            Db::name('wallet')->where('id', $receive_wallet['id'])->setInc('price', $data['num']);

                                            $time = time();
                                            $send_detail = [
                                                'before_price'=> $send_wallet['price'],
                                                'price' => $data['num'],
                                                'after_price'=> $send_wallet['price']-$data['num'],
                                                'de_type' => 2,
                                                'zc_type' => 5,
                                                'user_id' => $send_member['id'],
                                                'target_id' => $receive_member['id'],
                                                'wat_id' => $send_wallet['id'],
                                                'time' => $time
                                            ];
                                            $receive_detail = [
                                                'before_price'=> $receive_wallet['price'],
                                                'price' => $data['num'],
                                                'after_price'=> $receive_wallet['price']+$data['num'],
                                                'de_type' => 1,
                                                'sr_type' => 8,
                                                'user_id' => $receive_member['id'],
                                                'target_id' => $send_member['id'],
                                                'wat_id' => $receive_wallet['id'],
                                                'time' => $time
                                            ];
                                            // $detail['de_type'] = 2;
                                            // $detail['zc_type'] = 5;
                                            // $detail['user_id'] = $send_member['id'];

                                            $this->addDetail($send_detail);
//                                            Db::name('detail')->insert($send_detail);
                                            $this->addDetail($receive_detail);
//                                            Db::name('detail')->insert($receive_detail);
                                            Db::commit();
                                            $value = array('status'=>200,'mess'=>'转账成功','data'=>array('status'=>200));
                                        }
                                        catch (Exception $e){
                                            Db::rollback();
                                            $value = array('status'=>400,'mess'=>'转账失败','data'=>array('status'=>400));
                                        }
                                    }
                                    else{
                                        $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                    }
                                }
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'转账只能转给自己的下属团队','data'=>array('status'=>400));
                            }
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

    public function orderRecord(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];

                    $list = Db::name('wine_order_record')->alias('wor')
                            ->join('wine_deal_area wda', 'wor.wine_deal_area_id = wda.id', 'left')
                            ->where('wor.buy_id', $user_id)
                            ->field('wor.*, wda.deal_area,wda.desc')
                            ->order('wor.id desc')->select();
                    foreach ($list as $index=>$item){
                        $list[$index]['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                    }

                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
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

    public function orderRecordContract(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];

                    $list = Db::name('wine_order_record_contract')->alias('worc')
                            ->join('wine_contract_day wcd', 'worc.wine_contract_day_id = wcd.id', 'left')
                            ->where('worc.buy_id', $user_id)
                            // ->where('worc.addtime', '>=', strtotime('today'))
                            ->field('worc.*, wcd.day')
                            ->order('worc.id desc')->select();
                            // var_dump($list);exit;
                    foreach ($list as $index=>$item){
                        $list[$index]['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                    }

                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
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

    public function getConfig(){
        $config = Db::name('config')->whereIn('ename', ['manager_reward_to_buy_ticket','brand_to_buyTicket_exchange_perc'])->select();
        $data = [];
        foreach ($config as $k=>$v){
            $data[$v['ename']] = $v['value'];
        }
//        $data[$config['ename']] = $config['value'];

        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$data);
        return json($value);
    }

    public function getWalletss(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];

                    $info = Db::name('wallet')->where('user_id', $user_id)->find();

                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$info);
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

    public function brandExchangeBuyTicket(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    $yzresult = $this->validate($data,'Member.brandExchangeBuyTicket');
                    if(true !== $yzresult){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
                        $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                        if ($paypwd == md5($data['paypwd'])){
                            $wallet = Db::name('wallet')->where('user_id', $user_id)->find();
                            $wallet_info = $wallet;
                            $demands_brand_num = Db::name('config')->where('ename', 'brand_remain_num')->value('value');
                            if($wallet['brand']>=$data['num']+$demands_brand_num){
                                $wallet_id = $wallet['id'];
                                $rate = Db::name('config')->where('ename', 'brand_to_buyTicket_exchange_perc')->value('value');

                                $buy_ticket_num = (int)($data['num'] * $rate/100);

                                Db::startTrans();
                                try{
                                    Db::name('wallet')->where('user_id', $user_id)->dec('brand', $data['num'])
                                        ->inc('buy_ticket', $buy_ticket_num)->update();
                                    $time = time();
                                    $detail_income = [
                                        'de_type'=>1,
                                        'sr_type'=>9,
                                        'before_price'=> $wallet_info['buy_ticket'],
                                        'price'=>$buy_ticket_num,
                                        'after_price'=> $wallet_info['buy_ticket']+$buy_ticket_num,
                                        'user_id'=>$user_id,
                                        'wat_id'=>$wallet_id,
                                        'time'=>$time
                                    ];
                                    $detail_outlay = [
                                        'de_type'=>2,
                                        'zc_type'=>6,
                                        'before_price'=> $wallet_info['brand'],
                                        'price'=>$data['num'],
                                        'after_price'=> $wallet_info['brand']-$data['num'],
                                        'user_id'=>$user_id,
                                        'wat_id'=>$wallet_id,
                                        'time'=>$time
                                    ];

                                    $this->addDetail($detail_income);
                                    $this->addDetail($detail_outlay);
//                                    Db::name('detail')->insert($detail_income);
//                                    Db::name('detail')->insert($detail_outlay);
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'兑换成功','data'=>array('status'=>200));
                                }
                                catch (Exception $e){
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'兑换失败','data'=>array('status'=>400));
                                }
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'品牌使用费不足以兑换','data'=>array('status'=>400));
                            }
                        }
                        else{
                            $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
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

    public function managerRewardToBuyTicket(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    $yzresult = $this->validate($data,'Member.managerRewardToBuyTicket');
                    if(true !== $yzresult){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
                        $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                        if ($paypwd == md5($data['paypwd'])){
                            $wallet = Db::name('wallet')->where('user_id', $user_id)->find();
                            $wallet_info = $wallet;
                            $rate = Db::name('config')->where('ename', 'manager_reward_to_buy_ticket')->value('value');
                            $manager_reward_num = (int)($data['num'] * $rate/100);
                            if($wallet['manager_reward'] < 635){
                                $value = array('status'=>400,'mess'=>'最低要求635兑换','data'=>array('status'=>400));
                                return json($value);
                            }
                            
                            if($wallet['manager_reward']>=$data['num']){
                                $wallet_id = $wallet['id'];


                                Db::startTrans();
                                try{
                                    Db::name('wallet')->where('user_id', $user_id)->dec('manager_reward', $data['num'])
                                        ->inc('buy_ticket', $data['num']-$manager_reward_num)->update();
                                    $time = time();
                                    $detail_income = [
                                        'de_type'=>1,
                                        'sr_type'=>10,
                                        'before_price'=> $wallet_info['buy_ticket'],
                                        'price'=>$data['num']-$manager_reward_num,
                                        'after_price'=> $wallet_info['buy_ticket']+$data['num']-$manager_reward_num,
                                        'user_id'=>$user_id,
                                        'wat_id'=>$wallet_id,
                                        'time'=>$time
                                    ];
                                    $detail_outlay = [
                                        'de_type'=>2,
                                        'zc_type'=>7,
                                        'before_price'=> $wallet_info['manager_reward'],
                                        'price'=>$data['num'],
                                        'after_price'=> $wallet_info['manager_reward']-$data['num'],
                                        'user_id'=>$user_id,
                                        'wat_id'=>$wallet_id,
                                        'time'=>$time,
                                        // 'remark'=>'管理奖兑换购物券需扣除'.$rate.'%手续费'
                                    ];

                                    $this->addDetail($detail_income);
                                    $this->addDetail($detail_outlay);
//                                    Db::name('detail')->insert($detail_income);
//                                    Db::name('detail')->insert($detail_outlay);
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'兑换成功','data'=>array('status'=>200));
                                }
                                catch (Exception $e){
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'兑换失败','data'=>array('status'=>400));
                                }
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'管理奖不足以兑换','data'=>array('status'=>400));
                            }
                        }
                        else{
                            $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
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

    public function verifyPayPass(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');

                    $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                    if(md5($data['paypwd']) != $paypwd){
                        $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                    }
                    else{
                        $value = array('status'=>200,'mess'=>'验证成功','data'=>array('status'=>200));
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

    public function getMyTeam(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $post = input('post.');
                    $pageSize = 20;

                    $info['total_team_num'] = Db::name('member')->where('jiedian_team_id', 'like', '%,'.$user_id)->whereOr('jiedian_team_id', 'like', '%,'.$user_id.',%')->count();
                    $info['total_active_team_num'] = Db::name('member')->where('reg_enable', 1)->where(function ($query) use($user_id){
                        $query->where('jiedian_team_id', 'like', '%,'.$user_id)->whereOr('jiedian_team_id', 'like', '%,'.$user_id.',%');
                    })->count();

                    $list = Db::name('member')->alias('m')
                                ->where('m.team_id', 'like', '%,'.$user_id)->whereOr('m.team_id', 'like', '%,'.$user_id.',%')
                                // ->group('m.id')
                                ->limit(($post['page']-1)*$pageSize, $pageSize)
                                // ->field('m.user_name, w.total_stock')
                                ->field('m.true_name, m.phone,m.regtime,m.reg_enable, m.user_name,m.nick_name,m.id')
                                ->order('m.regtime desc')
                                ->select();
                                
                    if(count($list)){
                        foreach ($list as &$v){
                            $team_id = Db::name('member')->where('team_id', 'like', '%,'.$v['id'])->whereOr('team_id', 'like', '%,'.$v['id'].',%')->column('id');
                            $v['team_yeji'] = Db::name('crowd_order')->where('user_id', 'in', $team_id)->where('status', 0)->sum('price');
                            
                            $v['regtime'] = date('Y-m-d H:i', $v['regtime']);
                        }
                    }

                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>array('list'=>$list, 'info'=>$info));
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

    public function getMyAgent(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $post = input('post.');
                    $pageSize = 20;
                    
                    // $list = Db::name('member')->alias('m')
                    //         ->where('m.one_level', $user_id)
                    //         ->join('wine_level wl', 'wl.id = m.agent_type', 'left')
                    //         ->field('m.user_name, wl.level_name')
                    //         ->select();
                    $list = Db::name('member')->alias('m')
                        // ->join('wine_order_buyer wob', 'wob.buy_id = m.id', 'left')
                        ->join('wallet w', 'w.user_id = m.id', 'left')
                        ->join('wine_level wl', 'wl.id = m.agent_type', 'left')
                        // ->group('wob.buy_id')
                        ->where('m.one_level', $user_id)
                        // ->field('m.user_name, w.total_stock')
                        ->field('m.user_name, m.agent_type, wl.level_name, m.id, m.phone, w.total_stock')
                        ->limit(($post['page']-1)*$pageSize, $pageSize)
                        ->select();
                    if(count($list)){
                        foreach ($list as &$v){
                            $count = Db::name('wine_order_buyer')->where('delete', 0)->where('buy_id', $v['id'])->where('pay_status', 0)->count();
                            if($count){
                                $v['text'] = '未付款';
                                continue;
                            }
                            
                            $count = Db::name('wine_order_buyer')->where('delete', 0)->where('buy_id', $v['id'])->where('pay_status', 1)->count();
                            if($count || $v['total_stock']>0){
                                $v['text'] = '抢单成功';
                                continue;
                            }
                            
                            $v['text'] = '未抢单';
                        }
                    }

                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
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
 
    public function getNoPayCount(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];

                    // $list = Db::name('member')->alias('m')
                    //         ->where('m.one_level', $user_id)
                    //         ->where('w.total_stock', '>', 0)
                    //         ->join('wallet w', 'w.user_id = m.id', 'inner')
                    //         ->field('m.user_name, w.id')
                    //         ->select();
                    $curtime = strtotime(date('Y-m-d H:i:s'))-2.5*60*60;
                    $count = Db::name('wine_order_buyer')->where('pay_status', 0)->where('buy_id', $user_id)->where('addtime', '>', $curtime)->where('status', 1)->where('delete', 0)->count();

                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$count);
                }else{
                    // $value = $result;
                    $value = array('status'=>300,'mess'=>'身份验证失败.','data'=>array('status'=>300));
                }
            }else{
                $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    // 网站客服
    public function getCustimer(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $list = Db::name('config')->where('ename', 'web_telephone')->whereOr('ename', 'web_name')
                        ->whereOr('ename', 'web1_name')->whereOr('ename', 'web1_telephone')
                        ->whereOr('ename', 'web_wechat')
                        ->field('ename, value')
                        ->select();
                        
                    $customer = [];
                    foreach ($list as $k=>$v){
                        $customer[$v['ename']] = $v['value'];
                    }

                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$customer);
                }else{
                    // $value = $result;
                    $value = array('status'=>300,'mess'=>'身份验证失败.','data'=>array('status'=>300));
                }
            }else{
                $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function confirmEdit(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $input = input();
                    $user_id = $result['user_id'];
                    Db::name('member')->where('id', $input['id'])->update([
                        'emergency_phone' => $input['emergency_phone'],
                        'emergency_name' => $input['emergency_name']
                    ]);

                    $value = array('status'=>200,'mess'=>'更新成功','data'=>[]);
                }else{
                    // $value = $result;
                    $value = array('status'=>300,'mess'=>'身份验证失败.','data'=>array('status'=>300));
                }
            }else{
                $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function applyVip(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $input = input();
                    $user_id = $result['user_id'];
                    
                    $day3 = strtotime(date('Y-m-d', strtotime('-3 day')));
                    $today = strtotime('today')-1;
                    
                    
                    $count = Db::name('wine_order_record')->where('addtime', '>=', $day3)->where('addtime', '<=', $today)->where('buy_id', $user_id)->count();
                    if($count >= 6){
                        $vv = Db::name('wine_apply_vip')->where('user_id', $user_id)->where('date', date('Y-m-d'))->count();
                        if($vv > 0){
                            $value = array('status'=>300,'mess'=>'请勿重复申请.','data'=>array('status'=>300));
                        }
                        else{
                            $res = Db::name('wine_apply_vip')->insert([
                                'user_id' => $user_id,
                                'addtime' => time(),
                                'date' => date('Y-m-d'),
                                'status' => 0
                            ]);
                            if(!$res){
                                $value = array('status'=>300,'mess'=>'申请失败.','data'=>array('status'=>300));
                            }
                            else{
                                $value = array('status'=>200,'mess'=>'申请成功','data'=>[]);
                            }
                        }
                    }
                    else{
                        $value = array('status'=>300,'mess'=>'申请失败.','data'=>array('status'=>300));
                    }
                }else{
                    // $value = $result;
                    $value = array('status'=>300,'mess'=>'身份验证失败.','data'=>array('status'=>300));
                }
            }else{
                $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function checkPhone(){
        $data = input();
        
        if(!isset($data['phone'])){
            $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            return json($value);
        }
        
        $member = Db::name('member')->where('phone', $data['phone'])->field('user_name, true_name')->find();
        
        if(is_null($member)){
            $value = array('status'=>300,'mess'=>'缺少用户令牌','data'=>array('status'=>300));
            return json($value);
        }
        
        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$member);
        return json($value);
    }
    
    public function pointTicketRecord(){
        if(request()->isPost()){
            if(true){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                    
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $input = input();
                    $pageSize = $input['pageSize'];
                    $page = ($input['page']-1)*$pageSize;
                    $list = Db::name('detail')->where('user_id', $user_id)
                        ->where(function($query){
                        
                            $query->where('sr_type', 'in', [102,105,101,109,110,25,607,606,605,604,600,601,602,603,102,101,200,201,205,1000,1001])->whereOr('zc_type', 'in', [100,22,25]);
                        })->limit($page, $pageSize)->order('id desc')->select();
                        
                    foreach ($list as &$v){
                        $v['time'] = date('Y-m-d H:i:s', $v['time']);
                        if($v['sr_type'] == 102 || $v['sr_type'] == 105 || $v['sr_type'] == 101){
                            $v['remark'] = '退款';
                        }
                        else if($v['sr_type'] == 1000){
                            $v['remark'] = '复购抢购';
                        }
                        else if($v['sr_type'] == 1001){
                            $v['remark'] = '复购预约';
                        }
                        else if($v['sr_type'] == 109){
                            $v['remark'] = '加权';
                        }
                        else if($v['sr_type'] == 25){
                            $v['remark'] = '后台操作';
                        }
                        else if($v['sr_type'] == 110){
                            $v['remark'] = '购买商品赠送';
                        }
                        else if($v['sr_type'] == 604){
                            $v['remark'] = '后三十 特等奖';
                        }
                        else if($v['sr_type'] == 605){
                            $v['remark'] = '后三十 一等奖';
                        }
                        else if($v['sr_type'] == 606){
                            $v['remark'] = '后三十 二等奖';
                        }
                        else if($v['sr_type'] == 607){
                            $v['remark'] = '后三十 三等奖';
                        }
                        else if($v['sr_type'] == 600){
                            $v['remark'] = '前三十 特等奖';
                        }
                        else if($v['sr_type'] == 601){
                            $v['remark'] = '前三十 一等奖';
                        }
                        else if($v['sr_type'] == 602){
                            $v['remark'] = '前三十 二等奖';
                        }
                        else if($v['sr_type'] == 603){
                            $v['remark'] = '前三十 三等奖';
                        }
                        else if($v['sr_type'] == 102){
                            $v['remark'] = '前三期退款70%';
                        }
                        else if($v['sr_type'] == 101){
                            $v['remark'] = '爆仓退款100%';
                        }
                        else if($v['sr_type'] == 200){
                            $v['remark'] = '直推';
                        }
                        else if($v['sr_type'] == 201){
                            $v['remark'] = '间推';
                        }
                        else if($v['sr_type'] == 205){
                            $v['remark'] = 'v1-v3分润';
                        }
                        else if($v['zc_type'] == 100){
                            $v['remark'] = '预售或购买';
                        }
                        else if($v['zc_type'] == 25){
                            $v['remark'] = '后台操作';
                        }
                    }
                    
                    $value = array('status'=>400,'mess'=>'获取信息成功','data'=>$list);
                }
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function pointCreditRecord(){
        if(request()->isPost()){
            if(true){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                    
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $input = input();
                    $pageSize = $input['pageSize'];
                    $page = ($input['page']-1)*$pageSize;
                    $list = Db::name('detail')->where('user_id', $user_id)
                        ->where(function($query){
                        
                            $query->where('sr_type', 'in', [103,26])->whereOr('zc_type', 'in', [26,33]);
                        })->limit($page, $pageSize)->order('id desc')->select();
                        
                    foreach ($list as &$v){
                        $v['time'] = date('Y-m-d H:i:s', $v['time']);
                        if($v['sr_type'] == 103){
                            $v['remark'] = '退款';
                        }
                        else if($v['sr_type'] == 26){
                            $v['remark'] = '后台操作';
                        }
                        else if($v['zc_type'] == 26){
                            $v['remark'] = '后台操作';
                        }
                    }
                    
                    $value = array('status'=>400,'mess'=>'获取信息成功','data'=>$list);
                }
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function ticketBurnRecord(){
        if(request()->isPost()){
            if(true){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                    
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $input = input();
                    $pageSize = $input['pageSize'];
                    $page = ($input['page']-1)*$pageSize;
                    $list = Db::name('detail')->where('user_id', $user_id)
                        ->where(function($query){
                        
                            $query->where('sr_type', 'in', [27])->whereOr('zc_type', 'in', [27,108]);
                        })->limit($page, $pageSize)->order('id desc')->select();
                        
                    foreach ($list as &$v){
                        $v['time'] = date('Y-m-d H:i:s', $v['time']);
                        if($v['zc_type'] == 108){
                            $v['remark'] = '预售或购买';
                        }
                        else if($v['sr_type'] == 27){
                            $v['remark'] = '后台操作';
                        }
                        else if($v['zc_type'] == 27){
                            $v['remark'] = '后台操作';
                        }
                    }
                    
                    $value = array('status'=>400,'mess'=>'获取信息成功','data'=>$list);
                }
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
}

