<?php
namespace app\apicloud\controller;
use think\Db;
use think\Controller;
use app\common\util\WechatUtil;
use app\apicloud\model\Gongyong as GongyongMx;
use app\util\timeFormat;

class Community extends Common {
//    private $shield_keyword;
//
//    public function _initialize()
//    {
//        parent::_initialize();
//
//        $config = tpCache('basic');
//        $this->shield_keyword = $config['shield_keywords'];
//    }
//    public function test(){
//        $res = new timeFormat(1605633934);
//        $res->calculateTime();
//        var_dump($res->getTime());exit;
//    }

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

    public function ajax_article_list(){
        $page = input('page/d', 1);
        $comm_id = input('comm_id/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);

        $where = "useable = '1' and comm_id = '$comm_id' and (isopen = '1' or user_id = '" . $this->user['user_id'] . "')";
        $count = Db::name('community_article')->where($where)->count();
        $list = Db::name('community_article')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();
        foreach ($list as $key => $value) {
            $img_arr = [];
            $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);

            $list[$key]['user'] = $comm_user;

            // 正则
            $pattern = '/<img[^>]*src="([^"]*)"[^>]*>/i';
            preg_match_all($pattern, $value['content'], $matches);


            foreach($matches[1] as $k1=>$v1){
                if($k1>2)break;

                array_push($img_arr, $this->webconfig['weburl'].$v1);
            }
            $list[$key]['images'] = $img_arr;
            $list[$key]['format_time'] = (new timeFormat($value['addtime']))->calculateTime()->getTime();
            $isfollow = Db::name('community_fans')->where('user_id', $this->user['user_id'])->where('fan_user_id', $value['user_id'])->count();
            $list[$key]['isfollow'] = $isfollow;
            $list[$key]['isself'] = $value['user_id'] == $this->user['user_id'] ? 1 : 0;

            $praise = Db::name('community_best')->where('user_id', $this->user['user_id'])->where('post_id', $value['article_id'])->where('kind', $value['kind'])->value('praise');
            $list[$key]['isbest'] = $praise ?: 0;

            $list[$key]['feednum'] = Db::name('community_feed')->where('post_id', $value['article_id'])->where('kind', $value['kind'])->where('useable', 1)->count();
        }
        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
        return datamsg(WIN, '获取成功', $arr);
    }

    // 社群详细
    public function community_detail(){
        $id = input('id/d', 1);
        $data = Db::name('community_list')->where('comm_id', $id)->find();
        if(empty($data['imgurl']))$data['imgurl'] = 'https://cxy365-file.obs.cn-south-1.myhuaweicloud.com/static/Portal/community/%E5%BE%AE%E4%BF%A1%E5%9B%BE%E7%89%87_20201116101745.png';
        if(empty($data)){
            return datamsg(LOSE,'社群不存在');
        }
        if($data['useable'] == 0){
            return datamsg(LOSE,'社群审核中');
        }

        $comm_user = $this->memberInfo('user_name nickname, headimgurl', $where = ['id'=>$data['user_id']]);

        $data['user'] = $comm_user;
        $commUser = Db::name('community_user')->where('user_id', $this->user['user_id'])->where('comm_id', $data['comm_id'])->find();
        $data['cuser'] = $commUser;
        if($data['user_id'] == $this->user['user_id']){
            $data['ismy'] = 1;
        }
        $data['imgurl'] = $this->webconfig['weburl'].$data['imgurl'];
        $list['data'] = $data;

        $list['user_id'] = $this->user['user_id'];

        $this->addLog($this->user['user_id'], $id, 4);
        return datamsg(WIN, '获取成功', $list);
    }

    // 最新社群文章
    public function latest($return = false)
    {
        $list = $this->art(1);
        if($return === true){
            return $list;
        }

        return datamsg(WIN, '获取成功', $list);
    }

    public function ajax_search_list(){
        $page = input('page/d', 1);
        $kind = input('kind/d', 1);
        $keyword = input('keyword');
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);

        if($kind == 1){
            $where = "useable = '1' and isopen = '1'";
            if($keyword){
                $where .= " and title like '%$keyword%'";
            }
            $count = Db::name('community_article')->where($where)->where('kind', 1)->count();
            $list = Db::name('community_article')->where($where)->where('kind', 1)->order('addtime desc')->limit($limit, $pagesize)->select();
            foreach ($list as $key => $value) {
                $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$this->user['user_id']]);
                $list[$key]['user'] = $comm_user;

