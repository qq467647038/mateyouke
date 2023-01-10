<?php
namespace app\apicloud\controller;
use app\util\timeFormat;
use think\Db;
use think\Controller;
use app\common\service\MiniWxPay;
use app\apicloud\model\AliPayHelper;
use app\common\service\ComWxPay;
use app\common\service\PortalMiniWxPay;
use app\apicloud\model\Gongyong as GongyongMx;
use app\common\util\WechatUtil;
use app\util\obs;

class School extends Common {
	public $user;

    public function partUpload(){
        $obs = new obs();

    }
	
	// 验证用户登录
    public function _initialize(){
        parent::_initialize();
//
		$methodName = request()->action();
		if($methodName != 'getCourseLiveList' && $methodName != 'getcourselivelist'){
			$gongyong = new GongyongMx();
			$result = $gongyong->apivalidate();
			if($result['status'] == 200){
			   if($result['user_id']){
					$this->user = $result;

			   }else{
				   return datamsg(LOSE,'未登录',array('count'=>0));
			   }
			}else{
			   // return datamsg(LOSE,$result['mess'],array('count'=>0));
			}
		}
    }

    public function index(){
        //banner
        $banners = Db::name('ad')->where('pos_id', '18')->where('is_on', 1)->where('ad_type', 2)->field('id, ad_name')->select();
        foreach ($banners as $k=>$v){
            $ad_pic = Db::name('ad_pic')->where('ad_id', $v['id'])->field('pic, canshu')->find();
            if($ad_pic){
                $banners[$k]['ad_pic'] = $this->webconfig['weburl'].$ad_pic['pic'];
                $banners[$k]['ad_link'] = $ad_pic['canshu'];
            }
        }
        $list['banners'] = $banners;
        //广告
//        $ads = Db::name('ad')->where('pid', '51319')->select();
//        $list['ads'] = $ads;

        $teachers = Db::name('course_teacher')->where('useable', 1)->order("addtime desc")->limit(0, 3)->select();
        foreach ($teachers as $k=>$v){
            $teachers[$k]['imgurl'] = $this->webconfig['weburl'].$v['imgurl'];
        }
        $list['teachers'] = $teachers;

        $courses = Db::name('course_course')->where('useable', 1)->order("ishot desc,bestnum desc")->limit(0, 6)->select();
        foreach ($courses as $k=>$v){
            $courses[$k]['addtime'] = (new timeFormat($v['addtime']))->calculateTime()->getTime();
            $courses[$k]['imgurl'] = $this->webconfig['weburl'].$v['imgurl'];
        }
        $list['courses'] = $courses;

        $cats = Db::name('course_category')->order('catid asc')->select();
        $list['cats'] = $cats;

        /*$recs = M('course_course')->where('useable', 1)->where('isrec', 1)->order("addtime desc")->limit(0, 10)->select();
        foreach ($recs as $key => $value) {
            $pricetip = '';
            if($value['price'] > 0){
                if($this->checkCourse($value['course_id'])){
                    $recs[$key]['pricetip'] = '会员免费';
                }else{
                    $recs[$key]['pricetip'] = '￥' . $value['price'] . '元';
                }
            }else{
                $recs[$key]['pricetip'] = '免费';
            }
        }
        $this->assign('recs', $recs);*/

        return datamsg(WIN, '获取成功', $list);
    }

    public function ajax_course_list(){
        $teacher_id = input('teacher_id/d', 0);
        $isrec = input('isrec/d', 0);
        $isfree = input('isfree/d', 0);
        $catid = input('catid/d', 0);
        $keyword = input('keyword');
        $ishot = input('ishot/d', 0);
        $page = input('page/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);

        $where = "useable = '1'";
        if($teacher_id){
            $where .= " and teacher_id = '$teacher_id'";
        }
        if($isrec){
            $where .= " and isrec = '$isrec'";
        }
        if($isfree){
            $where .= " and price <= '0'";
        }
        if($catid){
            $where .= " and catid = '$catid'";
        }
        if($keyword){
            $where .= " and title like '%$keyword%'";
        }

        $order='addtime desc';
        if($ishot){
            $order='ishot desc, bestnum desc';
        }

        $count = Db::name('course_course')->where($where)->count();
        $list = Db::name('course_course')->where($where)->order($order)->limit($limit, $pagesize)->select();
        foreach ($list as $key => $value) {
            $list[$key]['imgurl'] = $this->webconfig['weburl'].$value['imgurl'];
            if($value['price'] > 0){
                $list[$key]['pricetip'] = '￥' . $value['price'];
            }else{
                $list[$key]['pricetip'] = '免费';
            }
        }
        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
        return datamsg(WIN, '获取成功', $arr);
    }

    public function follow(){
        $input = input('post.');
        $num = Db::name('course_fans')->where('user_id', $this->user['user_id'])->where('fan_user_id', $input['fan_user_id'])->count();
        if($this->user['user_id'] == $input['fan_user_id']){
            return datamsg(LOSE,'你要和自己交流吗？');
        }
        $teacher_id = Db::name('course_teacher')->where('user_id', $input['fan_user_id'])->value('teacher_id');
        if($num > 0){
            $r = Db::name('course_fans')->where('user_id', $this->user['user_id'])->where('fan_user_id', $input['fan_user_id'])->delete();
            if($r !== false){
                Db::name('course_teacher')->where('teacher_id', $teacher_id)->setDec('fansnum', 1);
                return datamsg(WIN, '取消成功');
            }else{
                return datamsg(LOSE,'取消失败');
            }
        }else{
            $data = [
                'user_id' => $this->user['user_id'],
                'fan_user_id' => $input['fan_user_id'],
                'addtime' => time()
            ];

            $r = Db::name('course_fans')->insert($data);
            if($r !== false){
                Db::name('course_teacher')->where('teacher_id', $teacher_id)->setInc('fansnum', 1);
                return datamsg(WIN, '关注成功');
            }else{
                return datamsg(LOSE,'关注失败');
            }
        }
    }

    public function ajax_teacher_sale_list(){
        $page = input('page/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);

        $teacherInfo = Db::name('course_teacher')->where('user_id', $this->user['user_id'])->find();
        if(is_null($teacherInfo))return datamsg(LOSE,'当前用户不是老师');

        $course_id_arr = Db::name('course_course')->where('teacher_id', $teacherInfo['teacher_id'])->column('course_id');

        $count = Db::name('course_order')->where('course_id', 'in', $course_id_arr)->where('state', 1)->count();
        $list = Db::name('course_order')->where('course_id', 'in', $course_id_arr)->where('state', 1)->limit($limit, $pagesize)->select();
        foreach ($list as $k=>$v) {
            $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            $list[$k]['user'] = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$v['user_id']]);
            $course = Db::name('course_course')->where('course_id', $v['course_id'])->find();
            $course['imgurl'] = $this->webconfig['weburl'].$course['imgurl'];
            $list[$k]['course'] = $course;
        }

        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
        return datamsg(WIN,'获取成功', $arr);
    }

    public function orderDetail(){
        $order_id = input('order_id/d', 0);

        $orderInfo = Db::name('course_order')->where('order_id', $order_id)->where('state', 1)->find();
        if(is_null($orderInfo))return datamsg(LOSE, '订单不存在');

        $orderInfo['addtime'] = date('Y-m-d', $orderInfo['addtime']);
        $courseInfo = Db::name('course_course')->where('course_id', $orderInfo['course_id'])->find();
        $courseInfo['imgurl'] = $this->webconfig['weburl'].$courseInfo['imgurl'];
        $teacherInfo = Db::name('course_teacher')->where('teacher_id', $courseInfo['teacher_id'])->find();
        $orderInfo['course'] = $courseInfo;
        $orderInfo['teacher'] = $teacherInfo;

        return datamsg(WIN,'获取成功', $orderInfo);
    }

