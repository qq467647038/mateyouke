<?php

namespace app\apicloud\controller;

use think\Controller;
use think\Db;

use app\common\service\SignatureHelper;
use app\common\service\SmsService;
use app\common\service\WxLoginService;
use app\common\service\MemberService;
use app\common\service\SendWxMsgService;
use app\common\logic\OrderAfterLogic;
use app\common\service\VipService;
use app\util\JSSDK;

use app\apicloud\controller\Common;

use app\common\model\Member as MemberModel;

class Test extends Common
{
    public function __construct()
    {
        parent::__construct();
        // $this->wxtest();
    }

    public function test()
    {

        $datas = Db::name('member1')->where('temp',0)->limit(300)->select();
        foreach ($datas as $data1){
            $data['id'] = $data1['id'];
            $data['user_name'] = $data1['user_name'];
            $data['member_recode'] = $data1['invitation'];
            $data['recode'] = $data1['recode'];
            $data['phone'] = $data1['phone'];
            $data['password'] = $data1['password'];
            $data['paypwd'] = $data1['pay_pwd'];
            $data['xieyi'] = $data1['xieyi'];
            $data['regtime'] = time();
            $data['one_level'] = $data1['one_level'];
            $data['two_level'] = $data1['two_level'];
            $data['real_name'] = $data1['real_name'];
            $data['jiedianid'] = $data1['one_level'];
            $data['login_code'] = uniqid();
            $a = explode(',',$data1['parent_ids']);
            $a = array_reverse($a);
            $b = ','.implode(',',$a);
            $data['team_id'] = $b;
            $data['jiedian_team_id'] = $b;
            
            // 启动事务
            Db::startTrans();
            try {
                Db::name('member')->insertGetId($data);
                $user_id = $data['id'];
                if ($user_id) {
                    $recode = $data1['recode'];
                    $token = settoken();
                    $brand = 0;
                    Db::name('rxin')->insert(array('token' => $token, 'user_id' => $user_id));
                    Db::name('contract_record_wallet')->insert(array('total_assets' => 0, 'user_id' => $user_id, 'cumulative_earnings' => 0, 'addtime' => time()));
                    Db::name('wallet')->insert(array('price' => 0, 'user_id' => $user_id, 'brand' => $brand));
                    Db::name('profit')->insert(array('price' => 0, 'user_id' => $user_id));
                    $ress = Db::name('wine_usdt_account_generated')->where('status', 0)->order('id asc')->find();
                    if ($ress) {
                        Db::name('wine_usdt_account_generated')->where('status', 0)->where('id', $ress['id'])->update([
                            'user_id' => $user_id,
                            'updatetime' => time(),
                            'status' => 1
                        ]);
                    }



                    Vendor('phpqrcode.phpqrcode');
                    //生成二维码图片
                    $object = new \QRcode();
                    $imgrq = date('Ymd', time());
                    if (!is_dir("./uploads/memberqrcode/" . $imgrq)) {
                        mkdir("./uploads/memberqrcode/" . $imgrq);
                    }
                    $weburl = Db::name('config')->where('ca_id', 5)->where('ename', 'weburl')->field('value')->find();
                    $url = $weburl['value'] . "/index/mobile/index.html?member_recode=" . $recode;
                    $imgfilepath = "./uploads/memberqrcode/" . $imgrq . "/qrcode_" . $user_id . ".jpg";
                    $object->png($url, $imgfilepath, 'L', 10, 2);
                    $imgurlfile = "uploads/memberqrcode/" . $imgrq . "/qrcode_" . $user_id . ".jpg";
                    Db::name('member')->update(array('qrcodeurl' => $imgurlfile, 'id' => $user_id));
                    Db::name('member1')->update(array('temp' => 1, 'id' => $user_id));

                    // 提交事务
                    Db::commit();
                   echo $user_id.'OK       '.PHP_EOL;
                }
            }
            catch
                (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    print_r($e);
                    $value = array('status' => 400, 'mess' => '注册失败' . $e->getMessage(), 'data' => array('status' => 400));
                }
            
        }


           


    }

    public function test2()
    {
        // $wx_config = Db::name('wx_config')->find();
        // $appid = $wx_config['appid'];
        // $appsecret = $wx_config['appsecret'];
        // $data = (new JSSDK($appid,$appsecret,''))->getJsApiTicket();
        // halt($data);
    }

}