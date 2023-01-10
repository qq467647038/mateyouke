<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2021/12/28
 * Time: 8:40
 */

namespace app\admin\controller;

use think\Db;

class WineGoodsContract extends Common
{
    public function lst()
    {
        $list = Db::name('wine_goods_contract')
                ->where('onsale', 1)
                ->order('sort asc')->paginate(25);

        $page = $list->render();

//        $filter = input('filter');
//        echo $filter;exit;
        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('filter', 3);

        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }
    
    // 生成
    public function generate(){
        
        
        $data = [
            'goods_name'=>$info['goods_name'],
            'addtime'=>$time,
            'goods_rate'=>$info['rate'],
            'goods_thumb'=>$info['thumb_url'],
            'sale_amount'=>$post['managerWard'],
            'sale_id'=>$user_id,
            'odd'=>uniqid(),
            'sort'=>1,
            'wine_goods_id'=>$post['wine_goods_id']
        ];
        $id = Db::name('wine_order_saler')->insertGetId($data);
    }

    //处理上传图片
    public function uploadify()
    {
//        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if ($file) {
            $info = aliyunOSS($_FILES);
//            $info = $file->validate(['size' => 3145728, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'wine_goods');
            if ($info) {
//                $date = date('Ymd', time());
//                $original = 'uploads/wine_goods/' . str_replace('\\', '/', $info->getSaveName());
                $original = $info['name'];

//                $image = \think\Image::open('./' . $original);
//                $image->thumb(640, 400)->save('./' . $original, null, 90);

                $picarr = array('img_url' => $original);
                $value = array('status' => 1, 'path' => $picarr);
            } else {
                $value = array('status' => 0, 'msg' => $file->getError());
            }
        } else {
            $value = array('status' => 0, 'msg' => '文件不存在');
        }
        return json($value);
    }

    //修改特价、新品、热销、推荐
    public function gaibian()
    {
//        $shop_id = session('shop_id');
        $id = input('post.id');
        $name = input('post.name');
        if ($name && $name == 'onsale') {
            $value = input('post.value');
            if (isset($value) && in_array($value, array(0, 1))) {
                $goods = Db::name('wine_goods')->where('id', $id)->find();
                if ($goods) {
                    $data[$name] = $value;
//                    $data['shop_id'] = $shop_id;
                    $data['id'] = $id;

                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('wine_goods')->update($data);

                        if ($name == "onsale" && $value == 1) {
//                            $ymanages = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $goods['cate_id'])->find();
//                            if (!$ymanages) {
//                                Db::name('shop_management')->insert(array('shop_id' => $shop_id, 'cate_id' => $goods['cate_id']));
//                            }
//
//                            $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $goods['brand_id'])->find();
//                            if (!$yrbrands) {
//                                Db::name('shop_managebrand')->insert(array('shop_id' => $shop_id, 'brand_id' => $goods['brand_id']));
//                            }

                            ys_admin_logs('设为商品酒业上架', 'wine_goods', $id);
                        } elseif ($name == "onsale" && $value == 0) {
//                            $ymanages = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $goods['cate_id'])->find();
//                            if ($ymanages) {
//                                $good_manages = Db::name('goods')->where('shop_id', $shop_id)->where('cate_id', $goods['cate_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
//                                if (!$good_manages) {
//                                    Db::name('shop_management')->where('id', $ymanages['id'])->delete();
//                                }
//                            }
//
//                            $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $goods['brand_id'])->find();
//                            if ($yrbrands) {
//                                $good_brands = Db::name('goods')->where('shop_id', $shop_id)->where('brand_id', $goods['brand_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
//                                if (!$good_brands) {
//                                    Db::name('shop_managebrand')->where('id', $yrbrands['id'])->delete();
//                                }
//                            }
                            ys_admin_logs('设为商品酒业下架', 'wine_goods', $id);
                        }
                        // 提交事务
                        Db::commit();
                        $result = 1;
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $result = 0;
                    }
                } else {
                    $result = 0;
                }
            } else {
                $result = 0;
            }
        } else {
            $result = 0;
        }
        return json($result);
    }

    public function add()
    {
        if (request()->isPost()) {
            $data = input('post.');
//            $admin_id = session('admin_id');
            $result = $this->validate($data, 'WineContract');
            if (true !== $result) {
                $value = array('status' => 0, 'mess' => $result);
                return json($value);
            }

            // 启动事务
            Db::startTrans();
            try {
                $goods_id = Db::name('wine_goods_contract')->insertGetId(array(
                    'goods_name' => $data['goods_name'],
                    'thumb_url' => $data['pic_id'],
                    'onsale' => 1,
                    'value' => $data['value'],
                    'addtime' => time(),
                    // 'best_max_day'=>$data['best_max_day']
                ));

                // 提交事务
                Db::commit();
                ys_admin_logs('添加合约商品', 'wine_goods', $goods_id);
                $value = array('status' => 1, 'mess' => '增加成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $value = array('status' => 0, 'mess' => $e->getMessage());
            }

            return json($value);
        } else {
            return $this->fetch();
        }
    }

    public function edit()
    {
        if (request()->isPost()) {
            if (input('post.id')) {
//                $shop_id = session('shop_id');
//                $admin_id = session('admin_id');
                $data = input('post.');
                // halt($data);
//                $data['shop_id'] = $shop_id;
                $result = $this->validate($data, 'WineContract');
                if (true !== $result) {
                    $value = array('status' => 0, 'mess' => $result);
                } else {
                    $goodss = Db::name('wine_goods_contract')->where('id', input('post.id'))->find();
                    if ($goodss) {
                        if ($goodss['checked'] == 2 && $data['onsale'] == 1) {
                            $value = array('status' => 0, 'mess' => '违规商品不可上架');
                            return json($value);
                        }
//                        $categorys = Db::name('category')->where('id', $data['cate_id'])->find();
//                        if ($categorys) {
                        if (true){
//                            $child_cates = Db::name('category')->where('pid', $data['cate_id'])->find();
//                            if (!$child_cates) {
                            if (true){
//                                $shcates = Db::name('shop_cate')->where('id', $data['shcate_id'])->where('shop_id', $shop_id)->find();
//                                if ($shcates) {
                                if (true){
//                                    $child_shcates = Db::name('shop_cate')->where('pid', $data['shcate_id'])->find();
//                                    if (!$child_shcates) {
                                    if (true){
//                                        if (!empty($data['brand_id'])) {
//                                            $brands = Db::name('brand')->where('id', $data['brand_id'])->where('find_in_set(' . $data['cate_id'] . ',cate_id_list)')->field('id')->find();
//                                            if (!$brands) {
//                                                $value = array('status' => 0, 'mess' => '品牌参数错误，编辑失败');
//                                                return json($value);
//                                            }
//                                        }

//                                        $gdtypes = Db::name('type')->where('id', $data['type_id'])->find();
//                                        if ($gdtypes && $data['type_id'] == $categorys['type_id']) {
                                            if (true){
//                                            if ($goodss['shop_price'] != $data['shop_price']) {
//                                                if ($huodong) {
//                                                    switch ($huodong) {
//                                                        case 1:
//                                                            $value = array('status' => 0, 'mess' => '商品已参与秒杀活动，活动期间不允许修改商品价格');
//                                                            break;
//                                                        case 2:
//                                                            $value = array('status' => 0, 'mess' => '商品已参与团购活动，活动期间不允许修改商品价格');
//                                                            break;
//                                                        case 3:
//                                                            $value = array('status' => 0, 'mess' => '商品已参与拼团活动，活动期间不允许修改商品价格');
//                                                            break;
//                                                    }
//                                                    return json($value);
//                                                }
//                                            }

//                                            if (!empty($data['pic_id'])) {
//                                                $zssjpics = Db::name('huamu_zspic')->where('id', $data['pic_id'])->where('admin_id', $admin_id)->find();
//                                                if ($zssjpics && $zssjpics['img_url']) {
//                                                    $data['thumb_url'] = $zssjpics['img_url'];
//                                                } else {
//                                                    $data['thumb_url'] = $goodss['thumb_url'];
//                                                }
//                                            } else {
//                                                $data['thumb_url'] = $goodss['thumb_url'];
//                                            }

//                                            if (!empty($data['mp'])) {
//                                                $dengjires = Db::name('member_level')->field('id')->order('id asc')->select();
//                                                $levelres = array();
//                                                foreach ($dengjires as $gv) {
//                                                    $levelres[] = $gv['id'];
//                                                }
//
//                                                $yzlevels = array();
//                                            }

//                                            $good_types = Db::name('type')->where('id', $data['type_id'])->field('id')->find();
//                                            if ($good_types) {
                                                if (true){
//                                                if ($goodss['type_id'] != $data['type_id']) {
//                                                    if ($huodong) {
//                                                        switch ($huodong) {
//                                                            case 1:
//                                                                $value = array('status' => 0, 'mess' => '商品已参与秒杀活动，活动期间不允许修改商品类型');
//                                                                break;
//                                                            case 2:
//                                                                $value = array('status' => 0, 'mess' => '商品已参与团购活动，活动期间不允许修改商品类型');
//                                                                break;
//                                                            case 3:
//                                                                $value = array('status' => 0, 'mess' => '商品已参与拼团活动，活动期间不允许修改商品类型');
//                                                                break;
//                                                        }
//                                                        return json($value);
//                                                    }
//                                                }

//                                                $goodattr_idres = Db::name('goods_attr')->alias('a')->field('a.*')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goodss['id'])->where('b.attr_type', 1)->select();
//                                                $goodsattrids = array();
//                                                if ($goodattr_idres) {
//                                                    foreach ($goodattr_idres as $kdr => $vdr) {
//                                                        $goodsattrids[$vdr['attr_id']][] = $vdr;
//                                                    }
//                                                }
//
//                                                $typeattrs = Db::name('attr')->where('type_id', $good_types['id'])->where('attr_type', 1)->find();
//                                                if ($typeattrs && empty($data['goods_attr'])) {
//                                                    $value = array('status' => 0, 'mess' => '缺少商品规格属性，增加失败');
//                                                    return json($value);
//                                                }
//
//                                                if (!empty($data['goods_attr'])) {
//                                                    $goodattres = $data['goods_attr'];
//                                                } else {
//                                                    $goodattres = '';
//                                                }
//
//                                                if ($goodattres && !is_array($goodattres)) {
//                                                    $value = array('status' => 0, 'mess' => '商品规格属性错误，增加失败');
//                                                    return json($value);
//                                                }

//                                                if ($goodattres) {
                                                    if (true){
//                                                    foreach ($goodattres as $yzkey => $yzval) {
//                                                        $yzshuxing = Db::name('attr')->where('id', $yzkey)->where('type_id', $data['type_id'])->find();
//                                                        if ($yzshuxing) {
//                                                            if ($yzshuxing['attr_type'] == 1) {
//                                                                if (!empty($yzval['attr_value']) && is_array($yzval['attr_value'])) {
//                                                                    if (count($yzval['attr_value']) != count(array_unique($yzval['attr_value']))) {
//                                                                        $value = array('status' => 0, 'mess' => '存在重复的属性值，增加失败');
//                                                                        return json($value);
//                                                                    } else {
//                                                                        for ($i = 0; $i < count($yzval['attr_value']); $i++) {
//                                                                            if (!empty(trim($yzval['attr_value'][$i]))) {
//                                                                                if (!isset($yzval['attr_price'][$i]) || !preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", trim($yzval['attr_price'][$i]))) {
//                                                                                    $value = array('status' => 0, 'mess' => '存在属性价格为空或参数错误，增加失败');
//                                                                                    return json($value);
//                                                                                }
//
//                                                                                if (!empty($yzval['id'][$i]) && $goodsattrids) {
//                                                                                    if (!empty($goodsattrids[$yzkey])) {
//                                                                                        foreach ($goodsattrids[$yzkey] as $vga) {
//                                                                                            if ($vga['id'] == $yzval['id'][$i]) {
//                                                                                                if (trim($yzval['attr_value'][$i]) != $vga['attr_value'] || trim($yzval['attr_price'][$i]) != $vga['attr_price']) {
//                                                                                                    if ($huodong) {
//                                                                                                        switch ($huodong) {
//                                                                                                            case 1:
//                                                                                                                $value = array('status' => 0, 'mess' => '商品已参与秒杀活动，活动期间不允许修改商品规格属性及价格');
//                                                                                                                break;
//                                                                                                            case 2:
//                                                                                                                $value = array('status' => 0, 'mess' => '商品已参与团购活动，活动期间不允许修改商品规格属性及价格');
//                                                                                                                break;
//                                                                                                            case 3:
//                                                                                                                $value = array('status' => 0, 'mess' => '商品已参与拼团活动，活动期间不允许修改商品规格属性及价格');
//                                                                                                                break;
//                                                                                                        }
//                                                                                                        return json($value);
//                                                                                                    }
//                                                                                                }
//                                                                                            }
//                                                                                        }
//                                                                                    }
//                                                                                }
//                                                                            } else {
//                                                                                $value = array('status' => 0, 'mess' => '存在属性值为空，增加失败');
//                                                                                return json($value);
//                                                                            }
//                                                                        }
//                                                                    }
//                                                                } else {
//                                                                    $value = array('status' => 0, 'mess' => '存在属性值参数错误，增加失败');
//                                                                    return json($value);
//                                                                }
//                                                            } elseif ($yzshuxing['attr_type'] == 0) {
//                                                                if (!empty($yzval['attr_value']) && !is_array($yzval['attr_value'])) {
//                                                                    if ($yzshuxing['attr_values']) {
//                                                                        if (strpos(',' . $yzshuxing['attr_values'] . ',', ',' . $yzval['attr_value'] . ',') === false) {
//                                                                            $value = array('status' => 0, 'mess' => '存在属性值错误，增加失败');
//                                                                            return json($value);
//                                                                        }
//                                                                    }
//                                                                }
//                                                            }
//                                                        } else {
//                                                            $value = array('status' => 0, 'mess' => '属性参数错误，增加失败');
//                                                            return json($value);
//                                                        }
//                                                    }
                                                }
                                            } else {
                                                $value = array('status' => 0, 'mess' => '商品类型参数错误，增加失败');
                                                return json($value);
                                            }

//                                            if (!empty($data['ypic_id'])) {
//                                                $count1 = Db::name('goods_pic')->where('goods_id', $data['id'])->where('id', 'in', $data['ypic_id'])->count();
//                                            } else {
//                                                $count1 = 0;
//                                            }

//                                            if (!empty($data['picres_id'])) {
//                                                $count2 = count($data['picres_id']);
//                                            } else {
//                                                $count2 = 0;
//                                            }
//
//                                            $countnum = $count1 + $count2;
//
//                                            $webconfig = $this->webconfig;

//                                            if ($countnum <= $webconfig['goodsimg_maxnum']) {

                                                if (true){
//                                                if (!empty($data['fuwu'])) {
//                                                    $fuwures = $data['fuwu'];
//                                                    if (is_array($fuwures)) {
//                                                        foreach ($fuwures as $vur) {
//                                                            $sertions = Db::name('sertion')->where('id', $vur)->where('is_show', 1)->find();
//                                                            if (!$sertions) {
//                                                                $value = array('status' => 0, 'mess' => '所选服务项信息错误');
//                                                                return json($value);
//                                                            }
//                                                        }
//                                                        $data['fuwu'] = implode(',', $fuwures);
//                                                    } else {
//                                                        $value = array('status' => 0, 'mess' => '所选服务项参数错误');
//                                                        return json($value);
//                                                    }
//                                                } else {
//                                                    $data['fuwu'] = '';
//                                                }


//                                                $data['search_keywords'] = str_replace('，', ',', $data['search_keywords']);

                                                // 启动事务
                                                Db::startTrans();
                                                try {
                                                    $update = array(
                                                        'id' => $data['id'],
                                                        'goods_name' => $data['goods_name'],
                                                        'onsale' => 1,
                                                        'value' => $data['value'],
                                                        'deposit' => $data['deposit'],
                                                        'addtime' => time(),
                                                        'day' => $data['day'],
                                                        // 'best_max_day'=>$data['best_max_day']
                                                    );
                                                    if (!empty($data['pic_id'])){
                                                        $update['thumb_url'] = $data['pic_id'];
                                                    }
                                                    Db::name('wine_goods_contract')->update($update);

                                                    $goods_id = $data['id'];


                                                    Db::commit();

                                                    ys_admin_logs('编辑合约商品', 'goods', $data['id']);
                                                    $value = array('status' => 1, 'mess' => '编辑成功');
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                    $value = array('status' => 0, 'mess' => '编辑失败'.$e->getMessage());
                                                }
                                            } else {
                                                $value = array('status' => 0, 'mess' => '商品图片最多上传1张');
                                            }
                                        } else {
                                            $value = array('status' => 0, 'mess' => '商品类型参数错误，编辑失败');
                                        }
                                    } else {
                                        $value = array('status' => 0, 'mess' => '店铺分类存在下级分类，编辑失败');
                                    }
                                } else {
                                    $value = array('status' => 0, 'mess' => '店铺分类参数错误');
                                }
                            } else {
                                $value = array('status' => 0, 'mess' => '商品分类存在下级分类，编辑失败');
                            }
                        } else {
                            $value = array('status' => 0, 'mess' => '参数错误');
                        }
                    } else {
                        $value = array('status' => 0, 'mess' => '找不到相关信息，编辑失败');
                    }
                }
            } else {
                $value = array('status' => 0, 'mess' => '缺少参数，编辑失败');
            }
            return json($value);
        } else {
            if (input('id')) {
//                $shop_id = session('shop_id');
//                $admin_id = session('admin_id');
                $goodss = Db::name('wine_goods_contract')->where('id', input('id'))->find();
                if ($goodss) {
                    $this->assign('goodss', $goodss);
                    return $this->fetch();
                } else {
                    $this->error('找不到相关信息');
                }
            } else {
                $this->error('缺少参数');
            }
        }
    }
}
