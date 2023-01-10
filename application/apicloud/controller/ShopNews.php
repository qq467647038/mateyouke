<?php
namespace app\apicloud\controller;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;
use Qcloud\Cos\Client;
use app\util\timeFormat;
use app\util\JSSDK;

class ShopNews extends Common {
    public $user;
    public function test(){
        var_dump($_FILES);

    }

    public function shareH5(){
        $url = urldecode($_POST["url"]);
        $wx_config = Db::name('wx_config')->find();
        $appid = $wx_config['appid'];
        $appsecret = $wx_config['appsecret'];
        $jssdk = new JSSDK($appid, $appsecret, $url);
        $signPackage = $jssdk->GetSignPackage();
        $appId = $signPackage['appId'];
        $timestamp = $signPackage['timestamp'];
        $nonceStr = $signPackage['nonceStr'];
        $signature = $signPackage['signature'];
        $surl = $signPackage['url'];
        $rawString = $signPackage['rawString'];
        $data = array("appId"=>$appId,"timestamp"=>$timestamp,"nonceStr"=>$nonceStr,"signature"=>$signature,"surl"=>$surl, 'rawString'=>$rawString); //返回给前端的数据
        //var_dump($trans_data);

        return datamsg(WIN, '获取成功1', $data);
    }

    // 验证用户登录
    public function _initialize(){
        parent::_initialize();

        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();
        if($result['status'] == 200){
            if($result['user_id']){
                $this->user = $result;
            }
        }
    }

    // 商户新闻 - 订阅
    public function subscribed(){
        $page = input('param.page') ? input('param.page') : 1;
        $size = input('param.size') ?  input('param.size') : 5;
        $art_id_arr = Db::name('art_subscribe')->where(['user_id'=>$this->user['user_id'], 'kind'=>2])->column('art_id');

        $list = Db::name('shop_news')->field('ar_title, author, onclick, ar_pic, addtime, id')->order('id desc')->where(['useable'=>1, 'id'=>['in', $art_id_arr]])->paginate($size)->each(function ($item){
            $item['addtime'] = (new timeFormat($item['addtime']))->calculateTime()->getTime();
            $item['ar_pic'] = $this->webconfig['weburl'].str_replace('\\', '/', $item['ar_pic']);

            return $item;
        });

        return datamsg(WIN, '获取成功', $list);
    }

