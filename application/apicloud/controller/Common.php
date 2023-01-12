<?php
namespace app\apicloud\controller;
use think\Controller;
use think\Db;
use app\apicloud\model\Gongyong as GongyongMx;
use EasyWeChat\Factory;
use app\util\obs;
use Exception;
class Common extends Controller{
    public $webconfig;
    public $user;
    public $dirname = '';

    public $tiqian = 0;
    
    public function _initialize(){
        define('TIME',time());
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept");
        $this->tiqian = Db::name('config')->where('ename', 'ahead_record_stop_common')->value('value')*60;
        
        $_configres = Db::name('config')->where('ca_id','in','1,2,5,8,11,15,16')->field('ename,value')->select();
        $configres = array();
        foreach ($_configres as $v){
            $configres[$v['ename']] = $v['value'];
        }
        $this->wechatConfig = [
			// 小程序配置数据表 sp_wxmini_config
            'app_id' => '',
            'secret' => '',

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名

            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
        ];

        $this->webconfig = $configres;
        $this->data = input();
    }
    
    public function generatedUsdtAccount(){
        set_time_limit(999);
        $count = Db::name('wine_usdt_account_generated')->where('status', 0)->count();
        
        $config = Db::name('config')->where('ename', 'in', ['usdt_generate_moren_pass', 'usdt_generate_keep_count'])->column('ename, value');
        $apiurl= 'http://172.19.176.169/v1/api/trc?ssr=1&pass='.$config['usdt_generate_moren_pass'];
        
        $arr = [];$time = time();
        if($count >= 10000 ){
            die();
        }
        $count = $config['usdt_generate_keep_count'] - $count;
        
        $count = 50;
        echo $count;
        if($count >= 0){
            for($i=0; $i<$count; $i++){
                $file_content = json_decode(file_get_contents($apiurl), true);
                
                $temp_arr = [
                    'address' => $file_content['address'],
                    'public_key' => $file_content['public_key'],
                    'private_key' => $file_content['private_key'],
                    'addtime' => $time
                ];
                sleep(1);
                array_push($arr, $temp_arr);
            }
            
            Db::name('wine_usdt_account_generated')->insertAll($arr);
        }
    }
    
    // 定时任务 - 卖家超时确认
    public function confirmTimeout(){
        $confirm_countdown = Db::name('config')->where('ename', 'confirm_timeout')->value('value');
        
        Db::name('wine_order_buyer')->where('delete', 0)->where('status', 'in', [1])->where('pay_status', 1)->where('paytime', '<', time()-$confirm_countdown*60*60)->chunk(1000, function($list) {
            // var_dump($list);exit;
            foreach ($list as $info){
                Db::startTrans();
                $time = time();
                
                try{
                    $res = Db::name('wine_order_buyer')->where('id', $info['id'])->update([
                        'status'=>8
                    ]);
                    if (!$res)throw new \Exception('失败');
                    
                    Db::commit();
                }
                catch (\Exception $e){
                    Db::rollback();
                    // $value = array('status' => 400, 'mess' => '转让失败', 'data' => array('status' => 400));
                }
            }
        });
    }
    
    // 定时任务 - 卖家合约超时确认
    public function confirmTimeoutContract(){
        $confirm_countdown = Db::name('config')->where('ename', 'confirm_timeout_contract')->value('value');
        
        Db::name('wine_order_buyer_contract')->where('delete', 0)->where('status', 'in', [1])->where('pay_status', 1)->where('paytime', '<', time()-$confirm_countdown*60*60)->chunk(1000, function($list) {
            // var_dump($list);exit;
            foreach ($list as $info){
                Db::startTrans();
                $time = time();
                
                try{
                    $res = Db::name('wine_order_buyer_contract')->where('id', $info['id'])->update([
                        'status'=>8
                    ]);
                    if (!$res)throw new \Exception('失败');
                    
                    Db::commit();
                }
                catch (\Exception $e){
                    Db::rollback();
                    // $value = array('status' => 400, 'mess' => '转让失败', 'data' => array('status' => 400));
                }
            }
        });
    }
    
    // 定时任务 - 买家超时支付
    public function payTimeout(){
        $confirm_countdown = Db::name('config')->where('ename', 'pay_timeout')->value('value');
        
        Db::name('wine_order_buyer')->where('delete', 0)->where('status', 'in', [1])->where('pay_status', 0)->where('addtime', '<', time()-$confirm_countdown*60*60)->chunk(1000, function($list) {
            // var_dump($list);exit;
            foreach ($list as $info){
                Db::startTrans();
                $time = time();
                
                try{
                    $res = Db::name('wine_order_buyer')->where('id', $info['id'])->update([
                        'status'=>9
                    ]);
                    if (!$res)throw new \Exception('失败');
                    
                    Db::commit();
                }
                catch (\Exception $e){
                    Db::rollback();
                    // $value = array('status' => 400, 'mess' => '转让失败', 'data' => array('status' => 400));
                }
            }
        });
    }
    
    // 定时任务 - 买家合约超时支付
    public function payTimeoutContract(){
        $confirm_countdown = Db::name('config')->where('ename', 'pay_timeout_contract')->value('value');
        
        Db::name('wine_order_buyer_contract')->where('delete', 0)->where('status', 'in', [1])->where('pay_status', 0)->where('addtime', '<', time()-$confirm_countdown*60*60)->chunk(1000, function($list) {
            // var_dump($list);exit;
            foreach ($list as $info){
                Db::startTrans();
                $time = time();
                
                try{
                    $res = Db::name('wine_order_buyer_contract')->where('id', $info['id'])->update([
                        'status'=>9
                    ]);
                    if (!$res)throw new \Exception('失败');
                    
                    Db::commit();
                }
                catch (\Exception $e){
                    Db::rollback();
                    // $value = array('status' => 400, 'mess' => '转让失败', 'data' => array('status' => 400));
                }
            }
        });
    }
    
    public function judgeEnable($user_id = 0){
        $count = Db::name('member')->where('id', $user_id)->where('checked', 1)->where('reg_enable', 1)->count();
        if($count > 0)return true;
        return false;
    }

    // 华为云OBS初始化分部上传
    public function initObs(){
        $obs = new obs();

        $res = $obs->initiateMultipartUpload($this->dirname);

        return datamsg(WIN, '获取成功', ['data'=>$res]);
    }

    // 逐个或并行上传段
    public function uploadPart(){
        $post = input('post.');

        $obs = new obs();

        $res = $obs->uploadPart($this->dirname, $post);

        return datamsg(WIN, '获取成功', ['data'=>$res]);
    }

    // 合并段
    public function completeMultipartUpload(){
        $post = input('post.');

        $obs = new obs();

        $res = $obs->listParts($this->dirname, $post);
        if($res){
            $res = $obs->completeMultipartUpload($this->dirname, $post);
        }

        return datamsg(WIN, '获取成功', ['data'=>$res]);
    }

    // 视频上传
    function uploadChooseVideo(){
        $file = $_FILES['file'];
        if($file){
            // 验证视频格式
            $type = explode('/', $file['type']);
            if($type[1] != 'mp4' && ($type[0] != 'video' || $type[1] != 'quicktime')){
                return datamsg(LOSE, '只能上传MP4格式的视频');
            }else{
                $type[1] = 'mp4';
            }

            // 验证视频大小
            if($file['size'] > 20*1024*1024){
                return datamsg(LOSE, '视频最大不能超过20M');
            }

            //图片文件夹判断
            $dirName = "video/course/" . date('Y') . '/' . date('m-d');

            $obs = new obs();
            $info = $obs->putObject($dirName.'/'.uniqid().'.'.$type[1], $file['tmp_name']);

            if($info){
                $videourl = $info['ObjectURL'];

//                $upload_video = Db::name('upload_video')->find();
//                if($upload_video){
//                    $res = Db::name('upload_video')->where('id', $upload_video['id'])->delete();
//                    if($res){
//                        // 删除华为云obs文件
//
//                    }
//                }
//
//                $data['videourl'] = $videourl;
//                $data['addtime'] = time();
//                $data['user_id'] = $this->user['user_id'];
//                Db::name('upload_video')->insert($data);
            }else{
                return datamsg(LOSE, $file->getError());
            }

        }

        return datamsg(WIN,'上传成功',array('videourl'=>$videourl));
    }
//    function uploadChooseVideo(){
//        $file = request()->file('file');
//        if($file){
//            //图片文件夹判断
//            $dirName = "public/uploads/course/video/" . date('Y') . '/' . date('m-d');
//            if(!is_dir($dirName)) {
//                mkdir($dirName, 0777, true);
//            }
//
//            $info = $file->validate(['size'=>200*1048576,'ext'=>'mp4,mp3'])->move(ROOT_PATH . $dirName);
//            if($info){
//                $original = 'uploads/course/video/'. date('Y') . '/' . date('m-d').'/'.$info->getSaveName();
//                $videourl = $original;
//
//                $upload_video = Db::name('upload_video')->find();
//                if($upload_video){
//                    $res = Db::name('upload_video')->where('id', $upload_video['id'])->delete();
//                    if($res && is_file(ROOT_DIR.$upload_video['videourl']))@unlink(ROOT_DIR.$upload_video['videourl']);
//                }
//
//                $data['videourl'] = '/'.$videourl;
//                $data['addtime'] = time();
//                $data['user_id'] = $this->user['user_id'];
//                Db::name('upload_video')->insert($data);
//            }else{
//                return datamsg(LOSE, $file->getError());
//            }
//
//        }
//
//        return datamsg(WIN,'上传成功',array('videoPath'=>$this->webconfig['weburl'].$videourl));
//    }

    public function loadHeadNav(){
        $list = Db::name('cate_art')->order('id asc')->where('useable=1')->select();

        $navArr = [];
        foreach ($list as $k=>$v){
            $navArr[$k]['cate_art_id'] = $v['id'];
            $navArr[$k]['id'] = $v['cate_keywords'];
            $navArr[$k]['name'] = $v['cate_name'];
        }
        $arr['list'] = $navArr;

        return $arr;
//        return datamsg(WIN,'获取成功', $arr);
    }

    public function verifyLogin(){
        if(!isset($this->user['user_id']) || $this->user['user_id'] <= 0)return datamsg(LOSE, '请先登录');
    }

    public function memberInfo($field = '*', $where = []){
        $userInfo = Db::name('member')->where($where)->field($field)->find();

        if(is_null($userInfo)){
            return '';
        }

        if(empty($userInfo['headimgurl'])){
            $userInfo['headimgurl'] = 'https://cxy365-file.obs.cn-south-1.myhuaweicloud.com/static/image/user-center/empty_headurl.png';
        }else{
            if(strpos($userInfo['headimgurl'], 'http') === false){
                $userInfo['headimgurl'] = $this->webconfig['weburl'].$userInfo['headimgurl'];
            }
        }

        if(preg_match("/^1[3456789]\d{9}$/", $userInfo['nickname'])){
            $userInfo['nickname'] = '匿名者';
        }

        if($field === '*' || count(explode(',', $field)) > 1)return $userInfo;
        return $userInfo[$field];
    }

    // 圖片上傳
    function uploadImg(){
        $file = request()->file('image');
        // var_dump($_FILES);exit;
        if($file){

            $info = aliyunOSS($_FILES);

            if($info){
                $original = $info['name'];
                $imgurl = $original;
            }else{
                return datamsg(LOSE, $file->getError());
            }
        }

        return datamsg(WIN,'上传成功',array('imgPath'=>$imgurl));
    }

    // 圖片上傳
    function uploadDynamicImg(){
        $file = request()->file('image');
        if($file){
            //图片文件夹判断
            $dirName = "public/uploads/community/dynamic/" . date('Y') . '/' . date('m-d');
            if(!is_dir($dirName)) {
                mkdir($dirName, 0777, true);
            }

            $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . $dirName);
            if($info){
                $original = 'uploads/community/dynamic/'. date('Y') . '/' . date('m-d').'/'.$info->getSaveName();

                $image = \think\Image::open('./'.$original);
                $image->thumb(300, 300)->save('./'.$original,null,90);
                $imgurl = $original;
            }else{
                return datamsg(LOSE, $file->getError());
            }

        }