                $list[$key]['imgurl'] = $this->webconfig['weburl'].$value['imgurl'];
            }
        }else{
            $where = "useable = '1'";
            if($keyword){
                $where .= " and title like '%$keyword%'";
            }
            $count = Db::name('community_list')->where($where)->count();
            $list = Db::name('community_list')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();
            foreach ($list as $key => $value) {
                $commUser = Db::name('community_user')->where('user_id', $this->user['user_id'])->where('comm_id', $value['comm_id'])->find();
                // echo Db::name('community_user')->getLastSql();exit;
                // $list[$key]['isjoin'] = is_null($commUser) ? 0 : 1;
                $list[$key]['cuser'] = $commUser;
                if($value['user_id'] == $this->user['user_id']){
                    $list[$key]['ismy'] = 1;
                }

                $list[$key]['imgurl'] = $this->webconfig['weburl'].$value['imgurl'];
            }
        }

        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
        return datamsg(WIN, '获取成功', $arr);
    }

    // 热门社群文章
    public function hot($return = false)
    {
        $list = $this->art(2);
        if($return === true){
            return $list;
        }

        return datamsg(WIN, '获取成功', $list);
    }

    // 取出查询的所有类型文章
    public function all()
    {
        $list['latest'] = $this->latest(true);

        $list['hot'] = $this->hot(true);

        return datamsg(WIN, '获取成功', $list);
    }

    // 个人中心
    public function my(){
        $this->verifyLogin();
        $list['my'] = 1;
        $list['user'] = $this->memberInfo('user_name nickname, headimgurl', ['id'=>$this->user['user_id']]);

        $mycomm = Db::name('community_list')->where('user_id', $this->user['user_id'])->find();
        $mycomm['imgurl'] = $this->webconfig['weburl'].$mycomm['imgurl'];
        $list['mycomm'] = $mycomm;

        //我加入的
        $joinlist = Db::name('community_user')->where('user_id', $this->user['user_id'])->where('ispass', 1)->select();
        foreach ($joinlist as $key => $value) {
            $comm = Db::name('community_list')->where('comm_id', $value['comm_id'])->find();

            if(!is_null($comm)){
                if($comm['user_id'] == $this->user['user_id']){
                    unset($joinlist[$key]);
                    continue;
                }

                $comm['imgurl'] = $this->webconfig['weburl'].$comm['imgurl'];
                $joinlist[$key]['comm'] = $comm;
            }else{
                unset($joinlist[$key]);
            }
        }

        sort($joinlist);
        $list['joinlist'] = $joinlist;
        return datamsg(WIN, '获取成功', $list);
    }

    // 社群申请列表
    public function myapply(){
        $this->verifyLogin();
        $list['my'] = 1;
        $list['user'] = $this->memberInfo('user_name nickname, headimgurl', ['id'=>$this->user['user_id']]);

        $mycomm = Db::name('community_list')->where('user_id', $this->user['user_id'])->find();
        $mycomm['imgurl'] = $this->webconfig['weburl'].$mycomm['imgurl'];
        $list['mycomm'] = $mycomm;

        $joinlist = Db::name('community_user')->where('user_id', 'neq', $mycomm['user_id'])->where('comm_id', $mycomm['comm_id'])->select();
        foreach ($joinlist as $key => $value) {
            $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);
            if(preg_match("/^1[3456789]\d{9}$/", $comm_user['nickname'])){
                $comm_user['nickname'] = '匿名';
            }
            $joinlist[$key]['user'] = $comm_user;
        }
        $list['joinlist'] = $joinlist;
        return datamsg(WIN, '获取成功', $list);
    }

    // 操作社群 - 同意|拒绝 加入
    public function setpassuser(){
        $this->verifyLogin();
        $input = input('post.');
        $comm = Db::name('community_list')->where('user_id', $this->user['user_id'])->find();

        $cuser = Db::name('community_user')->where('cuser_id', $input['cuser_id'])->find();

        $r = Db::name('community_user')->where('cuser_id', $input['cuser_id'])->where('comm_id', $comm['comm_id'])->update(['ispass' => $input['ispass']]);
        if($r){
            if($input['ispass'] == 1){
                Db::name('community_list')->where('comm_id', $comm['comm_id'])->setInc('user_num', 1);
                $this->sendMessage($this->user['user_id'], $cuser['user_id'], 'commpass', $comm['comm_id'], '恭喜你加入社群：' . $comm['title']);
            }else{
                $this->sendMessage($this->user['user_id'], $cuser['user_id'], 'commpass', $comm['comm_id'], '不同意您加入社群：' . $comm['title']);
            }

            return datamsg(WIN,'操作成功');
        }else{
            return datamsg(LOSE,'操作失败');
        }
    }

    // 文章列表查找
    public function art($type = 1)
    {
        $page = input('param.page') ? input('param.page') : 1;
        $size = input('param.size') ? input('param.size') : 8;

        $where = ['isopen' => 1, 'useable' => 1];
        $order = '';
        if($type == 1){
            // 最新
            $order = 'addtime desc';
        }
        elseif($type == 2){
            // 热门
            $order = 'bestnum desc';
        }

        return Db::name('community_article')->where($where)->order($order)->paginate($size)->each(function ($item){
            $item['nickname'] = Db::name('member')->where(['id'=>$item['user_id']])->value('user_name');
            $item['headimgurl'] = $this->memberInfo('headimgurl', ['id'=>$item['user_id']]);
            $item['addtime'] = (new timeFormat($item['addtime']))->calculateTime()->getTime();
            $item['img_arr'] = [];

            if($item['kind'] == 2){
                $item['title'] = $item['content'];
                $img_arr = array_filter(explode(',', $item['imglist']));

                foreach($img_arr as $k1=>$v1){
                    if($k1>2)break;

                    array_push($item['img_arr'], $this->webconfig['weburl'].$v1);
                }
            }elseif($item['kind'] == 1){
                // 正则
                $pattern = '/<img[^>]*src="([^"]*)"[^>]*>/i';
                preg_match_all($pattern, $item['content'], $matches);

                foreach($matches[1] as $k1=>$v1){
                    if($k1>2)break;

                    if(strpos($v1, $this->webconfig['weburl']) === false){
                        array_push($item['img_arr'], $this->webconfig['weburl'].$v1);
                    }else{
                        array_push($item['img_arr'], $v1);
                    }
                }
            }

            // 是否关注
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200) {
                if ($result['user_id']) {
                    $isfollow = Db::name('community_fans')->where('user_id', $result['user_id'])->where('fan_user_id', $item['user_id'])->count();
                    if($isfollow){
                        $item['followtxt'] = '已关注';
                        $item['isdel'] = true;
                    }else{
                        $item['followtxt'] = '关注';
                        $item['isdel'] = false;
                    }

                    $praise = Db::name('community_best')->where('user_id', $result['user_id'])->where('post_id', $item['article_id'])->where('kind', $item['kind'])->value('praise');
                    $item['isbest'] = $praise ?: 0;
//                    $info = Db::name('community_article_link_user')->where(['community_article_id'=>$item['article_id'], 'user_id'=>$result['user_id']])->field('praise, attention')->find();
//
//                    if($info){
//                        $item['attention'] = $info['attention'];
//                        $item['praise'] = $info['praise'];
//                    }else{
//                        $item['attention'] = 0;
//                        $item['praise'] = 0;
//                    }
                }
            }

            return $item;
        });
    }

    // 社群 - 发布文章页面
    public function create_article(){
        $kind = input('post.kind');

        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();
        if($result['status'] == 200){
            if($result['user_id']){
                $json = [];
                $protocal = $this->webconfig['weburl'];

                //获取我加入或者创建的社群
//                $commlist = Db::name('community_user')->where('user_id', $result['user_id'])->where('ispass', 1)->select();
//                foreach ($commlist as $key => $value) {
//                    $useable = Db::name('community_list')->where('comm_id', $value['comm_id'])->value('useable');
//                    if(!$useable){
//                        unset($commlist[$key]);
//                    }else{
//                        $title = Db::name('community_list')->where('comm_id', $value['comm_id'])->value('title');
//                        $commlist[$key]['title'] = $title;
//                    }
//                }
                $commlist = Db::name('community_user')->where('user_id', $result['user_id'])->where('ispass', 1)->paginate(100)->toArray();
                $commlist = $commlist['data'];
                foreach ($commlist as $key => $value) {
                    $useable = Db::name('community_list')->where('comm_id', $value['comm_id'])->value('useable');
                    if(!$useable){
                        unset($commlist[$key]);
                    }else{
                        $title = Db::name('community_list')->where('comm_id', $value['comm_id'])->value('title');
                        $commlist[$key]['title'] = $title;

                        $imgurl = Db::name('community_list')->where('comm_id', $value['comm_id'])->value('imgurl');
                        $commlist[$key]['imgurl'] = $this->webconfig['weburl'].$imgurl;
                    }
                }


                if(count($commlist) == 0 && $kind == 2){
                    return datamsg(LOSE,'请先加入社群',array('count'=>0));
                }

                $article_id = input('article_id/d', 0);
                if($article_id){
                    $data = Db::name('community_article')->where('article_id', $article_id)->find();
                    if(!empty($data['imglist'])){
                        $imglist_arr = explode(',', $data['imglist']);
                        $data['imglist'] = [];
                        foreach ($imglist_arr as $v){
                            array_push($data['imglist'], $this->webconfig['weburl'].$v);
                        }
                    }

                    $data['imgurl'] = $this->webconfig['weburl'].$data['imgurl'];

                    if($data['user_id'] != $result['user_id']){
                        return datamsg(LOSE,'你不能编辑',array('count'=>0));
                    }
                    $data['comm_title'] = Db::name('community_list')->where('comm_id', $data['comm_id'])->value('title');
                    $data['goods_name'] = $data['goods_id'] > 0 ? Db::name('goods')->where('id', $data['goods_id'])->value('goods_name') : '选择商品';
                    if($data['kind'] == 2){
                        $data['images'] = explode(',', $data['imgurl']);
                        $data['imagenum'] = count($data['images']);
                    }
                    // $data['content'] = str_replace('section', 'div', $data['content']);
//                    $data['content'] = img_add_protocal($data['content'], $this->webconfig['weburl']);
                    $res = img_add_protocal($data['content'], $this->webconfig['weburl']);
                    $data['album'] = $res[1];
                    $data['content'] = $res[0];

                    $kind = $data['kind'];
                }else{
                    $data['isopen'] = 1;
                    $data['comm_title'] = '@ 选择社群';
                    $data['goods_name'] = '选择商品';
                    $data['imagenum'] = 0;
                }
                $json['data'] = $data;

                //获取小铺商品
                $shop_id = Db::name('member')->where("id",$result['user_id'])->value('shop_id');
                $goods = $this->loadShopGoods($shop_id, $protocal, true);

                $json['goods'] = $goods;
                $json['kind'] = $kind;
                sort($commlist);
                $json['commlist'] = $commlist;
                $json['title'] = $kind == 1 ? '发布文章' : '发布动态';

                return datamsg(WIN,'获取成功', $json);
            }else{
                return datamsg(LOSE,'未登录',array('count'=>0));
            }
        }else{
            return datamsg(LOSE,$result['mess'],array('count'=>0));
        }
    }

    public function loadShopGoods($shop_id='', $protocal='', $return = false){
        $page = input('param.page') ? input('param.page') : 1;
        $size = input('param.size') ?  input('param.size') : 5;
        $where = [];

        if(!empty(input('param.searchInputTxt')))$where['goods_name'] = ['like', '%'.input('param.searchInputTxt').'%'];
        if(!$shop_id){$shop_id = Db::name('member')->where("id",$this->user['user_id'])->value('shop_id');}
        if(!$protocal)$protocal = $this->webconfig['weburl'];

        $list = Db::name("goods")->field('id,goods_name,thumb_url,zs_price')->where($where)->where('shop_id', $shop_id)->where("onsale = '1' and checked = '1' and is_recycle = '0'")->paginate($size)->each(function ($item)use($protocal){

            $item['thumb_url'] = $this->webconfig['weburl'].$item['thumb_url'];

            return $item;
        });

        if($return === true){
            return $list;
        }else{
            return datamsg(WIN, '获取成功', $list);
        }

    }

    private function shieldReplcae($content){
        $shield_keyword = getConfig()['shield_keywords'];
        if(empty($shield_keyword)){
            return $content;
        }
        $pattern = "/". $shield_keyword . "/i";
        $string = $content;
        if(preg_match_all($pattern, $content, $matches)){ //匹配到了结果
            $patternList = $matches[0]; //匹配到的数组
            //$count = count($patternList);
            $sensitiveWord = implode(',', $patternList); //敏感词数组转字符串
            $replaceArray = array_combine($patternList, array_fill(0,count($patternList),'*')); //把匹配到的数组进行合并，替换使用
            $string = strtr($content, $replaceArray); //结果替换
        }

        return $string;
    }

    public function delImg(){
        $imgurl = input('post.imgurl');
        if(!empty($imgurl)){
            $imgurl = str_replace($this->webconfig['weburl'], '', $imgurl);
            if(is_file(ROOT_DIR.'/'.$imgurl))@unlink(ROOT_DIR.'/'.$imgurl);

            return datamsg(WIN, '删除成功');
        }else{
            return datamsg(LOSE, '删除失败');
        }
    }

    // 社群 - 动态多图删除
    public function delDynamicImg(){
        $post = input('post.');
        $post['imglist'] = str_replace($this->webconfig['weburl'], '', $post['imglist']);

        if($post['article_id'] > 0){
            $info = Db::name('community_article')->where('article_id', $post['article_id'])->where('user_id', $this->user['user_id'])->find();

            if(!empty($info['imglist'])){
                $yyImgArr = explode(',', $info['imglist']);

                foreach ($post['imglist'] as $v){
                    $v = trim($v, '/');

                    if(!in_array($v, $yyImgArr)){
                        if(is_file(ROOT_DIR.'/'.$v))unlink(ROOT_DIR.'/'.$v);
                    }
                }

                // 删除图片成功
                return datamsg(WIN, '删除图片成功');
            }
        }

        // 删除多图
        if(!empty($post['imglist'])){
            foreach ($post['imglist'] as $v){
                $v = trim($v, '/');

                if(is_file(ROOT_DIR.'/'.$v))unlink(ROOT_DIR.'/'.$v);
            }
        }

        // 删除图片成功
        return datamsg(WIN, '删除图片成功');
    }

    // 社群 - 保存文章
    public function save_article(){
        if(!isset($this->user['user_id']) || $this->user['user_id'] <= 0)return datamsg(LOSE, '请先登录');
        $input = input('post.');
        $input['imgPath'] = json_decode($input['imgPath'], true);
//        $input['content'] = str_replace($this->webconfig['weburl'], '', $input['content']);

        //1、取整个图片代码
        for($i=0; $i<count($input['imgPath']); $i++){
            if(strpos($input['imgPath'][$i][0], 'data:image') !== false){
//                $explode_val = explode(',', $input['imgPath'][$i][0]);
//                $fileUrl = "/uploads/editor/" . date('Y') . '/' . date('m-d') . '/' . uniqid().rand(1, 10000).'.'.$input['imgPath'][$i][1];
//                file_put_contents(ROOT_DIR.$fileUrl, $explode_val[1]);
                $input['content'] = str_replace($input['imgPath'][$i][0], $input['imgPath'][$i][1], $input['content']);
            }
        }

        $get_id = $input['article_id'];
        if($get_id > 0){
            $data = [
                'comm_id' => $input['comm_id'],
                'title' => $input['title'],
                // 'kind' => $input['kind'],
                'description' => $this->shieldReplcae($input['description']),
                'content' => $this->shieldReplcae($input['content']),
                'goods_id' => $input['goods_id'],
                'isopen' => $input['isopen']
            ];
            if(!empty($input['imglist'])){
                if($input['kind'] == 2){
                    $input['imglist'] = str_replace($this->webconfig['weburl'], '', $input['imglist']);
                    $data['imglist'] = implode(',', $input['imglist']);
                }
            }else{
                $data['imglist'] = '';
            }

            //图片文件夹判断
            $dirName = "public/uploads/community/" . date('Y') . '/' . date('m-d');
            if(!is_dir($dirName)) {
                mkdir($dirName, 0777, true);
            }

            $r = Db::name('community_article')->where('article_id', $get_id)->where('user_id', $this->user['user_id'])->update($data);
            if($r !== false){
                $file = request()->file('image');
                if($file){

                    $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . $dirName);
                    if($info){
                        $original = 'uploads/community/'. date('Y') . '/' . date('m-d').'/'.$info->getSaveName();

                        $image = \think\Image::open('./'.$original);
                        $image->thumb(300, 300)->save('./'.$original,null,90);
                        $imgurl = $original;
                    }else{
                        return datamsg(LOSE, $file->getError());
                    }

                    $res = Db::name('community_article')->where('article_id', $get_id)->update(['imgurl' => $imgurl]);
                    if(!$res && file_exists('./'.$imgurl)){
                        @unlink('./'.$imgurl);
                    }
                }

                return datamsg(WIN,'修改成功',array('article_id'=>$get_id));
            }else{
                return datamsg(LOSE,'修改失败');
            }
        }else{
            $data = [
                'user_id' => $this->user['user_id'],
                'comm_id' => $input['comm_id'],
                'title' => $input['title'],
                'kind' => $input['kind'],
                'description' => $this->shieldReplcae($input['description']),
                'content' => $this->shieldReplcae($input['content']),
                'goods_id' => $input['goods_id'],
                'isopen' => $input['isopen'],
                'useable' => 1,
                'addtime' => time()
            ];
            if(!empty($input['imglist'])){
                if($input['kind'] == 2){
                    $input['imglist'] = str_replace($this->webconfig['weburl'], '', $input['imglist']);
                    $data['imglist'] = implode(',', $input['imglist']);
                }
            }

            //图片文件夹判断
            $dirName = "public/uploads/community/" . date('Y') . '/' . date('m-d');
            if(!is_dir($dirName)) {
                mkdir($dirName, 0777, true);
            }

            $article_id = Db::name('community_article')->insertGetId($data);
            if($article_id){
                $file = request()->file('image');
                if($file){
                    $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . $dirName);

                    if($info){
                        $original = 'uploads/community/'. date('Y') . '/' . date('m-d').'/'.$info->getSaveName();

                        $image = \think\Image::open('./'.$original);
                        $image->thumb(300, 300)->save('./'.$original,null,90);
                        $imgurl = $original;
                    }else{
                        return datamsg(LOSE, $file->getError());
                    }

                    $res = Db::name('community_article')->where('article_id', $article_id)->update(['imgurl' => $imgurl]);
                    if(!$res && file_exists('./'.$imgurl)){
                        @unlink('./'.$imgurl);
                    }
                }

                return datamsg(WIN,'发布成功',array('article_id'=>$article_id));
            }else{
                return datamsg(LOSE,'发布失败');
            }
        }
    }

    // 文章关注
    public function setAttention($article_id){
        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();
        if($result['status'] == 200){
            if($result['user_id']){
                $info = Db::name('community_article')->where(['article_id'=>$article_id, 'isopen'=>1, 'useable'=>1])->find();

                if($info){
                    $link = Db::name('community_article_link_user')->where(['user_id'=>$result['user_id'], 'community_article_id'=>$article_id])->find();

                    $attention = 1;     // 默认为关注
                    if($link){
                        $attention = $link['attention'] == 0 ? 1 : 0;
                        $res = Db::name('community_article_link_user')->where(['user_id'=>$result['user_id'], 'community_article_id'=>$article_id])->setField('attention', $attention);

                    }else{
                        $data['community_article_id'] = $article_id;
                        $data['user_id'] = $result['user_id'];
                        $data['attention'] = 1;
                        $data['addtime'] = time();
                        $res = Db::name('community_article_link_user')->insert($data);

                    }

                    if($res){
                        if($attention == 1){
                            Db::name('community_article')->where(['article_id'=>$article_id, 'isopen'=>1, 'useable'=>1])->setInc('attentionnum');
                            $text = '关注';
                        }else{
                            Db::name('community_article')->where(['article_id'=>$article_id, 'isopen'=>1, 'useable'=>1])->setDec('attentionnum');
                            $text = '取消';
                        }

                        return datamsg(WIN,$text.'成功');
                    }

                    return datamsg(LOSE,'操作失败');

                }else{
                    return datamsg(LOSE,'文章已删除或不存在');
                }


            }else{
                return datamsg(LOSE,'未登录',array('count'=>0));
            }
        }else{
            return datamsg(LOSE,$result['mess'],array('count'=>0));
        }

    }

    // 文章点赞
    public function setPraise($article_id){
        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();
        if($result['status'] == 200){
            if($result['user_id']){
                $info = Db::name('community_article')->where(['article_id'=>$article_id, 'isopen'=>1, 'useable'=>1])->find();

                if($info){
                    $link = Db::name('community_article_link_user')->where(['user_id'=>$result['user_id'], 'community_article_id'=>$article_id])->find();

                    $praise = 1;     // 默认为关注
                    if($link){
                        $praise = $link['praise'] == 0 ? 1 : 0;
                        $res = Db::name('community_article_link_user')->where(['user_id'=>$result['user_id'], 'community_article_id'=>$article_id])->setField('praise', $praise);

                    }else{
                        $data['community_article_id'] = $article_id;
                        $data['user_id'] = $result['user_id'];
                        $data['praise'] = 1;
                        $data['addtime'] = time();
                        $res = Db::name('community_article_link_user')->insert($data);

                    }

                    if($res){
                        if($praise == 1){
                            Db::name('community_article')->where(['article_id'=>$article_id, 'isopen'=>1, 'useable'=>1])->setInc('bestnum');
                            $text = '点赞';
                        }else{
                            Db::name('community_article')->where(['article_id'=>$article_id, 'isopen'=>1, 'useable'=>1])->setDec('bestnum');
                            $text = '取消';
                        }

                        return datamsg(WIN,$text.'成功');
                    }

                    return datamsg(LOSE,'操作失败');

                }else{
                    return datamsg(LOSE,'文章已删除或不存在');
                }


            }else{
                return datamsg(LOSE,'未登录',array('count'=>0));
            }
        }else{
            return datamsg(LOSE,$result['mess'],array('count'=>0));
        }

    }

    //关注用户
    public function follow(){
        $input = input('post.');
        $num = Db::name('community_fans')->where('user_id', $this->user['user_id'])->where('fan_user_id', $input['fan_user_id'])->count();
        if($this->user['user_id'] == $input['fan_user_id']){
            return datamsg(LOSE, '你要和自己交流吗');
        }
        if($num > 0){
            $r = Db::name('community_fans')->where('user_id', $this->user['user_id'])->where('fan_user_id', $input['fan_user_id'])->delete();
            if($r !== false){
                return datamsg(WIN, '取消成功');
            }else{
                return datamsg(LOSE, '取消失败');
            }
        }else{
            $data = [
                'user_id' => $this->user['user_id'],
                'fan_user_id' => $input['fan_user_id'],
                'addtime' => time()
            ];

            $r = Db::name('community_fans')->insert($data);
            if($r !== false){
                $this->sendMessage($this->user['user_id'], $input['fan_user_id'], 'follow', $input['fan_user_id'], '关注了您');
                return datamsg(WIN, '关注成功');
            }else{
                return datamsg(LOSE, '关注失败');
            }
        }
    }

    // 社群 - 创建|修改 页面
    public function create(){
        $comm_id = input('comm_id/d', 0);
        if($comm_id){
            $data = Db::name('community_list')->where('comm_id', $comm_id)->find();
            $data['imgurl'] = $this->webconfig['weburl'].$data['imgurl'];
            $info['data'] = $data;
            $info['title'] = '修改社群';
        }else{
            $info['title'] = '创建社群';
        }

        return datamsg(WIN,'获取成功', $info);
    }

    // 社群 - 保存创建社群
    public function savecomm(){
        $input = input('post.');
        $get_id = $input['comm_id'];

        //图片文件夹判断
        $dirName = "public/uploads/community/" . date('Y') . '/' . date('m-d');
        if(!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        if($input['isCover'] == 1){
            $file = request()->file('image');
            if($file){

                $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . $dirName);

                if($info){
                    $original = 'uploads/community/'. date('Y') . '/' . date('m-d').'/'.$info->getSaveName();

                    $image = \think\Image::open($original);
                    $image->thumb(300, 300)->save($original,null,90);
                    $imgurl = $original;
                }else{
                    return datamsg(LOSE, $file->getError());
                }
            }
        }

        if($get_id > 0){
            $data = [
                'title' => $input['title'],
                'description' => $input['description'],
                'isapply' => $input['isapply'],
                'useable' => 0
            ];

            if(!empty($imgurl)){
                $data['imgurl'] = '/'.$imgurl;
            }

            $r = Db::name('community_list')->where('comm_id', $get_id)->update($data);
            if($r !== false){
                return datamsg(WIN,'修改成功，请等待审核');
            }else{
                if(!$r && file_exists('./'.$imgurl)){
                    @unlink('./'.$imgurl);
                }

                return datamsg(LOSE,'修改失败');
            }
        }else{
            $num = Db::name('community_list')->where('user_id', $this->user['user_id'])->count();
            if($num > 0){
                return datamsg(LOSE,'您已创建了社群');
            }else{
                $data = [
                    'user_id' => $this->user['user_id'],
                    'title' => $input['title'],
                    'description' => $input['description'],
                    'imgurl' => $imgurl,
                    'isapply' => $input['isapply'],
                    'useable' => 0,
                    'user_num' => 1,
                    'addtime' => time()
                ];

                $comm_id = Db::name('community_list')->insertGetId($data);
                if($comm_id){
                    //创建成功以后自己自动加入群组
                    $data = [
                        'user_id' => $this->user['user_id'],
                        'comm_id' => $comm_id,
                        'isadmin' => 1,
                        'ispass' => 1,
                        'addtime' => time()
                    ];

                    Db::name('community_user')->insert($data);
                    return datamsg(WIN,'创建成功，请等待审核');
                }else{
                    if(!$comm_id && file_exists('./'.$imgurl)){
                        @unlink('./'.$imgurl);
                    }
                    return datamsg(LOSE,'创建失败');
                }
            }
        }
    }

    //加入社群
    public function joinin(){
        $input = input('post.');
        $num = Db::name('community_user')->where('user_id', $this->user['user_id'])->where('comm_id', $input['comm_id'])->where('ispass', 1)->count();
        $comm = Db::name('community_list')->where('comm_id', $input['comm_id'])->find();
        if(empty($input['comm_id'])){
            return datamsg(LOSE,'社群ID为空');
        }
        if(empty($comm)){
            return datamsg(LOSE,'社群不存在');
        }
        if($comm['useable'] == 0){
            return datamsg(LOSE,'该社群审核中');
        }
        if($num > 0){
            //退出群组操作
            if($this->user['user_id'] == $comm['user_id']){
                return datamsg(LOSE,'你是该社群创建人，不能退出');
            }

            //删除相关信息
            $r = Db::name('community_user')->where('user_id', $this->user['user_id'])->where('comm_id', $input['comm_id'])->delete();
            if($r){
                //删除相关动态和文章
                $articles = Db::name('community_article')->where('user_id', $this->user['user_id'])->where('comm_id', $input['comm_id'])->select();
                foreach ($articles as $key => $value) {
                    Db::name('community_feed')->where('user_id', $this->user['user_id'])->where('post_id', $value['article_id'])->delete();
                    Db::name('community_best')->where('user_id', $this->user['user_id'])->where('post_id', $value['article_id'])->delete();
                }
                Db::name('community_article')->where('user_id', $this->user['user_id'])->where('comm_id', $input['comm_id'])->delete();

                $this->sendMessage($this->user['user_id'], $comm['user_id'], 'comm', $comm['comm_id'], '退出了社群');
                return datamsg(WIN,'退出成功');
            }else{
                return datamsg(LOSE,'退出失败');
            }
        }else{
            $hasnum = Db::name('community_user')->where('user_id', $this->user['user_id'])->where('comm_id', $input['comm_id'])->count();
            if($hasnum > 0){
                Db::name('community_user')->where('user_id', $this->user['user_id'])->where('comm_id', $input['comm_id'])->update(['ispass' => 0]);
            }else{
                $data = [
                    'user_id' => $this->user['user_id'],
                    'comm_id' => $input['comm_id'],
                    'addtime' => time(),
                    'ispass' => 0
                ];

                $cuser_id = Db::name('community_user')->insertGetId($data);
                if($cuser_id){
                    if($comm['isapply'] == 1){
                        //加入需要审核时，发送消息通知创建者
                        $this->sendMessage($this->user['user_id'], $comm['user_id'], 'comm', $comm['comm_id'], '申请加入社群');
                        return datamsg(WIN,'申请成功，请等待通过审核');
                    }else{
                        Db::name('community_list')->where('comm_id', $comm['comm_id'])->setInc('user_num', 1);
                        Db::name('community_user')->where('cuser_id', $cuser_id)->update(['ispass' => 1]);
                        $this->sendMessage($comm['user_id'], $this->user['user_id'], 'commpass', $comm['comm_id'], '恭喜你加入社群：' . $comm['title']);
                        return datamsg(WIN,'加入成功');
                    }
                }else{
                    return datamsg(LOSE,'加入失败');
                }
            }
        }
    }

    // 社群 - 列表
    public function ajax_comm_list(){
        $page = input('page/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);

        $where = "useable = '1'";
        $count = Db::name('community_list')->where($where)->count();
        $list = Db::name('community_list')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();
        foreach ($list as $key => $value) {
            $list[$key]['imgurl'] = $this->webconfig['weburl'].$value['imgurl'];
            $cuser = Db::name('community_user')->where('user_id', $this->user['user_id'])->where('comm_id', $value['comm_id'])->find();
            if(empty($cuser)){
                $list[$key]['cuser'] = '';
            }else{
                $list[$key]['cuser'] = $cuser;
            }

            $list[$key]['ismy'] = $value['user_id'] == $this->user['user_id'] ? 1 : 0;
        }
        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);

        return datamsg(WIN,'获取成功3', $arr);
    }

    public function community_article_detail(){
        $article_id = input('id/d', 0);
        $data = Db::name('community_article')->where('article_id', $article_id)->find();

        if(!empty($data['imglist'])){
            $data['imglist'] = str_replace('uploads', $this->webconfig['weburl'].'uploads', $data['imglist']);
            $data['imglist'] = explode(',', $data['imglist']);
        }
        if(empty($data)){
            return datamsg(LOSE,'内容不存在');
        }
        if($data['useable'] == 0){
            return datamsg(LOSE,'内容审核中');
        }
        if($data['isopen'] == 0 && $data['user_id'] != $this->user['user_id']){
            return datamsg(LOSE,'内容未公开');
        }
//        $data['content'] = img_add_protocal($data['content'], $this->webconfig['weburl']);
        $res = img_add_protocal($data['content'], $this->webconfig['weburl']);
        $data['album'] = $res[1];
        $data['content'] = $res[0];

        Db::name('community_article')->where('article_id', $article_id)->setInc('shownum', 1);
        $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$data['user_id']]);
        if($comm_user)$comm_user['headimgurl'] = $comm_user['headimgurl'];

        $data['user'] = $comm_user;
        $isfollow = Db::name('community_fans')->where('user_id', $this->user['user_id'])->where('fan_user_id', $data['user_id'])->count();
        if($isfollow){
            $followtxt = '已关注';
        }else{
            $followtxt = '关注';
        }
        $data['addtime'] = (new timeFormat($data['addtime']))->calculateTime()->getTime();
        $data['isself'] = $data['user_id'] == $this->user['user_id'] ? 1 : 0;
        $images = explode(',', $data['imgurl']);
        $info['images'] = $images;
        if($data['kind'] == 2){
            $info['shareimage'] = $images[0];
        }
        $info['isdel'] = $isfollow > 0 ? true : false;
        $info['followtxt'] = $followtxt;

        $praise = Db::name('community_best')->where('user_id', $this->user['user_id'])->where('post_id', $article_id)->where('kind', $data['kind'])->value('praise');
        $info['isbest'] = $praise ?: 0;

        $data['feednum'] = Db::name('community_feed')->where('post_id', $data['article_id'])->where('kind', $data['kind'])->where('useable', 1)->count();
        $data['format_content'] = $this->Html2text($data['content']);

        if($data['goods_id'] > 0){
            $goods = Db::name('goods')->field('goods_name,thumb_url,id,shop_id,zs_price')->where('id', $data['goods_id'])->find();
            $goods['thumb_url'] = $this->webconfig['weburl'].$goods['thumb_url'];
            $data['goods'] = $goods;
        }

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        $this->addLog($this->user['user_id'], $article_id, $data['kind']);
        $info['http_type'] = $http_type;
        $info['data'] = $data;
        return datamsg(WIN,'获取成功', $info);
    }

    public function ajax_feed_list(){
        $page = input('page/d', 1);
        $post_id = input('post_id/d', 1);
        $pagesize = 5;
        $limit = $pagesize * ($page - 1);

        $where = "useable = '1' and post_id = '$post_id'";
        $count = Db::name('community_feed')->where($where)->count();
        $list = Db::name('community_feed')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();
        foreach ($list as $key => $value) {
            $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);
            $comm_user['headimgurl'] = $comm_user['headimgurl'];

            $list[$key]['user'] = $comm_user;
            $list[$key]['format_time'] = (new timeFormat($value['addtime']))->calculateTime()->getTime();

            $praise = Db::name('community_best')->where('user_id', $this->user['user_id'])->where('post_id', $value['feed_id'])->where('kind', 3)->value('praise');
            $list[$key]['isbest'] = $praise ?: 0;
        }
        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
        return datamsg(WIN,'获取成功', $arr);
    }

    //评论内容
    public function postfeed(){
        if(!isset($this->user['user_id']) || $this->user['user_id'] <= 0)return datamsg(LOSE, '请先登录');
        $input = input('post.');
        $data = [
            'user_id' => $this->user['user_id'],
            'post_id' => $input['post_id'],
            'kind' => $input['kind'],
            'content' => $this->shieldReplcae($input['content']),
            'useable' => 1,
            'addtime' => time()
        ];

        $feed_id = Db::name('community_feed')->insertGetId($data);
        if($feed_id){
            $to_user_id = Db::name('community_article')->where('article_id', $input['post_id'])->value('user_id');
            $kind = Db::name('community_article')->where('article_id', $input['post_id'])->value('kind');
            $this->sendMessage($this->user['user_id'], $to_user_id, 'feed', $input['post_id'], '评论了您的' . ($kind == 1 ? '文章' : '动态'), $input['content']);
            Db::name('community_article')->where('article_id', $input['post_id'])->setInc('feednum', 1);
            $arr = ['code' => 1, 'msg' => '评论成功'];
            $userInfo = Db::name('member')->where('id', $this->user['user_id'])->field('headimgurl, user_name nickname')->find();

            $arr['head_pic'] = $userInfo['headimgurl'] ? $userInfo['headimgurl'] : '/template/mobile/new2/static/images/user68.jpg';
            $arr['nickname'] = $userInfo['nickname'];
            $arr['format_time'] = (new timeFormat($data['addtime']))->calculateTime()->getTime();
            $arr['content'] = $data['content'];
            $arr['bestnum'] = 0;
            $arr['feed_id'] = $feed_id;
            return datamsg(WIN,'获取成功', $arr);
        }else{
            return datamsg(LOSE,'评论失败');
        }
    }

    // 删除社群成员
    public function delcuser(){
        $cuser_id = input('cuser_id/d', 0);
        if(empty($cuser_id)){
            return datamsg(LOSE,'ID为空');
        }

        $cuser = Db::name('community_user')->where('cuser_id', $cuser_id)->find();
        $data = Db::name('community_list')->where('comm_id', $cuser['comm_id'])->find();
        if($data['user_id'] != $this->user['user_id']){
            return datamsg(LOSE,'你不能操作');
        }
        if($cuser['user_id'] == $data['user_id']){
            return datamsg(LOSE,'你不能删除自己');
        }

        $r = Db::name('community_user')->where('cuser_id', $cuser_id)->delete();
        if($r !== false){
            //删除相关动态和文章
            $articles = Db::name('community_article')->where('user_id', $cuser['user_id'])->where('comm_id', $data['comm_id'])->select();
            foreach ($articles as $key => $value) {
                Db::name('community_feed')->where('user_id', $cuser['user_id'])->where('post_id', $value['article_id'])->delete();
                Db::name('community_best')->where('user_id', $cuser['user_id'])->where('post_id', $value['article_id'])->delete();
            }
            Db::name('community_article')->where('user_id', $cuser['user_id'])->where('comm_id', $data['comm_id'])->delete();

            $this->sendMessage($this->user['user_id'], $cuser['user_id'], 'commdel', $data['comm_id'], '将你踢出了社群');
            return datamsg(WIN,'删除成功');
        }else{
            return datamsg(LOSE,'删除失败');
        }
    }

    // 社群成员列表
    public function ajax_comm_user_list(){
        $page = input('page/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);
        $comm_id = input('comm_id/d', 0);

        $data = Db::name('community_list')->where('comm_id', $comm_id)->find();
        if(empty($data)){
            return datamsg(LOSE,'社群不存在');
        }
//        if($data['user_id'] != $this->user['user_id']){
//            return datamsg(LOSE,'你不能操作');
//        }

        if($data['user_id'] != $this->user['user_id']){
            $arr['list'] = [];
            $arr['pages'] = 0;
        }else{
            $where = "comm_id = '$comm_id' and ispass = '1'";
            $count = Db::name('community_user')->where($where)->count();
            $list = Db::name('community_user')->where($where)->order('isadmin desc, addtime desc')->limit($limit, $pagesize)->select();
            foreach ($list as $key => $value) {
                $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);

                $list[$key]['user'] = $comm_user;
            }
            $arr['list'] = $list;
            $arr['pages'] = @ceil($count / $pagesize);
        }

        return datamsg(WIN,'获取成功', $arr);
    }

    //内容点赞
    public function postbest(){
        $input = input('post.');
//        $num = Db::name('community_best')->where('user_id', $this->user['user_id'])->where('post_id', $input['post_id'])->where('kind', $input['kind'])->count();
//        if($num > 0){
//            return datamsg(LOSE,'已点赞');
//        }
        $info = Db::name('community_best')->where('user_id', $this->user['user_id'])->where('post_id', $input['post_id'])->where('kind', $input['kind'])->find();
        $text = $info['praise'] == 1 ? '取消' : '点赞';

        if(is_null($info)){
            $data = [
                'user_id' => $this->user['user_id'],
                'post_id' => $input['post_id'],
                'kind' => $input['kind'],
                'addtime' => time()
            ];
            $r = Db::name('community_best')->insert($data);
            if(!$r)return datamsg(LOSE, '点赞失败');
        }else{
            $update['praise'] = 0;
            if($info['praise'] == 0)
                $update['praise'] = 1;
            $res = Db::name('community_best')->where('user_id', $this->user['user_id'])->where('post_id', $input['post_id'])->where('kind', $input['kind'])->update($update);
            if(!$res)return datamsg(LOSE, $text.'失败');
        }

        if($input['kind'] == 3){
            if($text == '取消'){
                $res = Db::name('community_feed')->where('feed_id', $input['post_id'])->setDec('bestnum', 1);;
            }else{
                $res = Db::name('community_feed')->where('feed_id', $input['post_id'])->setInc('bestnum', 1);;
            }
        }else{
            $to_user_id = Db::name('community_article')->where('article_id', $input['post_id'])->value('user_id');

            $kind = Db::name('community_article')->where('article_id', $input['post_id'])->value('kind');

            if($text == '取消'){
                $res = Db::name('community_article')->where('article_id', $input['post_id'])->setDec('bestnum', 1);
            }else{
                $res = Db::name('community_article')->where('article_id', $input['post_id'])->setInc('bestnum', 1);
                if($res)
                    $this->sendMessage($this->user['user_id'], $to_user_id, 'best', $input['post_id'], '点赞了您的' . ($kind == 1 ? '文章' : '动态'));
            }
        }

        if(!$res)return datamsg(LOSE, $text.'失败');
            return datamsg(WIN, $text.'成功');
    }

    //个人主页
    public function community_user(){
        $user_id = input('user_id/d', 0);
        $data['user_id'] = $user_id;

        $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$user_id]);
        if(empty($comm_user)){
            return datamsg(LOSE,'用户不存在');
        }

        $isfollow = Db::name('community_fans')->where('user_id', $this->user['user_id'])->where('fan_user_id', $user_id)->count();
        if($isfollow){
            $followtxt = '已关注';
        }else{
            $followtxt = '关注';
        }

        if($user_id == $this->user['user_id']){
            $data['isself'] = 1;
        }else{
            $data['isself'] = 0;
        }

        //粉丝数量
        $fansnum = Db::name('community_fans')->where('fan_user_id', $user_id)->count();
        //关注数量
        $follownum = Db::name('community_fans')->where('user_id', $user_id)->count();
        //作品数量
        $awhere = "useable = '1' and user_id = '$user_id'";
        if($user_id != $this->user['user_id']){
            $awhere .= " and isopen = '1'";
        }
        $articlenum = Db::name('community_article')->where($awhere)->count();

        $this->addLog($this->user['user_id'], $user_id, 3);
        $data['isdel'] = $isfollow > 0 ? true : false;
        $data['isfollow'] = $isfollow;
        $data['followtxt'] = $followtxt;
        $data['user'] = $comm_user;
        $data['fansnum'] = $fansnum;
        $data['follownum'] = $follownum;
        $data['articlenum'] = $articlenum;
        return datamsg(WIN,'获取成功', $data);
    }

    public function ajax_user_article_list(){
        $page = input('page/d', 1);
        $user_id = input('user_id/d', 0);
        $kind = input('kind/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);

        if($kind == 1 || $kind == 2){
            $where = "useable = '1' and user_id = '$user_id' and kind = '$kind'";
            if($user_id != $this->user['user_id']){
                $where .= " and isopen = '1'";
            }
            $count = Db::name('community_article')->where($where)->count();
            $list = Db::name('community_article')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();

            foreach ($list as $key => $value) {
                $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);
                $list[$key]['addtime'] = (new timeFormat($value['addtime']))->calculateTime()->getTime();
                $list[$key]['imgurl'] = $this->webconfig['weburl'].$value['imgurl'];

                // 文本内容
                $pattern='/<img\s+src=[\\\'|bai \\\"](.*?(?:[\.gif|\.jpg]))[\\\'|\\\"].*?[\/]?>/';
                // $list[$key]['content'] = cut_str(strip_tags(preg_replace($pattern,'', $value['content'])), 60) ? cut_str(strip_tags(preg_replace($pattern,'', $value['content'])), 60) : $value['title'];
				// $list[$key]['content'] = $value['title'] ? $value['title'] : cut_str(strip_tags(preg_replace($pattern,'', $value['content'])), 60) ? cut_str(strip_tags(preg_replace($pattern,'', $value['content'])), 60);
                $list[$key]['content'] = $value['title'] ? $value['title'] : cut_str(strip_tags(preg_replace($pattern,'', $value['content'])), 60);

                    // 文本图片
                $list[$key]['img_arr'] = [];
                // 正则
                $pattern = '/<img[^>]*src="([^"]*)"[^>]*>/i';
                preg_match_all($pattern, $value['content'], $matches);
                foreach($matches[1] as $k1=>$v1){
                    array_push($list[$key]['img_arr'], $this->webconfig['weburl'].$v1);
                }

                $list[$key]['user'] = $comm_user;
                $list[$key]['images'] = explode(',', $value['imgurl']);
                $list[$key]['format_time'] = date('Y-m-d H:i:s', $value['addtime']);
                $comm_title = Db::name('community_list')->where('comm_id', $value['comm_id'])->value('title');
                $list[$key]['comm_title'] = !empty($comm_title) ? $comm_title : '无';

                $praise = Db::name('community_best')->where('user_id', $this->user['user_id'])->where('post_id', $value['article_id'])->where('kind', $value['kind'])->value('praise');
                $list[$key]['isbest'] = $praise ?: 0;
                $list[$key]['title'] = empty($value['title']) ? '' : $value['title'];
                $list[$key]['description'] = empty($value['description']) ? '' : $value['description'];

                $list[$key]['feednum'] = Db::name('community_feed')->where('post_id', $value['article_id'])->where('kind', $value['kind'])->where('useable', 1)->count();
            }
        }else{
            $where = "useable = '1' and user_id = '$user_id'";
            $count = Db::name('community_feed')->where($where)->count();
            $list = Db::name('community_feed')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();
            foreach ($list as $key => $value) {
                $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);

                $list[$key]['user'] = $comm_user;

                $article = Db::name('community_article')->field('article_id,description,content,imgurl,kind,title,imglist')->where('article_id', $value['post_id'])->find();
                if($article['kind'] == 2){
                    $article['title'] = $article['content'];
                    $article['imgurl'] = current(explode(',', $article['imglist']));
                }
                $article['imgurl'] = $this->webconfig['weburl'].$article['imgurl'];
                // $images = explode(',', $article['imgurl']);
                // $article['imgurl'] = $images[0];
                // $article['title'] = $article['kind'] == 1 ? $article['description'] : $article['content'];
                $list[$key]['article'] = $article;
            }
        }

        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
        if($user_id == $this->user['user_id']){
            $arr['isself'] = 1;
        }else{
            $arr['isself'] = 0;
        }
        return datamsg(WIN,'获取成功', $arr);
    }

    //发送消息
    private function sendMessage($user_id, $to_user_id, $kind = 'comm', $post_id, $message, $omessage = ''){
        $r = Db::name('community_message')->insert([
            'user_id' => $user_id,
            'to_user_id' => $to_user_id,
            'kind' => $kind,
            'post_id' => $post_id,
            'message' => $message,
            'omessage' => $omessage,
            'isview' => 0,
            'ispass' => 0,
            'addtime' => time()
        ]);

        return true;
    }

    //记录访问日志
    private function addLog($user_id, $post_id, $kind){
        if($kind == 1 || $kind == 2){
            $comm_id = Db::name('community_article')->where('article_id', $post_id)->value('comm_id');
        }elseif($kind == 3){
            $comm_id = Db::name('community_user')->where('user_id', $post_id)->value('comm_id');
        }elseif($kind == 4){
            $comm_id = $post_id;
        }else{
            $comm_id = 0;
        }
        $r = Db::name('community_log')->insert([
            'user_id' => $user_id,
            'kind' => $kind,
            'post_id' => $post_id,
            'comm_id' => $comm_id,
            'addtime' => time()
        ]);

        return true;
    }

    private function Html2text($string){
        $string = str_replace('section', 'div', $string);
        return strip_tags(htmlspecialchars_decode($string));
        $str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU","",$string);
        $alltext = "";
        $start = 1;
        for($i=0;$i<strlen($str);$i++)
        {
            if($start==0 && $str[$i]==">")
            {
                $start = 1;
            }
            else if($start==1)
            {
                if($str[$i]=="<")
                {
                    $start = 0;
                    $alltext .= " ";
                }
                else if(ord($str[$i])>31)
                {
                    $alltext .= $str[$i];
                }
            }
        }
        $alltext = str_replace("　"," ",$alltext);
        $alltext = preg_replace("/&([^;&]*)(;|&)/","",$alltext);
        $alltext = preg_replace("/[ ]+/s"," ",$alltext);
        return $alltext;
    }

    public function ajax_flow_list(){
        $page = input('page/d', 1);
        $pagesize = 10;
        $limit = $pagesize * ($page - 1);
        $user_id = input('user_id/d', 0);
        $kind = input('kind');

        if($kind == 'fans'){
            $where = "fan_user_id = '$user_id'";
        }else{
            $where = "user_id = '$user_id'";
        }

        $count = Db::name('community_fans')->where($where)->count();
        $list = Db::name('community_fans')->where($where)->order('addtime desc')->limit($limit, $pagesize)->select();
        foreach ($list as $key => $value) {
            if($kind == 'fans'){
                $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);
            }else{
                $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['fan_user_id']]);
            }
            $list[$key]['user'] = $comm_user;
        }
        $arr['list'] = $list;
        $arr['kind'] = $kind;
        $arr['pages'] = @ceil($count / $pagesize);

        return datamsg(WIN, '获取成功', $arr);
    }

    //删除内容
    public function del_user_article(){
        $article_id = input('article_id/d', 0);
        $data = Db::name('community_article')->where('article_id', $article_id)->find();
        if(empty($data)){
            return datamsg(LOSE, '内容不存在');
        }

        if($data['user_id'] != $this->user['user_id']){
            return datamsg(LOSE, '你不能删除');
        }

        $r = Db::name('community_article')->where('user_id', $this->user['user_id'])->where('article_id', $article_id)->delete();
        if($r){
            Db::name('community_feed')->where('post_id', $article_id)->delete();
            return datamsg(WIN, '删除成功');
        }else{
            return datamsg(LOSE, '删除失败');
        }
    }

    public function ajax_message_list(){
        $page = input('page/d', 1);
        $pagesize = 10;
        $to_user_id = $this->user['user_id'];
        $limit = $pagesize * ($page - 1);

        $where = "to_user_id = '$to_user_id'";
        $count = Db::name('community_message')->where($where)->count();
        $list = Db::name('community_message')->where($where)->order('isreply asc,isview asc,addtime desc')->limit($limit, $pagesize)->select();
        foreach ($list as $key => $value) {
            $list[$key]['format_time'] = date('Y-m-d H:i:s', $value['addtime']);
            $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);
            $list[$key]['user'] = $comm_user;
        }
        $arr['list'] = $list;
        $arr['pages'] = @ceil($count / $pagesize);
        return datamsg(WIN, '获取成功', $arr);
    }

    public function send_message(){
        $user_id = input('user_id/d', 0);
        // 更新为已读
        Db::name('school_message')->where('user_id', $user_id)->where('to_user_id', $this->user['user_id'])->where('isview', 0)->update(['isview' => 1]);

        $user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$user_id]);
        $data['user'] = $user;

        $mess_id = input('mess_id/d', 0);

        $from_user_id = $this->user['user_id'];
        $from_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$from_user_id]);
        $data['from_user'] = $from_user;

        // $list = Db::name('community_message')->where("(user_id = '$from_user_id' or to_user_id = '$from_user_id') and (user_id = '$user_id' or to_user_id = '$user_id') and kind = 'chat'")->select();
        $list = Db::name('school_message')->where("(user_id = '$from_user_id' or to_user_id = '$from_user_id') and (user_id = '$user_id' or to_user_id = '$user_id') and kind = 'chat'")->select();
        foreach ($list as $key => $value) {
            $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);
            $list[$key]['user'] = $comm_user;
        }

        $data['mess'] = $list;

        $data['from_user_id'] = $from_user_id;
        $data['user_id'] = $user_id;
        $data['mess_id'] = $mess_id;
        return datamsg(WIN, '获取成功', $data);
    }

    public function save_message(){
        $input = input('post.');
        $arr = $this->sendSchoolMessage($this->user['user_id'], $input['user_id'], 'chat', $input['user_id'], $input['content']);
        if($input['mess_id'] > 0){
            // Db::name('community_message')->where('mess_id', $input['mess_id'])->update(['isview' => 1, 'isreply' => 1]);
            Db::name('school_message')->where('mess_id', $input['mess_id'])->update(['isview' => 1, 'isreply' => 1]);
        }
        return datamsg(WIN, '发送成功', $arr);
    }

    private function sendSchoolMessage($user_id, $to_user_id, $kind = 'comm', $post_id, $message, $omessage = ''){
        $data = [
            'user_id' => $user_id,
            'to_user_id' => $to_user_id,
            'kind' => $kind,
            'post_id' => $post_id,
            'message' => $message,
            'omessage' => $omessage,
            'isview' => 0,
            'ispass' => 0,
            'addtime' => time()
        ];

        $r = Db::name('school_message')->insert($data);

        return $data;
    }
}