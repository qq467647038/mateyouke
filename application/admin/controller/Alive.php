<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
// 导入对应产品模块的client
use TencentCloud\Live\V20180801\LiveClient;
// 导入要请求接口对应的Request类
use TencentCloud\Live\V20180801\Models\DescribeLiveStreamOnlineListRequest;
use TencentCloud\Live\V20180801\Models\DropLiveStreamRequest;
use TencentCloud\Live\V20180801\Models\ForbidLiveStreamRequest;
use TencentCloud\Live\V20180801\Models\ResumeLiveStreamRequest;

use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;
use app\admin\services\SocketNotice;
use app\common\logic\OrderAfterLogic;
class Alive extends Common{
    public function lst(){
        //(new OrderAfterLogic())->sendmsg('213','qwe');die;
        $limit = input('param.limit/d', 7);
        $keyword = input('param.keyword');
        $where=[];
        if ($keyword) {
            $where['m.phone|m.user_name|s.shop_name'] = ['like', "%{$keyword}%"];
        }
        $field = 'm.user_name,m.phone,m.headimgurl,a.*,s.shop_name';
        $list = Db::name('alive')
            ->alias('a')
            ->field($field)
            ->join('member m','a.shop_id = m.shop_id','LEFT')
            ->join('shops s','s.id = a.shop_id','LEFT')
            ->where($where)
            ->order('a.id desc')
            ->paginate($limit)
            ->each(function ($item){
                if($this->webconfig['cos_file']=="开启"){
                    $item['cover'] = config('tengxunyun')['cos_domain'].'/'.$item['cover'];
                }else{
                    $item['cover'] = $this->webconfig['weburl'].'/'.$item['cover'];
                }
                $item['type_name'] =  db('type')->where(['id'=>$item['type_id']])->value('type_name');

                // 查询是否是课程
//                $res = db('alive_to_course')->where(['alive_id'=>$item['id']])->value('course');
//                if($res && $res==1){
//                    $item['course'] = 1;
//                }else{
//                    $item['course'] = 0;
//                }
                return $item;
            });
        $page = $list->render();
        $this->assign([
            'list'=>$list,
            'page'=>$page
        ]);
        return $this->fetch();
    }

    public function send(){
        if(request()->isAjax()){
            $data = input('post.');
            $data['create_time'] = time();
            $res = db('alive_send')->insert($data);
            $user = db('member')->field('wx_openid')->select();
            (new OrderAfterLogic())->sendmsg($user,$data);
            //(new OrderAfterLogic())->sendmsg(2,$data);

            //     if($res){
            //         $value = array('status'=>1,'mess'=>'添加成功');
            //     }else{
            //         $value = array('status'=>0,'mess'=>'添加失败');
            //     }
            // return json($value);
        }else{
            $list = db('alive_send')->select();
            $this->assign('list',$list);
            return $this->fetch(); 
        }
    }

    // 学堂卡
//    public function course(){
//        $alive_id = input('param.alive_id/d');
//        $course = input('param.course/d');
//
//        if(!is_numeric($alive_id) || !is_int($alive_id)){
//            return false;
//        }
//
//        $where['alive_id'] = $alive_id;
//        // 更新直播视频为课程直播
//        $aliveTOcourse = Db::name('alive_to_course')->where($where)->find();
//        if(is_null($aliveTOcourse)){
//            // 不存在则插入一条数据
//            $insert['alive_id'] = $alive_id;
//            $insert['course'] = $course;
//            $insert['addtime'] = time();
//
//            $res = Db::name('alive_to_course')->insert($insert);
//        }else{
//            // 存在则更新
//            $res = Db::name('alive_to_course')->where($where)->update(['course'=>$course]);
//        }
//        if($res){
//            return true;
//        }else{
//            return false;
//        }
//    }