    public function postfeed(){
        $this->verifyLogin();
        $input = input('post.');
        $data = [
            'user_id' => $this->user['user_id'],
            'shop_news_id' => $input['shop_news_id'],
            'pid' => $input['pid'],
            'content' => $input['content'],
            'useable' => 1,
            'addtime' => time()
        ];

        $feed_id = Db::name('shop_news_feed')->insertGetId($data);
        if($feed_id){
            Db::name('shop_news')->where('id', $input['shop_news_id'])->setInc('feednum', 1);
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

    public function poststow(){
        $this->verifyLogin();
        $input = input('post.');
//        $num = Db::name('shop_news_stow')->where('user_id', $this->user['user_id'])->where('shop_news_id', $input['post_id'])->count();
//        if($num > 0){
//            return datamsg(LOSE,'已收藏');
//        }
        $info = Db::name('shop_news_stow')->where('user_id', $this->user['user_id'])->where('shop_news_id', $input['post_id'])->find();
        $text = $info['stow'] == 1 ? '取消' : '收藏';

        if(is_null($info)){
            $data = [
                'user_id' => $this->user['user_id'],
                'shop_news_id' => $input['post_id'],
                'addtime' => time()
            ];

            $r = Db::name('shop_news_stow')->insert($data);
            if($r !== false){
                Db::name('shop_news')->where('id', $input['post_id'])->setInc('stownum', 1);

                return datamsg(WIN,'收藏成功',array('count'=>0));
            }else{
                return datamsg(LOSE,'收藏失败');
            }
        }else{
            $update['stow'] = 0;
            if($info['stow'] == 0)
                $update['stow'] = 1;

            $res = Db::name('shop_news_stow')->where('user_id', $this->user['user_id'])->where('shop_news_id', $input['post_id'])->update($update);
            if(!$res)return datamsg(LOSE, $text.'失败');

            if($text == '取消'){
                $res = Db::name('shop_news')->where('id', $input['post_id'])->setDec('stownum', 1);
                if(!$res)return datamsg(LOSE, $text.'失败');
            }else{
                $res = Db::name('shop_news')->where('id', $input['post_id'])->setInc('stownum', 1);
                if(!$res)return datamsg(LOSE, $text.'失败');
            }

            return datamsg(WIN, $text.'成功');
        }

    }

    // 商户新闻或评论赞
    public function praise($id, $kind){
        $this->verifyLogin();

        // 给文章点赞
//        $count = Db::name('shop_news_feed_to_user')->where(['shop_news_id'=>$id, 'kind'=>$kind, 'user_id'=>$this->user['user_id']])->count();
//
//        if($count > 0 )return datamsg(LOSE, '已点赞');
        $info = Db::name('shop_news_feed_to_user')->where(['shop_news_id'=>$id, 'kind'=>$kind, 'user_id'=>$this->user['user_id']])->find();
        $text = $info['praise'] == 1 ? '取消' : '点赞';

        if(is_null($info)){
            $data['user_id'] = $this->user['user_id'];
            $data['shop_news_id'] = $id;
            $data['kind'] = $kind;
            $data['addtime'] = time();
            $res = Db::name('shop_news_feed_to_user')->insert($data);
            if(!$res)return datamsg(LOSE, '点赞失败');
        }else{
            $update['praise'] = 0;
            if($info['praise'] == 0)
                $update['praise'] = 1;

            $res = Db::name('shop_news_feed_to_user')->where(['shop_news_id'=>$id, 'kind'=>$kind, 'user_id'=>$this->user['user_id']])->update($update);
            if(!$res)return datamsg(LOSE, $text.'失败');
        }

        if($kind == 1){
            if($text == '取消'){
                $res = Db::name('shop_news')->where('id', $id)->setDec('bestnum', 1);
            }else{
                $res = Db::name('shop_news')->where('id', $id)->setInc('bestnum', 1);
            }

        }elseif($kind == 2){
            if($text == '取消'){
                $res = Db::name('shop_news_feed')->where('id', $id)->setDec('bestnum', 1);
            }else{
                $res = Db::name('shop_news_feed')->where('id', $id)->setInc('bestnum', 1);
            }
        }
        if(!$res)return datamsg(LOSE, $text.'失败');
            return datamsg(WIN, $text.'成功');
    }

    // 加载商户文章评论
    public function loadDiscuss($detail_id = 0, $return = false){
        $page = input('param.page') ? input('param.page') : 1;
        $pagesize = input('param.size') ?  input('param.size') : 3;
        $limit = $pagesize * ($page - 1);

        $count = Db::name('shop_news_feed')->where(['shop_news_id'=>$detail_id, 'useable'=>1])->count();
        $list = Db::name('shop_news_feed')->where(['shop_news_id'=>$detail_id, 'useable'=>1, 'pid'=>0])->order('addtime desc')->limit($limit, $pagesize)->select();

        foreach ($list as $key => $item) {
            $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$item['user_id']]);

            $list[$key]['user'] = $comm_user;
            $list[$key]['addtime'] = (new timeFormat($item['addtime']))->calculateTime()->getTime();

            // 获取当前用户是否对评论的赞
            if(isset($this->user['user_id'])){
                $praise = Db::name('shop_news_feed_to_user')->where(['user_id'=>$this->user['user_id'], 'shop_news_id'=>$item['id'], 'kind'=>2])->value('praise');
                $list[$key]['isbest'] = $praise ?: 0;
            }else{
                $list[$key]['isbest'] = 0;
            }

            // 子评论
            $childs = Db::name('shop_news_feed')->where("pid = '$item[id]' and useable = '1'")->order('addtime desc')->select();
            foreach ($childs as $k => $v) {
                $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$v['user_id']]);
                $childs[$k]['user'] = $comm_user;
            }
            $list[$key]['childs'] = $childs;
        }

        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);

        if($return === true){
            return $arr;
        }
        return datamsg(WIN, '获取成功', $arr);
    }

    // 加载商户文章详情
    public function loadDesc($detail_id = 0){
        if($detail_id <= 0)return datamsg(LOSE, '参数异常');

        $info = Db::name('shop_news')
            ->where(['useable'=>1, 'id'=>$detail_id])
            ->field('id, ar_title, author, addtime, ar_pic, bestnum, cate_id, ar_content, onclick, description')
            ->find();

        if(is_null($info))return datamsg(LOSE, '文章不存在或已删除');
        $info['addtime'] = (new timeFormat($info['addtime']))->calculateTime()->getTime();
        $info['ar_pic'] = $this->webconfig['weburl'].str_replace('\\', '/', $info['ar_pic']);
        $res = img_add_protocal($info['ar_content'], $this->webconfig['weburl']);
        $info['album'] = $res[1];
        $info['ar_content'] = $res[0];
        if(empty($info['description']))$info['description'] = $info['ar_title'];

        // 获取当前用户是否对文章的赞
        if(isset($this->user['user_id'])){
            $praise = Db::name('shop_news_feed_to_user')->where(['user_id'=>$this->user['user_id'], 'shop_news_id'=>$info['id'], 'kind'=>1])->value('praise');
            $info['isbest'] = $praise;
        }else{
            $info['isbest'] = 0;
        }

        // 获取当前用户是否对文章订阅
        if(isset($this->user['user_id'])){
            $info['subscribe'] = Db::name('art_subscribe')->where('user_id', $this->user['user_id'])->where('art_id', $info['id'])->count();
        }else{
            $info['subscribe'] = 0;
        }

        // 获取当前用户是否对文章的收藏
        if(isset($this->user['user_id'])){
            $isstow = Db::name('shop_news_stow')->where('user_id', $this->user['user_id'])->where('shop_news_id', $info['id'])->value('stow');
            $info['isstow'] = $isstow;
        }else{
            $info['isstow'] = 0;
        }

        // 对当前文章添加浏览量
        Db::name('shop_news') ->where(['useable'=>1, 'id'=>$detail_id])->setInc('onclick', 1);

        // 相关推荐
        $max_id = Db::name('shop_news')->where(['useable'=>1, 'cate_id'=>$info['cate_id']])->max('id');
        $i = 0;
        $relate_arr = [];
        $not_id = [$detail_id];
        while (++$i < 3){
            $rand_num = random_int(0, $max_id);
            // if(isset($relate_arr[0]['id']))$not_id = $relate_arr[0]['id'];
            $res = Db::name('shop_news')->field('id, ar_title, author, addtime, ar_pic, bestnum, cate_id, ar_content, onclick')->where(['useable'=>1, 'cate_id'=>$info['cate_id'], 'id'=>['egt', $rand_num], 'id'=>['not in', $not_id]])->find();

            if(!is_null($res)){
                $res['ar_pic'] = $this->webconfig['weburl'].str_replace('\\', '/', $res['ar_pic']);
                $res['addtime'] = (new timeFormat($res['addtime']))->calculateTime()->getTime();

                array_push($relate_arr, $res);
                array_push($not_id, $res['id']);
            }
        }
        $info['relate'] = $relate_arr;

        // $info['discuss'] = $this->loadDiscuss($detail_id, true);

        return datamsg(WIN, '获取成功', $info);
    }

    // 商户所有新闻
    public function all(){
        if(request()->isPost()){
            $list = [];
            // 课堂
            $list['shop_new_kc'] = $this->findDesc(25, 2);

            // 轮播
            $list['shop_new_lb'] = $this->findDesc(28, 4);

            // 直播
            $list['shop_new_zb'] = $this->findDesc(29, 3);
            foreach($list['shop_new_zb']['list'] as $k=>$v){
                $list['shop_new_zb']['list'][$k]['img_arr'] = [];

                // 正则
                $pattern = '/<img[^>]*src="([^"]*)"[^>]*>/i';
                preg_match_all($pattern, $v['ar_content'], $matches);

                foreach($matches[1] as $k1=>$v1){
                    if($k1>2)break;
                    if(strpos($v1, 'http') === false){
                        array_push($list['shop_new_zb']['list'][$k]['img_arr'], $this->webconfig['weburl'].$v1);
                    }else{
                        array_push($list['shop_new_zb']['list'][$k]['img_arr'], $v1);
                    }
                }
            }

            // return $list;
           return datamsg(WIN,'获取数据成功',$list);
        }else{
            return datamsg(LOSE,'请求方式不正确');
        }
    }

    // 单一的类型新闻
    public function singleTypeNew(){
        $post = input('post.');

        $size = 10;
//        switch ($post['pid']){
//            case 25:
//                $this->index($size);
//                break;
//            case 28:
//                $this->lunbo($size);
//                break;
//            case 29:
//                $this->zb($size);
//                break;
//        }
        $list = $this->findDesc($post['pid'], $size);
        foreach($list['list'] as $k=>$v){
            $list['list'][$k]['img_arr'] = [];

            // 正则
            $pattern = '/<img[^>]*src="([^"]*)"[^>]*>/i';
            preg_match_all($pattern, $v['ar_content'], $matches);

            foreach($matches[1] as $k1=>$v1){
                if($k1>2)break;
                if(strpos($v1, 'http') === false){
                    array_push($list['list'][$k]['img_arr'], $this->webconfig['weburl'].$v1);
                }else{
                    array_push($list['list'][$k]['img_arr'], $v1);
                }
            }
        }

        return datamsg(WIN, '获取成功', $list);
    }

    // 免费课堂-文章
    public function index($size = 2){
        if(request()->isPost()){
            $list = $this->findDesc(25, $size);

            return datamsg(WIN, '获取成功', $list);
        }else{
            return datamsg(LOSE,'请求方式不正确');
        }
    }

    // 轮播-文章
    public function lunbo($size = 4){
        if(request()->isPost()){
            $list = $this->findDesc(28, $size);

            return datamsg(WIN, '获取成功', $list);
        }else{
            return datamsg(LOSE,'请求方式不正确');
        }
    }

    // 直播-文章
    public function zb($size = 4){
        if(request()->isPost()){
            $list = $this->findDesc(29, $size);
            foreach($list['list'] as $k=>$v){
                $list['list'][$k]['img_arr'] = [];

                // 正则
                $pattern = '/<img[^>]*src="([^"]*)"[^>]*>/i';
                preg_match_all($pattern, $v['ar_content'], $matches);

                foreach($matches[1] as $k1=>$v1){
                    if($k1>2)break;
                    if(strpos($v1, 'http') === false){
                        array_push($list['list'][$k]['img_arr'], $this->webconfig['weburl'].$v1);
                    }else{
                        array_push($list['list'][$k]['img_arr'], $v1);
                    }
                }
            }

            return datamsg(WIN, '获取成功', $list);
        }else{
            return datamsg(LOSE,'请求方式不正确');
        }
    }

    // APP进货banner
    public function getIncomeBanner(){
        $banners = Db::name('ad')->where('pos_id', '25')->where('is_on', 1)->where('ad_type', 2)->select();
        foreach ($banners as $k=>$v){
            $ad_pic = Db::name('ad_pic')->where('ad_id', $v['id'])->field('pic, canshu')->find();
            if($ad_pic){
                $banners[$k]['ad_pic'] = $ad_pic['pic'];
                $banners[$k]['ad_link'] = $ad_pic['canshu'];
            }
        }
        
        
        $post = input('post.');
        $member = [];
        if($post['token']){
            $user_id = Db::name('rxin')->where('token', $post['token'])->value('user_id');
            $member = Db::name('member')->where('id', $user_id)->field('reg_enable,idcard,true_name')->find();
        }
        

        return datamsg(WIN,'获取数据成功',['banners'=>$banners, 'member'=>$member]);
    }

    // APP首页Banner
    public function banner(){
        $banners = Db::name('ad')->where('pos_id', '24')->where('is_on', 1)->where('ad_type', 2)->select();
        foreach ($banners as $k=>$v){
            $ad_pic = Db::name('ad_pic')->where('ad_id', $v['id'])->field('pic, canshu')->find();
            if($ad_pic){
                $banners[$k]['ad_pic'] = $ad_pic['pic'];
                $banners[$k]['ad_link'] = $ad_pic['canshu'];
            }
        }
        
        $homeFive = Db::name('home_five')->select();

        return datamsg(WIN,'获取数据成功',['banners'=>$banners, 'homeFive'=>$homeFive]);
    }

    public function findDesc($cate_id, $size = 5){
        $page = input('param.page') ? input('param.page') : 1;
        $size = input('param.size') ?  input('param.size') : $size;
        if(!is_numeric($size)){
            return datamsg(LOSE,'长度类型错误');
        }

        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate(0);
        if($result['status'] == 200){
            $list = Db::name('shop_news')->field('sn.*')->alias('sn')->where(['sn.useable'=>1, 'sn.cate_id'=>$cate_id, 'ca.useable'=>1])->join('cate_art ca', 'sn.cate_id=ca.id')->order('sn.addtime desc')->limit(($page-1)*$size, $size)->select();
            $count = Db::name('shop_news')->field('sn.*')->alias('sn')->where(['sn.useable'=>1, 'sn.cate_id'=>$cate_id, 'ca.useable'=>1])->join('cate_art ca', 'sn.cate_id=ca.id')->count();

            foreach($list as $k=>$v){
                $list[$k]['ar_pic'] = $this->webconfig['weburl'].str_replace('\\', '/', $v['ar_pic']);
                $list[$k]['addtime'] = (new timeFormat($v['addtime']))->calculateTime()->getTime();
            }

            $lists['list'] = $list;
            $lists['pages'] = @ceil($count / $size);
            return $lists;
            // return datamsg(WIN,'获取数据成功',$list);
        }else{
            return datamsg(LOSE,$result['mess']);
        }
    }

    // 订阅
    public function subscribe($detail_id = 0){
        $this->verifyLogin();
        if($detail_id <= 0)return datamsg(LOSE, '参数异常');
        if(!isset($this->user['user_id']) || $this->user['user_id'] <= 0)return datamsg(LOSE, '请先登陆');

        $info = Db::name('shop_news')->where(['useable'=>1, 'id'=>$detail_id])->find();
        if(is_null($info))return datamsg(LOSE, '文章不存在或已删除');

        $data['art_id'] = $detail_id;
        $data['user_id'] = $this->user['user_id'];
        $data['kind'] = 2;
        $data['addtime'] = time();
        $res = Db::name('art_subscribe')->where(['art_id'=>$detail_id, 'user_id'=>$this->user['user_id']])->count();
        if($res){
            // 已订阅
            $res = Db::name('art_subscribe')->where(['art_id'=>$detail_id, 'user_id'=>$this->user['user_id']])->delete();
            $text = '取消';
        }else{
            // 未订阅
            $res = Db::name('art_subscribe')->insert($data);
            $text = '成功';
        }

        if($res){
            return datamsg(WIN, '订阅'.$text);
        }else{
            return datamsg(LOSE, '操作失败');
        }
    }

    // 文章二维码分享
    public function shareImg(){
        $this->verifyLogin();
        $id = input('shop_news_id/d', 0);
        $width = input('width/d', 750);
        $height = input('height/d', 1200);
        $arr['imgurl'] = $this->getShareImage($id, $width, $height);

        return datamsg(WIN, '获取成功', $arr);
    }

    //生成分享图
    public function getShareImage($id, $width, $height){
        if(empty($id)) return '';
        $info  = Db::name('shop_news')->where('id', $id)->find();

        // $teacher = Db::name('course_teacher')->where('teacher_id', $course['teacher_id'])->value('title');
        if(preg_match('/http/', $info['ar_pic'])){
            $imgurl = $info['ar_pic'];
        }else{
            $imgurl = $_SERVER["DOCUMENT_ROOT"] .'/'. $info['ar_pic'];
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

        //画封面图片
        $infoImg          = $this->createImageFromFile($imgurl);
        $imgw = $canvas_width;
        $per = round($width / $g_w, 3);
        $n_h = $gs_h * $per;
        imagecopyresampled($im, $infoImg, 0, 0, 0, 0, $imgw, $n_h, $g_w, $gs_h);

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
        imagecopyresampled($im, $logoimg, 20, 20, 0, 0, $g_l_w, $gs_l_h, $g_l_w, $gs_l_h);

        //获取用户头像
        $user_info      = $this->memberInfo('*', ['id'=>$this->user['user_id']]);

        if(empty($user_info["headimgurl"])){
            $head_pic = $_SERVER["DOCUMENT_ROOT"] . '/template/mobile/new2/static/images/user68.jpg';
        }else{
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $user_info["headimgurl"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
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

        imagecopyresampled($im, $img, 140 , $cus_height - 90, 0, 0, 50, 50, $w, $h);
        if($head_pic && !empty($user_info["headimgurl"])){
            unlink($head_pic);
        }
        //描述
        //imagettftext($im, 14,0, 60, $cus_height + 70, $font_color_2 ,$font_file, $user_info["nickname"]);

        imagettftext($im, 14, 0, 10, $cus_height+10, $font_color_2 ,$font_file, $this->mg_cn_substr($info["ar_title"], 45) . '...');
        // imagettftext($im, 14, 0, 10, $cus_height + 90, $font_color_2 ,$font_file, $this->mg_cn_substr($info["ar_title"], 30) . '...');

        imagettftext($im, 12, 0, 10, $cus_height + 80, $font_color_3 ,$font_file, $this->mg_cn_substr($user_info["user_name"], 10) . ' 邀请您一起来学习');

        imagettftext($im, 14, 0, 10, $cus_height + 130, $font_color_3 ,$font_file, '来自 [ 孝笑学堂 ]');
        //imagettftext($im, 14,0, 60, 420, $font_color_2 ,$font_file, $teacher);

        //二维码
        vendor('phpqrcode.phpqrcode');
        $value = "/Portal/Article-details/Article-details/id=" . $info["info"] . "/user_id/" . $user_info["id"];

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
        imagedestroy($infoImg);
        imagedestroy($QR);
        return $image_data_base64;
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

    public function getOfficialArtList(){
        $page = input('page/d', 1);
        $kind = input('kind/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);

        if($kind == 1 || $kind == 2){
            $where = "useable = '1'";
            $count = Db::name('shop_news')->where($where)->count();
            $list = Db::name('shop_news')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();

            foreach ($list as $key => $value) {
                $list[$key]['addtime'] = (new timeFormat($value['addtime']))->calculateTime()->getTime();
                $list[$key]['imgurl'] = $this->webconfig['weburl'].$value['ar_pic'];

                // 文本内容
                $pattern='/<img\s+src=[\\\'|bai \\\"](.*?(?:[\.gif|\.jpg]))[\\\'|\\\"].*?[\/]?>/';
                $list[$key]['content'] = $value['title'] ? $value['title'] : cut_str(strip_tags(preg_replace($pattern,'', $value['ar_content'])), 160);

                // 文本图片
//                $list[$key]['img_arr'] = [];
//                // 正则
//                $pattern = '/<img[^>]*src="([^"]*)"[^>]*>/i';
//                preg_match_all($pattern, $value['content'], $matches);
//                foreach($matches[1] as $k1=>$v1){
//                    array_push($list[$key]['img_arr'], $this->webconfig['weburl'].$v1);
//                }
//
//                $list[$key]['images'] = explode(',', $value['imgurl']);
//                $list[$key]['format_time'] = date('Y-m-d H:i:s', $value['addtime']);
//                $comm_title = Db::name('community_list')->where('comm_id', $value['comm_id'])->value('title');
//                $list[$key]['comm_title'] = !empty($comm_title) ? $comm_title : '无';
//

                $praise = Db::name('shop_news_feed_to_user')->where('user_id', $this->user['user_id'])->where('shop_news_id', $value['id'])->where('kind', 1)->value('praise');
                $list[$key]['isbest'] = $praise ?: 0;
//                $list[$key]['title'] = empty($value['title']) ? '' : $value['title'];
//                $list[$key]['description'] = empty($value['description']) ? '' : $value['description'];

//                $list[$key]['feednum'] = Db::name('community_feed')->where('post_id', $value['article_id'])->where('kind', $value['kind'])->where('useable', 1)->count();
            }
        }else{

        }

        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
        return datamsg(WIN,'获取成功', $arr);
    }

    public function baseInfo(){
        $data['art_count'] = Db::name('shop_news')->where('useable', 1)->count();

        return datamsg(WIN,'获取成功', $data);
    }
}