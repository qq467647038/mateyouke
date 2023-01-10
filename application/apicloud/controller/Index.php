<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;
use app\apicloud\model\Goods as GoodsModel;

/**
 * @title 首页
 * @description 首页相关接口
 */
class Index extends Common{
    public function crowdGoodsLst(){
        $input = input();
        $pageSize = $input['pageSize'];
        $page = ($input['page']-1)*$pageSize;
        
        $list = Db::name('crowd_goods')->limit($page, $pageSize)->select();
        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
        return json($value);
    }
    
    public function getPcInfo()
    {
        if(request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];

                $pc_login_info = Db::name('member')->where('id', $user_id)->field('pc_account')->find();
                if(!is_null($pc_login_info) && !empty($pc_login_info['pc_account']))$pc_login_info['login_url'] = $this->webconfig['weburl'] . 'member';

                $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$pc_login_info);
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function service_qrcode_img(){
        $service_qrcode = Db::name('shops')->where('id', 1)->value('service_qrcode');
        
        $service_qrcode = $this->webconfig['weburl'].$service_qrcode;
        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$service_qrcode);
        return json($value);
    }
    
    public function cate_goods(){
        $input = input();
        $pageSize = $input['pageSize'];
        $page = ($input['page']-1)*$pageSize;
        
        $list = Db::name('goods')->where('onsale', 1)->where('is_recycle', 0)->where('checked', 1)->where('type', 4)->limit($page, $pageSize)->select();
        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
        return json($value);
    }
    
    public function common_goods(){
//        echo 1;exit;
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(true){
                    $page = 1;
                    if(input('post.page', $page) && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page', $page))){

                        $pagenum = input('post.page', $page);

                        if(true){
                            
                            $webconfig = $this->webconfig;
                            $perpage = $webconfig['app_goodlst_num'];
                            $persd = input('post.pageSize', $perpage);
                            $offset = ($pagenum-1)*$persd;
                            
                            $where2 = "a.onsale = 1";
                            $where3 = '';
                            $where4 = '';
                            $where5 = '';
                            $where6 = '';
                            
                            if(input('post.low_price') && input('post.max_price')){
                                $low_price = input('post.low_price');
                                $max_price = input('post.max_price');
                                
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
                                    $value = array('status'=>400,'mess'=>'最低价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $max_price)){
                                    $value = array('status'=>400,'mess'=>'最高价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                if($low_price >= $max_price){
                                    $value = array('status'=>400,'mess'=>'最低价格需小于最大价格','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                $where4 = "a.zs_price >= '".$low_price."' AND a.zs_price <= '".$max_price."'";
                            }elseif(input('post.low_price') && !input('post.max_price')){
                                $low_price = input('post.low_price');
                                
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
                                    $value = array('status'=>400,'mess'=>'最低价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }

                                $where4 = "a.zs_price >= '".$low_price."'";
                            }elseif(!input('post.low_price') && input('post.max_price')){
                                $max_price = input('post.max_price');
                                
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $max_price)){
                                    $value = array('status'=>400,'mess'=>'最高价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                $where4 = "a.zs_price <= '".$max_price."'";
                            }
                            
                            $sortarr = array('a.id'=>'desc');

                            $goodres = Db::name('goods')->alias('a')->where('a.type', 'notIn', [4,5])->field('a.id,a.goods_name,a.sale_num,a.fictitious_sale_num,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where1)->where($where2)->where($where3)->where($where4)->where($where5)->where($where6)->where("b.open_status = 1")->order($sortarr)->limit($offset,$perpage)->select();
                            // var_dump($goodres);exit;
                            // halt(Db::name('goods')->getLastSql());
                            if($goodres){
                                foreach ($goodres as $k =>$v){
                                    $goodres[$k]['thumb_url'] = $v['thumb_url'];
                                    $goodres[$k]['coupon'] = 0;
                                    $goodres[$k]['sale_num'] = $v['sale_num']+$v['fictitious_sale_num'];
                                
                                    $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                                    
                                }
                            }

                            
                            $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$goodres);
                        }else{
                            $value = array('status'=>400,'mess'=>'分类信息参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少分类参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    // public function cate_goods(){
    //     if(request()->isPost()) {
    //         $cate_list = Db::name('category')->where('is_show', 1)->where('recommend', 1)->where('pid', 0)->field('cate_name,id')->select();

    //         foreach ($cate_list as $k=>$v){
    //             $cate_id_arr = Db::name('category')->where('pid', $v['id'])->column('id');
    //             array_push($cate_id_arr, $v['id']);

    //             $goods_list = Db::name('goods')->order('id desc')->where('onsale', 1)->where('is_recycle', 0)->where('checked', 1)->where('cate_id', 'in', $cate_id_arr)->limit(4)->select();

    //             if(!$goods_list)
    //             {
    //                 unset($cate_list[$k]);
    //                 continue;
    //             }

    //             $cate_list[$k]['goods_list'] = $goods_list;
    //         }

    //         $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$cate_list);
    //     }else{
    //         $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
    //     }

    //     return json($value);
    // }

    public function setPcInfo()
    {
        if(request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
                $pc_login_info = input();

                if(empty($pc_login_info['pc_account']))
                {
                    $value = array('status'=>400,'mess'=>'账号不能为空','data'=>array('status'=>400));
                    return json($value);
                }

                if(empty($pc_login_info['pc_pass']))
                {
                    $value = array('status'=>400,'mess'=>'密码不能为空','data'=>array('status'=>400));
                    return json($value);
                }

                $count = Db::name('member')->where('pc_account', $pc_login_info['pc_account'])->count();
                if($count > 0)
                {
                    $value = array('status'=>400,'mess'=>'账号已存在','data'=>array('status'=>400));
                    return json($value);
                }

                $member_info = Db::name('member')->where('id', $user_id)->field('pc_account, pc_pass')->find();

                if(trim($member_info['pc_account']) || trim($member_info['pc_pass']))
                {
                    $value = array('status'=>400,'mess'=>'请勿重复设置','data'=>array('status'=>400));
                    return json($value);
                }

                $data['pc_account'] = $pc_login_info['pc_account'];
                $data['pc_pass'] = md5($pc_login_info['pc_pass']);
                $res = Db::name('member')->where('id', $user_id)->update($data);
                if($res)
                {
                    $value = array('status'=>200,'mess'=>'后台账号密码设置成功');
                }
                else
                {
                    $value = array('status'=>400,'mess'=>'后台账号密码设置失败','data'=>array('status'=>400));
                }
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function repairMemberTeamData(){
        Db::name('member')->field('one_level,id')->chunk(100, function ($members){
            $update = [];
            foreach ($members as $k=>$v)
            {
                $data = [];
                if($v['one_level'] > 0)
                {
                    $p_member = Db::name('member')->where('id', $v['one_level'])->find();

                    if (!is_null($p_member))
                    {
//                        $data['one_level'] = $p_member['id'];
//                        $data['two_level'] = $p_member['one_level'];
                        $data['id'] = $v['id'];
                        $data['team_id'] = $p_member['team_id'].','.$p_member['id'];
                        $data['agent_num'] = $this->agent_num($v['id']);

                        array_push($update, $data);
                    }
                }
            }

            model('member')->saveAll($update);
        });
    }

    public function agent_num($user_id)
    {
        return Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'])->where('is_vip', 1)->count();
    }

    /**
     * @title 测试demo接口
     * @description 接口说明
     * @author 开发者
     * @url apicloud/index/indexinfo
     * @method GET
     *
     * @header name:device require:1 default: desc:设备号
     *
     * @param name:id type:int require:1 default:1 other: desc:唯一ID
     *
     * @return name:名称
     * @return mobile:手机号
     * @return list_messages:消息列表@
     * @list_messages message_id:消息ID content:消息内容
     * @return object:对象信息@!
     * @object attribute1:对象属性1 attribute2:对象属性2
     * @return array:数组值#
     * @return list_user:用户列表@
     * @list_user name:名称 mobile:手机号 list_follow:关注列表@
     * @list_follow user_id:用户id name:名称
     */
    public function indexinfo(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $webconfig = $this->webconfig;

                $ads = Db::name('ad')->where('pos_id',6)->where('is_on',1)->find();
                $banner_pic = array();
                if($ads){
                    if($ads['ad_type'] == 1){
                        $banner_pic[] = array('pic'=>$ads['ad_pic'],'canshu'=>$ads['ad_canshu']);
                    }else{
                        $banner_pic = Db::name('ad_pic')->where('ad_id',$ads['id'])->field('pic,canshu')->order('sort asc')->select();
                    }
                }

                $ad_ones = Db::name('ad')->where('pos_id',10)->where('is_on',1)->find();
                $ad_pic_one = array();
                if($ad_ones){
                    if($ad_ones['ad_type'] == 1){
                        $ad_pic_one = array('pic'=>$webconfig['weburl'].'/'.$ad_ones['ad_pic'],'canshu'=>$ad_ones['ad_canshu']);
                    }
                }

                $ad_twos = Db::name('ad')->where('pos_id',11)->where('is_on',1)->find();
                $ad_pic_two = array();
                if($ad_twos){
                    if($ad_twos['ad_type'] == 1){
                        $ad_pic_two = array('pic'=>$webconfig['weburl'].'/'.$ad_twos['ad_pic'],'canshu'=>$ad_twos['ad_canshu']);
                    }
                }

                $ad_threes = Db::name('ad')->where('pos_id',12)->where('is_on',1)->find();
                $ad_pic_three = array();
                if($ad_threes){
                    if($ad_threes['ad_type'] == 1){
                        $ad_pic_three = array('pic'=>$webconfig['weburl'].'/'.$ad_threes['ad_pic'],'canshu'=>$ad_threes['ad_canshu']);
                    }
                }

                $ad_fours = Db::name('ad')->where('pos_id',13)->where('is_on',1)->find();
                $ad_pic_four = array();
                if($ad_fours){
                    if($ad_fours['ad_type'] == 1){
                        $ad_pic_four = array('pic'=>$webconfig['weburl'].'/'.$ad_fours['ad_pic'],'canshu'=>$ad_fours['ad_canshu']);
                    }
                }

                if($banner_pic){
                    foreach ($banner_pic as $k =>$v){
                        $banner_pic[$k]['pic'] = $webconfig['weburl'].'/'.$v['pic'];
                    }
                }

                $time = time();
                $dctime = date('Y-m-d',time());
                $tomtime = date('Y-m-d',time()+3600*24);
                $hdtime = '';
                $end_time = '';

                $sale_times = Db::name('sale_time')->order('time asc')->field('time')->select();
                $last_sale_time_index = count($sale_times) -1; // 最后一个时间段对应的索引值，从0开始
                if($sale_times){
                    $rushtime = array();

                    foreach ($sale_times as $k2 => $v2){
                        if($v2['time'] < 10){
                            $dcthetime = strtotime($dctime.' 0'.$v2['time'].':00:00'); // 时间<10，前面加0修饰
                        }else{
                            $dcthetime = strtotime($dctime.' '.$v2['time'].':00:00');
                        }

                        if(!empty($sale_times[$k2+1])){
                            if($sale_times[$k2+1]['time'] < 10){
                                $end_dcthetime = strtotime($dctime.' 0'.$sale_times[$k2+1]['time'].':00:00');
                            }else{
                                $end_dcthetime = strtotime($dctime.' '.$sale_times[$k2+1]['time'].':00:00');
                            }
                        }else{
                            // 当为最后一个时
                            if($sale_times[0]['time'] < 10){
                                $end_dcthetime = strtotime($tomtime.' 0'.$sale_times[0]['time'].':00:00');
                            }else{
                                $end_dcthetime = strtotime($tomtime.' '.$sale_times[0]['time'].':00:00');
                            }
                        }

                        if($time >= $dcthetime){
                            $cuxiao = 1;
                        }else{
                            $cuxiao = 0;
                        }
                        $rushtime[] = array('time'=>$dcthetime,'end_time'=>$end_dcthetime,'cuxiao'=>$cuxiao,'show'=>0);
                    }

                    if($rushtime){
                        foreach ($rushtime as $key => $val){
                            if($time >= $val['time'] && $time < $val['end_time']){
                                $hdtime = $val['time'];
                                $end_time = $val['end_time'];
                                break;
                            }
                        }
                    }

                    // 当不在秒杀时间段时，默认选在第一个时间段
                    if(empty($hdtime) && empty($end_time)){
                        $hdtime = $rushtime[0]['time'];
                        $end_time = $rushtime[0]['end_time'];
                    }
                    /*当秒杀时间未到的时候，默认获取第一个秒杀的开始时段，此时前端显示为即将开始倒计时
                    倒计时为end_time - now_time
                    */
                    if ($time<= $rushtime[0]['time']){
                        $end_time = $rushtime[0]['time'];
                    }

                }

                if(!empty($hdtime) && !empty($end_time)){

                    $hdinfos = array('hdtime'=>$hdtime,'end_time'=>$end_time,'dqtime'=>time());
                }else{
                    $hdinfos = array();
                }

                $indexinfos = array('banner_pic'=>$banner_pic,'ad_pic_one'=>$ad_pic_one,'ad_pic_two'=>$ad_pic_two,'ad_pic_three'=>$ad_pic_three,'ad_pic_four'=>$ad_pic_four,'hdinfos'=>$hdinfos);
                $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$indexinfos);
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function companyDesc(){
        $company_desc = Db::name('news')->where('id', '32')->value('ar_content');
        $company_desc = img_add_protocal($company_desc, $this->webconfig['weburl']);


        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$company_desc[0]);
        return json($value);
    }
//    public function companyDesc(){
//        $company_desc = Db::name('config')->where('ename', 'company_desc')->value('value');
//        $company_desc = img_add_protocal($company_desc, $this->webconfig['weburl']);
//
//        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$company_desc[0]);
//        return json($value);
//    }

    public function getgoodlst(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                    $pagenum = input('post.page');

                    $webconfig = $this->webconfig;
                    $perpage = $webconfig['app_goodlst_num'];
                    $offset = ($pagenum-1)*$perpage;

                    $goodres = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.onsale',1)->where('b.open_status',1)->order(array('a.sort'=>'asc','a.zonghe_lv'=>'desc','a.id'=>'desc'))->limit($offset,$perpage)->select();
                    // dump($goodres);die;
                    if($goodres){
                        foreach ($goodres as $k =>$v){
                            $goodres[$k]['thumb_url'] = $webconfig['weburl'].'/'.$v['thumb_url'];
                            $goodres[$k]['coupon'] = 0;

                            $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                            $gongyong = new GongyongMx();
                            $activitys = $gongyong->pdrugp($ruinfo);

                            if($activitys){
                                $goodres[$k]['is_activity'] = $activitys['ac_type'];

                                if(!empty($activitys['goods_attr'])){
                                    $goods_attr_str = '';
                                    $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$activitys['goods_attr'])->where('a.goods_id',$v['id'])->where('b.attr_type',1)->select();
                                    if($gares){
                                        foreach ($gares as $key => $val){
                                            if($key == 0){
                                                $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                            }else{
                                                $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                            }
                                        }
                                        $goodres[$k]['goods_name'] = $v['goods_name'].' '.$goods_attr_str;
                                    }
                                }

                                $goodres[$k]['zs_price'] = $activitys['price'];
                            }else{
                                $goodres[$k]['is_activity'] = 0;
                                $goodres[$k]['zs_price'] = $v['min_price'];
                            }

                            if(!$activitys || in_array($activitys['ac_type'], array(1,2))){
                                //优惠券
                                $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                                if($coupons){
                                    $goodres[$k]['coupon'] = 1;
                                }
                            }
                        }
                    }

                    $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$goodres);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    // 平台客服热线
    public function getServiceHotline(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $serviceHotline = $this->webconfig['web_telephone'];
                $value = array('status'=>200,'mess'=>'获取成功','data'=>array('serviceHotline'=>$serviceHotline));
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }


    /**
     * @function获取商城首页内容
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function index(){
        //首页bannner
        $banner_pic = $this->bannerTransfer(6);

        //新品bannner
        $banner_new = $this->bannerTransfer(14)[0];

        //囤货bannner
        $banner_tun = $this->bannerTransfer(15)[0];

        //热销bannner
        $banner_hot = $this->bannerTransfer(16)[0];
        $webconfig = $this->webconfig;
        $url = $webconfig['weburl'];
        $limit = 8;
        $field = "id,goods_name,concat('$url',thumb_url) as thumb_url,case when min_price is null then market_price else min_price end as price";

        //囤货即低价商品
        $goods_onsale = GoodsModel::field($field)
            ->where(['onsale'=>1,'is_recycle'=>0])
            ->where(['is_special' => 1])
//            ->orderRaw('rand()')
            //->order('market_price','asc')
            ->order(array('sort'=>'asc','market_price'=>'asc'))
            ->limit($webconfig['tun_goods_num'])
            ->select();

        //热销商品
        $goods_hot = GoodsModel::field($field)
            ->where(['onsale'=>1,'is_recycle'=>0])->where(['is_hot' => 1])
//            ->orderRaw('rand()')
            //->order('sale_num','desc')
            ->order(array('sort'=>'asc','sale_num'=>'desc'))
            ->limit($webconfig['tun_goods_num'])
            ->select();

        //商城logo 用于登录后显示于顶部
        $logo = setMedia($webconfig['logo']);
        //秒杀商品
        //获取当前秒杀的时间段
        $sale_times = Db::name('sale_time')
            ->order('time asc')
            ->field('time')
            ->select();
        //获取当前的小时
        $time_hour = date('H');
        //默认显示第一场的商品 并且是即将
        $sDate = strtotime(date("Y-m-d ".$sale_times[0]['time'].":0:0"));
        $stime = 0;
        $next_time = null;
        $begin = null;
        foreach ($sale_times as $k=>$v){
            if ($time_hour>=$v['time'] && $time_hour < $sale_times[$k+1]['time']){
                //当前的场次
                $stime = $v['time'];
                $sDate = strtotime(date("Y-m-d $stime:0:0"));
                $next_time = $sDate;
                $begin = true;
            }
        }

        //当前无进行的场次的情况，查找下一场开始的时间
        if (!$stime){
            $next_time = $sale_times[0]['time'];
            $next_time = strtotime(date("Y-m-d $next_time:0:0"));
            $begin = false;
        }


        $rushres = Db::name('rush_activity')->alias('a')
            ->field('a.id,a.goods_id,a.goods_attr,a.price,a.num,a.sold,b.goods_name,b.thumb_url,b.shop_price,b.min_price,b.max_price,b.zs_price,b.leixing,b.shop_id')
            ->join('sp_goods b  ','a.goods_id = b.id','INNER')
            ->join('sp_shops c','a.shop_id = c.id','INNER')
            ->where('a.checked',1)
            ->where('a.recommend',1)
            ->where('a.is_show',1)
            ->where('a.start_time','lt',$sDate)
            ->where('a.end_time','gt',time())
            ->where('b.onsale',1)
            ->where('c.open_status',1)
            ->group('a.goods_id')
            ->order('a.apply_time asc')
            ->limit($webconfig['tun_goods_num'])
            ->select();

        if($rushres){
            foreach ($rushres as $kc => $vc){
                $rushres[$kc]['thumb_url'] = $webconfig['weburl'].'/'.$vc['thumb_url'];

                if($vc['goods_attr']){
                    $goods_attr_str = '';
                    $gares = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_value,a.attr_price,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$vc['goods_attr'])->where('a.goods_id',$vc['goods_id'])->where('b.attr_type',1)->select();
                    if($gares){
                        foreach ($gares as $kr => $vr){
                            if($kr == 0){
                                $goods_attr_str = $vr['attr_name'].':'.$vr['attr_value'];
                            }else{
                                $goods_attr_str = $goods_attr_str.' '.$vr['attr_name'].':'.$vr['attr_value'];
                            }
                            $rushres[$kc]['shop_price']+=$vr['attr_price'];
                        }
                        $rushres[$kc]['goods_name']=$rushres[$kc]['goods_name'].' '.$goods_attr_str;
                        $rushres[$kc]['shop_price']=sprintf("%.2f", $rushres[$kc]['shop_price']);
                    }else{
                        $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                        return json($value);
                    }
                }else{
                    if($vc['min_price'] != $vc['max_price']){
                        $rushres[$kc]['shop_price'] = $vc['min_price'].'-'.$vc['max_price'];
                    }else{
                        $rushres[$kc]['shop_price'] = $vc['min_price'];
                    }
                }
                $rushres[$kc]['yslv'] = sprintf("%.2f",$vc['sold']/$vc['num'])*100;
            }
        }
        $data = [
            'status'=>200,
            'mess'=>'获取信息成功',
            'banner' => $banner_pic,
            'banner_new' => $banner_new,
            'banner_tun' => $banner_tun,
            'banner_hot' => $banner_hot,
            'goods_onsale' => $goods_onsale,
            'goods_hot' => $goods_hot,
            'logo'  => $logo,
            'rushres' => $rushres,
            'time_arr' => [
                'next_time' => $next_time,
                'now_time'  => time(),
                'begin'     => $begin
            ],
        ];
        return json($data);
    }

    /**
     * @function获取新品上市
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function getNewGoodsList(){
        $pagenum = input('post.page');
        if (empty($pagenum) || !isset($pagenum)){
            $pagenum = 1;
        }
        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $offset = ($pagenum-1)*$perpage;
        $url = $webconfig['weburl'];
        $field = "id,goods_name,concat('$url',thumb_url) as thumb_url,case when min_price is null then market_price else min_price end as price";

        //新品上市
        $goods_new = GoodsModel::field($field)
            ->where(['onsale'=>1,'is_recycle'=>0])
            ->where(['is_new' => 1])
            ->order('addtime','desc')
            ->limit($offset,$perpage)
            ->select();

        $data = [
            'status'=>200,
            'mess'=>'获取信息成功',
            'goods_new' => $goods_new,
        ];
        return json($data);

    }

    /**
     * @function 土特产商城
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function portalMallIndex(){
        //banner部分
        $banner = $this->bannerTransfer(17);
        $banner2 = $this->bannerTransfer(23);

        $cateres = $this->cateres('348');
        $data = [
            'status'=>200,
            'mess'=>'获取信息成功',
            'banner'    => $banner,
            'banner2'    => $banner2,
            'cateres' => $cateres,

        ];
        return json($data);
    }

    /**
     * @function 免费游
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function freeTravel($cate_id = 0){
        $cate_id = input('post.cate_id');
        $cateres = $this->cateres($cate_id);
        $data = [
            'status'=>200,
            'mess'=>'获取信息成功',
            'cateres' => $cateres,

        ];
        return json($data);
    }

    public function homeCate(){
        // 所有分类
        $allCategory = Db::name('category')->where('is_show', 1)->where('recommend', 1)->where('pid', 0)->order('sort asc')->select();
        // foreach ($allCategory as $k=>&$v)
        // {
        //     $v['cate_pic'] = $this->webconfig['weburl'] . $v['cate_pic'];
        // }

        $data = [
            'status'=>200,
            'mess'=>'获取信息成功',
            'allCategory'=>$allCategory
        ];
        return json($data);
    }

    public function allCategory($pos = ''){
        $where = [];
        if($pos == 'home')
        {
            $where['recommend'] = 1;
        }

        //分类部分
        $url = $this->webconfig['weburl'];

        $cateres = Db::name('category')
            ->where('is_show',1)
            ->where('pid',0)
            ->where($where)
            ->field("id,cate_name,concat('$url',cate_pic) as cate_pic")
            ->order('sort asc')
            ->select();

        return $cateres;
    }

    public function cateres($pid = 0){
        //分类部分
        $url = $this->webconfig['weburl'];

        $cateres = Db::name('category')
            ->where('pid',$pid)
            ->where('is_show',1)
            ->field("id,cate_name,concat('$url',cate_pic) as cate_pic")
            ->order('sort asc')
            ->select();

        foreach ($cateres as $k=>&$v){
            $v['index'] = (int)($k+1);
        }

        return $cateres;
    }



    /**
     * @function轮播图获取与转换
     * @param $pos_id
     * @author Feifan.Chen <1057286925@qq.com>
     * @return array
     */
    public function bannerTransfer($pos_id){
        $ads = Db::name('ad')
            ->where('pos_id',$pos_id)
            ->where('is_on',1)
            ->select();
        $banner_pic = array();
        if($ads){
            foreach ($ads as $k=>$v){
                if ($v['ad_type'] == 1){
                    $banner_pic[] = [
                        'pic'=>$v['ad_pic'],
                        'canshu'=>$v['ad_canshu']
                    ];
                }else{
                    $banner_pic[] = Db::name('ad_pic')
                        ->where('ad_id',$v['id'])
                        ->field('pic,canshu')
                        ->order('sort asc')
                        ->select();
                }
            }
        }

        if (!empty($banner_pic)){
            //二维数组
            if ($banner_pic[0][0]){
                foreach ($banner_pic[0] as $k=>&$v){
                    $v['pic'] = setMedia($v['pic']);
                }
                $banner_pic = $banner_pic[0];
            }else{
                foreach ($banner_pic as $k=>&$v){
                    $v['pic'] = setMedia($v['pic']);
                }
            }
        }
        return $banner_pic;
    }

    public function getAccessToken(){
        $appid = '';
        $secret = '';
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$secret";
        $token = https_request($url);
//        halt($token);
        return $token['access_token'];
    }

    public function cardList(){
        $pagenum = input('post.page');
        $perpage = 10;
        $offset = ($pagenum-1)*$perpage;
        $this->test('list');
    }
    public function test($cate = null){
        $cate = is_null($cate) ? 'categories' : $cate;

        $access_token = $this->getAccessToken();
        $config = [
            'app_id' => '',
            'secret' => '',
            'response_type' => 'array',

        ];
        $factory = new \EasyWeChat\Factory();
        $app = $factory::officialAccount($config);
//        $app = ['access_token'=> $access_token];
//     echo $access_token;die;
        $object = new \EasyWeChat\OfficialAccount\Card\Client($app,null);
        $data = $object->$cate();
        halt($data);
    }
    
    public function getDownloadUrl(){
        $input = input();
        $where = [];
        if(isset($input['type']) && !empty($input['type'])){
            $where['type'] = $input['type'];
        }
        
        $info = Db::name('download_app')->where($where)->field('id, addtime', true)->order('id desc')->find();
        if(isset($info['version']) && $info['version']!=$input['version']){
            $info['update'] = true;
        }
        else{
            $info['update'] = false;
        }
        // $info['update'] = false;
        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$info);
        return json($value);
    }
}