    // 新增直播间
    public function addAlive(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            $result = $this->validate($data,'Alive');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $shop = db('shops')->find($data['shop_id']);
                if(!$shop){
                    $value = array('status'=>0,'mess'=>'该店铺ID不存在');
                    return json($value);
                }
                $data['create_time'] = time();
                $res = db('alive')->insert($data);
                if($res){
                    $value = array('status'=>1,'mess'=>'添加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'添加失败');
                }
            }
            return json($value);
        }else{
            $typeList = db('type')->field('id,type_name')->select();
            $this->assign('typeList',$typeList);
            return $this->fetch();
        }
    }

    // 修改直播间
    public function editAlive(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            $result = $this->validate($data,'Alive');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                // echo 1;
                // dump($data);die;
                $shop = db('shops')->find($data['shop_id']);
                if(!$shop){
                    $value = array('status'=>0,'mess'=>'该店铺ID不存在');
                    return json($value);
                }
                $res = db('alive')->where(['id'=>$data['id']])->update($data);
                // dump($res);
                if($res !== false){
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }else{
                    $value = array('status'=>0,'mess'=>'编辑失败');
                }
            }
            return json($value);
        }else{
            $id = input('param.id');
            $aliveInfo = db('alive')->find($id);
            $typeList = db('type')->field('id,type_name')->select();
            // echo $_SERVER['HTTP_REFERER'];die;
            $this->assign('typeList',$typeList);
            $this->assign('data',$aliveInfo);
            $this->assign('referer',$_SERVER['HTTP_REFERER']);
            return $this->fetch();
        }
    }
    
    // 检查直播间标题是否重复
    public function checkAliveName(){
        if(request()->isAjax()){
            $aliveName = Db::name('alive')->where('title',input('post.title'))->find();
            if($aliveName){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }

    // 检查商家id是否重复
    public function checkShopId(){
        if(request()->isAjax()){
            $aliveName = Db::name('alive')->where('title',input('post.title'))->find();
            if($aliveName){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }

    //处理上传图片
//    public function uploadify(){
//        $admin_id = session('admin_id');
//        $file = request()->file('filedata');
//        if($file){
//
//                $picarr = $this->qcloudCosUpload($file, 'liveroom', 1);
//
//                $picarr = array('img_url'=>$picarr['wz'],'cover'=>$picarr['dz']);
//                $value = array('status'=>1,'path'=>$picarr);
//
//
//
//        }else{
//            $value = array('status'=>0,'msg'=>'文件不存在');
//        }
//        return json($value);
//    }

    //处理上传图片
    public function uploadify(){
        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'alive');
            if($info){

                $getSaveName = str_replace("\\","/",$info->getSaveName());
                $original = '/uploads/alive/'.$getSaveName;
                $image = \think\Image::open('./'.$original);
                $image->thumb(500, 500)->save('./'.$original,null,90);

                $picarr = array('img_url'=>$original,'cover'=>$original);
                $value = array('status'=>1,'path'=>$picarr);
            }else{
                $value = array('status'=>0,'msg'=>$file->getError());
            }
        }else{
            $value = array('status'=>0,'msg'=>'文件不存在');
        }
        return json($value);
    }


    /**
     * @func 获取直播入驻的详细信息
     */
    public function info(){
        $uid = input('param.id');
        $where['m.id']=$uid;
        $field = 'm.user_name,m.phone,m.headimgurl,m.integral,m.summary,m.sex,m.email,m.wxnum,m.qqnum,m.regtime,am.*';
        $info = Db::name('alive_member')
            ->alias('am')
            ->field($field)
            ->join('member m','am.uid = m.id','LEFT')
            ->where($where)
            ->find();
        $this->assign([
           'info'=>$info
        ]);
        return $this->fetch();
    }


    /**
     * 是否是新人
     */
    public function isnewperson(){
        $id = input('param.id/d');
        if(empty($id)){
            datamsg(LOSE,'id不能为空');
        }else{
            $find = db('alive')->where(['id'=>$id])->find();
            if(empty($find)){
                datamsg(LOSE,'没有找到对应的数据');
            }
            if($find['isnewperson'] == 1){
                $data['isnewperson']=-1;
            }else{
                $data['isnewperson']=1;
            }
            $result = db('alive')->where(['id'=>$id])->update($data);
            if($result){
                datamsg(WIN,'更新成功');
            }else{
                datamsg(LOSE,'更新失败');
            }
        }
    }




    /**
     * 是否推荐
     */
    public function isrecommend(){
        $id = input('param.id/d');
        if(empty($id)){
            datamsg(LOSE,'id不能为空');
        }else{
            $find = db('alive')->where(['id'=>$id])->find();
            if(empty($find)){
                datamsg(LOSE,'没有找到对应的数据');
            }
            if($find['isrecommend'] == 1){
                $data['isrecommend']=-1;
            }else{
                $data['isrecommend']=1;
            }
            $result = db('alive')->where(['id'=>$id])->update($data);
            if($result){
                datamsg(WIN,'更新成功');
            }else{
                datamsg(LOSE,'更新失败');
            }
        }
    }

    /**
     * 获取直播监控信息
     */
    public function alivemonitor(){
        $list = [];
        $field = 'm.user_name,m.phone,m.headimgurl,a.*,am.lastlogin_time,am.hot,am.recommend,am.prohibit';
        try {
            $pushdomain = db('config')->where(['ename'=>'pushdomain'])->value('value');
            $playdomain = db('config')->where(['ename'=>'playdomain'])->value('value');
            // 实例化一个证书对象，入参需要传入腾讯云账户secretId，secretKey
            $cred = new Credential("123456", "123456");
        
            // # 实例化要请求产品(以cvm为例)的client对象
            $client = new LiveClient($cred, "ap-shanghai");
        
            // 实例化一个请求对象
            $req = new DescribeLiveStreamOnlineListRequest();
            //pushdomain
            $req->DomainName = $pushdomain;
            $req->AppName = "live";
            $req->PageSize = 50;
            //print_r($req);
            // 通过client对象调用想要访问的接口，需要传入请求对象
            $resp = $client->DescribeLiveStreamOnlineList($req);
            $online_data = json_decode($resp->toJsonString(),true);
            $StreamName = "";
            $time_data = [];
            if(isset($online_data['OnlineInfo']) && !empty($online_data['OnlineInfo'])){
                foreach($online_data['OnlineInfo'] as $key=>$value){
                    $StreamName = $StreamName . $value['StreamName'].',';
                    $time_data[$value['StreamName']] = $value['PublishTime'];
                }
                $StreamName = substr($StreamName,0,-1);
                $list = Db::name('alive')
                ->alias('a')
                ->field($field)
                ->join('member m','a.shop_id = m.shop_id','LEFT')
                ->join('alive_member am','am.uid = m.id','LEFT')
                ->where("a.room in (".$StreamName.")")
                ->group("a.room")
                ->select();
                if($list){
                    //$type = "http://";
                    foreach($list as $k=>$v){
                        $type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                        if(empty($type)){
                            $type = "https://";
                        }
                        $list[$k]['address'][0] = 'rtmp://'.$playdomain.'/live/'.$v['room'];
                        $list[$k]['address'][1] = $type.$playdomain.'/live/'.$v['room'].'.flv';
                        $list[$k]['address'][2] = $type.$playdomain.'/live/'.$v['room'].'.m3u8';
                        $list[$k]['alivetime'] = $time_data[$v['room']];
                        $list[$k]['cover'] = config('tengxunyun')['cos_domain'].'/'.$v['cover'];
                    }
                }
            }
            $this->assign([
                'list'=>$list,
            ]);
            return $this->fetch();
            //print_r($resp->toJsonString());
        }
        catch(TencentCloudSDKException $e) {
            $this->assign([
                'list'=>$list,
            ]);
            return $this->fetch();
        }
    }

    /***
     * 暂停 某个直播间
     */
    public function alivestop(){
        $id = input('param.id');
        $alive = db('alive')->where(['id'=>$id])->find();
        if(empty($alive)){
            datamsg(LOSE,'没有找到直播间');
        }
        try {
            $pushdomain = db('config')->where(['ename'=>'pushdomain'])->value('value');
            // 实例化一个证书对象，入参需要传入腾讯云账户secretId，secretKey
            $cred = new Credential("AKIDGLFsQnyBPRQEk67F3kFlB4mIfqunANn1", "CD2Ret3rUwDI2b8UhiXBdptujJhuwmKh");
        
            // # 实例化要请求产品(以cvm为例)的client对象
            $client = new LiveClient($cred, "ap-shanghai");
            
            // 实例化一个请求对象
            $req = new DropLiveStreamRequest();
            //pushdomain
            $req->DomainName = $pushdomain;
            $req->AppName = "live";
            $req->StreamName = $alive['room'];
            //print_r($req);
            // 通过client对象调用想要访问的接口，需要传入请求对象
            $resp = $client->DropLiveStream($req);
            $ret_data = json_decode($resp->toJsonString(),true);
            $StreamName = "";
            $time_data = [];
            datamsg(WIN,'操作成功',$ret_data);
        }
        catch(TencentCloudSDKException $e) {
            datamsg(LOSE,'操作失败',$e);
        }
    }

    /**
     * 关闭某个直播间
     */
    public function aliveclose(){
        $id = input('param.id');
        $alive = db('alive')->where(['id'=>$id])->find();
        if(empty($alive)){
            datamsg(LOSE,'没有找到直播间');
        }
        try {
            $pushdomain = db('config')->where(['ename'=>'pushdomain'])->value('value');
            // 实例化一个证书对象，入参需要传入腾讯云账户secretId，secretKey
            $cred = new Credential("AKIDGLFsQnyBPRQEk67F3kFlB4mIfqunANn1", "CD2Ret3rUwDI2b8UhiXBdptujJhuwmKh");
        
            // # 实例化要请求产品(以cvm为例)的client对象
            $client = new LiveClient($cred, "ap-shanghai");
            
            // 实例化一个请求对象
            $req = new ForbidLiveStreamRequest();
            //pushdomain
            $req->DomainName = $pushdomain;
            $req->AppName = "live";
            $req->StreamName = $alive['room'];
            //print_r($req);
            // 通过client对象调用想要访问的接口，需要传入请求对象
            $resp = $client->ForbidLiveStream($req);
            $ret_data = json_decode($resp->toJsonString(),true);
            $StreamName = "";
            $time_data = [];
            // $data = [
            //     'status'  => 2
            // ];
            $this->changestatus($id);
            //db('alive')->where(['id'=>$id])->update($data);
            $member_data = db('member')->where(['shop_id'=>$alive['shop_id']])->find();
            $client_data = db('member_clientid')->where(['user_id'=>$member_data['id']])->find();
            $send_data = [
                'clientid' => $client_data['client_id'] , 'notice_content' => '直播间已被关闭!'
            ];
            $model = new SocketNotice();
            $model->send($send_data);
            datamsg(WIN,'操作成功',$ret_data);
        }
        catch(TencentCloudSDKException $e) {
            datamsg(LOSE,'操作失败',$e);
        }
    }

    public function test(){
        $id = $_GET['id'];
        echo  $this->changestatus($id);
    }

    public function changestatus($id){
        $data = [
            'status'  => 2
        ];
        $res = db('alive')->where(['id'=>$id])->update($data);
        return $res;
        //return db('alive')->getLastsql();
    }

    /***
     * 恢复某个直播间
     */
    public function resetalive(){
        $id = input('param.id');
        //$id = 21;
        $alive = db('alive')->where(['id'=>$id])->find();
        if(empty($alive)){
            datamsg(LOSE,'没有找到直播间');
        }
        try {
            $pushdomain = db('config')->where(['ename'=>'pushdomain'])->value('value');
            // 实例化一个证书对象，入参需要传入腾讯云账户secretId，secretKey
            $cred = new Credential("AKIDGLFsQnyBPRQEk67F3kFlB4mIfqunANn1", "CD2Ret3rUwDI2b8UhiXBdptujJhuwmKh");
        
            // # 实例化要请求产品(以cvm为例)的client对象
            $client = new LiveClient($cred, "ap-shanghai");
            
            // 实例化一个请求对象
            $req = new ResumeLiveStreamRequest();
            //pushdomain
            $req->DomainName = $pushdomain;
            $req->AppName = "live";
            $req->StreamName = $alive['room'];
            //$req->StreamName = '75266274';
            
            //print_r($req);
            // 通过client对象调用想要访问的接口，需要传入请求对象
            $resp = $client->ResumeLiveStream($req);
            //print_r($resp);exit();
            $ret_data = json_decode($resp->toJsonString(),true);
            //print_r($ret_data);exit();
            $StreamName = "";
            $time_data = [];
            $data = [
                'status'  => -1
            ];
            db('alive')->where(['id'=>$id])->update($data);
            datamsg(WIN,'操作成功',$ret_data);
        }
        catch(TencentCloudSDKException $e) {
            //datamsg(LOSE,'操作失败',$e);
        }
    }

    /***
     * 给某个主播间的主播发送提醒
     */
    public function sendnotice(){
        $id = input('param.id');
        $alive = db('alive')->where(['id'=>$id])->find();
        if(empty($alive)){
            datamsg(LOSE,'没有找到直播间');
        }
        $member_data = db('member')->where(['shop_id'=>$alive['shop_id']])->find();
        $client_data = db('member_clientid')->where(['user_id'=>$member_data['id']])->find();
        $send_data = [
            'clientid' => $client_data['client_id'] , 'notice_content' => '警告信息，请规范直播!'
        ];
        $model = new SocketNotice();
        $res = $model->send($send_data);
        echo json_encode(['status' => 0 , 'data'=>$res]);
        exit();
    }


    /***
     * 生成直播推流服务签名信息
     */
    public function sign(){
    }

    /***
     *请求
     */
    private function curl_req($data,$url,$sign){
        $files = $this->makecookie($sign);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR,$files);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
        //return json_decode($output,true);
    }


}
?>