    public function ajax_teacher_list(){
        $page = input('page/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);

        $where = "useable = '1'";
        $count = Db::name('course_teacher')->where($where)->count();
        $list = Db::name('course_teacher')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();
        foreach ($list as $key => $value) {
            $list[$key]['imgurl'] = $this->webconfig['weburl'].$value['imgurl'];
            $isfollow = Db::name('course_fans')->where('user_id', $this->user['user_id'])->where('fan_user_id', $value['user_id'])->count();
            $list[$key]['isfollow'] = $isfollow;
            $list[$key]['isself'] = $value['user_id'] == $this->user['user_id'] ? 1 : 0;
            $list[$key]['description'] = mb_substr($value['description'], 0, 22) . '...';
        }
        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
        return datamsg(WIN, '获取成功', $arr);
    }

    public function ajax_index_list(){
        $isrec = input('isrec/d', 0);
        $isfree = input('isfree/d', 0);
        $catid = input('catid/d', 0);

        $where = "useable = '1'";
        if($isrec){
            $where .= " and isrec = '$isrec'";
        }
        if($isfree){
            $where .= " and price <= '0'";
        }
        if($catid){
            $where .= " and catid = '$catid'";
        }
        $list = Db::name('course_course')->where($where)->order('addtime desc')->limit(0, 10)->select();
        foreach ($list as $key => $value) {
            $list[$key]['imgurl'] = $this->webconfig['weburl'].$value['imgurl'];
            $pricetip = '';
            if($value['price'] > 0){
                $list[$key]['pricetip'] = '￥' . $value['price'];

            }else{
                $list[$key]['pricetip'] = '免费';
            }
        }
        $arr['list'] = $list;
        return datamsg(WIN, '获取成功', $arr);
    }

    private function checkCourse($course_id){
        $num = Db::name('course_card')->where('course_id', $course_id)->count();
        if($num > 0){
            return true;
        }else{
            return false;
        }
    }
	
	// 课程详情
    public function course_detail(){
		$info = [];
        $course_id = input('course_id/d', 0);
        $user_id = input('user_id/d', 0);
        if($user_id){
            session('share_user_id', $user_id);
        }
		
        $data = Db::name('course_course')->where('course_id', $course_id)->find();
//        $data['content'] = img_add_protocal($data['content'], $this->webconfig['weburl']);
        $res = img_add_protocal($data['content'], $this->webconfig['weburl']);
        $data['album'] = $res[1];
        $data['content'] = $res[0];
        if(empty($data)){
			return datamsg(LOSE,'课程不存在',array('count'=>0));
        }
        if($data['useable'] == 0){
			return datamsg(LOSE,'课程审核中',array('count'=>0));
        }
		
        if($this->user['user_id'] == $data['user_id']){
            $data['isfree'] = 1;
        }else{
            if($data['price'] > 0){
                $ispay = Db::name('course_order')->where('user_id', $this->user['user_id'])->where('course_id', $course_id)->where('state', 1)->count();
                if($ispay && $ispay > 0){
                    $data['isfree'] = 1;
                }else{
                    $data['isfree'] = 0;
                }
            }else{
                $data['isfree'] = 1;
            }
        }

        $teacher = Db::name('course_teacher')->where('teacher_id', $data['teacher_id'])->find();
        $teacher['imgurl'] = $this->webconfig['weburl'].$teacher['imgurl'];
        $data['teacher'] = $teacher;
		
        $data['tags'] = array_filter(explode(',', $data['tags']));

        $cats = Db::name('course_video_category')->where("pid = '0' and course_id = '$course_id'")->order('sort asc')->select();
        foreach ($cats as $key => $value) {
            $child = Db::name('course_video_category')->where("pid = '$value[catid]' and course_id = '$course_id'")->order('sort asc')->select();
            $childnum = 0;
            foreach ($child as $k => $v) {
                $video_id = Db::name('course_video')->where('course_id', $course_id)->where('catid', $v['catid'])->where('useable', 1)->value('video_id');
                if(!empty($video_id)){
                    $study = Db::name('course_study')->where('course_id', $course_id)->where('video_id', $video_id)->where('user_id', $this->user['user_id'])->find();
                    $info['timeDisplay'] = !empty($study) ? $study['timeDisplay'] : 0;
                    if(empty($study)){
                        $child[$k]['isstudy'] = 0;
                    }elseif($study['timeDisplay'] >= $study['duration']){
                        $child[$k]['isstudy'] = 1;
                    }else{
                        $child[$k]['isstudy'] = 2;
                    }
                    if($key == 0 && $k == 0){
                        $child[$k]['isprew'] = 1;
                    }else{
                        $child[$k]['isprew'] = 0;
                    }
                    $childnum++;
                }else{
                    unset($child[$k]);
                }
            }
            if($childnum > 0){
                if($key == 0){
                    $cats[$key]['open'] = 1;
                }else{
                    $cats[$key]['open'] = 0;
                }
                $cats[$key]['child'] = array_values($child);
            }else{
                unset($cats[$key]);
            }
            
        }
        $data['cats'] = array_values($cats);
        if(!empty($data['cats'][0]['child'][0])){
            $firstdata = $data['cats'][0]['child'][0];
            $firstvideo = Db::name('course_video')->where('course_id', $firstdata['course_id'])->where('catid', $firstdata['catid'])->find();
            $firstvideo['imgurl'] = $this->webconfig['weburl'].str_replace('\\', '/', $firstvideo['imgurl']);

            if(!empty($firstvideo['out_videourl'])){
                $firstvideo['videourl'] = $firstvideo['out_videourl'];
            }else{
                if(preg_match('/oss_course/', $firstvideo['videourl'])){
                    // $obsConf = C('Obs');
                    $obsConf = 'https://cxy365-file.obs.cn-south-1.myhuaweicloud.com/';
                    // $video['videourl'] = $obsConf['url'] . $video['videourl'];
                    $firstvideo['videourl'] = $obsConf . $firstvideo['videourl'];
                }else{
                    $firstvideo['videourl'] = $this->webconfig['weburl'].$firstvideo['videourl'];
                }
            }
            $data['firstdata'] = $firstvideo;
            $data['hasfirst'] = 1;
        }else{
            $data['hasfirst'] = 0;
        }
        $isbest = Db::name('course_best')->where('user_id', $this->user['user_id'])->where('post_id', $course_id)->where('kind', 1)->value('praise');
		$info['isbest'] = $isbest;

        $isstow = Db::name('course_stow')->where('user_id', $this->user['user_id'])->where('post_id', $course_id)->value('stow');
		$info['isstow'] = $isstow;

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
		$info['http_type'] = $http_type;

        $shareinfo = str_replace(array("\r\n", "\r", "\n"), "", $data['description']);
        $shareinfo = preg_replace('/\s+/', '', $shareinfo);
		$info['shareinfo'] = $shareinfo;

		$info['data'] = $data;
		return datamsg(WIN, '获取成功', $info);
	}
	
    // 评论列表
	public function ajax_feed_list(){
        $page = input('page/d', 1);
        $post_id = input('post_id/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);

        $where = "useable = '1' and post_id = '$post_id' and pid = '0'";
        $count = Db::name('course_feed')->where($where)->count();
        $list = Db::name('course_feed')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();
        foreach ($list as $key => $value) {
            $comm_user = Db::name('member')->field('id,user_name as nickname,headimgurl')->where('id', $value['user_id'])->find();
            if(preg_match("/^1[3456789]\d{9}$/", $comm_user['nickname'])){
                $comm_user['nickname'] = '匿名';
            }
            $list[$key]['user'] = $comm_user;
            $list[$key]['addtime'] = (new timeFormat($value['addtime']))->calculateTime()->getTime();

            $praise = Db::name('course_best')->where('user_id', $this->user['user_id'])->where('post_id', $value['feed_id'])->where('kind', 2)->value('praise');
            $list[$key]['isbest'] = $praise ?: 0;
            $list[$key]['images'] = !empty($value['imgurl']) ? explode(',', $value['imgurl']) : [];

            $childs = Db::name('course_feed')->where("pid = '$value[feed_id]' and useable = '1'")->order('addtime desc')->select();
            foreach ($childs as $k => $v) {
                $comm_user = Db::name('member')->field('id,user_name as nickname,headimgurl')->where('id', $v['user_id'])->find();
                if(preg_match("/^1[3456789]\d{9}$/", $comm_user['nickname'])){
                    $comm_user['nickname'] = '匿名';
                }
                $childs[$k]['user'] = $comm_user;
            }
            $list[$key]['childs'] = $childs;
        }
        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
		return datamsg(WIN, '评论成功', $arr);
    }
	
    //内容点赞
    public function postbest(){
        $input = input('post.');
//        $num = Db::name('course_best')->where('user_id', $this->user['user_id'])->where('post_id', $input['post_id'])->where('kind', $input['kind'])->count();
//        if($num > 0){
//			return datamsg(LOSE,'已点赞');
//        }

        $info = Db::name('course_best')->where('user_id', $this->user['user_id'])->where('post_id', $input['post_id'])->where('kind', $input['kind'])->find();
        $text = $info['praise'] == 1 ? '取消' : '点赞';
        if(is_null($info)){
            $data = [
                'user_id' => $this->user['user_id'],
                'post_id' => $input['post_id'],
                'kind' => $input['kind'],
                'addtime' => time()
            ];

            $r = Db::name('course_best')->insert($data);
            if(!$r)return datamsg(LOSE, '点赞失败');
        }else{
            $update['praise'] = 0;
            if($info['praise'] == 0)
                $update['praise'] = 1;

            $r = Db::name('course_best')->where('user_id', $this->user['user_id'])->where('post_id', $input['post_id'])->where('kind', $input['kind'])->update($update);
            if(!$r)return datamsg(LOSE, $text.'失败');
        }

        if($input['kind'] == 2){
            if($text == '取消'){
                $res = Db::name('course_feed')->where('feed_id', $input['post_id'])->setDec('bestnum', 1);
            }else{
                $res = Db::name('course_feed')->where('feed_id', $input['post_id'])->setInc('bestnum', 1);
            }

        }else{
            if($text == '取消'){
                $res = Db::name('course_course')->where('course_id', $input['post_id'])->setDec('bestnum', 1);
            }else{
                $res = Db::name('course_course')->where('course_id', $input['post_id'])->setInc('bestnum', 1);
            }

        }
        if(!$res)return datamsg(LOSE, $text.'失败');
        return datamsg(WIN, $text.'成功');
    }
	
    public function ajax_video(){
        $course_id = input('course_id/d', 0);
        $catid = input('catid/d', 0);

        $video = Db::name('course_video')->where('course_id', $course_id)->where('catid', $catid)->find();

        if(empty($video)){
			return datamsg(LOSE,'视频获取失败',array('count'=>0));
        }else{
            if($video['goods_id'] > 0){
                $goods = Db::name('goods')->field('goods_name,original_img,goods_id,merchant_id,shop_price')->where('goods_id', $video['goods_id'])->find();
                $video['goods'] = $goods;
            }
            if(input('isfree') == 1){
                $study = Db::name('course_study')->where('user_id', $this->user['user_id'])->where('course_id', $course_id)->where('video_id', $video['video_id'])->find();
                if(!empty($study)){
                    Db::name('course_study')->where('user_id', $this->user['user_id'])->where('course_id', $course_id)->where('video_id', $video['video_id'])->update(['addtime' => time()]);
                }else{
                    Db::name('course_study')->insert([
                        'user_id' => $this->user['user_id'],
                        'course_id' => $course_id,
                        'video_id' => $video['video_id'],
                        'teacher_id' => $video['teacher_id'],
                        'addtime' => time()
                    ]);
                    Db::name('course_course')->where('course_id', $course_id)->setInc('shownum', 1);
                }
                $video['currtime'] = $study['timeDisplay'];
            }else{
                $video['currtime'] = 0;
            }
            if(!empty($video['out_videourl'])){
                $video['videourl'] = $video['out_videourl'];
            }else{
                if(preg_match('/oss_course/', $video['videourl'])){
                    // $obsConf = C('Obs');
                    $obsConf = 'https://cxy365-file.obs.cn-south-1.myhuaweicloud.com/';
                    // $video['videourl'] = $obsConf['url'] . $video['videourl'];
                    $video['videourl'] = $obsConf . $video['videourl'];
                }else{
                    $video['videourl'] = $video['videourl'];
                }
            }
            
			return datamsg(WIN, '获取成功', $video);
        }
    }
	
    public function poststow(){
        $input = input('post.');
//        $num = Db::name('course_stow')->where('user_id', $this->user['user_id'])->where('post_id', $input['post_id'])->count();
//        if($num > 0){
//			return datamsg(LOSE,'已收藏');
//        }
        $info = Db::name('course_stow')->where('user_id', $this->user['user_id'])->where('post_id', $input['post_id'])->find();
        $text = $info['stow'] == 1 ? '取消' : '收藏';

        if(is_null($info)){
            $data = [
                'user_id' => $this->user['user_id'],
                'post_id' => $input['post_id'],
                'addtime' => time()
            ];

            $r = Db::name('course_stow')->insert($data);
            if(!$r)return datamsg(LOSE, '收藏失败');
        }else{
            $update['stow'] = 0;
            if($info['stow'] == 0)
                $update['stow'] = 1;

            $res = Db::name('course_stow')->where('user_id', $this->user['user_id'])->where('post_id', $input['post_id'])->update($update);
            if(!$res)return datamsg(LOSE, $text.'失败');
        }

        if($text == '取消'){
            $res = Db::name('course_course')->where('course_id', $input['post_id'])->setDec('stownum', 1);
        }else{
            $res = Db::name('course_course')->where('course_id', $input['post_id'])->setInc('stownum', 1);
        }

        if(!$res)return datamsg(LOSE,$text.'失败');
            return datamsg(WIN,$text.'成功');
    }
	
    public function postfeed(){
        $input = input('post.');
        $data = [
            'user_id' => $this->user['user_id'],
            'post_id' => $input['post_id'],
            'pid' => $input['pid'],
            'content' => $input['content'],
            'useable' => 1,
            'addtime' => time()
        ];

        $feed_id = Db::name('course_feed')->insertGetId($data);
        if($feed_id){

            if(!empty($input['imgstr'])){
                //图片文件夹判断
                $dirName = "public/uploads/course/" . date('Y') . '/' . date('m-d');
                if(!is_dir($dirName)) {
                    mkdir($dirName);
                    chmod($dirName, 0777);
                }

                $wx_user = Db::name('wx_user')->find();
                $wx = new WechatUtil($wx_user);

                $access = $wx->getAccessToken();
                $imgstrs = explode(',', $input['imgstr']);
                $imgurlarr = [];
                foreach ($imgstrs as $k => $v) {
                    $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access . "&media_id=" . $v;
                    $img = $wx->httpRequest($url);

                    while (1)
                    {
                        $fileName = time() . mt_rand(0,10) . ".jpg";
                        $fileName = $dirName . "/" . $fileName;
                        if(!file_exists($fileName)){
                            break;
                        }
                    }

                    file_put_contents($fileName,$img);
                    array_push($imgurlarr, "/" . $fileName);
                }
                Db::name('course_feed')->where('feed_id', $feed_id)->update(['imgurl' => implode(',', $imgurlarr)]);
            }

            Db::name('course_course')->where('course_id', $input['post_id'])->setInc('feednum', 1);
            $arr = ['code' => 1, 'msg' => '评论成功'];
            $arr['headimgurl'] = $this->user['headimgurl'] ? $this->user['headimgurl'] : '/template/mobile/new2/static/images/user68.jpg';
            $arr['nickname'] = $this->user['user_name'];
            $arr['addtime'] = (new timeFormat($data['addtime']))->calculateTime()->getTime();
            $arr['content'] = $data['content'];
            $arr['bestnum'] = 0;
            $arr['feed_id'] = $feed_id;
			return datamsg(WIN, '获取成功', $arr);
        }else{
			return datamsg(LOSE,'评论失败');
        }
    }
	
	// 课程视频二维码分享
	public function shareImg(){
        $course_id = input('course_id/d', 0);
        $width = input('width/d', 750);
        $height = input('height/d', 1200);
        $arr['imgurl'] = $this->getShareImage($course_id, $width, $height);
		
		return datamsg(WIN, '获取成功', $arr);
    }
	
    private function getNetworkImgType($url){


        $ch = curl_init(); //初始化curl

        curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); //支持https
        curl_exec($ch);//执行curl会话
        $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
        curl_close($ch);//关闭资源连接
        if ($http_code['http_code'] == 200) {
            $theImgType = explode('/',$http_code['content_type']);
            if($theImgType[0] == 'image'){
                return $theImgType[1];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
	
    private function createImageFromFile($file){
        if(preg_match('/http(s)?:\/\//',$file)){
            $fileSuffix = $this->getNetworkImgType($file);
        }else{
            $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
        }

        if(!$fileSuffix) return false;
        switch ($fileSuffix){
            case 'jpeg':
                $theImage = @imagecreatefromjpeg($file);
                // dump($theImage);die;
                break;
            case 'jpg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'png':
                $theImage = @imagecreatefrompng($file);
                break;
            case 'gif':
                $theImage = @imagecreatefromgif($file);
                break;
            default:
                $theImage = @imagecreatefromstring(file_get_contents($file));
                break;
        }
        return $theImage;
    }
	
    //生成分享图
    public function getShareImage($course_id, $width, $height){
        if(empty($course_id)) return '';
        $course  = Db::name('course_course')->where('course_id', $course_id)->find();
        $teacher = Db::name('course_teacher')->where('teacher_id', $course['teacher_id'])->value('title');
        if(preg_match('/http/', $course['imgurl'])){
            $imgurl = $course['imgurl'];
        }else{
            $imgurl = $_SERVER["DOCUMENT_ROOT"] .'/'. $course['imgurl'];
        }

        list($g_w, $gs_h)  = getimagesize($imgurl);
        $g_h = 700;

        $canvas_width  = $width;
        $canvas_heigth = 600;//$height;
        $im = imagecreatetruecolor($canvas_width, $canvas_heigth);

        //填充画布背景色
        $color = imagecolorallocate($im, 255, 255, 255);//白色
        imagefill($im, 0, 0, $color);//填充

        //字体文件
        $font_file      = $_SERVER["DOCUMENT_ROOT"]."/public/css/msyhl.ttc";
        $font_file_bold = $_SERVER["DOCUMENT_ROOT"]."/public/css/bold.ttf";
        $t2             = $_SERVER["DOCUMENT_ROOT"]."/public/css/t2.ttf";

        //设定字体的颜色
        $font_color_2     = ImageColorAllocate ($im, 0, 0, 0);
        $font_color_red   = ImageColorAllocate ($im, 217, 45, 32);
        $font_color_3     = ImageColorAllocate ($im, 133, 133, 133);

        //画课程图片
        $courseImg          = $this->createImageFromFile($imgurl);
        $imgw = $canvas_width;
        $per = round($width / $g_w, 3);
        $n_h = $gs_h * $per;
        imagecopyresampled($im, $courseImg, 0, 0, 0, 0, $imgw, $n_h, $g_w, $gs_h);

        $cus_height = 350;

        $bottomim = imagecreatetruecolor($canvas_width, 300);
        $bottomcolor = imagecolorallocate($bottomim, 255, 255, 255);//白色
        imagefill($bottomim, 0, 0, $bottomcolor);//填充
        imagecopyresampled($im, $bottomim, 0, 350, 0, 0, $canvas_width, 300, $canvas_width, 350);

        $eyeimgurl = $_SERVER["DOCUMENT_ROOT"] . '/template/mobile/new2/static/course/images/eye.png';

        list($g_e_w, $gs_e_h)  = getimagesize($eyeimgurl);
        $eyeimg          = $this->createImageFromFile($eyeimgurl);
        imagecopyresampled($im, $eyeimg, 20, $cus_height - 38, 0, 0, 130, 38, $g_e_w, $gs_e_h);

        $sitelogo = $_SERVER["DOCUMENT_ROOT"] . '/template/mobile/new2/static/course/images/06.png';
        list($g_l_w, $gs_l_h)  = getimagesize($sitelogo);
        $logoimg          = $this->createImageFromFile($sitelogo);
//        imagecopyresampled($im, $logoimg, 20, 20, 0, 0, $g_l_w, $gs_l_h, $g_l_w, $gs_l_h);

        //获取用户头像
        $user_info      = Db::name('member')->where(['id'=>$this->user['user_id']])->find();
		
        if(empty($user_info["headimgurl"])){
            $head_pic = $_SERVER["DOCUMENT_ROOT"] . '/template/mobile/new2/static/images/user68.jpg';
        }else{
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $user_info["headimgurl"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $file_content = curl_exec($ch);
            curl_close($ch);

            if ($file_content) {
                $head_pic_path = $_SERVER["DOCUMENT_ROOT"]."/".time().rand(1, 10000).'.png';
                file_put_contents($head_pic_path, $file_content);
                $head_pic = $head_pic_path;
            }else{
                $head_pic = $_SERVER["DOCUMENT_ROOT"] . '/template/mobile/new2/static/images/user68.jpg';
            }
        }
        
        //画用户头像
        $logo = @imagecreatefromstring(file_get_contents($head_pic));
        $wh  = getimagesize($head_pic);
        $w   = $wh[0];
        $h   = $wh[1];
        $w   = min($w, $h);
        $h   = $w;
        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true);
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r   = $w / 2;
        $y_x = $r;
        $y_y = $r;
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($logo, $x, $y);
                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }

        imagecopyresampled($im, $img, 60 , $cus_height - 30, 0, 0, 50, 50, $w, $h);
        if($head_pic && !empty($user_info["headimgurl"])){
            unlink($head_pic);
        }
        //描述
        //imagettftext($im, 14,0, 60, $cus_height + 70, $font_color_2 ,$font_file, $user_info["nickname"]);

        imagettftext($im, 14, 0, 10, $cus_height + 90, $font_color_2 ,$font_file, $this->mg_cn_substr($course["title"], 30) . '...');

        imagettftext($im, 12, 0, 10, $cus_height + 130, $font_color_3 ,$font_file, $this->mg_cn_substr($user_info["user_name"], 10) . ' 邀请您一起来学习');

        imagettftext($im, 14, 0, 10, $cus_height + 180, $font_color_3 ,$font_file, '来自 [ 孝笑学堂 ]');
        //imagettftext($im, 14,0, 60, 420, $font_color_2 ,$font_file, $teacher);

        //二维码
        vendor('phpqrcode.phpqrcode');
        // $value = "http://".$_SERVER["HTTP_HOST"]."/mobile/course/course_detail/course_id/" . $course["course_id"] . "/user_id/" . $user_info["id"];
        $value = "/Portal/Course-Details/Course-Details/course_id/" . $course["course_id"] . "/user_id/" . $user_info["id"];

        $errorCorrectionLevel = 'L';          //容错级别
        $matrixPointSize = 5;                 //生成图片大小
        //生成二维码图片
        $qr_code_path = $_SERVER["DOCUMENT_ROOT"].'/uploads/qr_code/';
        $filename     =$qr_code_path .time().rand(1, 10000).'.png';
        \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
        $QR = $filename;        //已经生成的原始二维码图片文件
        $QR = imagecreatefromstring(file_get_contents($QR));

        $wh  = getimagesize($filename);
        imagecopyresampled($im, $QR, $canvas_width - 160, $cus_height + 50, 0, 0, 150, 150, $wh[0], $wh[1]);
        imagettftext($im, 12, 0, $canvas_width - 140, $cus_height + 210, $font_color_2 ,$font_file, '扫码查看此课程');
        unlink($filename);

        $image_data_base64 = "";
        ob_start();
        imagepng($im);
        $image_data = ob_get_contents ();
        ob_end_clean ();
        $image_data_base64 = "data:image/png;base64,". base64_encode ($image_data);

        imagedestroy($im);
        imagedestroy($bottomim);
        imagedestroy($courseImg);
        imagedestroy($QR);
        return $image_data_base64;
    }
	
    private function mg_cn_substr($str,$len,$start = 0){
        $q_str = '';
        $q_strlen = ($start + $len)>strlen($str) ? strlen($str) : ($start + $len);

        //如果start不为起始位置，若起始位置为乱码就按照UTF-8编码获取新start
        if($start and json_encode(substr($str,$start,1)) === false){
            for($a=0;$a<3;$a++){
                $new_start = $start + $a;
                $m_str = substr($str,$new_start,3);
                if(json_encode($m_str) !== false) {
                    $start = $new_start;
                    break;
                }
            }
        }

        //切取内容
        for($i=$start;$i<$q_strlen;$i++){
            //ord()函数取得substr()的第一个字符的ASCII码，如果大于0xa0的话则是中文字符
            if(ord(substr($str,$i,1))>0xa0){
                $q_str .= substr($str,$i,3);
                $i+=2;
            }else{
                $q_str .= substr($str,$i,1);
            }
        }
        return $q_str;
    }

    // 更新视频课程播放时间
    public function setvideo(){
        $course_id = input('course_id/d', 0);
        $catid = input('catid/d', 0);
        $timeDisplay = input('timeDisplay/d', 0);
        $duration = input('duration/d', 0);
        $video = Db::name('course_video')->where('course_id', $course_id)->where('catid', $catid)->find();
        if(!empty($video)){

            $study = Db::name('course_study')->where('course_id', $course_id)->where('video_id', $video['video_id'])->where('user_id', $this->user['user_id'])->find();
            if(($study['timeDisplay'] < $study['duration']) || $study['duration'] == 0){
                $r = Db::name('course_study')->where('user_id', $this->user['user_id'])->where('course_id', $course_id)->where('video_id', $video['video_id'])->update(['timeDisplay' => $timeDisplay, 'duration' => $duration]);
            }

            return datamsg(WIN,'ok');
        }else{
            return datamsg(LOSE,'error');
        }
    }

    // 得到课程直播列表
    public function getCourseLiveList(){
        $page = input('param.page') ? input('param.page') : 1;
        $size = input('param.size') ?  input('param.size') : 6;

        $list = Db::name('course_course')->where(['useable'=>1])->field('course_id, addtime, shownum, imgurl, title, course_id')->order('addtime desc')->paginate($size)->each(function ($item){
            $item['imgurl'] = $this->webconfig['weburl'].str_replace('\\', '/', $item['imgurl']);
            $item['addtime'] = (new timeFormat($item['addtime']))->calculateTime()->getTime();
            $item['alltime'] = Db::name('course_video')->where('course_id', $item['course_id'])->value('alltime');

            return $item;
        });

        return datamsg(WIN,'获取成功', $list);
    }

    // 验证老师中心页面
    public function TeacherPage(){
        $count  = Db::name('course_teacher')->where('user_id', $this->user['user_id'])->count();

        return datamsg(WIN,'获取成功', ['count'=>$count]);
    }

    // 老师信息
    public function TeacherInfo(){
        $input = input('post.');
        $info  = Db::name('course_teacher')->where('user_id', $this->user['user_id'])->where('teacher_id', $input['teacher_id'])->find();
        // $info['content'] = img_add_protocal($info['content'], $this->webconfig['weburl']);
        $info['content'] = $info['content'];
        $info['imgurl'] = $this->webconfig['weburl'].$info['imgurl'];

        return datamsg(WIN,'获取成功', $info);
    }
	
	// 老师中心
    public function setinfo()
    {
        $input = input('post.');
        $input['imgPath'] = json_decode($input['imgPath'], true);
        // $input['content'] = str_replace($this->webconfig['weburl'], '', $input['content']);
//        $input['content'] = $input['content'];
        $get_id = $input['teacher_id'];

        //1、取整个图片代码
        for($i=0; $i<count($input['imgPath']); $i++){
            if(strpos($input['imgPath'][$i][0], 'data:image') !== false){
//                $explode_val = explode(',', $input['imgPath'][$i][0]);
//                $fileUrl = "/uploads/editor/" . date('Y') . '/' . date('m-d') . '/' . uniqid().rand(1, 10000).'.'.$input['imgPath'][$i][1];
//                file_put_contents(ROOT_DIR.$fileUrl, $explode_val[1]);
                $input['content'] = str_replace($input['imgPath'][$i][0], $input['imgPath'][$i][1], $input['content']);
            }
        }

        //图片文件夹判断
        $dirName = "public/uploads/school/" ;
        if(!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        if($input['isCover'] == 1){
            $file = request()->file('image');
            if($file){

                $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . $dirName);

                if($info){
                    $original = 'uploads/school/' .$info->getSaveName();

                    $image = \think\Image::open('./'.$original);
                    $image->thumb(300, 300)->save('./'.$original,null,90);
                    $imgurl = $original;
                }else{
                    return datamsg(LOSE, $file->getError());
                }
            }
        }

        $data = [
            'title' => $input['title'],
            'description' => $input['description'],
            'content' => $input['content']
        ];
        if ($get_id > 0) {
            if(!empty($imgurl)){
                $data['imgurl'] = $imgurl;
            }

            $data['useable'] = 0;
            $r = Db::name('course_teacher')->where('teacher_id', $get_id)->update($data);
            if ($r !== false) {
                return datamsg(WIN,'编辑成功，请等待审核');
            } else {
                return datamsg(LOSE,'编辑失败');
            }
        } else {
            // 验证是否已存在
            if(Db::name('course_teacher')->where('user_id', $this->user['user_id'])->count() > 0)return datamsg(LOSE,'操作异常');

            $data['user_id'] = $this->user['user_id'];
            $data['useable'] = 0;
            $data['imgurl'] = $imgurl;
            $data['addtime'] = time();

            $teacher_id = Db::name('course_teacher')->insertGetId($data);
            if ($teacher_id) {
                return datamsg(WIN,'新增成功，请等待审核');
            } else {
                return datamsg(LOSE,'新增失败');
            }
        }
    }

    // 老师详情
    public function teacher_detail(){
        $teacher_id = input('teacher_id/d', 0);
        $teacher = Db::name('course_teacher')->where('teacher_id', $teacher_id)->find();

        if(empty($teacher)){
            return datamsg(LOSE,'名师不存在');
        }

        $teacher['token'] = Db::name('rxin')->where('user_id', $teacher['user_id'])->value('token');
        $teacher['imgurl'] = $this->webconfig['weburl'].$teacher['imgurl'];

        $isfollow = Db::name('course_fans')->where('user_id', $this->user['user_id'])->where('fan_user_id', $teacher['user_id'])->count();
        $teacher['isfollow'] = $isfollow;
        if($isfollow){
            $followtxt = '取消关注';
        }else{
            $followtxt = '关注';
        }

        //粉丝数量
        $fansnum = Db::name('course_fans')->where('fan_user_id', $teacher['user_id'])->count();
        //关注数量
        $follownum = Db::name('course_fans')->where('user_id', $teacher['user_id'])->count();
        //课程数量
        $coursenum = Db::name('course_course')->where('teacher_id', $teacher_id)->where('useable', 1)->count();

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $data['http_type'] = $http_type;

        $shareinfo = str_replace(array("\r\n", "\r", "\n"), "", $teacher['description']);
        $shareinfo = preg_replace('/\s+/', '', $shareinfo);
        $data['shareinfo'] = $shareinfo;

        $data['isdel'] = $isfollow > 0 ? 'true' : 'false';
        $data['followtxt'] = $followtxt;
        $data['data'] = $teacher;
        $data['fansnum'] = $fansnum;
        $data['follownum'] = $follownum;
        $data['coursenum'] = $coursenum;
        $data['isself'] = $teacher['user_id'] == $this->user['user_id'] ? 1 : 0;
        return datamsg(WIN,'获取成功', $data);
    }

    public function myCourseOrder(){
        $studies = Db::name('course_order')->field('distinct course_id as c_id, amount')->where('state', 1)->where('user_id', $this->user['user_id'])->select();

        foreach ($studies as $key => $value) {
            $course = Db::name('course_course')->where('course_id', $value['c_id'])->find();
            if(!empty($course)){
                $studies[$key]['course'] = $course;

                $teacher = Db::name('course_teacher')->where('teacher_id', $course['teacher_id'])->find();
                $teacher['imgurl'] = $this->webconfig['weburl'].$teacher['imgurl'];
                $studies[$key]['teacher'] = $teacher;

            }else{
                unset($studies[$key]);
            }
        }
        sort($studies);
        return datamsg(WIN,'获取成功', $studies);
    }

    public function mystudy(){
        $studies = Db::name('course_study')->field('distinct course_id as c_id')->where('user_id', $this->user['user_id'])->select();

        foreach ($studies as $key => $value) {
            $course = Db::name('course_course')->where('course_id', $value['c_id'])->find();
            $course['imgurl'] = $this->webconfig['weburl'].$course['imgurl'];
            if(!empty($course)){
                $studies[$key]['course'] = $course;

                //获取百分比
                $mystudies = Db::name('course_study')->where('user_id', $this->user['user_id'])->where('course_id', $value['c_id'])->select();

                $num = 0;
                foreach ($mystudies as $k => $v) {
                    if($v['timeDisplay'] == $v['duration']){
                        $num += 1;
                    }
                }
                $per = ($num / $course['coursenum']) * 100;
                $studies[$key]['per'] = round($per, 2);
            }else{
                unset($studies[$key]);
            }
        }
        $data['my'] = 1;
        $data['list'] = $studies;
        return datamsg(WIN,'获取成功', $data);
    }

    public function pay(){
        // $orderUtil = new Order();
        $course_id = input('course_id/d', 0);
        $msg = input('msg/d', 0);
        $course = Db::name('course_course')->where('course_id', $course_id)->find();
        $teacher = Db::name('course_teacher')->where('teacher_id', $course['teacher_id'])->find();
        $share_user_id = session('share_user_id');
        if($share_user_id == $this->user['user_id']){
            $share_user_id = 0;
        }

        $order = Db::name('course_order')->where('user_id', $this->user['user_id'])->where('course_id', $course_id)->find();
        if(!empty($order)){
            $data["order"] = $order;
        }else{
            $order   =  [
                "order_sn"      =>    "vd".date("Ymdhis").rand(100,999),
                "addtime"   =>    time(),
                "amount"  =>    $course["price"],
                "user_id"       =>    $this->user['user_id'],
                "state"    =>    0,
                "course_id"      =>    empty($course_id)     ? 0 : $course_id,
                'share_user_id' => $share_user_id
            ];
            Db::name('course_order')->insert($order);
            $data["order"] = $order;
        }
        //获取所有支付工具
        // $info["paymentList"]   =   $orderUtil->getAllPayTool();
        $info["user"]          =   $this->memberInfo('*', ['id'=>$this->user['user_id']]);
        $info["course_id"]          =   $course_id;
        $info["course_info"]          =   $course;
        $info["teacher_info"]          =   $teacher;
        $info["data"]          =   $data;
        $info["msg"]          =   $msg;
        return datamsg(WIN,'获取成功', $info);
    }

    // 快捷支付
    public function payQuick(){
        $order_sn            =   input("order_sn");
        $zf_type            =   input("zf_type");
        $order               =   Db::name('course_order')->where('order_sn', $order_sn)->find();
        //订单是否存在
        if(!empty($order)){
            if($order['state'] == 1){
                return datamsg(LOSE,'此订单，已完成支付!');
            }
        }

        $userInfo   =  $this->memberInfo('*', ['id'=>$this->user['user_id']]);
        $nowtime = time();
        switch($zf_type){
            case 1:
                //获取支付宝支付配置信息返回
                //获取支付金额
                $money = $order['amount'];
                $reoderSn = $order['order_sn'];
                $notify_url = $this->webconfig['weburl']."/apicloud/CourseAliPay/aliNotify";
                $AliPayHelper = new AliPayHelper();
                $data = $AliPayHelper->getPrePayOrder('商品支付',$money,$reoderSn,$notify_url);
                $value = array('status'=>200,'mess'=>'获取成功成功','data'=>array('order_number'=>$reoderSn,'infos'=>$data));
                //$value = array('status'=>400,'mess'=>'支付宝支付暂未开通','data'=>array('status'=>400));
                return json($value);
                break;
            case 2:
                //获取订单号
                $reoderSn = $order['order_sn'];
                //获取支付金额
                $money = $order['amount'];

                // $wx = new Wxpay();
                $wx = new MiniWxPay();

                $body = '课程支付';//支付说明

                $out_trade_no = $reoderSn;//订单号

                $total_fee = $money * 100;//支付金额(乘以100)

                $time_start = $nowtime;

                // $time_expire = $orderinfos['time_out'];

                $notify_url = $this->webconfig['weburl'].'/apicloud/CourseWxpaynotify/notify';//回调地址

                $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_start+300, $notify_url, $userInfo);//调用微信支付的方法
                if($order['prepay_id']){
                    //判断返回参数中是否有prepay_id
                    $order['out_trade_no'] = $out_trade_no;
                    $order1 = $wx->getOrder($order);//执行二次签名返回参数
                    $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$out_trade_no,'infos'=>$order1));
                }else{
                    $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                }
                return json($value);
                break;
            case 4:
                    //获取订单号
                    $reoderSn = $order['order_sn'];
                    //获取支付金额
                    $money = $order['amount'];

                    $wx = new Wxpay();

                    $body = '课程支付';//支付说明

                    $out_trade_no = $reoderSn;//订单号

                    $total_fee = $money * 100;//支付金额(乘以100)

                    $time_start = $nowtime;

                    // $time_expire = $orderinfos['time_out'];

                    $notify_url = $this->webconfig['weburl'].'/apicloud/CourseWxpaynotify/notify';//回调地址

                    $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_start+300, $notify_url, $userInfo);//调用微信支付的方法
                    if($order['prepay_id']){
                        //判断返回参数中是否有prepay_id
                        $order['out_trade_no'] = $out_trade_no;
                        $order1 = $wx->getOrder($order);//执行二次签名返回参数
                        $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$out_trade_no,'infos'=>$order1));
                    }else{
                        $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                    }
                return json($value);
                break;
            case 5:
                    $reoderSn = $order['order_sn'];
                    //获取支付金额
                    $money = $order['amount'];

                    $wx = new ComWxPay();

                    $body = '课程支付';//支付说明

                    $out_trade_no = $reoderSn;//订单号

                    $total_fee = $money * 100;//支付金额(乘以100)

                    $time_start = $nowtime;

                    // $time_expire = $orderinfos['time_out'];

                    $notify_url = $this->webconfig['weburl'].'/apicloud/CourseWxpaynotify/notify';//回调地址

                    $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_start+300, $notify_url, $userInfo);//调用微信支付的方法

                    if($order['prepay_id']){
                        //判断返回参数中是否有prepay_id
                        $order['out_trade_no'] = $out_trade_no;
                        $order1 = $wx->getOrder($order);//执行二次签名返回参数

                        $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$out_trade_no,'infos'=>$order1));
                    }else{
                        $value = array('status'=>400,'mess'=>$order['return_msg'],'data'=>array('status'=>400));
                    }
                    echo json_encode($value);exit;
                break;

            case 6:
                //获取订单号
                $reoderSn = $order['order_sn'];
                //获取支付金额
                $money = $order['amount'];

                // $wx = new Wxpay();
                $wx = new PortalMiniWxPay();

                $body = '课程支付';//支付说明

                $out_trade_no = $reoderSn;//订单号

                $total_fee = $money * 100;//支付金额(乘以100)

                $time_start = $nowtime;

                // $time_expire = $orderinfos['time_out'];

                $notify_url = $this->webconfig['weburl'].'/apicloud/CourseWxpaynotify/notify';//回调地址

                $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_start+300, $notify_url, $userInfo);//调用微信支付的方法

                if($order['prepay_id']){
                    //判断返回参数中是否有prepay_id
                    $order['out_trade_no'] = $out_trade_no;
                    $order1 = $wx->getOrder($order);//执行二次签名返回参数
                    $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$out_trade_no,'infos'=>$order1));
                }else{
                    $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                }
                return json($value);
                break;
            case 3:
                $this->payWithMoney();
                break;
        }
    }

    // 余额支付
    public function payWithMoney(){
        $order_sn            =   input("order_sn");
        $order               =   Db::name('course_order')->where('order_sn', $order_sn)->find();
        //订单是否存在
        if(!empty($order)){
            if($order['state'] == 1){
                return datamsg(LOSE,'此订单，已完成支付!');
            }
        }

        $userInfo   =  $this->memberInfo('*', ['id'=>$this->user['user_id']]);
        $wallets = Db::name('wallet')->where('user_id',$this->user['user_id'])->find();
        $payMoney   =  $order['amount'];
        $money      =  $wallets['price'];
//        if( $payMoney > $money ){
//            return datamsg(LOSE,'余额不足');
//        }

        $res = $this->wallet($wallets, $order);

        echo json_encode($res);exit;
        //更改用户相关信息
//        $upUserInfo        =    [
//            'price'   =>   $money - $payMoney
//        ];

        // 启动事务
//        Db::startTrans();
//        try{
//            // Db::name('wallet')->where("user_id", $this->user['user_id'])->where('price', $money)->update($upUserInfo);
//
//            // 更改订单相关信息
//            $res = Db::name('course_order')->where('order_sn', $order_sn)->update([
//                'state' => 1,
//                'paytime' => time(),
//                'pay_code' => 'money',
//                'pay_name' => '余额支付'
//            ]);
//            if(!$res){
//                throw new \Exception('订单状态修改失败');
//            }
//
//            // 提交事务
//            Db::commit();
//            return datamsg(WIN, '支付成功');
//        } catch (\Exception $e) {
//            // 回滚事务
//            Db::rollback();
//
//            return datamsg(LOSE, $e->getMessage());
//        }
    }

    public function wallet($wallets, $orderinfos){
        // 暂时取消支付密码
        // $paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
        $paypwd = true;
        if($paypwd){
            $pay_password = input('post.pay_password');
            // if($pay_password && preg_match("/^\\d{6}$/", $pay_password)){
            if(true){
                // if($paypwd == md5($pay_password)){
                if(true){
                    if($wallets['price'] >= $orderinfos['amount']){
                        $sheng_price = $wallets['price']-$orderinfos['amount'];

                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('wallet')->where(['id'=>$wallets['id']])->update(array('price'=>$sheng_price));
                            $wallet_info = $wallets;
                            $detail = [
                                'de_type'=>2,
                                'zc_type'=>2,
                                'before_price'=> $wallet_info['price'],
                                'price'=>$orderinfos['amount'],
                                'after_price'=> $wallet_info['price']-$orderinfos['amount'],
                                'order_type'=>100,
                                'order_id'=>$orderinfos['order_id'],
                                'user_id'=>$wallets['user_id'],
                                'wat_id'=>$wallets['id'],
                                'time'=>time()
                            ];
                            $this->addDetail($detail);
//                            Db::name('detail')->insert($detail);

                            // 更改订单相关信息
                            $res = Db::name('course_order')->where('order_sn', $orderinfos['order_sn'])->update([
                                'state' => 1,
                                'paytime' => time(),
                                'pay_code' => 'money',
                                'pay_name' => '余额支付'
                            ]);
                            if(!$res){
                                throw new \Exception('订单状态修改失败');
                            }

                            // 提交事务
                            Db::commit();

                            $value = array('status'=>200,'mess'=>'支付成功');
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status'=>400,'mess'=>'钱包余额支付失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'钱包余额不足，支付失败','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                }
            }else{
                $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请先设置支付密码','data'=>array('status'=>400));
        }

        return $value;
    }

    public function myteacher(){
        // $num  = Db::name('course_teacher')->where('user_id', $this->user['user_id'])->count();
        $data = [];
        $teacher = Db::name('course_teacher')->where('user_id', $this->user['user_id'])->find();
        $teacher['imgurl'] = $this->webconfig['weburl'].$teacher['imgurl'];
        $data['data'] = $teacher;

        $courseids = [];
        $courses = Db::name('course_course')->where('teacher_id', $teacher['teacher_id'])->select();
        foreach ($courses as $key => $value) {
            array_push($courseids, $value['course_id']);
        }

        $start = date("Y-m-d",time()) . " 0:0:0";
        $stime = strtotime($start);
        $end = date("Y-m-d",time()) . " 24:00:00";
        $etime = strtotime($end);

        //收益
        if(count($courseids) > 0){
            $amount = Db::name('course_order')->where("course_id in (" . implode(',', $courseids) . ") and state = '1'")->sum('amount');
            $amount = empty($amount) ? 0 : $amount;
            $todayamount = Db::name('course_order')->where("course_id in (" . implode(',', $courseids) . ") and state = '1' and addtime >= '$stime' and addtime <= '$etime'")->sum('amount');
            $todayamount = empty($todayamount) ? 0 : $todayamount;
            $bestnum = Db::name('course_best')->where("post_id in (" . implode(',', $courseids) . ") and addtime >= '$stime' and addtime <= '$etime'")->count();
            $stownum = Db::name('course_stow')->where("post_id in (" . implode(',', $courseids) . ") and addtime >= '$stime' and addtime <= '$etime'")->count();
            $follownum = Db::name('course_fans')->where("fan_user_id = '" . $this->user['user_id'] . "' and addtime >= '$stime' and addtime <= '$etime'")->count();
        }else{
            $todayamount = 0;
            $amount = 0;
            $bestnum = 0;
            $stownum = 0;
            $follownum = 0;
        }
        $data['amount'] = $amount;
        $data['todayamount'] = $todayamount;
        $data['bestnum'] = $bestnum;
        $data['stownum'] = $stownum;
        $data['follownum'] = $follownum;
        $data['my'] = 1;
        return datamsg(WIN, '获取成功', $data);
    }

    public function category(){
        $list = Db::name('course_category')->select();
        return datamsg(WIN, '获取成功', $list);
    }

    public function savetag(){
        $input = input('post.');
        $num  = Db::name('course_tags')->where('user_id', $this->user['user_id'])->where('tag', $input['tag'])->count();
        if($num > 0){
            return datamsg(LOSE, '标签已存在');
        }else{
            $insertLastId = Db::name('course_tags')->insertGetId([
                'user_id' => $this->user['user_id'],
                'tag' => $input['tag']
            ]);

            $return = [];
            if($insertLastId){
                $return = [
                    'tag_id' => $insertLastId,
                    'tag' => $input['tag']
                ];
            }

            return datamsg(WIN, 'ok', $return);
        }
    }

    public function peopleTags(){
        $list = Db::name('course_tags')->where('user_id', $this->user['user_id'])->select();
//        foreach ($list as $k=>$v){
//            $list[$k]['selected'] = false;
//        }

        return datamsg(WIN, '获取成功', $list);
    }

    public function delPeopleTags(){
        $post = input('post.');
        $res = Db::name('course_tags')->where('user_id', $this->user['user_id'])->where('tag_id', $post['tag_id'])->delete();

        if($res){
            return datamsg(WIN, '删除成功');
        }else{
            return datamsg(LOSE, '删除失败');
        }
    }

    public function savecourse(){
        $input = input('post.');

        //图片文件夹判断
        $dirName = "public/uploads/school/course/" ;
        if(!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        if($input['isCover'] == 1){
            $file = request()->file('image');
            if($file){

                $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . $dirName);

                if($info){
                    $original = 'uploads/school/course/' .$info->getSaveName();

                    $image = \think\Image::open('./'.$original);
                    $image->thumb(300, 300)->save('./'.$original,null,90);
                    $imgurl = $original;
                }else{
                    // return datamsg(LOSE, $file->getError());
                }
            }
        }

        if($input['price'] < 0)return datamsg(LOSE, '金额必须大于等于0');
        $get_id = $input['course_id'];
        $data = [
            'title' => $input['title'],
            'catid' => $input['catid'],
            'description' => $input['description'],
            'content' => $input['content'],
            'tags' => $input['tags'],
            'price' => $input['price']
        ];
        if(!empty($imgurl))$data['imgurl'] = $imgurl;

        if($get_id > 0){
            $data['useable'] = 0;
            $r = Db::name('course_course')->where('course_id', $get_id)->update($data);
            if($r !== false){
                return datamsg(WIN, '编辑成功，请等待审核');
            }else{
                return datamsg(LOSE, '删除失败');
            }
        }else{
            $teacher_id = Db::name('course_teacher')->where('user_id', $this->user['user_id'])->value('teacher_id');
            $data['user_id'] = $this->user['user_id'];
            $data['teacher_id'] = $teacher_id;
            $data['useable'] = 0;
            $data['addtime'] = time();
            $course_id = Db::name('course_course')->insertGetId($data);
            if($course_id){
                return datamsg(WIN, '编辑成功，请等待审核');
            }else{
                return datamsg(LOSE, '编辑失败');
            }
        }
    }

    public function mycourse(){
        $user_id = $this->user['user_id'];
        $list = Db::name('course_course')->where('user_id', $user_id)->order('addtime desc')->select();
        foreach ($list as $key => $value) {
            $list[$key]['teacher'] = Db::name('course_teacher')->where('teacher_id', $value['teacher_id'])->value('title');
            $list[$key]['format_time'] = date('Y-m-d', $value['addtime']);
            $list[$key]['imgurl'] = $this->webconfig['weburl'].$value['imgurl'];
            $list[$key]['tags'] = trim($value['tags'], ',');
        }

        return datamsg(WIN, '获取成功', $list);
    }

    public function upcourse(){
        $list = [];
        $course_id = input('course_id/d', 0);
        if($course_id){
            $data = Db::name('course_course')->where('course_id', $course_id)->find();
            $data['imgurl'] = $this->webconfig['weburl'].$data['imgurl'];
            $data['content'] = $data['content'];
            $data['ori_tags'] = $data['tags'];
            !empty($data['tags']) && $data['tags'] = explode(',', $data['tags']);
            $data['catname'] = Db::name('course_category')->where('catid', $data['catid'])->value('catname');
        }
//        $tags = Db::name('course_tags')->where('tag', 'in', $data['tags'])->where('user_id', $this->user['user_id'])->select();
//        foreach ($tags as $k=>$v){
//            $tags[$k]['selected'] = true;
//        }
        $tags = Db::name('course_tags')->where('user_id', $this->user['user_id'])->select();
        foreach ($tags as $k=>$v){
            if(in_array($v['tag'], $data['tags'])){
                $tags[$k]['selected'] = true;
            }else{
                $tags[$k]['selected'] = false;
            }
        }
        $data['tags'] = $tags;

        return datamsg(WIN, '获取成功', $data);
    }

    public function getcategory(){
        $input = input('post.');
        $course_id = $input['course_id'];
        $pid = $input['pid'];
        if($input['kind'] == 0){
            $list = Db::name('course_video_category')->where("pid = '0' and course_id = '$course_id'")->select();
        }else{
            $list = Db::name('course_video_category')->where("pid = '$pid' and course_id = '$course_id'")->select();
        }

        return datamsg(WIN, '获取成功', $list);
    }

    //保存视频章节
    public function savecategory(){
        $input = input('post.');
        $course_id = $input['course_id'];
        $pid = $input['pid'];
        $catname = $input['catname'];

        $num = Db::name('course_video_category')->where('catname', $catname)->where('pid', $pid)->where('course_id', $course_id)->count();
        if($num > 0){
            return datamsg(LOSE, '该章节已存在');
        }else{
            $data = [
                'pid' => $pid,
                'course_id' => $course_id,
                'catname' => $catname
            ];

            $catid = Db::name('course_video_category')->insertGetId($data);
            $data['catid'] = $catid;
            if($catid){
                return datamsg(WIN, '添加成功', $data);
            }else{
                return datamsg(LOSE, '添加失败');
            }
        }
    }

    public function savevideo(){
        $input = input('post.');

        //图片文件夹判断
        $imgurl = '';
        $dirName = "public/uploads/school/video/" ;
        if(!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        if($input['isCover'] == 1){
            $file = request()->file('image');
            if($file){

                $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . $dirName);

                if($info){
                    $original = 'uploads/school/video/' .$info->getSaveName();

                    $image = \think\Image::open('./'.$original);
                    $image->thumb(300, 300)->save('./'.$original,null,90);
                    $imgurl = $original;
                }else{
                    // return datamsg(LOSE, $file->getError());
                }
            }
        }

        $get_id = $input['video_id'];

        //判断该课程该章节下是否已存在
        $hasvideo = Db::name('course_video')->where('user_id', $this->user['user_id'])->where('catid', $input['catid'])->where('course_id', $input['course_id'])->find();
        if(!isset($get_id) && !empty($hasvideo)){
            $get_id = $hasvideo['video_id'];
        }

        $data = [
            'pid' => $input['pid'],
            'catid' => $input['catid'],
            'goods_id' => $input['goods_id'],
            'displaytime' => $input['displaytime'],
            'alltime' => $input['alltime']
        ];
        if(!empty($input['videourl']))$data['videourl'] = str_replace($this->webconfig['weburl'], '', $input['videourl']);
        if(!empty($input['out_videourl']))$data['out_videourl'] = $input['out_videourl'];
        if(!empty($imgurl))$data['imgurl'] = '/'.$imgurl;

        $upload_video = Db::name('upload_video')->find();
        if($get_id > 0){
            $data['useable'] = 0;
            $r = Db::name('course_video')->where('video_id', $get_id)->update($data);
            if($r !== false){
                Db::name('upload_video')->where('id', $upload_video['id'])->delete();

                if(!empty($hasvideo['videourl'])){
                    if(is_file(ROOT_DIR.$hasvideo['videourl']))@unlink(ROOT_DIR.$hasvideo['videourl']);
                }

                return datamsg(WIN, '编辑成功，请等待审核', $data);
            }else{
                return datamsg(LOSE, '编辑失败');
            }
        }else{
            $teacher_id = Db::name('course_teacher')->where('user_id', $this->user['user_id'])->value('teacher_id');
            $data['user_id'] = $this->user['user_id'];
            $data['teacher_id'] = $teacher_id;
            $data['course_id'] = $input['course_id'];
            $data['useable'] = 0;
            $data['addtime'] = time();
            $video_id = Db::name('course_video')->insertGetId($data);
            if($video_id){
                Db::name('upload_video')->where('id', $upload_video['id'])->delete();
                Db::name('course_course')->where('course_id', $input['course_id'])->setInc('coursenum', 1);
                return datamsg(WIN, '新增成功，请等待审核');
            }else{
                return datamsg(LOSE, '新增失败');
            }
        }
    }

//    public function savevideo(){
//        $input = input('post.');
//
//        //图片文件夹判断
//        $imgurl = '';
//        $dirName = "public/uploads/school/video/" ;
//        if(!is_dir($dirName)) {
//            mkdir($dirName, 0777, true);
//        }
//        if($input['isCover'] == 1){
//            $file = request()->file('image');
//            if($file){
//
//                $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . $dirName);
//
//                if($info){
//                    $original = 'uploads/school/video/' .$info->getSaveName();
//
//                    $image = \think\Image::open('./'.$original);
//                    $image->thumb(300, 300)->save('./'.$original,null,90);
//                    $imgurl = $original;
//                }else{
//                    // return datamsg(LOSE, $file->getError());
//                }
//            }
//        }
//
//        $get_id = $input['video_id'];
//
//        //判断该课程该章节下是否已存在
//        $hasvideo = Db::name('course_video')->where('user_id', $this->user['user_id'])->where('catid', $input['catid'])->where('course_id', $input['course_id'])->find();
//        if(!isset($get_id) && !empty($hasvideo)){
//            $get_id = $hasvideo['video_id'];
//        }
//
//        $data = [
//            'pid' => $input['pid'],
//            'catid' => $input['catid'],
//            'goods_id' => $input['goods_id'],
//            'displaytime' => $input['displaytime'],
//            'alltime' => $input['alltime']
//        ];
//        if(!empty($input['videourl']))$data['videourl'] = str_replace($this->webconfig['weburl'], '/', $input['videourl']);
//        if(!empty($imgurl))$data['imgurl'] = '/'.$imgurl;
//
//        $upload_video = Db::name('upload_video')->find();
//        if($get_id > 0){
//            $data['useable'] = 0;
//            $r = Db::name('course_video')->where('video_id', $get_id)->update($data);
//            if($r !== false){
//                Db::name('upload_video')->where('id', $upload_video['id'])->delete();
//
//                if(!empty($hasvideo['videourl'])){
//                    if(is_file(ROOT_DIR.$hasvideo['videourl']))@unlink(ROOT_DIR.$hasvideo['videourl']);
//                }
//
//                return datamsg(WIN, '编辑成功，请等待审核', $data);
//            }else{
//                return datamsg(LOSE, '编辑失败');
//            }
//        }else{
//            $teacher_id = Db::name('course_teacher')->where('user_id', $this->user['user_id'])->value('teacher_id');
//            $data['user_id'] = $this->user['user_id'];
//            $data['teacher_id'] = $teacher_id;
//            $data['course_id'] = $input['course_id'];
//            $data['useable'] = 0;
//            $data['addtime'] = time();
//            $video_id = Db::name('course_video')->insertGetId($data);
//            if($video_id){
//                Db::name('upload_video')->where('id', $upload_video['id'])->delete();
//                Db::name('course_course')->where('course_id', $input['course_id'])->setInc('coursenum', 1);
//                return datamsg(WIN, '新增成功，请等待审核');
//            }else{
//                return datamsg(LOSE, '新增失败');
//            }
//        }
//    }

    public function upvideo(){
        $course_id = input('course_id/d', 0);
        $course = Db::name('course_course')->where('course_id', $course_id)->find();
        if(empty($course)){
            return datamsg(LOSE, '课程不存在');
        }

        if($course['user_id'] != $this->user['user_id']){
            return datamsg(LOSE, '不能操作');
        }

        $video_id = input('video_id/d', 0);
        if($video_id){
            $data = Db::name('course_video')->where('video_id', $video_id)->find();

            $data['videourl']  = (strpos($data['videourl'], 'http') === false && !empty($data['videourl'])) ? $this->webconfig['weburl'].$data['videourl'] : (!empty($data['videourl']) ? $data['videourl'] : $data['out_videourl']);
            $data['pname'] = Db::name('course_video_category')->where('catid', $data['pid'])->value('catname');
            $data['catname'] = Db::name('course_video_category')->where('catid', $data['catid'])->value('catname');
            if(!empty($data['imgurl']))$data['imgurl'] = $this->webconfig['weburl'].$data['imgurl'];
            $d['course_video'] = $data;
        }
        $d['course_id'] = $course_id;

        return datamsg(WIN, '获取成功', $d);
    }

    public function course_video(){
        $course_id = input('course_id/d', 0);
        $course = Db::name('course_course')->where('course_id', $course_id)->find();
        if(empty($course)){
            return datamsg(LOSE, '课程不存在');
        }

        if($course['user_id'] != $this->user['user_id']){
            return datamsg(LOSE, '不能操作');
        }

        $list = Db::name('course_video')->where('course_id', $course_id)->select();
        foreach ($list as $key => $value) {
            $list[$key]['pname'] = Db::name('course_video_category')->where('catid', $value['pid'])->value('catname');
            $list[$key]['catname'] = Db::name('course_video_category')->where('catid', $value['catid'])->value('catname');
            if(!empty($list[$key]['imgurl']))$list[$key]['imgurl'] = $this->webconfig['weburl'].$list[$key]['imgurl'];
        }

        $data['list'] = $list;
        $data['course_id'] = $course_id;
        return datamsg(WIN, '获取成功', $data);
    }

    public function ajax_flow_list(){
        $page = input('page/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);
        $teacher_id = input('teacher_id/d', 0);
        $user_id = Db::name('course_teacher')->where('teacher_id', $teacher_id)->value('user_id');
        $kind = input('kind');
        if($kind == 'fans'){
            $where = "fan_user_id = '$user_id'";
        }else{
            $where = "user_id = '$user_id'";
        }

        $count = Db::name('course_fans')->where($where)->count();
        $list = Db::name('course_fans')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();
        foreach ($list as $key => $value) {
            if($kind == 'fans'){
                $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);
            }else{
                $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['fan_user_id']]);
            }
            $list[$key]['user'] = $comm_user;

            $list[$key]['isself'] = 0;
            if($user_id == $this->user['user_id']){
                $list[$key]['isself'] = 1;
            }
        }
        $arr['list'] = $list;
        $arr['kind'] = $kind;
        $arr['pages'] = @ceil($count / $pagesize);

        return datamsg(WIN, '获取成功', $arr);
    }

    public function loadAllChatList(){
        $page = input('page/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);
        $user_id = $this->user['user_id'];

        // 查询所有发送给我的
        $list1 = Db::name('school_message')->field('*, sum(case isview when 0 then 1 else 0 end) unread_count')->where('to_user_id', $user_id)->order('addtime desc')->group('user_id')->select();
        // 查询我发送给所有人的
        $list2 = Db::name('school_message')->where('user_id', $user_id)->order('addtime desc')->group('to_user_id')->select();
        foreach ($list1 as $k=>$v){
            foreach ($list2 as $k1=>$v1){
                if($v['user_id'] == $v1['to_user_id']){
                    if($v['addtime'] > $v1['addtime']){
                        unset($list2[$k1]);
                    }else{
                        $list1[$k] = $list2[$k1];
                        unset($list2[$k1]);
                    }
                }
            }
        }
        // 合并聊天
        $merge_list = array_merge($list1, $list2);

        foreach ($merge_list as $k=>$v){
            $merge_list[$k]['addtime'] = (new timeFormat($v['addtime']))->calculateTime()->getTime();
            if($v['user_id'] != $user_id){
                $merge_list[$k]['other_party'] = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$v['user_id']]);
            }else{
                $merge_list[$k]['other_party'] = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$v['to_user_id']]);
            }
        }
        $arr['list'] = $merge_list;
        // $arr['pages'] = @ceil($count / $pagesize);

        // 系统通知未读数
        $sys_unread_count = Db::name('community_message')->where('to_user_id', $user_id)->where('isview', 0)->count();
        $arr['sys_unread_count'] = $sys_unread_count;

        return datamsg(WIN, '获取成功', $arr);
    }
}