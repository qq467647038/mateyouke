<?php

namespace app\common\service;

use app\common\model\Member as MemberModel;

use think\Db;

class MemberService
{
    /**
     * 微信公众号登录
     *
     * @return void
     */
    public function comWxLogin($data)
    {
        if(isset($data['unionid'])){
            $userInfo = MemberModel::findByUnId($data['unionid']);
            // 没有unid时查找openid
            if(!$userInfo){
                $userInfo = $this->isUser($data);
            }
            $userInfo = $this->findUserInfo($userInfo);
            return $userInfo;
        }else{
            $userInfo = $this->isUser($data);
            $userInfo = $this->findUserInfo($userInfo);
        }
        return $userInfo;
    }

    /**
     * 判断是否有该用户
     *
     * @param [type] $data
     * @return boolean
     */
    private function isUser($data)
    {
        $userInfo = MemberModel::findByWxOpenId($data['openid']);
        // 没有账号则注册
        if(!$userInfo && isset($data['openid']) && !empty($data['openid'])){
             $userInfo = $this->comRegister($data);
        }
        return $userInfo;
    }

    /**
     * 获取用户其他信息(token等)
     *
     * @param [type] $userInfo
     * @return void
     */
    private function findUserInfo($userInfo)
    {
        // 返回信息
        $rxs = Db::name('rxin')->where('user_id',$userInfo['id'])->field('token')->find();
        $userInfo['token'] = $rxs['token'];
        $userInfo['role'] = getUserRole($userInfo['id']);
        // if($userInfo['pid'] > 0){
        //     $shop =  Db::name('member')->where('id',$userInfo['pid'])->find();
        //     $userInfo['serviceShopId'] = $shop['shop_id'];
        // }
        return $userInfo;
    }

    /**
     * 公众号注册
     *
     * @return void
     */
    private function comRegister($data)
    {
        $userInfo = [];
        $token = settoken();
        $rxs = Db::name('rxin')->where('token',$token)->find();
        $recode = settoken();
        $recodeinfos = Db::name('member')->where('recode',$recode)->field('id')->find();
        $appinfo_code = isset($data['devicetoken'])?$data['devicetoken']:"";
        if($rxs && $recodeinfos) return $userInfo;

        // 启动事务
        Db::startTrans();
        try {

            $map = [
                'user_name' => $data['nickname'],
                'recode' => $recode,
                'appinfo_code' => $appinfo_code,
                'xieyi' => 1,
                'wx_openid' => $data['openid'],
                'unionid' => isset($data['unionid']) ? $data['unionid']:'',
                'headimgurl' => $data['head_pic']
            ];

            $userInfo = MemberModel::add($map);
            $user_id = $userInfo['id'];
            if($user_id){
                Db::name('rxin')->insert(array('token'=>$token,'user_id'=>$user_id));
                Db::name('wallet')->insert(array('price'=>0,'user_id'=>$user_id));
                Db::name('profit')->insert(array('price'=>0,'user_id'=>$user_id));
            }

            bandPid($user_id, (int)trim(input('post.shareid')));
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

            // 提交事务
            Db::commit();
            return $userInfo;

        } catch (\Exception $th) {

            // 回滚事务
            Db::rollback();
            return $userInfo;

        }


        
    }

}