        return datamsg(WIN,'上传成功',array('imgPath'=>$this->webconfig['weburl'].$imgurl));
    }
    
    /**
     * @description: token验证
     * @Author: cbing
     * @param : $isToken,1验证用户令牌，0不验证用户令牌
     * @return: json
     */
    function checkToken($isToken = 1){
        if(request()->isPost()){
                if($isToken){
                    $token = input('post.token');
                    if(empty($token)){
                        $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
                        return $value;
                    }
                }
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate($isToken);
                return $result;
            
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
            return $value;
        }
    }
	
	
	/**
	 *@description:把用户输入的文本转义（主要针对特殊符号和emoji表情）
	 * @Author: lxb
	 * @param : $str字符串
	 * @return: json
	*/
	function userTextEncode($str){
		if(!is_string($str))return $str;
		if(!$str || $str=='undefined')return '';

		$text = json_encode($str); //暴露出unicode
		$text = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i",function($str){
			return addslashes($str[0]);
		},$text); //将emoji的unicode留下，其他不动，这里的正则比原答案增加了d，因为我发现我很多emoji实际上是\ud开头的，反而暂时没发现有\ue开头。
		return json_decode($text);
	}
	
	/**
	* @description:解码上面的转义
	* @Author: lxb
	* @param : $str字符串
	* @return: json
	*/
	function userTextDecode($str){
		$text = json_encode($str); //暴露出unicode
		$text = preg_replace_callback('/\\\\\\\\/i',function($str){
			return '\\';
		},$text); //将两条斜杠变成一条，其他不动
		return json_decode($text);
	}
	
	/**
	* @description: 获取单个配置
	* @Author: lxb
	* @param : $id:配置ID
	* @return: json
	*/
	function getConfigInfo($id){
		
		$res = Db::name('config')->where('id',$id)->field('ename,value,values')->find();
		//print_r($res);die();
		return $res;
	}
	
	/**
	* @description: 会员积分规则
	* @Author: lxb
	* @param : $user_id:用户id;$num:积分;$type:1登录,2邀请注册,3完善信息（绑定手机）,4完善信息（上传头像）,5观看直播（30min）,6直播留言（次）,7分享（次）,8购物消费（%）,9订单评价（次）,10日常签到,11连续签到
	* @return: json
	*/
	function getIntegralRules($type){
		
		$configids = array("1"=>"155","2"=>"156","3"=>"157","4"=>"158","5"=>"159","6"=>"160","7"=>"161","8"=>"162","9"=>"163");
		
		$res = Db::name('config')->where('id',$configids[$type])->field('ename,value')->find();
		//print_r($res);die();
		return $res['value'];
	}
    /**
     * @description: 会员积分描述
     * @Author: lxb
     * @param : $type:1登录,2邀请注册,3完善信息（绑定手机）,4完善信息（上传头像）,5观看直播（30min）,6直播留言（次）,7分享（次）,8购物消费（%）,9订单评价（次）,10日常签到,11连续签到
     * @return: json
     */
    function getIntegralTitle($type){
        switch ($type){
            case 1:
                $title = '登录';
                break;
            case 2:
                $title = '邀请注册';
                break;
            case 3:
                $title = '绑定手机';
                break;
            case 4:
                $title = '上传头像';
                break;
            case 5:
                $title = '观看直播（30min）';
                break;
            case 6:
                $title = '直播发言';
                break;
            case 7:
                $title = '分享';
                break;
            case 8:
                $title = '购物消费';
                break;
            case 9:
                $title = '订单评价';
                break;
            case 10:
                $title = '日常签到';
                break;
            case 11:
                $title = '连续签到';
                break;
            default:
                $title = '未知类型';
                break;
        }
        return $title;
    }
	
	/**
	* @description: 会员积分
	* @Author: lxb
	* @param : $user_id:用户id;$num:积分;$type:1登录,2邀请注册,3完善信息（绑定手机）,4完善信息（上传头像）,5观看直播（30min）,6直播留言（次）,7分享（次）,8购物消费（%）,9订单评价（次）
	* @return: json
	*/
	function addIntegral($user_id,$num,$type,$order_id=0,$class = 0){
		
		if($type == 4){//修改头像只送1次
			$res = Db::name('member_integral')->where('user_id',$user_id)->where('type',4)->field('integral')->find();
			if(empty($res)){
				$data['user_id'] = $user_id;
				$data['integral'] = $num;
				$data['type'] = $type;
				$data['order_id'] = $order_id;
				$data['addtime'] = time();
				$data['class']  = $class;
			}
		}else{
			$data['user_id'] = $user_id;
			$data['integral'] = $num;
			$data['type'] = $type;
			$data['order_id'] = $order_id;
			$data['addtime'] = time();
			$data['class'] = $class;
		}
        Db::name('member_integral')->insert($data);
        if ($class == 0){
            Db::name('member')->where('id',$user_id)->setInc('integral', $num);
        }else{
            Db::name('member')->where('id',$user_id)->setDec('integral', $num);
        }
		return true;
	}
	
	/**
	* @description: 会员等级查询
	* @Author: lxb
	* @param : $num:传入的积分;$type 0返回会员等级名称，1返回会员折扣率
	* @return: json
	*/
	function getMemberLevel($num,$type=0){
		
		$level = Db::name('member_level')->field('rate,level_name,points_min,points_max')->order('sort asc')->select();
		
		foreach ($level as $value) {
			if (($num >= $value['points_min']) && ($num < $value['points_max'])) {
				return $type==1 ? $value['rate']: $value['level_name'];
			}
		}
		
	}

    /**
     * @description: 会员等级查询
     * @Author: lxb
     * @param : $integral:传入的积分
     * @return: array
     */
    function getMemberLevelInfo($integral){
        $levelInfo = Db::name('member_level')->where('points_min','<=',$integral)->where('points_max','>=',$integral)->order('sort asc')->find();
        return $levelInfo;
    }
	
	
	/**
	* @description: 直播间粉丝积分规则
	* @Author: lxb
	* @param : $user_id:用户id;$num:积分;$type:1累积观看10分钟 2累积观看30分钟 3累积观看60分钟 4发言每10次 5分享直播间（单日上线5次） 6点赞满10次（每日限一次） 7关注主播（仅限一次） 8连续7天观看10分钟以上 9购物一次（签收无退货） 10购物金额分（签收无退货）每100元 11购物评价 12优质购物评价（晒图，30字以上）
	* @return: json
	*/
	function getAliveIntegralRules($type){
		
		$configids = array("1"=>"169","2"=>"170","3"=>"171","4"=>"172","41"=>"173","5"=>"174","51"=>"175","6"=>"176","61"=>"177","7"=>"178","8"=>"179","9"=>"180","10"=>"181","11"=>"182","12"=>"183");
		
		$res = Db::name('config')->where('id',$configids[$type])->field('ename,value')->find();
		//print_r($res);die();
		return $res['value'];
	}
	
	/**
	* @description: 粉丝积分
	* @Author: lxb
	* @param : $user_id:用户id;$num:积分;$type:1累积观看10分钟 2累积观看30分钟 3累积观看60分钟 4发言每10次 5分享直播间（单日上线5次） 6点赞满10次（每日限一次） 7关注主播（仅限一次） 8连续7天观看10分钟以上 9购物一次（签收无退货） 10购物金额分（签收无退货）每100元 11购物评价 12优质购物评价（晒图，30字以上）
	* @return: json
	*/
	function addAliveIntegral($user_id,$shopid,$room,$num,$type,$order_id=0,$ping_id=0){
		
		$follow = Db::name('alive_fans')->where(['user_id'=>$user_id,'room'=>$room])->find();
		//默认未关注直播间
		if(empty($follow) && $user_id && $room){
			$arr['user_id'] = $user_id;
			$arr['room'] = $room;
			$arr['integral'] = 0;
			$arr['isfollow'] = 0;
			$arr['addtime'] = time();
			//print_r($data);die();
			Db::name('alive_fans')->insert($arr);
		}
		
		if(in_array($type, array('4','5','6'))){//上限
			
			//获取上限值
			$type_up = $type."1";
			$upnum = $this->getAliveIntegralRules($type_up);
			
			//当日起始时间
			$str_s=date("Y-m-d",time())." 00:00:00";
			$starttime = strtotime($str_s);
			$str_e=date("Y-m-d",time())." 23:59:59";
			$endtime=strtotime($str_e);
		
			$res = Db::name('fans_integral')->where('user_id',$user_id)->where('type',$type)->where('addtime','>',$starttime)->field('integral')->count();
			if($res < $upnum){
				$data['user_id'] = $user_id;
				$data['integral'] = $num;
				$data['type'] = $type;
				$data['shopid'] = $shopid;
				$data['room'] = $room;
				$data['order_id'] = $order_id;
				$data['ping_id'] = $ping_id;
				$data['addtime'] = time();
				//print_r($data);die();
				Db::name('fans_integral')->insert($data);
				Db::name('alive_fans')->where(['user_id'=>$user_id,'room'=>$room])->setInc('integral', $num);
			}
		}else{
			$data['user_id'] = $user_id;
			$data['integral'] = $num;
			$data['type'] = $type;
			$data['shopid'] = $shopid;
			$data['room'] = $room;
			$data['order_id'] = $order_id;
			$data['ping_id'] = $ping_id;
			$data['addtime'] = time();
			//print_r($data);die();
			Db::name('fans_integral')->insert($data);
			Db::name('alive_fans')->where(['user_id'=>$user_id,'room'=>$room])->setInc('integral', $num);
		}
		return true;
	}
	
	/**
	* @description: 粉丝等级查询
	* @Author: lxb
	* @param : $num:传入的积分;$type 0返回等级名称，1返回折扣率
	* @return: json
	*/
	function getFansLevel($num,$type=0){
		
		$level = Db::name('fans_level')->field('rate,level_name,points_min,points_max')->order('sort asc')->select();
		
		foreach ($level as $value) {
			if (($num >= $value['points_min']) && ($num < $value['points_max'])) {
				return $type==1 ? $value['rate']: $value['level_name'];
			}
		}
		
	}

    //处理上传图片
    public function uploadify()
    {
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){


                    $file = request()->file('filedata');
                    if ($file) {
                        $info = aliyunOSS($_FILES);
                        if ($info) {
                            $original = $info['name'];
                            $picarr = array('img_url' => $original);
                            $value = array('status' => 1, 'path' => $picarr);
                        } else {
                            $value = array('status' => 0, 'msg' => $file->getError());
                        }
                    } else {
                        $value = array('status' => 0, 'msg' => '文件不存在');
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

	public function loadHomeInfo(){
        $loadHeadNav = $this->loadHeadNav();
        $list['loadHeadNav'] = $loadHeadNav['list'];

        $ShopNews = new ShopNews();
//        $subscribed = $ShopNews->subscribed();
//        $list['subscribed'] = $subscribed;

        $all = $ShopNews->all();
        $list['all'] = $all;

        $banner = $ShopNews->banner();
        $list['banner'] = $banner;

        $alive = new Alive();
        $list['alive'] = $alive->getaliveindex_copy();

//        $school = new School();
//        $list['school'] = $school->getCourseLiveList();

        return datamsg(WIN, '获取成功', $list);
    }

	// wine_goods 极差
    // $user_id 购买者     $total_reward奖励的总金额   
    public function wineGoodsGradePoor($user_id=0, $total_reward=0, $cur_level = 1, $wine_level_id_arr=[], $target_id=0, $cur_rate=0, $wine_goods_id=0, $wine_deal_area_id=0, $contract=0){
        
	    if($total_reward == 0 || $user_id == 0){
	        return false;
        }

        $time = time();
        
        $info = Db::name('member')->where('id', $user_id)->find();

        if ($info){
            if($info['jiedianid'] && $info['jiedianid']>0){
                $info['one_level'] = $info['jiedianid'];
            }
            
            if(empty($info['one_level']) || $info['one_level'] == 0){
                return false;
            }
            $poor_member = Db::name('member')->where('id', $info['one_level'])->find();
            
            $poor_member['agent_type'] = $poor_member['false_agent_type']>$poor_member['agent_type'] ? $poor_member['false_agent_type'] : $poor_member['agent_type'];

            $countt = 0;
            if ($contract == 0){
                $rree1 = Db::name('wine_order_record')->where('wine_deal_area_id', $wine_deal_area_id)->where('wine_goods_id', $wine_goods_id)->where('buy_id', $poor_member['id'])->where('addtime', '>=', strtotime('today'))->find();
                $rree2 = Db::name('wine_order_qiangou')->where('wine_deal_area_id', $wine_deal_area_id)->where('wine_goods_id', $wine_goods_id)->where('buy_id', $poor_member['id'])->where('addtime', '>=', strtotime('today'))->find();
            }
            elseif($contract == 1){
                // $rree1 = Db::name('wine_order_record_contract')->where('wine_deal_area_id', $wine_deal_area_id)->where('wine_goods_id', $wine_goods_id)->where('buy_id', $poor_member['id'])->where('addtime', '>=', strtotime('today'))->find();
                // $rree2 = Db::name('wine_order_qiangou_contract')->where('wine_deal_area_id', $wine_deal_area_id)->where('wine_goods_id', $wine_goods_id)->where('buy_id', $poor_member['id'])->where('addtime', '>=', strtotime('today'))->find();
                $countt22 = 1;
            }

            if(isset($rree1['wine_deal_area_id']) && $rree1['wine_deal_area_id']==10){
                if(!is_null($rree2)){
                    $countt = 1;
                }
            }
            else{
                if(!is_null($rree1) && !is_null($rree2)){
                    $countt = 1;
                }
            }
// echo $info['one_level'].'-';

                // 总的等级
                $wine_level_id_arr = Db::name('wine_level')->order('id asc')->column('id');
                Db::startTrans();
                try{
                    $wallet_ss = Db::name('wallet')->where('user_id', $info['one_level'])->find();
                    $wallet_id = $wallet_ss['id'];
                    $wallet_info = $wallet_ss;
                    
                    if(isset($info['one_level']) && $info['one_level']>0){
                        if ($cur_level == 1){
                            $rate = Db::name('config')->where('ename', 'direct_reward')->value('value');
    
                            $redirect = $total_reward*$rate/100;
                        
                            if(($poor_member['reg_enable']==1 && $countt==1) || $countt22==1){
                                // 直推奖励
                                Db::name('wallet')->where('user_id', $info['one_level'])->inc('commission', $redirect)->update();
    
                                $detail_direct = [
                                    'de_type' => 1,
                                    'sr_type' => 64,
                                    'before_price'=> $wallet_info['commission'],
                                    'price' => $redirect,
                                    'after_price'=> $wallet_info['commission']+$redirect,
                                    'user_id' => $info['one_level'],
                                    'wat_id' => $wallet_id,
                                    'time' => $time,
                                    'agent_type'=>$poor_member['agent_type'],
                                    'target_id' => $info['id']
                                ];
                                if($contract == 1)$detail_direct['sr_type'] = 1154;
                                $this->addDetail($detail_direct);
    //                            Db::name('detail')->insert($detail_direct);
                            }
                        }
                    }

                    $level_rate = Db::name('wine_level')->where('id', $poor_member['agent_type'])->value('rate');
                    $agent_type = $poor_member['agent_type'];
                    $init = 0;
                    if($level_rate > $cur_rate){
                        $init = $level_rate;
                        $level_rate = $level_rate - $cur_rate;
                        $cur_rate = $init;
                    }
                    else{
                        $level_rate = $init;
                    }
                    // echo $agent_type.'-';
                    // var_dump($wine_level_id_arr);
                    if (in_array($agent_type, $wine_level_id_arr)){
                        
                        if ($level_rate>0 && $agent_type>0 && $total_reward>0){

                            $level_reward = $total_reward*$level_rate/100;
                            
                            if(($poor_member['reg_enable'] == 1 && $countt==1) || $countt22==1){
                                Db::name('wallet')->where('user_id', $info['one_level'])->inc('commission', $level_reward)
                                    ->update();
    
                                $detail_extreme_manage = [
                                    'de_type' => 1,
                                    'sr_type' => 65,
                                    'before_price'=> $wallet_info['commission'],
                                    'price' => $level_reward,
                                    'after_price'=> $wallet_info['commission']+$level_reward,
                                    'user_id' => $info['one_level'],
                                    'wat_id' => $wallet_id,
                                    'time' => $time,
                                    'agent_type'=>$agent_type,
                                    'target_id' => $target_id
                                ];
                                if($contract == 1)$detail_extreme_manage['sr_type'] = 1155;
                                $this->addDetail($detail_extreme_manage);
//                                Db::name('detail')->insert($detail_extreme_manage);
                            }

                            if(($key = array_search($agent_type,$wine_level_id_arr))){
                                unset($wine_level_id_arr[$key]);
                            }
                        }
                    }

                    // 管理奖代数奖励
                    $manage = [1,2,3,4,5];
                    if (in_array($cur_level, $manage) && (($poor_member['reg_enable']==1 && $countt==1) || $countt22==1)) {
                        // code...
                        $this->manageReward($cur_level, $info['one_level'], $total_reward, $target_id, $contract);
                    }
                    Db::commit();
                    
                    // 极差奖励
                    if (count($wine_level_id_arr)>0)$this->wineGoodsGradePoor($info['one_level'], $total_reward, ++$cur_level, $wine_level_id_arr, $target_id, $cur_rate, $wine_goods_id, $wine_deal_area_id, $contract);
                }
                catch(\Exception $e){
                    echo $e->getMessage();
                    Db::rollback();
                }
        }

    }
    
    public function manageReward($generation_num, $user_id, $total_reward, $target_id, $contract){
        $rate = Db::name('wine_manage_rewards')->where('generation_num', $generation_num)->value('rate');
        if($rate > 0){
            $manageRewardAmount = $total_reward * $rate/100;

            $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
            $manage_reward_detail = [
                'de_type' => 1,
                'sr_type' => 80,
                'before_price'=> $wallet_info['commission'],
                'price' => $manageRewardAmount,
                'after_price'=> $wallet_info['commission']+$manageRewardAmount,
                'user_id' => $user_id,
                'wat_id' => $wallet_info['id'],
                'time' => time(),
                'target_id' => $target_id
            ];
            if($contract == 1)$manage_reward_detail['sr_type'] = 1180;
            $res = $this->addDetail($manage_reward_detail);
//            $res = Db::name('detail')->insert($manage_reward_detail);
            if(!$res)throw new Exception('失败');
            
            $res = Db::name('wallet')->where('user_id', $user_id)->setInc('commission', $manageRewardAmount);
            if(!$res)throw new Exception('失败');
        }
    }

    // wine_goods 升级
    // $user_id 购买者
    public function wineGoodsUpgrade($user_id=0){
	    $userInfo = Db::name('member')->where('id', $user_id)->find();
	    
	    // 父级
	    if(isset($userInfo['one_level']) && $userInfo['one_level']>0){
	        $parentUser = Db::name('member')->where('id', $userInfo['one_level'])->find();
	        
	        if($parentUser['agent_type'] == 0){
	           // $count = Db::name('member')->where('one_level', $parentUser['id'])->where('agent_num', '>', 0)->count();
	            $pid = $parentUser['id'];
	            $direct_count = Db::name('member')->where('one_level', $pid)->where('reg_enable', 1)->count();
            	$team_count = Db::name('member')->where('reg_enable', 1)->where('id', '<>', $pid)->where(function ($query) use($pid) {
                    $query->where('team_id', 'like', '%,'.$pid)->whereOr('team_id', 'like', '%,'.$pid.',%');
                })->count();
                // echo $team_count;exit;
                $wine_level_info = Db::name('wine_level')->where('id', 1)->find();
	            if($direct_count>=$wine_level_info['uplevel_num'] && $team_count>=$wine_level_info['team_num']){
	                Db::name('member')->where('id', $pid)->update([
	                    'agent_type' => 1
	                ]);
	            }
	        }
	        elseif($parentUser['agent_type']>=1 && $parentUser['agent_type']<=4){
	           // $v1 = 0;
	            $agent_type = $parentUser['agent_type']+1;
	            $pid = $parentUser['id'];
	            $direct_count = Db::name('member')->where('one_level', $pid)->where('reg_enable', 1)->where('agent_type', '>=', $parentUser['agent_type'])->count();
            	$team_count = Db::name('member')->where('reg_enable', 1)->where('id', '<>', $pid)->where(function ($query) use($pid) {
                    $query->where('team_id', 'like', '%,'.$pid)->whereOr('team_id', 'like', '%,'.$pid.',%');
                })->count();
                // echo $agent_type;exit;
                $wine_level_info = Db::name('wine_level')->where('id', $agent_type)->find();
                // var_dump($wine_level_info);exit;
	            if($direct_count>=$wine_level_info['uplevel_num'] && $team_count>=$wine_level_info['team_num']){
	                Db::name('member')->where('id', $pid)->update([
	                    'agent_type' => $agent_type
	                ]);
	            }
	        }
	        
            $this->wineGoodsUpgrade($userInfo['one_level']);
        }

    }

    // 定时任务 - 等级判断
    public function timingTaskLevelVerify(){
	    Db::name('member')->chunk(100, function ($members){
	        foreach ($members as $member){
                $this->wineGoodsUpgrade($member['id']);
            }
        });
    }
    
    // 定时任务 - 合约进货转出售
    public function incomeToSaleContract(){
        Db::name('wine_order_buyer_contract')->where('pay_status', 1)->where('status', 'in', [2])->where('transfer', 1)->where('transfer_wine_contract_day_id', '>', 0)->where('delete', 0)->where('day', '>', 0)->chunk(1000, function ($buyers){
            foreach ($buyers as $buyer){
                Db::startTrans();
                try{
                    $wine_goods = Db::name('wine_goods_contract')->where('id', $buyer['wine_goods_id'])->field('value, goods_name, thumb_url goods_thumb')->find();
                    
                    $wine_contract_day = Db::name('wine_contract_day')->where('id', $buyer['transfer_wine_contract_day_id'])->field('day, day_rate, id')->find();
                    
                    if(is_null($wine_contract_day))throw new \Exception('失败5');
                    $day_rate = $wine_contract_day['day_rate'];
                    if(!$day_rate || $day_rate<=0)throw new \Exception('失败5');
                    
                    if($buyer['re_furnace'] == 1){
                        $day = $buyer['day'];
                        $sale_amount = $buyer['sale_amount'];
                    }
                    else{
                        $day = $wine_contract_day['day'];
                        $sale_amount = $buyer['buy_amount'] + $buyer['buy_amount']*$day_rate/100;
                    }
                    if(!$day || $day<=0)throw new \Exception('失败5');
                    
                    $addtime = $buyer['sale_addtime'];
                    $timeEnd = strtotime(date('Y-m-d', $addtime+$day*24*60*60));
                    // var_dump($timeEnd);exit;
                    
                    if(time() > $timeEnd){
                        $sale = [
                            'goods_name' => $wine_goods['goods_name'],
                            'addtime' => time(),
                            'goods_rate' => $day_rate,
                            'goods_thumb' => $wine_goods['goods_thumb'],
                            'pipei_amount' => $sale_amount,
                            'sale_amount' => $sale_amount,
                            'sale_id' => $buyer['buy_id'],
                            'odd' => $buyer['odd'],
                            'wine_goods_id' => $buyer['wine_goods_id'],
                            'status' => 0,
                            'type' => 1,
                            'profit' => $sale_amount-$buyer['buy_amount'],
                            'upgrade' => $buyer['upgrade'],
                            'wine_contract_day_id' => $buyer['transfer_wine_contract_day_id']
                        ];
                        $res = Db::name('wine_order_saler_contract')->insert($sale);
                        if(!$res)throw new \Exception('失败4');
                        
                        
                        $res = Db::name('wine_order_buyer_contract')->where('id', $buyer['id'])->update([
                            'delete' => 1
                        ]);
                        if(!$res)throw new \Exception('失败5');
                    }
                        
                    Db::commit();
                }
                catch(\Exception $e){
                    Db::rollback();
                    echo $e->getMessage();
                }
            }
        });
    }
    
    // 定时任务 - 进货转出售
    public function incomeToSale(){
        // $wine_goods = Db::name('wine_goods')->field('value, rate, day, id, goods_name, thumb_url')->order('id desc')->select();
        $wine_deal_area = Db::name('wine_deal_area')->column('id, deal_area');
        $income_to_sale_lead_time = Db::name('config')->where('ename', 'income_to_sale_lead_time')->value('value');
        
        $config = Db::name('config')->where('ename', 'in', ['legoufen_frozen_most', 'legoufen_frozen_rate'])->column('ename, value');
        $legoufen_frozen_most = $config['legoufen_frozen_most'];
        $legoufen_frozen_rate = $config['legoufen_frozen_rate'];
       
        Db::name('wine_order_buyer')->where('pay_status', 1)->where('status', 'in', [2])->where('delete', 0)->where('day', '>', 0)->chunk(1000, function ($buyers) use ($wine_deal_area, $income_to_sale_lead_time, $legoufen_frozen_most, $legoufen_frozen_rate){
            foreach ($buyers as $buyer){
                Db::startTrans();
                try{
                    
                    $wine_goods = Db::name('wine_goods')->where('id', $buyer['wine_goods_id'])->field('value, rate goods_rate, goods_name, thumb_url goods_thumb')->find();
                    $value = $wine_goods['value'];
                    
                    $valueArr = explode('-', $value);
                    // if($buyer['sale_amount'] >= $valueArr[1]){
                    if(false){
                        $day = $buyer['day'];
                        $addtime = $buyer['sale_addtime'];
                        // $wine_deal_area_id = $buyer['wine_deal_area_id'];
                        
                        $timeEnd = strtotime(date('Y-m-d', $addtime+$day*24*60*60));
                        
                        $start = explode(':', explode('-', $wine_deal_area[$buyer['wine_deal_area_id']])[0]);
                        $startTime = $start[0]*60*60 + $start[1]*60 - 5*60;
                        
                        if(time() > $timeEnd+$startTime){
                            $res = Db::name('wine_order_buyer')->where('id', $buyer['id'])->update([
                                'top_stop' => 1
                            ]);
                            if(!$res)throw new \Exception('转出售失败1');
                            
                            // $kou = $buyer['buy_amount']*$legoufen_frozen_rate/100*$buyer['day'];
                            $buyer_frozen_fuel = $buyer['sale_frozen_point'];
                            $wallet_info = Db::name('wallet')->where('user_id', $buyer['buy_id'])->find();
                            // var_dump($buyer_frozen_fuel);exit;
                            $res = Db::name('wallet')->where('user_id', $buyer['buy_id'])->dec('sale_frozen_point', $buyer_frozen_fuel)->update();
                            if(!$res)throw new \Exception('转出售失败2');
                            
                             $detail = [
                                'de_type' => 2,
                                'zc_type' => 63,
                                'before_price'=> $wallet_info['sale_frozen_point'],
                                'price' => $buyer_frozen_fuel,
                                'after_price'=> $wallet_info['sale_frozen_point']-$buyer_frozen_fuel,
                                'user_id' => $buyer['buy_id'],
                                'wat_id' => $wallet_info['id'],
                                'time' => time(),
                                'target_id' => $buyer['id']
                             ];
                            $res = $this->addDetail($detail);
//                             $res = Db::name('detail')->insert($detail);
                             if(!$res)throw new \Exception('转出售失败3');
                        }
                    }
                    else{
                        $day = $buyer['day'];
                        $addtime = $buyer['sale_addtime'];
                        // $wine_deal_area_id = $buyer['wine_deal_area_id'];
                        
                        $timeEnd = strtotime(date('Y-m-d', $addtime+$day*24*60*60));
                        
                        $start = explode(':', explode('-', $wine_deal_area[$buyer['wine_deal_area_id']])[0]);
                        $startTime = $start[0]*60*60 + $start[1]*60 - 5*60;
                        
                        
                        // if(time() > $timeEnd+$startTime){
                        if(time() > $timeEnd){
                            $sale = [
                                'goods_name' => $wine_goods['goods_name'],
                                'addtime' => time(),
                                'goods_rate' => $wine_goods['goods_rate'],
                                'goods_thumb' => $wine_goods['goods_thumb'],
                                'pipei_amount' => $buyer['sale_amount'],
                                'sale_amount' => $buyer['sale_amount'],
                                'sale_id' => $buyer['buy_id'],
                                'odd' => $buyer['odd'],
                                'wine_goods_id' => $buyer['wine_goods_id'],
                                'status' => 0,
                                'type' => 1,
                                'profit' => $buyer['sale_amount']-$buyer['buy_amount'],
                                'upgrade' => $buyer['upgrade'],
                                'wine_deal_area_id' => $buyer['wine_deal_area_id']
                            ];
                            $res = Db::name('wine_order_saler')->insert($sale);
                            if(!$res)throw new \Exception('失败4');
                            
                            
                            $res = Db::name('wine_order_buyer')->where('id', $buyer['id'])->update([
                                'delete' => 1
                            ]);
                            if(!$res)throw new \Exception('失败5');

                        }
                    }
                    
                    Db::commit();
                }
                catch(\Exception $e){
                    Db::rollback();
                    echo $e->getMessage();
                }
            }
        });
    }
    
    // 定时任务 - 冻结
    public function exchangeFinesFrozen(){
        die();
        // 匹配进货付款冻结时间已过(小时)
        $pipei_frozen_timeout_frozen = Db::name('config')->where('ename', 'pipei_frozen_timeout_frozen')->value('value');
        
        Db::name('wine_order_buyer')
            ->where('addtime', '<', time()-$pipei_frozen_timeout_frozen*60*60)
            ->where('pay_status', 0)
            ->where('status', 4)->where('delete', 0)
            ->chunk(1000, function ($buyers){
                foreach ($buyers as $v){
                    Db::name('wine_order_buyer')->where('id', $v['id'])->update([
                        'status' => 5
                    ]);
                    
                    Db::name('wine_order_saler')->where('id', $v['wine_order_saler_id'])->where('status', 1)->update([
                        'status' => 0
                    ]);
                    
                    // 订单超时未付款 冻结时间已过  -  冻结惩罚
                    Db::name('member')->where('id', $v['buy_id'])->update([
                        'checked' => 0
                    ]);
                }
            });
    }
    
    // 定时任务 - 罚款
    public function exchangeFines(){
        // 匹配进货付款时间已过罚款(小时)
        $pipei_paytime_timeout_fines = Db::name('config')->where('ename', 'pipei_paytime_timeout_fines')->value('value');
        
        Db::name('wine_order_buyer')
            ->where('addtime', '<', time()-$pipei_paytime_timeout_fines*60*60)
            ->where('pay_status', 0)
            ->where('status', 1)->where('delete', 0)
            ->chunk(1000, function ($buyers){
                foreach ($buyers as $v){
                    $addtime = $v['addtime'];
                    $time = time();
                    
                    Db::startTrans();
                    try{
                        $res = Db::name('wine_order_buyer')->where('id', $v['id'])->update([
                            'status' => 4
                        ]);
                        if(!$res)throw new \Exception('异常');
                        
                        Db::commit();
                    }
                    catch(\Exception $e){
                        Db::rollback();
                    }
                }
            });
    }
    
    // 定时任务 - 自动确认
    public function confirmAuto(){
        $exchange_success_brand = Db::name('config')->where('ename', 'exchange_success_brand')->value('value');
        
        // 确认时间倒计时(小时)
        $confirm_countdown = Db::name('config')->where('ename', 'confirm_countdown')->value('value');
        Db::name('wine_order_buyer')->where('delete', 0)->where('status', 'in', [1, 4])->where('paytime', '<', time()-$confirm_countdown*60*60)->where('pay_status', 1)->chunk(1000, function($list) use($exchange_success_brand){
            // var_dump($list);exit;
            foreach ($list as $info){
                Db::startTrans();
                $time = time();
                
                try{
                    $res = Db::name('wine_order_buyer')->where('id', $info['id'])->update([
                        'status'=>2,
                        'confirm_exchange' => $time
                    ]);
                    if (!$res)throw new \Exception('转让失败');


                    $wine_order_saler_info = Db::name('wine_order_saler')
                        ->where('sale_id', $info['sale_id'])
                        ->where('delete', 0)
                        ->where('status', 1)
                        ->where('id', $info['wine_order_saler_id'])->find();
                    if ($wine_order_saler_info['pipei_amount'] == $wine_order_saler_info['sale_amount']){
                         $income_total_price = Db::name('wine_order_buyer')->where('delete', 0)->where('sale_id', $info['sale_id'])->where('pay_status', 1)
                                ->where('wine_order_saler_id', $wine_order_saler_info['id'])->sum('buy_amount');

                         if($income_total_price == $wine_order_saler_info['sale_amount']){
                             $res = Db::name('wine_order_saler')->where('id', $wine_order_saler_info['id'])->update([
                                'status'=>2,
                                'confirm_exchange'=>$time
                             ]);
                             if (!$res)throw new \Exception('转让失败');
                             
                             Db::name('member')->where('id', $info['buy_id'])->setInc('agent_num');
                            //  if (!$res)throw new \Exception('转让失败2');
                         }
                    }

                    Db::commit();

                }
                catch (\Exception $e){
                    Db::rollback();
                }
            }
        });
    }
    
    // 定时任务 - 提前匹配转正常匹配
    public function advanceToNormal(){
        $wine_goods = Db::name('wine_goods')->column('id, adopt');
        $kou_buyer_profit_perc = Db::name('config')->where('ename', 'kou_buyer_profit_perc')->value('value');

        Db::name('wine_order_buyer_advance_match')->where('delete', 0)->chunk(1000, function ($advance_match) use ($wine_goods, $kou_buyer_profit_perc){
            foreach ($advance_match as $v){
                $id = $v['id'];
                unset($v['id']);
                $wine_goods_id = $v['wine_goods_id'];
                $adopt = explode('-', $wine_goods[$wine_goods_id]);
                $hi = explode(':', $adopt[0]);
                // var_dump(date('d', $v['addtime']));exit;
                // if(date('H')>=$hi[0] && date('d')!=date('d', $v['addtime'])){
                if((date('H')>=$hi[0] && date('H', $v['addtime'])<$hi[0]) || (date('H')>=$hi[0] && date('d')!=date('d', $v['addtime']))){
                    Db::startTrans();
                    try{
                        $v['addtime'] = time();
                        $v['active_time'] = strtotime(date('Y-m-d', $v['addtime']) . ' ' . $hi[0] . ':00:00');
                        $res = Db::name('wine_order_buyer')->insert($v);
                        if(!$res){
                            throw new \Exception('转换失败');
                        }
                        
                        $res = Db::name('wine_order_buyer_advance_match')->where('delete', 0)->where('id', $id)->update([
                            'delete' => 1
                        ]);
                        if(!$res){
                            throw new \Exception('转换删除失败');
                        }
                        
                        $res = Db::name('wine_order_saler')->where('id', $v['wine_order_saler_id'])->where('status', 4)->update([
                            'status' => 1
                        ]);
                        // if(!$res){
                        //     throw new \Exception('转换销售表更新失败');
                        // }
                
                        $res = Db::name('wine_order_record')->where('id', $v['wine_order_record_id'])->where('status', 4)->update([
                            // 'buy_amount' => $v['buy_amount'],
                            'status' => 1
                        ]);
                        if (!$res){
                            throw new \Exception('订货记录失败');
                        }
                        
                        $kou = ($v['sale_amount'] - $v['buy_amount'])*$kou_buyer_profit_perc/100;
                        $wallet_info = Db::name('wallet')->where('user_id', $v['buy_id'])->find();
                        $res = Db::name('wallet')->where('user_id', $v['buy_id'])->setDec('brand', $kou);
                        if(!$res)throw new \Exception('品牌使用费不足');
                        
                        $detail = [
                            'de_type' => 2,
                            'zc_type' => 11,
                            'before_price'=> $wallet_info['brand'],
                            'price' => $kou,
                            'after_price'=> $wallet_info['brand']-$kou,
                            'user_id' => $v['buy_id'],
                            'wat_id' => $wallet_info['id'],
                            'time' => time()
                        ];

                        $res = $this->addDetail($detail);
//                        $res = Db::name('detail')->insert($detail);
                        if(!$res)throw new \Exception('匹配失败1');
                        
                        
                        Db::commit();
                    }
                    catch(\Exception $e){
                        Db::rollback();
                        // echo $e->getMessage();
                    }
                }
            }
        });
    }
    
    public function returnFule(){
        $legoufen_frozen_return = Db::name('config')->where('ename', 'legoufen_frozen_return')->value('value')*60*60;
        $wine_deal_area = Db::name('wine_deal_area')->column('id, deal_area');
        
        Db::name('wine_order_record')->where('status', 0)->where('addtime', '>=', strtotime('today'))->chunk(1000, function ($wine_order_record) use($legoufen_frozen_return, $wine_deal_area){
            foreach ($wine_order_record as $v){
                $sss = explode('-', $wine_deal_area[$v['wine_deal_area_id']])[1];
                $curtime = strtotime(date('Y-m-d', $v['addtime']).' '.$sss);
                
                if($curtime+$legoufen_frozen_return < time()){
                    Db::startTrans();
                    try{
                        $frozen_fuel = $v['frozen_point'];
                        // $wine_yuyue_return_info = Db::name('wine_yuyue_return')->where('user_id', $v['buy_id'])->where('wine_goods_id', $v['wine_goods_id'])->where('wine_deal_area_id', $v['wine_deal_area_id'])->where('addtime', '>=', strtotime('today'))->where('addtime', '<', strtotime(date('Y-m-d', $v['addtime']).' '.explode('-', $wine_deal_area[$v['wine_deal_area_id']])[0]))->find();
                        // $wine_yuyue_return_info = Db::name('wine_yuyue_return')->where('user_id', $v['buy_id'])->where('wine_goods_id', $v['wine_goods_id'])->where('wine_deal_area_id', $v['wine_deal_area_id'])->where('addtime', '>=', strtotime('today'))->where('addtime', '<=', strtotime(date('Y-m-d', $v['addtime']).' '.explode('-', $wine_deal_area[$v['wine_deal_area_id']])[1]))->find();
                        $wine_yuyue_return_info = Db::name('wine_order_qiangou')->where('buy_id', $v['buy_id'])->where('wine_goods_id', $v['wine_goods_id'])->where('wine_deal_area_id', $v['wine_deal_area_id'])->where('addtime', '>=', strtotime('today'))->where('addtime', '<=', strtotime(date('Y-m-d', $v['addtime']).' '.explode('-', $wine_deal_area[$v['wine_deal_area_id']])[1]))->find();
                        
                        if(is_null($wine_yuyue_return_info)){
                            Db::name('wine_order_record')->where('id', $v['id'])->where('status', 0)->update([
                                'status' => 2,
                                'updatetime'=>time()
                            ]);
                        }
                        else{
                            $res = Db::name('wine_order_record')->where('id', $v['id'])->where('status', 0)->update([
                                'status' => 1,
                                'updatetime'=>time()
                            ]);
                            if(!$res)throw new \Exception('返还失败');
                            $wallet_info = Db::name('wallet')->where('user_id', $v['buy_id'])->find();
                            
                            $res = Db::name('wallet')->where('user_id', $v['buy_id'])->dec('frozen_point', $frozen_fuel)->inc('point', $frozen_fuel)->update();
                            if(!$res)throw new \Exception('返还失败');

                             $detail = [
                                'de_type' => 2,
                                'zc_type' => 71,
                                'before_price'=> $wallet_info['frozen_point'],
                                'price' => $frozen_fuel,
                                'after_price'=> $wallet_info['frozen_point']-$frozen_fuel,
                                'user_id' => $v['buy_id'],
                                'wat_id' => $wallet_info['id'],
                                'time' => time(),
                                'target_id' => $v['id']
                             ];
                            $res = $this->addDetail($detail);
//                             $res = Db::name('detail')->insert($detail);
                             if(!$res)throw new \Exception('返还失败');
                            
                             $detail = [
                                'de_type' => 1,
                                'sr_type' => 71,
                                'before_price'=> $wallet_info['point'],
                                'price' => $frozen_fuel,
                                'after_price'=> $wallet_info['point']+$frozen_fuel,
                                'user_id' => $v['buy_id'],
                                'wat_id' => $wallet_info['id'],
                                'time' => time(),
                                'target_id' => $v['id']
                             ];
                            $res = $this->addDetail($detail);
//                             $res = Db::name('detail')->insert($detail);
                             if(!$res)throw new \Exception('返还失败');
                        }
                        
                         Db::commit();
                    }
                    catch(Exception $e){
                        Db::rollback();
                    }
                }
            }
        });
    }
    
    public function returnFuleContract(){
        $legoufen_frozen_return = Db::name('config')->where('ename', 'jingpai_faile_return_deposit')->value('value')*60*60;
        // $wine_deal_area = Db::name('wine_deal_area_contract')->column('id, deal_area');
        $wine_deal_area = Db::name('wine_contract_day')->column('id, deal_area');
        
        Db::name('wine_order_record_contract')->where('status', 0)->where('addtime', '>=', strtotime('today'))->chunk(1000, function ($wine_order_record) use($legoufen_frozen_return, $wine_deal_area){
            foreach ($wine_order_record as $v){
                $sss = explode('-', $wine_deal_area[$v['wine_contract_day_id']])[1];
                $curtime = strtotime(date('Y-m-d', $v['addtime']).' '.$sss);
                
                if($curtime+$legoufen_frozen_return < time()){
                    Db::startTrans();
                    try{
                        $frozen_fuel = $v['frozen_point'];
                        
                        $qiangou = Db::name('wine_order_qiangou_contract')->where('addtime', '>=', strtotime('today'))->where('buy_id', $v['buy_id'])->where('wine_goods_id', $v['wine_goods_id'])->where('wine_contract_day_id', $v['wine_contract_day_id'])->count();
                        
                        $buyer_order = Db::name('wine_order_buyer_contract')->where('addtime', '>=', strtotime('today'))->where('buy_id', $v['buy_id'])->where('wine_goods_id', $v['wine_goods_id'])->where('wine_contract_day_id', $v['wine_contract_day_id'])->count();
                        
                        if($buyer_order && $buyer_order>0){
                            Db::name('wine_order_record_contract')->where('id', $v['id'])->where('status', 0)->update([
                                'status' => 3,
                                'updatetime'=>time()
                            ]);
                        }
                        else{
                            if($qiangou == 0){
                                Db::name('wine_order_record_contract')->where('id', $v['id'])->where('status', 0)->update([
                                    'status' => 2,
                                    'updatetime'=>time()
                                ]);
                            }
                            else{
                                $res = Db::name('wine_order_record_contract')->where('id', $v['id'])->where('status', 0)->update([
                                    'status' => 1,
                                    'updatetime'=>time()
                                ]);
                                if(!$res)throw new \Exception('返还失败');
                                $wallet_info = Db::name('wallet')->where('user_id', $v['buy_id'])->find();
                                
                                $res = Db::name('wallet')->where('user_id', $v['buy_id'])->dec('frozen_point', $frozen_fuel)->inc('point', $frozen_fuel)->update();
                                if(!$res)throw new \Exception('返还失败');
    
                                 $detail = [
                                    'de_type' => 2,
                                    'zc_type' => 1100,
                                    'before_price'=> $wallet_info['frozen_point'],
                                    'price' => $frozen_fuel,
                                    'after_price'=> $wallet_info['frozen_point']-$frozen_fuel,
                                    'user_id' => $v['buy_id'],
                                    'wat_id' => $wallet_info['id'],
                                    'time' => time(),
                                    'target_id' => $v['id']
                                 ];
                                $res = $this->addDetail($detail);
                                 if(!$res)throw new \Exception('返还失败');
                                
                                 $detail = [
                                    'de_type' => 1,
                                    'sr_type' => 1111,
                                    'before_price'=> $wallet_info['point'],
                                    'price' => $frozen_fuel,
                                    'after_price'=> $wallet_info['point']+$frozen_fuel,
                                    'user_id' => $v['buy_id'],
                                    'wat_id' => $wallet_info['id'],
                                    'time' => time(),
                                    'target_id' => $v['id']
                                 ];
                                $res = $this->addDetail($detail);
                                 if(!$res)throw new \Exception('返还失败');
                            }
                        }
                        
                        Db::commit();
                    }
                    catch(Exception $e){
                        Db::rollback();
                    }
                }
            }
        });
    }
    
    public function autoAuditVip(){
        Db::name('wine_apply_vip')->where('status', 0)->chunk(1000, function($wine_apply_vipsss){
            foreach ($wine_apply_vipsss as $wine_apply_vip){
                $vip_enjoy_day = Db::name('config')->where('ename', 'vip_enjoy_day')->value('value');
                    
                $time = strtotime('+'.$vip_enjoy_day.' day');
    
                $info = Db::name('member')->where('id', $wine_apply_vip['user_id'])->find();
                Db::startTrans();
                try{
                    if(!$info['vip_time']){
                        $res = Db::name('member')->where('id', $wine_apply_vip['user_id'])->update([
                            'vip_time' => $time
                        ]);
                        // echo Db::name('member')->getLastSql();
                        if(!$res){
                            throw new Exception('失败');
                        }
                    }else{
                        $addtime = $vip_enjoy_day*24*60*60;
                        
                        if($info['vip_time'] > time()){
                            $res = Db::name('member')->where('id', $wine_apply_vip['user_id'])->setInc('vip_time', $addtime);
                            if(!$res){
                                throw new Exception('失败');
                            }
                        }else{
                            $res = Db::name('member')->where('id', $wine_apply_vip['user_id'])->update([
                                'vip_time'=>$time
                            ]);
                            if(!$res){
                                throw new Exception('失败');
                            }
                        }
                    }
                    
                    $res = Db::name('wine_apply_vip')->where('id', $wine_apply_vip['id'])->update(['status'=>1]);
                    if(!$res){
                        throw new Exception('失败');
                    }
                    
                    $value = array('status'=>1,'mess'=>'审核成功');
                    Db::commit();
                }
                catch(Exception $e){
                    $value = array('status'=>0,'mess'=>'失败');
                    Db::rollback();
                }
            }
        });
    }
    
    public function calcuThreeOrder(){
        $fourDay = strtotime(date('Y-m-d', strtotime('-4 day')));
        
	    Db::name('member')->chunk(1000, function ($members) use($fourDay){
	        foreach ($members as $member){
	            $agent_num = Db::name('wine_order_buyer')->where('status', 'in', [2, 6])->where('buy_id', $member['id'])->where('addtime', '>=', $fourDay)->where('addtime', '<', strtotime('today'))->count();
	            
	            Db::name('member')->where('id', $member['id'])->update([
	                'agent_num' => $agent_num
 	            ]);
            }
        });
    }
    
    public function demotedChecking(){
        Db::name('member')->chunk(1000, function($members){
            foreach ($members as $member){
                $cur_user_id = $member['id'];
                
                $list = Db::name('member')->where('agent_num', '>', 0)->order('agent_type desc')->group('agent_type')->where('agent_num', '>', 0)->where('agent_type', '>', 0)->where('id', '<>', $cur_user_id)
                    ->where(function ($query) use($cur_user_id) {
                    $query->where('team_id', 'like', '%,'.$cur_user_id)->whereOr('team_id', 'like', '%,'.$cur_user_id.',%');
                })->field('team_id, id, agent_type, count(agent_type) agent_type_count')->select();
                // var_dump($list);exit;
                
                if(count($list) == 0){
                    Db::name('member')->where('id', $cur_user_id)->update([
                        'agent_type' => 0
                    ]);
                }
                
                $count = 0;
                foreach($list as $k=>$v){
                    if($k == 0){
                        Db::name('member')->where('id', $cur_user_id)->update([
                            'agent_type' => 0
                        ]);
                    }
                    
                    $count += $v['agent_type_count'];
                    if($count >= 3){
                        $agent_type = $v['agent_type'];
                        $agent_type++;
                        if($agent_type > 5){
                            $agent_type = 5;
                        }
                        Db::name('member')->where('id', $cur_user_id)->update([
                            'agent_type' => $agent_type
                        ]);
                        break;
                    }
                }
            }
        });
    }

    public function addDetail($detail){
        if($detail['price'] == 0){
            return true;
        }
	    $res = Db::name('detail')->insert($detail);
	    if($res)return true;
	    return false;
    }
    
    // 合约出售失败进入回炉
    public function reFurnace(){
        Db::name('wine_order_saler_contract')
            // ->where('status', 2)
            ->where('status', 0)
            ->where('delete', 0)
            ->chunk(1000, function($list){
                foreach ($list as $v){
                    if(strtotime('today') < $v['addtime']){
                        continue;
                    }
// <<<<<<< HEAD
//                     Db::startTrans();
//                     try{
//                         $res = Db::name('wine_order_saler_contract')->where('status', 0)->where('delete', 0)->where('id', $v['id'])->update([
//                             'delete' => 1,
//                             'deletetime'=>time()
//                         ]);
//                         if(!$res)throw new \Exception('失败');
                        
//                         $info = Db::name('wine_order_buyer_contract')->where('odd', $v['odd'])->where('wine_goods_id', $v['wine_goods_id'])
//                             ->where('delete', 1)
//                             ->where('transfer_wine_contract_day_id', $v['wine_contract_day_id'])->order('id desc')->find();
//                         if (is_null($info)) {
//                             // code...
//                             throw new \Exception('失败');
//                         }
//                         else{
//                             $wine_contract_day_info = Db::name('wine_contract_day')->where('id', $info['transfer_wine_contract_day_id'])->find();
// =======
                    else{
                        Db::startTrans();
                        try{
                            $res = Db::name('wine_order_saler_contract')->where('status', 0)->where('delete', 0)->where('id', $v['id'])->update([
                                'delete' => 1,
                                'deletetime'=>time()
                            ]);
                            if(!$res)throw new \Exception('失败');
// >>>>>>> 385be4a980fc9563afd00f8f45bc803f79a8bad0
                            
                            $info = Db::name('wine_order_buyer_contract')->where('odd', $v['odd'])->where('wine_goods_id', $v['wine_goods_id'])
                                ->where('delete', 1)
                                ->where('transfer_wine_contract_day_id', $v['wine_contract_day_id'])->order('id desc')->find();
                            if (is_null($info)) {
                                // code...
                                throw new \Exception('失败');
                            }
                            else{
                                $wine_contract_day_info = Db::name('wine_contract_day')->where('id', $info['transfer_wine_contract_day_id'])->find();
                                
// <<<<<<< HEAD
//                                 $info['pid'] = $info['id'];
//                                 $info['up_odd'] = $info['odd'];
//                                 unset($info['id']);
//                                 $info['sale_amount'] = $info['sale_amount'] + $income;
//                                 $info['odd'] = uniqid();
//                                 $info['delete'] = 0;
//                                 $info['re_furnace'] = 1;
//                                 $info['addtime'] = time();
//                                 $info['day'] = $info['day']+$wine_contract_day_info['day'];
//                                 $res = Db::name('wine_order_buyer_contract')->insert($info);
//                                 if(!$res){
// =======
                                if(is_null($wine_contract_day_info)){
// >>>>>>> 385be4a980fc9563afd00f8f45bc803f79a8bad0
                                    throw new \Exception('失败');
                                }
                                else{
                                    $income = $info['buy_amount'] * $wine_contract_day_info['day_rate']/100;
                                    
                                    $info['pid'] = $info['id'];
                                    $info['up_odd'] = $info['odd'];
                                    unset($info['id']);
                                    $info['sale_amount'] = $info['sale_amount'] + $income;
                                    $info['odd'] = uniqid();
                                    $info['delete'] = 0;
                                    $info['re_furnace'] = 1;
                                    $info['addtime'] = time();
                                    $info['day'] = $info['day']+$wine_contract_day_info['day'];
                                    $res = Db::name('wine_order_buyer_contract')->insert($info);
                                    if(!$res){
                                        throw new \Exception('失败');
                                    }
                                }
                            }
                            
                            Db::commit();
                        }
                        catch(\Exception $e){
                            Db::rollback();
                        }
                    }
                }
            });
    }
    
    public function paidan(){
        $list = Db::name('wine_deal_area')->where('status', 1)->where('id', '<>', 10)->select();
        
        foreach ($list as $v){
            $deal_area = explode('-', $v['deal_area']);
            
            $starttime = strtotime(date('Y-m-d').' '.$deal_area[0]);
            $time = time();
            
            if($time >= $starttime){
            // if(true){
                $memberIdArr = Db::name('member')->where('qiandantequan', 1)->column('id');

                $memberRecordList = Db::name('wine_order_record')->where('addtime', '>=', strtotime('today'))->where('wine_deal_area_id', $v['id'])->where('buy_id', 'in', $memberIdArr)->select();
                // var_dump($memberRecordList);exit;
                foreach ($memberRecordList as $v1){
                    $count = Db::name('wine_order_buyer')->where('addtime', '>=', strtotime('today'))->where('buy_id', $v1['buy_id'])->where('wine_deal_area_id', $v1['wine_deal_area_id'])->where('generate_buyer_dan', 1)->count();
                    if($count == 0){
                        $wine_order_saler_info = Db::name('wine_order_saler')->alias('wos')
                            ->join('wine_goods wg', 'wg.id = wos.wine_goods_id', 'inner')
                            ->join('wine_deal_area wda', 'wda.id = wos.wine_deal_area_id', 'inner')
                            ->where('wine_deal_area_id', $v1['wine_deal_area_id'])
                            ->field('wda.deal_area, wda.odd_num, wg.deposit, wg.goods_name, wg.thumb_url, wos.sale_amount, wg.rate, wos.sale_id, wos.wine_goods_id, wos.id wine_order_saler_id, wos.odd, wos.wine_deal_area_id, wda.id wda_id,wos.id')
                            ->where('wos.status', 0)->where('wos.onsale', 1)->where('wos.delete', 0)->find();
                        
                        if(!is_null($wine_order_saler_info)){
                            Db::startTrans();
                            try{
                               $res = Db::name('wine_order_saler')->where('id', $wine_order_saler_info['id'])->where('status', 0)->update([
                                    'status' => 1
                                ]);
                                if(!$res){
                                    throw new \Exception('失败');
                                }
                                
                                $insert_data = [
                                    'goods_name' => $wine_order_saler_info['goods_name'],
                                    'goods_thumb' => $wine_order_saler_info['thumb_url'],
                                    'addtime' => $starttime,
                                    'buy_amount' => $wine_order_saler_info['sale_amount'],
                                    'sale_amount' => $wine_order_saler_info['sale_amount'] + $wine_order_saler_info['sale_amount']*$wine_order_saler_info['rate']/100,
                                    'buy_id' => $v1['buy_id'],
                                    'sale_id' => $wine_order_saler_info['sale_id'],
                                    'wine_goods_id' => $wine_order_saler_info['wine_goods_id'],
                                    'status' => 1,
                                    'wine_order_saler_id'=>$wine_order_saler_info['id'],
                                    'odd' => uniqid(),
                                    'day' => 0,
                                    'wine_deal_area_id' => $wine_order_saler_info['wine_deal_area_id'],
                                    'date' => date('Y-m-d'),
                                    'generate_buyer_dan'=>1
                                ];
                                $res = Db::name('wine_order_buyer')->insertGetId($insert_data);
                                if(!$res){
                                    throw new \Exception('抢购失败');
                                }
                                
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
    
    public function assign_buyer(){
        $list = Db::name('wine_order_saler')->where('status', 0)->where('assign_buyer_id', '>', 0)->select();
        
        foreach ($list as $v){
            $wine_deal_area_info = Db::name('wine_deal_area')->where('id', $v['wine_deal_area_id'])->find();
            $deal_area = explode('-', $wine_deal_area_info['deal_area']);
            
            $starttime = strtotime(date('Y-m-d').' '.$deal_area[0]) - 100;
            $time = time();
            
            if($time >= $starttime){
            // if(true){
                // $memberIdArr = Db::name('member')->where('qiandantequan', 1)->column('id');
                $memberinfo = Db::name('member')->where('id', $v['assign_buyer_id'])->find();
                if(!is_null($memberinfo)){
                    Db::startTrans();
                    try{
                        $res = Db::name('wine_order_saler')->where('status', 0)->where('assign_buyer_id', '>', 0)->where('delete', 0)->where('id', $v['id'])->update([
                            'status'=>1
                        ]);
                        if(!$res){
                            throw new \Exception('失败');
                        }
                        
                        $insert_data = [
                            'goods_name' => $v['goods_name'],
                            'goods_thumb' => $v['goods_thumb'],
                            'addtime' => $starttime+100,
                            'buy_amount' => $v['sale_amount'],
                            'sale_amount' => $v['sale_amount'] + $v['sale_amount']*$v['goods_rate']/100,
                            'buy_id' => $memberinfo['id'],
                            'sale_id' => $v['sale_id'],
                            'wine_goods_id' => $v['wine_goods_id'],
                            'status' => 1,
                            'wine_order_saler_id'=>$v['id'],
                            'odd' => uniqid(),
                            'day' => 0,
                            'wine_deal_area_id' => $v['wine_deal_area_id'],
                            'date' => date('Y-m-d')
                        ];
                        $res = Db::name('wine_order_buyer')->insertGetId($insert_data);
                        if(!$res){
                            throw new Exception('抢购失败');
                        }
                        
                        Db::commit();
                    }
                    catch(Exception $e){
                        Db::rollback();
                    } 
                }
            }
        }
    }
    
    // 合约指定买家
    public function assign_buyer_contract(){
        $list = Db::name('wine_order_saler_contract')->where('status', 0)->where('assign_buyer_id', '>', 0)->select();
        
        foreach ($list as $v){
            $wine_deal_area_info = Db::name('wine_contract_day')->where('id', $v['wine_contract_day_id'])->find();
            $deal_area = explode('-', $wine_deal_area_info['deal_area']);
            
            $starttime = strtotime(date('Y-m-d').' '.$deal_area[0]) - 100;
            $time = time();
            
            if($time >= $starttime){
            // if(true){
                // $memberIdArr = Db::name('member')->where('qiandantequan', 1)->column('id');
                $memberinfo = Db::name('member')->where('id', $v['assign_buyer_id'])->find();
                if(!is_null($memberinfo)){
                    Db::startTrans();
                    try{
                        $res = Db::name('wine_order_saler_contract')->where('status', 0)->where('assign_buyer_id', '>', 0)->where('delete', 0)->where('id', $v['id'])->update([
                            'status'=>1
                        ]);
                        if(!$res){
                            throw new \Exception('失败');
                        }
                        
                        $insert_data = [
                            'goods_name' => $v['goods_name'],
                            'goods_thumb' => $v['goods_thumb'],
                            'addtime' => $starttime+100,
                            'buy_amount' => $v['sale_amount'],
                            'sale_amount' => $v['sale_amount'] + $v['sale_amount']*$v['goods_rate']/100,
                            'buy_id' => $memberinfo['id'],
                            'sale_id' => $v['sale_id'],
                            'wine_goods_id' => $v['wine_goods_id'],
                            'status' => 1,
                            'wine_order_saler_id'=>$v['id'],
                            'odd' => uniqid(),
                            'day' => $wine_deal_area_info['day'],
                            'wine_contract_day_id' => $wine_deal_area_info['id']
                        ];
                        $res = Db::name('wine_order_buyer_contract')->insertGetId($insert_data);
                        if(!$res){
                            throw new Exception('抢购失败');
                        }
                        
                        Db::commit();
                    }
                    catch(Exception $e){
                        Db::rollback();
                    } 
                }
            }
        }
    }
    
    public function searingTizheng(){
        Db::name('member')->chunk(1000, function($list) {
            foreach ($list as $info){
           
                $buy_amount = Db::name('wine_order_buyer')->where('buy_id', $info['id'])->where('status', 2)->where('pay_status', 1)->sum('buy_amount');
       
                $profit = $buy_amount * 0.02;
                
                $res = Db::name('member')->where('id', $info['id'])->update([
                    'sale_earnings' => $profit
                ]);
                echo '【'.$info['id'].'】=='.$buy_amount.'==='.$profit.PHP_EOL;
                // if($res){
                //     echo '【'.$info['id'].'】=='.$buy_amount.'==='.$profit.PHP_EOL;
                // }
            }
        });
    }
    
    
    public function crowdAjust(){
        Db::name('crowd_goods')->where('status', 0)->where('jiesu', 0)->chunk(1000, function($list){
            foreach ($list as $info){
                // $qi_time = $info['cur_qi']*3*24*60*60+$info['addtime'];
                // $qi_time = 3*24*60*60+$info['addtime'];
                $end_time = strtotime(date('Y-m-d', $info['addtime']))+34*60*60 + 48*60*60;
                $time = time();
                // if($time > $qi_time){
                if($time > $end_time || $info['cur_crowd_num'] == $info['crowd_value']){
                    Db::startTrans();
                    try{
                        if($info['cur_crowd_num'] == $info['crowd_value']){
                            // 成功
                            $res = Db::name('crowd_goods')->where('id', $info['id'])->update(['jiesu'=>1]);
                            if(!$res){
                                throw new Exception('抢购失败1');
                            }
                            
                            $value = Db::name('config')->where('ename', 'crowd_value')->value('value');
                            $next_data = $info;
                            // $next_data['addtime'] = $qi_time;
                            $next_data['addtime'] = time();
                            $next_data['crowd_value'] = $info['crowd_value'] + $info['crowd_value']*$value/100;
                            $next_data['cur_qi'] = $info['cur_qi']+1;
                            $next_data['status'] = 0;
                            $next_data['jiesu'] = 0;
                            $next_data['pre_sale'] = 0;
                            $next_data['sy'] = 0;
                            $next_data['cur_crowd_num'] = 0;
                            unset($next_data['id']);
                            $res = Db::name('crowd_goods')->insert($next_data);
                            if(!$res){
                                throw new Exception('抢购失败2');
                            }
                            
                            Db::name('crowd_order')->where('goods_id', $info['id'])->where('qi', $info['cur_qi'])->where('status', 0)->chunk(1000, function($lsts) use($info){
                                foreach ($lsts as $lsts_value){
                                    $v1v2v3 = $lsts_value['price']*0.03;
                                    $total_v = 3;
                                    $fen_price = 0;
                                    
                                    $cur_member1 = Db::name('member')->where('id', $lsts_value['user_id'])->find();
                                    
                                    $team_id = array_filter(explode(',', $cur_member1['team_id']));
                                    if(count($team_id)){
                                        $id_agent_type = Db::name('member')->where('id', 'in', $team_id)->column('id,agent_type');
                                        
                                        $agent_type = 0;$v1_same = 0;$v2_same = 0;$v3_same = 0;
                                        for($i=count($team_id); $i>0; $i--){
                                            $cur_agent_type = $id_agent_type[$team_id[$i]];
                                            if($cur_agent_type > $agent_type){
                                                $agent_type = $cur_agent_type;
                                                if($fen_price > 0){
                                                    $res = Db::name('crowd_goods')->where('id', $info['id'])->inc('sy', $fen_price)->update();
                                                    if(!$res){
                                                        throw new Exception('抢购失败3');
                                                    }
                                                    
                                                    $fen_price = 0;
                                                }
                                                
                                                if($cur_agent_type == 1){
                                                    $v1_same = 1;
                                                    $fen_price = $lsts_value['price']*0.01;
                                                }
                                                else if($cur_agent_type == 2){
                                                    $v2_same = 1;
                                                    if($v1_same == 1){
                                                        $fen_price = $lsts_value['price']*0.01;
                                                    }
                                                    else{
                                                        $fen_price = $lsts_value['price']*0.02;
                                                    }
                                                }
                                                else if($cur_agent_type == 3){
                                                    $v3_same = 1;
                                                    if($v1_same==1 && $v2_same==1){
                                                        $fen_price = $lsts_value['price']*0.01;
                                                    }
                                                    else if($v1_same==0 && $v2_same==0){
                                                        $fen_price = $lsts_value['price']*0.03;
                                                    }
                                                    else if($v1_same == 1){
                                                        $fen_price = $lsts_value['price']*0.02;
                                                    }
                                                    else{
                                                        $fen_price = $lsts_value['price']*0.01;
                                                    }
                                                }
                                                
                                                $v_fenrun_wallet = Db::name('wallet')->where('user_id', $team_id[$i])->find();
                                                if($v_fenrun_wallet){
                                                    $res = Db::name('wallet')->where('id', $v_fenrun_wallet['id'])->inc('point_ticket', $fen_price)->update();
                                                    if(!$res){
                                                        throw new Exception('抢购失败4');
                                                    }   
                                                    
                                                    $detal = [
                                                        'de_type' => 1,
                                                        'sr_type' => 205,
                                                        'before_price'=> $v_fenrun_wallet['point_ticket'],
                                                        'price' => $fen_price,
                                                        'after_price'=> $v_fenrun_wallet['point_ticket']+$fen_price,
                                                        'user_id' => $team_id[$i],
                                                        'wat_id' => $v_fenrun_wallet['id'],
                                                        'time' => time(),
                                                        'target_id'=>$lsts_value['id']
                                                    ];
                                                    $res = $this->addDetail($detal);
                                                    if(!$res){
                                                        throw new Exception('抢购失败5');
                                                    }
                                                }
                                                
                                                $fen_price = $fen_price/2;
                                            }
                                            else if($cur_agent_type == $agent_type && $cur_agent_type>0){
                                                if ($fen_price < 1) {
                                                    // code...
                                                    if($fen_price > 0){
                                                        $res = Db::name('crowd_goods')->where('id', $info['id'])->inc('sy', $fen_price)->update();
                                                        if(!$res){
                                                            throw new Exception('抢购失败6');
                                                        }
                                                        
                                                        $fen_price = 0;
                                                    }
                                                    
                                                    if($agent_type == 3){
                                                        break;
                                                    }
                                                }
                                                else{
                                                    $v_fenrun_wallet = Db::name('wallet')->where('user_id', $team_id[$i])->find();
                                                    if($v_fenrun_wallet){
                                                        $res = Db::name('wallet')->where('id', $v_fenrun_wallet['id'])->inc('point_ticket', $fen_price)->update();
                                                        if(!$res){
                                                            throw new Exception('抢购失败7');
                                                        }   
                                                        
                                                        $detal = [
                                                            'de_type' => 1,
                                                            'sr_type' => 205,
                                                            'before_price'=> $v_fenrun_wallet['point_ticket'],
                                                            'price' => $fen_price,
                                                            'after_price'=> $v_fenrun_wallet['point_ticket']+$fen_price,
                                                            'user_id' => $team_id[$i],
                                                            'wat_id' => $v_fenrun_wallet['id'],
                                                            'time' => time(),
                                                            'target_id'=>$lsts_value['id']
                                                        ];
                                                        $res = $this->addDetail($detal);
                                                        if(!$res){
                                                            throw new Exception('抢购失败8');
                                                        }
                                                    }
                                                    $fen_price = $fen_price/2;
                                                }
                                            }
                                            else{
                                                // if()
                                                // Db::name('crowd_goods')->where('id', $info['id'])->inc('sy', )->update();
                                            }
                                        }
                                    }
                                }
                            });
                            
                            $dai_qi = $info['cur_qi'] - 3;
                            if($dai_qi > 0){
                                $ins = Db::name('crowd_goods')->where('crowd_mark', $info['crowd_mark'])->where('cur_qi', $dai_qi)->where('status', 0)->find();
                                
                                if($ins){
                                    $res = Db::name('crowd_goods')->where('crowd_mark', $info['crowd_mark'])->where('cur_qi', $dai_qi)->where('status', 0)->update(['status'=>2]);
                                    if(!$res){
                                        throw new Exception('抢购失败10');
                                    }
                                    
                                    // 当前期减3的当期退款收益
                                    $res = Db::name('crowd_order')->where('goods_id', $ins['id'])->where('qi', $ins['cur_qi'])->where('status', 0)->update(['status'=>3, 'receive_time'=>time()+24*60*60]);
                                    if(!$res){
                                        throw new Exception('抢购失败11');
                                    }
                                    
                                    // 直推间推
                                    Db::name('crowd_order')->where('goods_id', $ins['id'])->where('qi', $ins['cur_qi'])->where('status', 3)->where('jiesu', 0)->chunk(1000, function($lst){
                                        
                                        foreach ($lst as $in){
                                            $res = Db::name('crowd_order')->where('jiesu', 0)->where('id', $in['id'])->update(['jiesu'=>1]);
                                            if(!$res){
                                                throw new Exception('抢购失败19');
                                            }
                                            
                                            $cur_member = Db::name('member')->where('id', $in['user_id'])->find();
                                            if($cur_member['one_level']){
                                                $wallet_info = Db::name('wallet')->where('user_id', $cur_member['one_level'])->find();
                                                $res = Db::name('wallet')->where('user_id', $cur_member['one_level'])->inc('point_ticket', $in['price']*0.02)->update();
                                                if(!$res){
                                                    throw new Exception('抢购失败12');
                                                }
                                            
                                                $detal = [
                                                    'de_type' => 1,
                                                    'sr_type' => 200,
                                                    'before_price'=> $wallet_info['point_ticket'],
                                                    'price' => $in['price']*0.02,
                                                    'after_price'=> $wallet_info['point_ticket']+$in['price']*0.02,
                                                    'user_id' => $cur_member['one_level'],
                                                    'wat_id' => $wallet_info['id'],
                                                    'time' => time(),
                                                    'target_id'=>$in['id']
                                                ];
                                                $res = $this->addDetail($detal);
                                                if(!$res){
                                                    throw new Exception('抢购失败13');
                                                }
                                            }
                                            
                                            if($cur_member['two_level']){
                                                $wallet_info = Db::name('wallet')->where('user_id', $cur_member['two_level'])->find();
                                                $res = Db::name('wallet')->where('user_id', $cur_member['two_level'])->inc('point_ticket', $in['price']*0.01)->update();
                                                if(!$res){
                                                    throw new Exception('抢购失败14');
                                                }
                                            
                                                $detal = [
                                                    'de_type' => 1,
                                                    'sr_type' => 201,
                                                    'before_price'=> $wallet_info['point_ticket'],
                                                    'price' => $in['price']*0.01,
                                                    'after_price'=> $wallet_info['point_ticket']+$in['price']*0.01,
                                                    'user_id' => $cur_member['two_level'],
                                                    'wat_id' => $wallet_info['id'],
                                                    'time' => time(),
                                                    'target_id'=>$in['id']
                                                ];
                                                $res = $this->addDetail($detal);
                                                if(!$res){
                                                    throw new Exception('抢购失败15');
                                                }
                                            }
                                        }
                                    });
                                    // $tuikuan_value = Db::name('config')->where('ename', 'tuikuan_value')->value('value');
                                    // Db::name('crowd_order')->where('goods_id', $ins['id'])->where('qi', $ins['cur_qi'])->where('status', 0)->chunk(1000, function($lst){
                                    //     foreach ($lst as $in){
                                    //         $res = Db::name('crowd_order')->where('id', $in['id'])->update(['status'=>3]);
                                    //         if(!$res){
                                    //             throw new Exception('抢购失败');
                                    //         }
                                            
                                    //         $sprice = $in['price'] + $in['price']*$tuikuan_value/100;
                                    //         $wallet_info = Db::name('wallet')->where('user_id', $in['user_id'])->find();
                                    //         $res = Db::name('wallet')->where('user_id', $in['user_id'])->inc('point_ticket', $sprice)->update();
                                    //         if(!$res){
                                    //             throw new Exception('抢购失败');
                                    //         }
                                            
                                    //         $detal = [
                                    //             'de_type' => 1,
                                    //             'sr_type' => 105,
                                    //             'before_price'=> $wallet_info['point_ticket'],
                                    //             'price' => $sprice,
                                    //             'after_price'=> $wallet_info['point_ticket']+$sprice,
                                    //             'user_id' => $in['user_id'],
                                    //             'wat_id' => $wallet_info['id'],
                                    //             'time' => time(),
                                    //             'target_id'=>$in['id']
                                    //         ];
                                    //         $res = $this->addDetail($detal);
                                    //         if(!$res){
                                    //             throw new Exception('抢购失败');
                                    //         }
                                    //     }
                                    // });
                                }
                            }
                        }
                        else{
                            // 失败
                            $res = Db::name('crowd_goods')->where('id', $info['id'])->update(['status'=>1, 'jiesu'=>1]);
                            if(!$res){
                                throw new Exception('抢购失败20');
                            }
                            
                            // 加权
                            $total_reward = 0;
                            $total_crowd_value = Db::name('crowd_goods')->where('crowd_mark', $info['crowd_mark'])->where('id', '<>', $info['id'])->sum('crowd_value');
                            if($total_crowd_value > 0){
                                $total_reward = $total_crowd_value*0.02;
                                $all_crowd_goods_id = Db::name('crowd_goods')->where('crowd_mark', $info['crowd_mark'])->column('id');
                                $this->blowUp($total_reward, $all_crowd_goods_id);
                            }
                            
                            // 当前订单退款
                            Db::name('crowd_order')->where('goods_id', $info['id'])->where('qi', $info['cur_qi'])->where('status', 0)->chunk(1000, function($lst){
                                foreach ($lst as $in){
                                    $res = Db::name('crowd_order')->where('id', $in['id'])->update(['status'=>1]);
                                    if(!$res){
                                        throw new Exception('抢购失败21');
                                    }
                                    
                                    // $reward_point_ticket = 0;
                                    // if($total_reward > 0){
                                    //     $reward_point_ticket = $total_reward*$in['price']/$info['crowd_value'];
                                    // }
                                    
                                    $wallet_info = Db::name('wallet')->where('user_id', $in['user_id'])->find();
                                    // $res = Db::name('wallet')->where('user_id', $in['user_id'])->inc('point_ticket', $in['price']+$reward_point_ticket)->update();
                                    $res = Db::name('wallet')->where('user_id', $in['user_id'])->inc('point_ticket', $in['price'])->update();
                                    if(!$res){
                                        throw new Exception('抢购失败22');
                                    }
                                    
                                    $detal = [
                                        'de_type' => 1,
                                        'sr_type' => 101,
                                        'before_price'=> $wallet_info['point_ticket'],
                                        'price' => $in['price'],
                                        'after_price'=> $wallet_info['point_ticket']+$in['price'],
                                        'user_id' => $in['user_id'],
                                        'wat_id' => $wallet_info['id'],
                                        'time' => time(),
                                        'target_id'=>$in['id']
                                    ];
                                    $res = $this->addDetail($detal);
                                    if(!$res){
                                        throw new Exception('抢购失败23');
                                    }
                                    
                                    // if($reward_point_ticket > 0){
                                    //     $detals = [
                                    //         'de_type' => 1,
                                    //         'sr_type' => 109,
                                    //         'before_price'=> $detal['after_price'],
                                    //         'price' => $reward_point_ticket,
                                    //         'after_price'=> $wallet_info['after_price']+$reward_point_ticket,
                                    //         'user_id' => $in['user_id'],
                                    //         'wat_id' => $wallet_info['id'],
                                    //         'time' => time(),
                                    //         'target_id'=>$in['id']
                                    //     ];
                                    //     $res = $this->addDetail($detals);
                                    //     if(!$res){
                                    //         throw new Exception('抢购失败');
                                    //     }
                                    // }
                                }
                            });
                            
                            // 往前三七退款
                            $qian_goods_id = Db::name('crowd_goods')->where('crowd_mark', $info['crowd_mark'])->where('cur_qi', '<', $info['cur_qi'])->where('cur_qi', '>', $info['cur_qi']-4)->where('status', 0)->column('id');
                            $res = Db::name('crowd_goods')->where('id', 'in', $qian_goods_id)->where('status', 0)->update(['status'=>1]);
                            if(!$res){
                                throw new Exception('抢购失败24');
                            }
                            Db::name('crowd_order')->where('goods_id', 'in', $qian_goods_id)->where('qi','<', $info['cur_qi'])->where('qi', '>', $info['cur_qi']-4)->where('status', 0)->chunk(1000, function($qian_list){
                                foreach ($qian_list as $in){
                                    $res = Db::name('crowd_order')->where('id', $in['id'])->update(['status'=>2]);
                                    if(!$res){
                                        throw new Exception('抢购失败25');
                                    }
                                    
                                    $wallet_info = Db::name('wallet')->where('user_id', $in['user_id'])->find();
                                    $res = Db::name('wallet')->where('user_id', $in['user_id'])->inc('point_ticket', $in['price']*0.7)->inc('point_credit', $in['price']*0.3)->update();
                                    if(!$res){
                                        throw new Exception('抢购失败26');
                                    }
                                    
                                    $detal = [
                                        'de_type' => 1,
                                        'sr_type' => 102,
                                        'before_price'=> $wallet_info['point_ticket'],
                                        'price' => $in['price']*0.7,
                                        'after_price'=> $wallet_info['point_ticket']+$in['price']*0.7,
                                        'user_id' => $in['user_id'],
                                        'wat_id' => $wallet_info['id'],
                                        'time' => time(),
                                        'target_id'=>$in['id']
                                    ];
                                    $res = $this->addDetail($detal);
                                    if(!$res){
                                        throw new Exception('抢购失败27');
                                    }
                                    
                                    $detal = [
                                        'de_type' => 1,
                                        'sr_type' => 103,
                                        'before_price'=> $wallet_info['point_credit'],
                                        'price' => $in['price']*0.3,
                                        'after_price'=> $wallet_info['point_credit']+$in['price']*0.3,
                                        'user_id' => $in['user_id'],
                                        'wat_id' => $wallet_info['id'],
                                        'time' => time(),
                                        'target_id'=>$in['id']
                                    ];
                                    $res = $this->addDetail($detal);
                                    if(!$res){
                                        throw new Exception('抢购失败28');
                                    }
                                    
                                    // $detal = [
                                    //     'de_type' => 1,
                                    //     'sr_type' => 104,
                                    //     'before_price'=> $wallet_info['point_record'],
                                    //     'price' => $in['price']*0.2,
                                    //     'after_price'=> $wallet_info['point_record']+$in['price']*0.2,
                                    //     'user_id' => $in['user_id'],
                                    //     'wat_id' => $wallet_info['id'],
                                    //     'time' => time(),
                                    //     'target_id'=>$in['id']
                                    // ];
                                    // $res = $this->addDetail($detal);
                                    // if(!$res){
                                    //     throw new Exception('抢购失败');
                                    // }
                                }
                            });
                        }
                        Db::commit();
                    }
                    catch(Exception $e){
                        echo $e->getMessage();
                        Db::rollback();
                    }
                }
            }
        });
    }
    
    // 特等奖10%   一等奖20%   二等奖30%    三等奖40%
    public function blowUp($total_reward=0, $all_crowd_goods_id=[]){
        if($total_reward<=0 || empty($all_crowd_goods_id)){
            return ;
        }
        $time = time();
        $total_reward = $total_reward/2;
        
        $res = Db::name('crowd_order')->where('goods_id', 'in', $all_crowd_goods_id)->where('blow_up_60', 0)->update(['blow_up_60'=>1]);
        if(!$res){
            return ;
        }
        
        // 前三十名
        // 参与期数最高的15名
        $user_id_arr_to_betcount = Db::name('crowd_order')->where('goods_id', 'in', $all_crowd_goods_id)->group('user_id,qi')->order('addtime asc')->column('user_id');
        $user_id_arr_to_betcount = array_count_values($user_id_arr_to_betcount);
        arsort($user_id_arr_to_betcount);
        $user_id_arr_to_betcount = array_slice($user_id_arr_to_betcount, 0, 15, true);
        $user_id_arr_to_betcount_user_id = array_keys($user_id_arr_to_betcount);
        
        // 金额累计最高的15名
        $user_id_arr_to_betprice = Db::name('crowd_order')->where('goods_id', 'in', $all_crowd_goods_id)->group('user_id')->order('addtime asc')->column('user_id,sum(price)');
        arsort($user_id_arr_to_betprice);
        $user_id_arr_to_betprice = array_slice($user_id_arr_to_betprice, 0, 15, true);
        $user_id_arr_to_betprice_user_id = array_keys($user_id_arr_to_betprice);
        
        // 特等奖
        $special_q30 = 0;
        if(count($user_id_arr_to_betprice_user_id))$special_q30 = $user_id_arr_to_betprice_user_id[0];
        $special_q30_wallet = Db::name('wallet')->where('user_id', $special_q30)->find();
        if($special_q30_wallet){
            $res = Db::name('wallet')->where('user_id', $special_q30)->inc('point_ticket', $total_reward*0.1)->update();
            if(!$res){
                throw new Exception('抢购失败');
            }
            
            $detal = [
                'de_type' => 1,
                'sr_type' => 600,
                'before_price'=> $special_q30_wallet['point_ticket'],
                'price' => $total_reward*0.1,
                'after_price'=> $special_q30_wallet['point_ticket']+$total_reward*0.1,
                'user_id' => $special_q30,
                'wat_id' => $special_q30_wallet['id'],
                'time' => $time,
                // 'target_id'=>$in['id']
            ];
            $res = $this->addDetail($detal);
            if(!$res){
                throw new Exception('抢购失败');
            }
        }
        
        // 一等奖
        $one_q30 = [];
        if(count($user_id_arr_to_betprice_user_id)>=2){
            for($i=1; $i<count($user_id_arr_to_betprice_user_id);$i++){
                if($i<=2)array_push($one_q30, $user_id_arr_to_betprice_user_id[$i]);
            }
        }
        if(count($user_id_arr_to_betcount_user_id)){
            for($i=0; $i<count($user_id_arr_to_betcount_user_id);$i++){
                if($i<=1)array_push($one_q30, $user_id_arr_to_betcount_user_id[$i]);
            }
        }
        if(count($one_q30)){
            $one_q30_reward = $total_reward * 0.2;
            
            for($j=0; $j<count($one_q30); $j++){
                $one_q30_wallet = Db::name('wallet')->where('user_id', $one_q30[$j])->find();
                if($one_q30_wallet){
                    $res = Db::name('wallet')->where('user_id', $one_q30[$j])->inc('point_ticket', $one_q30_reward)->update();
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                    
                    $detal = [
                        'de_type' => 1,
                        'sr_type' => 601,
                        'before_price'=> $one_q30_wallet['point_ticket'],
                        'price' => $one_q30_reward,
                        'after_price'=> $one_q30_wallet['point_ticket']+$one_q30_reward,
                        'user_id' => $one_q30[$j],
                        'wat_id' => $one_q30_wallet['id'],
                        'time' => $time,
                        // 'target_id'=>$in['id']
                    ];
                    $res = $this->addDetail($detal);
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                }
            }
            
        }
        
        // 二等奖
        $two_q30 = [];
        if(count($user_id_arr_to_betprice_user_id)>=4){
            for($i=3; $i<count($user_id_arr_to_betprice_user_id);$i++){
                if($i<=7)array_push($two_q30, $user_id_arr_to_betprice_user_id[$i]);
            }
        }
        if(count($user_id_arr_to_betcount_user_id)>=3){
            for($i=2; $i<count($user_id_arr_to_betcount_user_id);$i++){
                if($i<=6)array_push($two_q30, $user_id_arr_to_betcount_user_id[$i]);
            }
        }
        if(count($two_q30)){
            $two_q30_reward = $total_reward * 0.3;
            
            for($j=0; $j<count($two_q30); $j++){
                $two_q30_wallet = Db::name('wallet')->where('user_id', $two_q30[$j])->find();
                if($two_q30_wallet){
                    $res = Db::name('wallet')->where('user_id', $two_q30[$j])->inc('point_ticket', $two_q30_reward)->update();
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                    
                    $detal = [
                        'de_type' => 1,
                        'sr_type' => 602,
                        'before_price'=> $two_q30_wallet['point_ticket'],
                        'price' => $two_q30_reward,
                        'after_price'=> $two_q30_wallet['point_ticket']+$two_q30_reward,
                        'user_id' => $two_q30[$j],
                        'wat_id' => $two_q30_wallet['id'],
                        'time' => $time,
                        // 'target_id'=>$in['id']
                    ];
                    $res = $this->addDetail($detal);
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                }
            }
            
        }
        
        // 三等奖
        $three_q30 = [];
        if(count($user_id_arr_to_betprice_user_id)>=9){
            for($i=8; $i<count($user_id_arr_to_betprice_user_id);$i++){
                if($i<=14)array_push($three_q30, $user_id_arr_to_betprice_user_id[$i]);
            }
        }
        if(count($user_id_arr_to_betcount_user_id)>=8){
            for($i=7; $i<count($user_id_arr_to_betcount_user_id);$i++){
                if($i<=14)array_push($three_q30, $user_id_arr_to_betcount_user_id[$i]);
            }
        }
        if(count($three_q30)){
            $three_q30_reward = $total_reward * 0.4;
            
            for($j=0; $j<count($three_q30); $j++){
                $three_q30_wallet = Db::name('wallet')->where('user_id', $three_q30[$j])->find();
                if($three_q30_wallet){
                    $res = Db::name('wallet')->where('user_id', $three_q30[$j])->inc('point_ticket', $three_q30_reward)->update();
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                    
                    $detal = [
                        'de_type' => 1,
                        'sr_type' => 603,
                        'before_price'=> $three_q30_wallet['point_ticket'],
                        'price' => $three_q30_reward,
                        'after_price'=> $three_q30_wallet['point_ticket']+$three_q30_reward,
                        'user_id' => $three_q30[$j],
                        'wat_id' => $three_q30_wallet['id'],
                        'time' => $time,
                        // 'target_id'=>$in['id']
                    ];
                    $res = $this->addDetail($detal);
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                }
            }
            
        }
        
        
        
        // 后三十名
        // 特等奖
        $user_id_h30 = [];
        $special_h30 = Db::name('crowd_order')->where('goods_id', 'in', $all_crowd_goods_id)->order('id desc')->find();
        $special_h30_wallet = Db::name('wallet')->where('user_id', $special_h30['user_id'])->find();
        if($special_h30_wallet){
            array_push($user_id_h30, $special_h30['user_id']);
            $res = Db::name('wallet')->where('user_id', $special_h30['user_id'])->inc('point_ticket', $total_reward*0.1)->update();
            if(!$res){
                throw new Exception('抢购失败');
            }
            
            $detal = [
                'de_type' => 1,
                'sr_type' => 604,
                'before_price'=> $special_h30_wallet['point_ticket'],
                'price' => $total_reward*0.1,
                'after_price'=> $special_h30_wallet['point_ticket']+$total_reward*0.1,
                'user_id' => $special_h30['user_id'],
                'wat_id' => $special_h30_wallet['id'],
                'time' => $time,
                // 'target_id'=>$in['id']
            ];
            $res = $this->addDetail($detal);
            if(!$res){
                throw new Exception('抢购失败');
            }
        }
        
        // 一等奖
        $one_h30 = Db::name('crowd_order')->group('user_id')->where('goods_id', 'in', $all_crowd_goods_id)->where('user_id', 'notIn', $user_id_h30)->order('id desc')->limit('4')->column('max(id) id,user_id');
        $one_h30 = array_values($one_h30);
        if(count($one_h30)){
            $user_id_h30 = array_merge($user_id_h30, $one_h30);
            $one_h30_reward = $total_reward * 0.2;
            for($j=0; $j<count($one_h30); $j++){
                $one_h30_wallet = Db::name('wallet')->where('user_id', $one_h30[$j])->find();
                if($one_h30_wallet){
                    $res = Db::name('wallet')->where('user_id', $one_h30[$j])->inc('point_ticket', $one_h30_reward)->update();
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                    
                    $detal = [
                        'de_type' => 1,
                        'sr_type' => 605,
                        'before_price'=> $one_h30_wallet['point_ticket'],
                        'price' => $one_h30_reward,
                        'after_price'=> $one_h30_wallet['point_ticket']+$one_h30_reward,
                        'user_id' => $one_h30[$j],
                        'wat_id' => $one_h30_wallet['id'],
                        'time' => $time,
                        // 'target_id'=>$in['id']
                    ];
                    $res = $this->addDetail($detal);
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                }
            }
            
        }
        
        // 二等奖
        $two_h30 = Db::name('crowd_order')->group('user_id')->where('goods_id', 'in', $all_crowd_goods_id)->where('user_id', 'notIn', $user_id_h30)->order('id desc')->limit('10')->column('max(id) id,user_id');
        $two_h30 = array_values($two_h30);
        if(count($two_h30)){
            $user_id_h30 = array_merge($user_id_h30, $two_h30);
            $two_h30_reward = $total_reward * 0.3;
            
            for($j=0; $j<count($two_h30); $j++){
                $two_h30_wallet = Db::name('wallet')->where('user_id', $two_h30[$j])->find();
                if($two_h30_wallet){
                    $res = Db::name('wallet')->where('user_id', $two_h30[$j])->inc('point_ticket', $two_h30_reward)->update();
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                    
                    $detal = [
                        'de_type' => 1,
                        'sr_type' => 606,
                        'before_price'=> $two_h30_wallet['point_ticket'],
                        'price' => $two_h30_reward,
                        'after_price'=> $two_h30_wallet['point_ticket']+$two_h30_reward,
                        'user_id' => $two_h30[$j],
                        'wat_id' => $two_h30_wallet['id'],
                        'time' => $time,
                        // 'target_id'=>$in['id']
                    ];
                    $res = $this->addDetail($detal);
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                }
            }
            
        }
        
        // 三等奖
        $three_h30 = Db::name('crowd_order')->group('user_id')->where('goods_id', 'in', $all_crowd_goods_id)->where('user_id', 'notIn', $user_id_h30)->order('id desc')->limit('15')->column('max(id) id,user_id');
        $three_h30 = array_values($three_h30);
        if(count($three_h30)){
            $user_id_h30 = array_merge($user_id_h30, $three_h30);
            $three_h30_reward = $total_reward * 0.4;
            
            for($j=0; $j<count($three_h30); $j++){
                $three_h30_wallet = Db::name('wallet')->where('user_id', $three_h30[$j])->find();
                if($three_h30_wallet){
                    $res = Db::name('wallet')->where('user_id', $three_h30[$j])->inc('point_ticket', $three_h30_reward)->update();
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                    
                    $detal = [
                        'de_type' => 1,
                        'sr_type' => 607,
                        'before_price'=> $three_h30_wallet['point_ticket'],
                        'price' => $three_h30_reward,
                        'after_price'=> $three_h30_wallet['point_ticket']+$three_h30_reward,
                        'user_id' => $three_h30[$j],
                        'wat_id' => $three_h30_wallet['id'],
                        'time' => $time,
                        // 'target_id'=>$in['id']
                    ];
                    $res = $this->addDetail($detal);
                    if(!$res){
                        throw new Exception('抢购失败');
                    }
                }
            }
            
        }
    }
    
    public function repeatBuy(){
        Db::name('crowd_order')->where('status', 3)->where('receive_time', '<', time())->chunk(1000, function($list){
           foreach ($list as $info){
                Db::startTrans();
                try{
                    $crowd_goods_info = Db::name('crowd_goods')->where('id', $info['goods_id'])->find();
                    $last_crowd_goods_info = Db::name('crowd_goods')->where('crowd_mark', $crowd_goods_info['crowd_mark'])->order('cur_qi desc')->find();
                    
                    if($last_crowd_goods_info['status'] == 1){
                        throw new Exception('订单已结束');
                    }
                    
                    $sy_crowd_value = $last_crowd_goods_info['crowd_value'] - $last_crowd_goods_info['cur_crowd_num'];
                    if($sy_crowd_value >= $info['price']){
                        $goodsYmd = date('Y-m-d', $last_crowd_goods_info['addtime']);
                        $curYmd = date('Y-m-d');
                        $adjut = 0;
                        $strtime = strtotime($goodsYmd);
                        // if($goodsYmd == $curYmd){
                        //     $adjut = $strtime+122400;
                        // }
                        // else{
                        //     $adjut = $strtime+36000;
                        // }
                        $adjut = $strtime+122400;
                        
                        $price = $info['price'];
                        $wallet_info = Db::name('wallet')->where('user_id', $info['user_id'])->find();
                        $ticket_burn = $price * 0.02;
                        if(!is_null($wallet_info)){
                            if(true){
                                $time = time();
                                $pre_sale_num = $last_crowd_goods_info['crowd_value']*0.7;
                                $sy_pre_sale = $pre_sale_num - $last_crowd_goods_info['pre_sale'];
                                $sy_total_sale = $last_crowd_goods_info['crowd_value'] - $last_crowd_goods_info['cur_crowd_num'];
                                
                                $res = Db::name('crowd_order')->where('id', $info['id'])->where('status', 3)->update(['status'=>5]);
                                if(!$res){
                                    throw new Exception('失败');
                                }
                                
                                if($time>=$adjut){
                                    // 。。。
                                    if($price>$sy_total_sale){
                                        throw new Exception('不足');
                                    }
                                    else{
                                        $buy_data = [
                                            'price' => $price,
                                            'goods_id' => $last_crowd_goods_info['id'],
                                            'addtime' => time(),
                                            'type' => 2,
                                            'user_id' => $info['user_id'],
                                            'qi'=>$last_crowd_goods_info['cur_qi'],
                                            'pid'=>$info['id']
                                        ];
                                        
                                        $res = Db::name('crowd_order')->insert($buy_data);
                                        if(!$res){
                                            throw new Exception('购买失败');
                                        }
                                        
                                        $res = Db::name('crowd_goods')->where('id', $last_crowd_goods_info['id'])->inc('cur_crowd_num', $price)->update();
                                        if(!$res){
                                            throw new Exception('购买失败');
                                        }
                                    }
                                }
                                else{
                                    // 预售
                                    if($last_crowd_goods_info['pre_sale'] >= $pre_sale_num){
                                        throw new Exception('预售数已销售一空');
                                    }
                                    else{
                                        if($price <= $sy_pre_sale){
                                            if(false){
                                                throw new Exception('购买只能是100的倍数');
                                            }
                                            else{
                                                $buy_data = [
                                                    'price' => $price,
                                                    'goods_id' => $last_crowd_goods_info['id'],
                                                    'addtime' => time(),
                                                    'type' => 1,
                                                    'user_id' => $info['user_id'],
                                                    'qi'=>$last_crowd_goods_info['cur_qi'],
                                                    'pid'=>$info['id']
                                                ];
                                                $res = Db::name('crowd_order')->insert($buy_data);
                                                if(!$res){
                                                    throw new Exception('购买失败');
                                                }

                                                $res = Db::name('crowd_goods')->where('id', $last_crowd_goods_info['id'])->inc('pre_sale', $price)->inc('cur_crowd_num', $price)->update();
                                                if(!$res){
                                                    throw new Exception('购买失败');
                                                }
                                            }
                                        }
                                        else{
                                            throw new Exception('剩余预售数不足');
                                        }
                                    }
                                }
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'积分不足或者门店不足','data'=>array('status'=>400));
                            }
                        }
                        else{
                            $value = array('status'=>400,'mess'=>'钱包异常','data'=>array('status'=>400));
                        }
                    }
                    
                    Db::commit();
                }
                catch(Exception $e){
                    echo $e->getMessage();
                    Db::rollback();
                }
            } 
        });
    }

    /****计算客户资产*****/
    public function calAch(){
        Db::name('member')->where('ach_time', '<' ,time() - 1800)->chunk(1000, function($list){
            foreach ($list as $info){
                Db::startTrans();
                try{
                    $wallet_info = Db::name('wallet')->where('user_id', $info['id'])->find();

                    $wallet_amount = $wallet_info['price'] + $wallet_info['point_ticket'];

                    $order_info = Db::name('crowd_order')->where('user_id', $info['id'])->where('addtime','>',time() - 9*86400)->find();
                    if($wallet_amount >= 1000 || $order_info){
                        $res = Db::name('member')->where('id', $info['id'])->update(['ach_time'=>time(),'is_effect'=>1]);
                    }else{
                        Db::name('member')->where('id', $info['id'])->update(['ach_time'=>time()]);
                    }

                    Db::commit();
                }
                catch(Exception $e){
                    echo $e->getMessage();
                    Db::rollback();
                }
            }
        });

    }

    public function levelUp(){
        echo time();
        Db::name('member')->chunk(1000, function($list){
            foreach ($list as $info){
                Db::startTrans();
                try{
                    $son_count = Db::name('member')->where('one_level', $info['id'])->where('is_effect',1)->count();

                    $team_count = Db::name('member')->where('team_id','like', '%,'.$info['id'].'%')->where('is_effect',1)->count();
                    
                    if($son_count > 0 || $team_count > 0){
                        echo '用户id'.$info['id'].'--直推【'.$son_count.'】--团队【'.$team_count.'】'.PHP_EOL;
                        
                    }
                    
                    
                    if($son_count >= 50 && $team_count >= 500){
                        echo '用户id'.$info['id'].'--直推【'.$son_count.'】--团队【'.$team_count.'】'.PHP_EOL;
                        Db::name('member')->where('id', $info['id'])->update(['agent_type'=>3]);
                    }elseif($son_count >= 20 && $team_count >= 200){
                        echo '用户id'.$info['id'].'--直推【'.$son_count.'】--团队【'.$team_count.'】'.PHP_EOL;
                        Db::name('member')->where('id', $info['id'])->update(['agent_type'=>2]);
                    }elseif($son_count >= 10 && $team_count >= 50){
                        echo '用户id'.$info['id'].'--直推【'.$son_count.'】--团队【'.$team_count.'】'.PHP_EOL;
                        Db::name('member')->where('id', $info['id'])->update(['agent_type'=>1]);
                    }else{
                        Db::name('member')->where('id', $info['id'])->update(['agent_type'=>0]);
                    }

                    Db::commit();
                }
                catch(Exception $e){
                    echo $e->getMessage();
                    Db::rollback();
                }
            }
        });
        echo '====='.time();
    }

}
