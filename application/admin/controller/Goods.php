<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Db;

class Goods extends Common
{
    public function lst()
    {
        $shop_id = session('shop_id');

        $filter = input('filter');
        if (!$filter || !in_array($filter, array(1, 2, 3))) {
            $filter = 3;
        }

        $where = array();
        $where['a.shop_id'] = $shop_id;
        $where['a.is_recycle'] = 0;
        switch ($filter) {
            case 1:
                $where['a.onsale'] = 1;
                break;
            case 2:
                $where['a.onsale'] = 0;
                break;
            case 3:

                break;
        }


        $list = Db::name('goods')->alias('a')->field('a.type,a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name,vip_price, a.zkj')->join('sp_category b', 'a.cate_id = b.id', 'LEFT')->where($where)->order('a.id desc')->paginate(25);

        $page = $list->render();
        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
        $brandres = Db::name('brand')->field('id,brand_name')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('filter', $filter);
        $this->assign('cateres', recursive($cateres));
        $this->assign('brandres', $brandres);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }

    public function catelist()
    {
        if (input('cate_id')) {
            $cid = input('cate_id');
            $filter = input('filter');
            if (!$filter || !in_array($filter, array(1, 2, 3))) {
                $filter = 3;
            }

            $shop_id = session('shop_id');

            $where = array();
            $where['a.shop_id'] = $shop_id;
            $where['a.is_recycle'] = 0;
            switch ($filter) {
                case 1:
                    $where['a.onsale'] = 1;
                    break;
                case 2:
                    $where['a.onsale'] = 0;
                    break;
                case 3:

                    break;
            }


            $cate_name = Db::name('category')->where('id', $cid)->value('cate_name');
            $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
            $cateId = array();
            $cateId = get_all_child($cateres, $cid);
            $cateId[] = $cid;
            $cateId = implode(',', $cateId);
            $where['a.cate_id'] = array('in', $cateId);
            $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.market_price,a.shop_price,a.onsale,b.cate_name,c.brand_name')->join('sp_category b', 'a.cate_id = b.id', 'LEFT')->join('sp_brand c', 'a.brand_id = c.id', 'LEFT')->where($where)->order('a.addtime desc')->paginate(25);
            $page = $list->render();

            $brandres = Db::name('brand')->field('id,brand_name')->select();

            if (input('page')) {
                $pnum = input('page');
            } else {
                $pnum = 1;
            }

            $this->assign('cate_id', $cid);
            $this->assign('cate_name', $cate_name);
            $this->assign('list', $list);
            $this->assign('page', $page);
            $this->assign('pnum', $pnum);
            $this->assign('filter', $filter);
            $this->assign('cateres', recursive($cateres));
            $this->assign('brandres', $brandres);
            if (request()->isAjax()) {
                return $this->fetch('ajaxpage');
            } else {
                return $this->fetch('lst');
            }
        } else {
            $this->error('缺少参数');
        }
    }

    //商品回收站
    public function hslst()
    {
        $where = array();
        $where['a.shop_id'] = session('shop_id');
        $where['a.is_recycle'] = 1;
        $where['a.onsale'] = 0;
        $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name')->join('sp_category b', 'a.cate_id = b.id', 'LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
        $brandres = Db::name('brand')->field('id,brand_name')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('cateres', recursive($cateres));
        $this->assign('brandres', $brandres);
        if (request()->isAjax()) {
            return $this->fetch('hsajaxpage');
        } else {
            return $this->fetch('hslst');
        }
    }

    //修改特价、新品、热销、推荐
    public function gaibian()
    {
        $shop_id = session('shop_id');
        $id = input('post.id');
        $name = input('post.name');
        if ($name && $name == 'onsale') {
            $value = input('post.value');
            if (isset($value) && in_array($value, array(0, 1))) {
                $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shop_id)->where('is_recycle', 0)->where('checked', 1)->field('id,cate_id,brand_id')->find();
                if ($goods) {
                    $data[$name] = $value;
                    $data['shop_id'] = $shop_id;
                    $data['id'] = $id;

                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('goods')->update($data);

                        if ($name == "onsale" && $value == 1) {
                            $ymanages = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $goods['cate_id'])->find();
                            if (!$ymanages) {
                                Db::name('shop_management')->insert(array('shop_id' => $shop_id, 'cate_id' => $goods['cate_id']));
                            }

                            $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $goods['brand_id'])->find();
                            if (!$yrbrands) {
                                Db::name('shop_managebrand')->insert(array('shop_id' => $shop_id, 'brand_id' => $goods['brand_id']));
                            }

                            ys_admin_logs('设为上架', 'goods', $id);
                        } elseif ($name == "onsale" && $value == 0) {
                            $ymanages = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $goods['cate_id'])->find();
                            if ($ymanages) {
                                $good_manages = Db::name('goods')->where('shop_id', $shop_id)->where('cate_id', $goods['cate_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                                if (!$good_manages) {
                                    Db::name('shop_management')->where('id', $ymanages['id'])->delete();
                                }
                            }

                            $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $goods['brand_id'])->find();
                            if ($yrbrands) {
                                $good_brands = Db::name('goods')->where('shop_id', $shop_id)->where('brand_id', $goods['brand_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                                if (!$good_brands) {
                                    Db::name('shop_managebrand')->where('id', $yrbrands['id'])->delete();
                                }
                            }
                            ys_admin_logs('设为下架', 'goods', $id);
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

    //设置默认商品属性
    public function progaibian()
    {
        $shop_id = session('shop_id');
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $goods_id = input('post.goods_id');
        $goods = Db::name('goods')->where('id', $goods_id)->where('shop_id', $shop_id)->where('is_recycle', 0)->field('id')->find();
        if ($goods) {
            $products = Db::name('product')->where('id', $id)->where('goods_id', $goods_id)->find();
            if ($products) {
                if ($value == 1) {
                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('product')->where('goods_id', $goods_id)->where('def', 1)->update(array('def' => 0));
                        Db::name('product')->where('id', $id)->where('goods_id', $goods_id)->update(array('def' => 1));
                        // 提交事务
                        Db::commit();
                        ys_admin_logs('设为默认商品库存', 'product', $id);
                        $result = 1;
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $result = 0;
                    }
                } elseif ($value == 0) {
                    $count = Db::name('product')->where('id', $id)->where('goods_id', $goods_id)->update(array('def' => 0));
                    if ($count > 0) {
                        ys_admin_logs('撤销默认商品库存', 'product', $id);
                        $result = 1;
                    } else {
                        $result = 0;
                    }
                }
            } else {
                $result = 0;
            }
        } else {
            $result = 0;
        }
        return json($result);
    }

    //处理上传图片
    public function uploadify()
    {
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
        return json($value);
    }

    //手动删除未保存的上传图片手机
    public function delfile()
    {
        if (input('post.zspic_id')) {
            $admin_id = session('admin_id');
            $zspic_id = input('post.zspic_id');
            $pics = Db::name('huamu_zspic')->where('id', $zspic_id)->where('admin_id', $admin_id)->find();
            if ($pics && $pics['img_url']) {
                $count = Db::name('huamu_zspic')->where('id', $pics['id'])->update(array('img_url' => ''));
                if ($count > 0) {
                    if ($pics['img_url'] && file_exists('./' . $pics['img_url'])) {
                        @unlink('./' . $pics['img_url']);
                    }
                    $value = 1;
                } else {
                    $value = 0;
                }
            } else {
                $value = 0;
            }
        } else {
            $value = 0;
        }
        return json($value);
    }

    //处理上传属性图片
    public function uploadifyattr()
    {
        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if ($file) {
            $info = $file->validate(['size' => 3145728, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'goodsattr_pic');
            if ($info) {
                $original = 'uploads/goodsattr_pic/' . $info->getSaveName();
                $image = \think\Image::open('./' . $original);
                $image->thumb(640, 400)->save('./' . $original, null, 90);
                $zspic_id = Db::name('ptadmin_zsattrpic')->insertGetId(array('admin_id' => $admin_id, 'img_url' => $original));
                $picarr = array('img_url' => $original, 'pic_id' => $zspic_id);
                $value = array('status' => 1, 'path' => $picarr);
            } else {
                $value = array('status' => 0, 'msg' => $file->getError());
            }
        } else {
            $value = array('status' => 0, 'msg' => '文件不存在');
        }
        return json($value);
    }

    //手动删除未保存的上传图片
    public function delattrfile()
    {
        if (input('post.zspic_id')) {
            $admin_id = session('admin_id');
            $zspic_id = input('post.zspic_id');
            $pics = Db::name('ptadmin_zsattrpic')->where('id', $zspic_id)->where('admin_id', $admin_id)->find();
            if ($pics && $pics['img_url']) {
                $count = Db::name('ptadmin_zsattrpic')->delete($pics['id']);
                if ($count > 0) {
                    if ($pics['img_url'] && file_exists('./' . $pics['img_url'])) {
                        if (unlink('./' . $pics['img_url'])) {
                            $value = 1;
                        } else {
                            $value = 0;
                        }
                    } else {
                        $value = 0;
                    }
                } else {
                    $value = 0;
                }
            } else {
                $value = 0;
            }
        } else {
            $value = 0;
        }
        return json($value);
    }

    //处理多图片上传
    public function uploadifys()
    {
        $admin_id = session('admin_id');
        $shop_id = session('shop_id');
        $webconfig = $this->webconfig;

        $nupload = Db::name('huamu_zsduopic')->where('admin_id', $admin_id)->count();
        if (input('post.goods_id')) {
            $goods = Db::name('goods')->where('id', input('post.goods_id'))->where('shop_id', $shop_id)->where('is_recycle', 0)->find();
            if ($goods) {
                $ycount = Db::name('goods_pic')->where('goods_id', input('post.goods_id'))->count();
                $uploadcount = $nupload + $ycount;
            } else {
                $value = array('status' => 0, 'msg' => '上传失败');
                return json($value);
            }
        } else {
            $uploadcount = $nupload;
        }

        if ($uploadcount >= $webconfig['goodsimg_maxnum']) {
            $value = array('status' => 0, 'msg' => '商品图片最多上传' . $webconfig['goodsimg_maxnum'] . '张');
            return json($value);
        }

        $file = request()->file('filedata');
        if ($file) {
            $info = aliyunOSS($_FILES);
//            $info = $file->validate(['size' => 3145728, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'goods_pic');
            if ($info) {
//                $original = 'uploads/goods_pic/' . str_replace('\\', '/', $info->getSaveName());
//                $image = \think\Image::open('./' . $original);
//                $image->thumb(640, 400)->save('./' . $original, null, 90);
                $original = $info['name'];
                $pic_id = Db::name('huamu_zsduopic')->insertGetId(array('img_url' => $original, 'admin_id' => $admin_id));
                $picarr = array('pic_url' => $original, 'id' => $pic_id);
//                $picarr = array('pic_url' => $original);
                $value = array('status' => 1, 'path' => $picarr);
            } else {
                $value = array('status' => 0, 'msg' => $file->getError());
            }
        } else {
            $value = array('status' => 0, 'msg' => '文件不存在');
        }
        return json($value);
    }

    //手动删除批量上传未提交的图片
    public function deletefile()
    {
        if (input('post.pic_id')) {
            $pic_id = input('post.pic_id');
            $admin_id = session('admin_id');
            $img_url = Db::name('huamu_zsduopic')->where('id', $pic_id)->where('admin_id', $admin_id)->value('img_url');
            if ($img_url) {
                $count = Db::name('huamu_zsduopic')->delete($pic_id);
                if ($count > 0) {
//                    if ($img_url && file_exists('./' . $img_url)) {
//                        if (unlink('./' . $img_url)) {
//                            $value = 1;
//                        } else {
//                            $value = 0;
//                        }
//                    } else {
//                        $value = 0;
//                    }
                    $value = 1;
                } else {
                    $value = 0;
                }
            } else {
                $value = 0;
            }
        } else {
            $value = 0;
        }
        return json($value);
    }

    public function deleteone()
    {
        $shop_id = session('shop_id');

        if (input('post.ypic_id') && input('post.goods_id')) {
            $goods = Db::name('goods')->where('id', input('post.goods_id'))->where('shop_id', $shop_id)->field('id')->find();
            if ($goods) {
                $pics = Db::name('goods_pic')->where('id', input('post.ypic_id'))->where('goods_id', input('post.goods_id'))->field('id,img_url')->find();
                if ($pics) {
                    $count = Db::name('goods_pic')->delete(input('post.ypic_id'));
                    if ($count > 0) {
                        if (!empty($pics['img_url']) && file_exists('./' . $pics['img_url'])) {
                            @unlink('./' . $pics['img_url']);
                        }
                        $value = 1;
                    } else {
                        $value = 0;
                    }
                } else {
                    $value = 0;
                }
            } else {
                $value = 0;
            }
        } else {
            $value = 0;
        }
        return json($value);
    }

    public function add()
    {
        if (request()->isPost()) {
            $shop_id = session('shop_id');
            $admin_id = session('admin_id');
            $data = input('post.');
            $data['is_free'] = 1;
            $data['is_special'] = 0;
            $data['is_new'] = 0;
            $data['vip_price'] = 0;
            $data['distribute_price'] = 0;
            $data['brand_id'] = 0;
            $data['shop_id'] = $shop_id;
            $result = $this->validate($data, 'Goods');
            if (true !== $result) {
                $value = array('status' => 0, 'mess' => $result);
                return json($value);
            }
            $categorys = Db::name('category')->where('id', $data['cate_id'])->find();
            if (!$categorys) {
                $value = array('status' => 0, 'mess' => '商品分类参数错误');
                return $value;
            }
            $child_cates = Db::name('category')->where('pid', $data['cate_id'])->find();
            if ($child_cates) {
                $value = array('status' => 0, 'mess' => '商品分类存在下级分类，新增失败');
                return json($value);
            }
            $shcates = Db::name('shop_cate')->where('id', $data['shcate_id'])->where('shop_id', $shop_id)->find();
            if (!$shcates) {
                $value = array('status' => 0, 'mess' => '店铺分类参数错误');
                return json($value);
            }
            $child_shcates = Db::name('shop_cate')->where('pid', $data['shcate_id'])->find();
            if ($child_shcates) {
                $value = array('status' => 0, 'mess' => '所选店铺分类存在下级分类，新增失败');
                return json($value);
            }
            if (!empty($data['brand_id'])) {
                $brands = Db::name('brand')->where('id', $data['brand_id'])->where('find_in_set(' . $data['cate_id'] . ',cate_id_list)')->field('id')->find();
                if (!$brands) {
                    $value = array('status' => 0, 'mess' => '品牌参数错误，新增失败');
                    return json($value);
                }
            }


            if (!empty($data['pic_id'])) {
                $data['thumb_url'] = $data['pic_id'];
            } else {
                $value = array('status' => 0, 'mess' => '请上传缩略图');
                return json($value);
            }

            /*if(!empty($data['mp'])){
                     $dengjires = Db::name('member_level')->field('id')->order('id asc')->select();
                     $levelres = array();
                     foreach ($dengjires as $gv){
                     $levelres[] = $gv['id'];
                     }

                     $yzlevels = array();
                     foreach ($data['mp'] as $uk => $uv){
                     if(in_array($uk, $levelres)){
                     if(isset($uv) && trim($uv) == 0){
                     $value = array('status'=>0,'mess'=>'会员价格不能为0元，增加失败');
                     return json($value);
                     }
                     if(!isset($uv) || !preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", trim($uv))){
                     $value = array('status'=>0,'mess'=>'会员价格存在为空或格式错误，增加失败');
                     return json($value);
                     }
                     $yzlevels[] = $uk;
                     }else{
                     $value = array('status'=>0,'mess'=>'会员价格等级参数错误，增加失败');
                     return json($value);
                     }
                     }
                     if($yzlevels != $levelres){
                     $value = array('status'=>0,'mess'=>'会员价格不完整，增加失败');
                     return json($value);
                     }
                    }*/


            $good_types = Db::name('type')->where('id', $data['type_id'])->find();
            if (!$good_types || $data['type_id'] != $categorys['type_id']) {
                $value = array('status' => 0, 'mess' => '商品类型参数错误，新增失败');
                return json($value);
            }

            $typeattrs = Db::name('attr')->where('type_id', $good_types['id'])->where('attr_type', 1)->find();
            if ($typeattrs && empty($data['goods_attr'])) {
                $value = array('status' => 0, 'mess' => '缺少商品规格属性，增加失败');
                return json($value);
            }

            if (!empty($data['goods_attr'])) {
                $goodattres = $data['goods_attr'];
            } else {
                $goodattres = '';
            }

            if ($goodattres && !is_array($goodattres)) {
                $value = array('status' => 0, 'mess' => '商品规格属性错误，增加失败');
                return json($value);
            }

            if ($goodattres) {
                foreach ($goodattres as $yzkey => $yzval) {
                    $yzshuxing = Db::name('attr')->where('id', $yzkey)->where('type_id', $data['type_id'])->find();
                    if ($yzshuxing) {
                        if ($yzshuxing['attr_type'] == 1) {
                            if (!empty($yzval['attr_value']) && is_array($yzval['attr_value'])) {
                                if (count($yzval['attr_value']) != count(array_unique($yzval['attr_value']))) {
                                    $value = array('status' => 0, 'mess' => '存在重复的属性值，增加失败');
                                    return json($value);
                                } else {
                                    for ($i = 0; $i < count($yzval['attr_value']); $i++) {
                                        if (!empty(trim($yzval['attr_value'][$i]))) {
                                            if (!isset($yzval['attr_price'][$i]) || !preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", trim($yzval['attr_price'][$i]))) {
                                                $value = array('status' => 0, 'mess' => '存在属性价格为空或参数错误，增加失败');
                                                return json($value);
                                            }
                                        } else {
                                            $value = array('status' => 0, 'mess' => '存在属性值为空，增加失败');
                                            return json($value);
                                        }
                                    }
                                }
                            } else {
                                $value = array('status' => 0, 'mess' => '存在属性值参数错误，增加失败');
                                return json($value);
                            }
                        } elseif ($yzshuxing['attr_type'] == 0) {
                            if (!empty($yzval['attr_value']) && !is_array($yzval['attr_value'])) {
                                if ($yzshuxing['attr_values']) {
                                    if (strpos(',' . $yzshuxing['attr_values'] . ',', ',' . $yzval['attr_value'] . ',') === false) {
                                        $value = array('status' => 0, 'mess' => '存在属性值错误，增加失败');
                                        return json($value);
                                    }
                                }
                            }
                        }
                    } else {
                        $value = array('status' => 0, 'mess' => '属性参数错误，增加失败');
                        return json($value);
                    }
                }
            }


            if (!empty($data['picres_id'])) {
                $countnum = count($data['picres_id']);
                $webconfig = $this->webconfig;
                if ($countnum > $webconfig['goodsimg_maxnum']) {
                    $value = array('status' => 0, 'mess' => '商品图片最多上传' . $webconfig['goodsimg_maxnum'] . '张');
                    return json($value);
                }
            }

            if (!empty($data['fuwu'])) {
                $fuwures = $data['fuwu'];
                if (is_array($fuwures)) {
                    foreach ($fuwures as $vur) {
                        $sertions = Db::name('sertion')->where('id', $vur)->where('is_show', 1)->find();
                        if (!$sertions) {
                            $value = array('status' => 0, 'mess' => '所选服务项信息错误');
                            return json($value);
                        }
                    }
                    $data['fuwu'] = implode(',', $fuwures);
                } else {
                    $value = array('status' => 0, 'mess' => '所选服务项参数错误');
                    return json($value);
                }
            } else {
                $data['fuwu'] = '';
            }

            $data['search_keywords'] = str_replace('，', ',', $data['search_keywords']);

            // 启动事务
            Db::startTrans();
            try {
                $goods_id = Db::name('goods')->insertGetId(array(
                    'fictitious_sale_num' => $data['fictitious_sale_num'],
                    'zkj' => $data['zkj'],
                    'type' => $data['type'],
                    'goods_name' => $data['goods_name'],
                    'thumb_url' => $data['thumb_url'],
                    'market_price' => $data['market_price'],
                    'shop_price' => $data['shop_price'],
                    'onsale' => $data['onsale'],
                    'cate_id' => $data['cate_id'],
                    'brand_id' => $data['brand_id'],
                    'type_id' => $data['type_id'],
                    'search_keywords' => $data['search_keywords'],
                    'goods_desc' => $data['goods_desc'],
                    'keywords' => $data['keywords'],
                    'sort' => $data['sort'],
                    'goods_brief' => $data['goods_brief'],
                    'fuwu' => $data['fuwu'],
                    'addtime' => time(),
                    'is_free' => $data['is_free'],
                    'is_recycle' => $data['is_recycle'],
                    'is_special' => $data['is_special'],
                    'is_new' => $data['is_new'],
                    'is_hot' => $data['is_hot'],
                    'shcate_id' => $data['shcate_id'],
                    'is_recommend' => $data['is_recommend'],
                    'checked' => 1,
                    'leixing' => 1,
                    'shop_id' => $data['shop_id'],
                    'vip_price' => $data['vip_price'],
                    'distribute_price' => $data['distribute_price'],
                ));

                if ($goods_id) {
                    //添加会员价格
                    /*if(!empty($data['mp'])){
                     foreach ($data['mp'] as $k => $v){
                     $v = trim($v);
                     Db::name('member_price')->insert(array(
                     'price'=>$v,
                     'level_id'=>$k,
                     'goods_id'=>$goods_id
                     ));
                     }
                     }*/

                    //添加商品图片
                    if (!empty($data['picres_id'])) {
                        $sort2 = $data['sort2'];
                        foreach ($data['picres_id'] as $key => $val) {
                            $img_url = Db::name('huamu_zsduopic')->where('id', $val)->where('admin_id', $admin_id)->value('img_url');
                            if ($img_url) {
                                if (empty($sort2[$key])) {
                                    $sort2[$key] = 0;
                                }
                                Db::name('goods_pic')->insert(array('img_url' => $img_url, 'sort' => $sort2[$key], 'goods_id' => $goods_id));
                            }
                        }
                    }

                    //添加商品属性
                    if (!empty($data['goods_attr'])) {
                        $goods_attr = $data['goods_attr'];
                    } else {
                        $goods_attr = '';
                    }

                    if ($goods_attr) {
                        foreach ($goods_attr as $key2 => $val2) {
                            $attrshuxing = Db::name('attr')->where('id', $key2)->where('type_id', $data['type_id'])->find();
                            if ($attrshuxing['attr_type'] == 1) {
                                if (!empty($val2['attr_value']) && is_array($val2['attr_value'])) {
                                    for ($i = 0; $i < count($val2['attr_value']); $i++) {
                                        if (!empty(trim($val2['attr_value'][$i]))) {
                                            if ($attrshuxing['is_upload'] == 1 && !empty($val2['attrpic_id'][$i])) {
                                                $attr_pic = Db::name('ptadmin_zsattrpic')->where('id', $val2['attrpic_id'][$i])->value('img_url');
                                            } else {
                                                $attr_pic = '';
                                            }
                                            Db::name('goods_attr')->insert(array('attr_id' => $key2, 'attr_value' => trim($val2['attr_value'][$i]), 'attr_price' => trim($val2['attr_price'][$i]), 'attr_pic' => $attr_pic, 'goods_id' => $goods_id));
                                        }
                                    }
                                }
                            } elseif ($attrshuxing['attr_type'] == 0) {
                                if (!empty($val2['attr_value']) && !is_array($val2['attr_value'])) {
                                    if (!empty(trim($val2['attr_value']))) {
                                        Db::name('goods_attr')->insert(array('attr_id' => $key2, 'attr_value' => trim($val2['attr_value']), 'goods_id' => $goods_id));
                                    }
                                }
                            }

                            /*if(!empty($val2['attr_value']) && is_array($val2['attr_value'])){
                             for($i=0; $i<count($val2['attr_value']); $i++){
                             if(!empty(trim($val2['attr_value'][$i]))){
                             Db::name('goods_attr')->insert(array('attr_id'=>$key2,'attr_value'=>trim($val2['attr_value'][$i]),'attr_price'=>trim($val2['attr_price'][$i]),'goods_id'=>$goods_id));
                             }
                             }
                             }elseif(!empty($val2['attr_value']) && !is_array($val2['attr_value'])){
                             if(!empty(trim($val2['attr_value']))){
                             Db::name('goods_attr')->insert(array('attr_id'=>$key2,'attr_value'=>trim($val2['attr_value']),'goods_id'=>$goods_id));
                             }
                             }*/
                        }
                    }

                    //更新商品属性集合
                    $goods_shuxing = Db::name('goods_attr')->where('goods_id', $goods_id)->field('attr_id,attr_value')->select();
                    if ($goods_shuxing) {
                        $shuxings = '';
                        foreach ($goods_shuxing as $kcv => $gcv) {
                            if ($kcv == 0) {
                                $shuxings = $gcv['attr_id'] . ':' . $gcv['attr_value'];
                            } else {
                                $shuxings = $shuxings . ',' . $gcv['attr_id'] . ':' . $gcv['attr_value'];
                            }
                        }
                        Db::name('goods')->update(array('id' => $goods_id, 'shuxings' => $shuxings));
                    }

                    $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,a.attr_price')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goods_id)->where('b.attr_type', 1)->select();
                    if ($radiores) {
                        $radioattr = array();
                        foreach ($radiores as $cv) {
                            $radioattr[$cv['attr_id']][] = $cv;
                        }

                        $radioprice = array();
                        foreach ($radioattr as $zk => $zv) {
                            foreach ($zv as $zval) {
                                $radioprice[$zk][] = $zval['attr_price'];
                            }
                        }

                        $min_attr_price = 0;
                        $max_attr_price = 0;

                        foreach ($radioprice as $rv) {
                            $min_attr_price += min($rv);
                            $max_attr_price += max($rv);
                        }

                        $min_shop_price = $data['shop_price'] + $min_attr_price;
                        $max_shop_price = $data['shop_price'] + $max_attr_price;
                        $min_market_price = $data['market_price'] + $min_attr_price;
                        $max_market_price = $data['market_price'] + $max_attr_price;
                    } else {
                        $min_shop_price = $data['shop_price'];
                        $max_shop_price = $data['shop_price'];
                        $min_market_price = $data['market_price'];
                        $max_market_price = $data['market_price'];
                    }

                    Db::name('goods')->update(array('min_market_price' => $min_market_price, 'max_market_price' => $max_market_price, 'min_price' => $min_shop_price, 'max_price' => $max_shop_price, 'zs_price' => $min_shop_price, 'id' => $goods_id));

                    $managements = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $data['cate_id'])->find();
                    if (!$managements) {
                        Db::name('shop_management')->insert(array('shop_id' => $shop_id, 'cate_id' => $data['cate_id']));
                    }

                    $managebrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $data['brand_id'])->find();
                    if (!$managebrands) {
                        Db::name('shop_managebrand')->insert(array('shop_id' => $shop_id, 'brand_id' => $data['brand_id']));
                    }

                    Db::name('shops')->where('id', $shop_id)->setInc('goods_num', 1);
                }

                // 提交事务
                Db::commit();
                if ($zssjpics && $zssjpics['img_url']) {
                    Db::name('huamu_zspic')->where('id', $zssjpics['id'])->update(array('img_url' => ''));
                }

                $zsattrpics = Db::name('ptadmin_zsattrpic')->where('admin_id', $admin_id)->field('id,img_url')->select();
                if ($zsattrpics) {
                    foreach ($zsattrpics as $v) {
                        Db::name('ptadmin_zsattrpic')->delete($v['id']);
                    }
                }

                if (!empty($data['picres_id'])) {
                    $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id', $admin_id)->field('id,img_url')->select();
                    if ($zsinduspics) {
                        foreach ($zsinduspics as $v) {
                            Db::name('huamu_zsduopic')->delete($v['id']);
                        }
                    }
                }

                ys_admin_logs('添加商品', 'goods', $goods_id);
                $value = array('status' => 1, 'mess' => '增加成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $value = array('status' => 0, 'mess' => $e->getMessage());
            }

            return json($value);
        } else {
            $shop_id = session('shop_id');
            $admin_id = session('admin_id');
            $zssjpics = Db::name('huamu_zspic')->where('admin_id', $admin_id)->find();
            if ($zssjpics && $zssjpics['img_url']) {
                Db::name('huamu_zspic')->where('id', $zssjpics['id'])->update(array('img_url' => ''));
                if ($zssjpics['img_url'] && file_exists('./' . $zssjpics['img_url'])) {
                    @unlink('./' . $zssjpics['img_url']);
                }
            }

            $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id', $admin_id)->field('id,img_url')->select();
            if ($zsinduspics) {
                foreach ($zsinduspics as $v) {
                    Db::name('huamu_zsduopic')->delete($v['id']);
                    if ($v['img_url'] && file_exists('./' . $v['img_url'])) {
                        @unlink('./' . $v['img_url']);
                    }
                }
            }

            $zsattrpics = Db::name('ptadmin_zsattrpic')->where('admin_id', $admin_id)->field('id,img_url')->select();
            if ($zsattrpics) {
                foreach ($zsattrpics as $v) {
                    Db::name('ptadmin_zsattrpic')->delete($v['id']);
                    if ($v['img_url'] && file_exists('./' . $v['img_url'])) {
                        @unlink('./' . $v['img_url']);
                    }
                }
            }

            $cateres = Db::name('category')->field('id,cate_name,tjgd,pid')->order('sort asc')->select();
            $shcateres = Db::name('shop_cate')->where('shop_id', $shop_id)->field('id,cate_name,pid')->order('sort asc')->select();
            $levres = Db::name('member_level')->field('id,level_name')->order('id asc')->select();
            $sertionres = Db::name('sertion')->where('is_show', 1)->field('id,ser_name')->order('sort asc')->select();
            if (input('cate_id')) {
                $this->assign('cate_id', input('cate_id'));
            }
            $this->assign('cateres', recursive($cateres));
            $this->assign('shcateres', recursive($shcateres));
            $this->assign('levres', $levres);
            $this->assign('sertionres', $sertionres);
            return $this->fetch();
        }
    }

    public function getshuxingLst()
    {
        if (request()->isPost()) {
            if (input('post.typeid') && input('post.cate_id')) {
                $typeId = input('post.typeid');
                $cate_id = input('post.cate_id');

                $cates = Db::name('category')->where('id', $cate_id)->find();
                if ($cates) {
                    $gdtypes = Db::name('type')->where('id', $typeId)->find();
                    if ($gdtypes && $typeId == $cates['type_id']) {
                        $attrres = Db::name('attr')->where('type_id', $typeId)->order('sort asc')->select();
                    } else {
                        $attrres = '';
                    }
                } else {
                    $attrres = '';
                }
            } else {
                $attrres = '';
            }
            return json($attrres);
        }
    }

    public function getAttrLst()
    {
        if (input('post.type_id') && input('post.id')) {
            if (input('post.cate_id')) {
                $shop_id = session('shop_id');
                $type_id = input('post.type_id');
                $id = input('post.id');
                $cate_id = input('post.cate_id');

                $cates = Db::name('category')->where('id', $cate_id)->find();
                if ($cates) {
                    $gdtypes = Db::name('type')->where('id', $type_id)->find();

                    if ($gdtypes && $type_id == $cates['type_id']) {
                        $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shop_id)->where('is_recycle', 0)->field('id')->find();
                        if ($goods) {
                            $attrres = Db::name('attr')->where('type_id', $type_id)->order('sort asc')->select();
                            $arr = Db::name('goods_attr')->where('goods_id', $id)->select();
                            $gares = array();
                            if ($arr) {
                                foreach ($arr as $k => $v) {
                                    $gares[$v['attr_id']][] = $v;
                                }
                            }
                            $value = array('attrres' => $attrres, 'gares' => $gares);
                        } else {
                            $value = '';
                        }
                    } else {
                        $value = '';
                    }
                } else {
                    $value = '';
                }
            } else {
                $value = '';
            }
        } else {
            $value = '';
        }
        return json($value);
    }

    public function edit()
    {
        if (request()->isPost()) {
            if (input('post.id')) {
                $shop_id = session('shop_id');
                $admin_id = session('admin_id');
                $data = input('post.');
                // halt($data);
                $data['shop_id'] = $shop_id;
                $result = $this->validate($data, 'Goods');
                if (true !== $result) {
                    $value = array('status' => 0, 'mess' => $result);
                } else {
                    $goodss = Db::name('goods')->where('id', input('post.id'))->where('shop_id', $shop_id)->where('is_recycle', 0)->find();
                    if ($goodss) {
                        if ($goodss['checked'] == 2 && $data['onsale'] == 1) {
                            $value = array('status' => 0, 'mess' => '违规商品不可上架');
                            return json($value);
                        }

                        //活动信息
                        $huodong = 0;
                        $activitys = Db::name('rush_activity')->where('goods_id', $goodss['id'])->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                        if (!$activitys) {
                            $activitys = Db::name('group_buy')->where('goods_id', $goodss['id'])->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                            if (!$activitys) {
                                $activitys = Db::name('assemble')->where('goods_id', $goodss['id'])->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                                if ($activitys) {
                                    $huodong = 3;
                                }
                            } else {
                                $huodong = 2;
                            }
                        } else {
                            $huodong = 1;
                        }

                        $categorys = Db::name('category')->where('id', $data['cate_id'])->find();
                        if ($categorys) {
                            $child_cates = Db::name('category')->where('pid', $data['cate_id'])->find();
                            if (!$child_cates) {
                                $shcates = Db::name('shop_cate')->where('id', $data['shcate_id'])->where('shop_id', $shop_id)->find();
                                if ($shcates) {
                                    $child_shcates = Db::name('shop_cate')->where('pid', $data['shcate_id'])->find();
                                    if (!$child_shcates) {
                                        if (!empty($data['brand_id'])) {
                                            $brands = Db::name('brand')->where('id', $data['brand_id'])->where('find_in_set(' . $data['cate_id'] . ',cate_id_list)')->field('id')->find();
                                            if (!$brands) {
                                                $value = array('status' => 0, 'mess' => '品牌参数错误，编辑失败');
                                                return json($value);
                                            }
                                        }

                                        $gdtypes = Db::name('type')->where('id', $data['type_id'])->find();
                                        if ($gdtypes && $data['type_id'] == $categorys['type_id']) {
                                            if ($goodss['shop_price'] != $data['shop_price']) {
                                                if ($huodong) {
                                                    switch ($huodong) {
                                                        case 1:
                                                            $value = array('status' => 0, 'mess' => '商品已参与秒杀活动，活动期间不允许修改商品价格');
                                                            break;
                                                        case 2:
                                                            $value = array('status' => 0, 'mess' => '商品已参与团购活动，活动期间不允许修改商品价格');
                                                            break;
                                                        case 3:
                                                            $value = array('status' => 0, 'mess' => '商品已参与拼团活动，活动期间不允许修改商品价格');
                                                            break;
                                                    }
                                                    return json($value);
                                                }
                                            }

                                            if (!empty($data['pic_id'])) {
    //                                                $zssjpics = Db::name('huamu_zspic')->where('id', $data['pic_id'])->where('admin_id', $admin_id)->find();
    //                                                if ($zssjpics && $zssjpics['img_url']) {
    //                                                    $data['thumb_url'] = $zssjpics['img_url'];
    //                                                } else {
    //                                                    $data['thumb_url'] = $goodss['thumb_url'];
    //                                                }
                                                $data['thumb_url'] = $data['pic_id'];
                                            } else {
                                                $data['thumb_url'] = $goodss['thumb_url'];
                                            }

                                            if (!empty($data['mp'])) {
                                                $dengjires = Db::name('member_level')->field('id')->order('id asc')->select();
                                                $levelres = array();
                                                foreach ($dengjires as $gv) {
                                                    $levelres[] = $gv['id'];
                                                }

                                                $yzlevels = array();
                                            }

                                            $good_types = Db::name('type')->where('id', $data['type_id'])->field('id')->find();
                                            if ($good_types) {
                                                if ($goodss['type_id'] != $data['type_id']) {
                                                    if ($huodong) {
                                                        switch ($huodong) {
                                                            case 1:
                                                                $value = array('status' => 0, 'mess' => '商品已参与秒杀活动，活动期间不允许修改商品类型');
                                                                break;
                                                            case 2:
                                                                $value = array('status' => 0, 'mess' => '商品已参与团购活动，活动期间不允许修改商品类型');
                                                                break;
                                                            case 3:
                                                                $value = array('status' => 0, 'mess' => '商品已参与拼团活动，活动期间不允许修改商品类型');
                                                                break;
                                                        }
                                                        return json($value);
                                                    }
                                                }

                                                $goodattr_idres = Db::name('goods_attr')->alias('a')->field('a.*')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goodss['id'])->where('b.attr_type', 1)->select();
                                                $goodsattrids = array();
                                                if ($goodattr_idres) {
                                                    foreach ($goodattr_idres as $kdr => $vdr) {
                                                        $goodsattrids[$vdr['attr_id']][] = $vdr;
                                                    }
                                                }

                                                $typeattrs = Db::name('attr')->where('type_id', $good_types['id'])->where('attr_type', 1)->find();
                                                if ($typeattrs && empty($data['goods_attr'])) {
                                                    $value = array('status' => 0, 'mess' => '缺少商品规格属性，增加失败');
                                                    return json($value);
                                                }

                                                if (!empty($data['goods_attr'])) {
                                                    $goodattres = $data['goods_attr'];
                                                } else {
                                                    $goodattres = '';
                                                }

                                                if ($goodattres && !is_array($goodattres)) {
                                                    $value = array('status' => 0, 'mess' => '商品规格属性错误，增加失败');
                                                    return json($value);
                                                }

                                                if ($goodattres) {
                                                    foreach ($goodattres as $yzkey => $yzval) {
                                                        $yzshuxing = Db::name('attr')->where('id', $yzkey)->where('type_id', $data['type_id'])->find();
                                                        if ($yzshuxing) {
                                                            if ($yzshuxing['attr_type'] == 1) {
                                                                if (!empty($yzval['attr_value']) && is_array($yzval['attr_value'])) {
                                                                    if (count($yzval['attr_value']) != count(array_unique($yzval['attr_value']))) {
                                                                        $value = array('status' => 0, 'mess' => '存在重复的属性值，增加失败');
                                                                        return json($value);
                                                                    } else {
                                                                        for ($i = 0; $i < count($yzval['attr_value']); $i++) {
                                                                            if (!empty(trim($yzval['attr_value'][$i]))) {
                                                                                if (!isset($yzval['attr_price'][$i]) || !preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", trim($yzval['attr_price'][$i]))) {
                                                                                    $value = array('status' => 0, 'mess' => '存在属性价格为空或参数错误，增加失败');
                                                                                    return json($value);
                                                                                }

                                                                                if (!empty($yzval['id'][$i]) && $goodsattrids) {
                                                                                    if (!empty($goodsattrids[$yzkey])) {
                                                                                        foreach ($goodsattrids[$yzkey] as $vga) {
                                                                                            if ($vga['id'] == $yzval['id'][$i]) {
                                                                                                if (trim($yzval['attr_value'][$i]) != $vga['attr_value'] || trim($yzval['attr_price'][$i]) != $vga['attr_price']) {
                                                                                                    if ($huodong) {
                                                                                                        switch ($huodong) {
                                                                                                            case 1:
                                                                                                                $value = array('status' => 0, 'mess' => '商品已参与秒杀活动，活动期间不允许修改商品规格属性及价格');
                                                                                                                break;
                                                                                                            case 2:
                                                                                                                $value = array('status' => 0, 'mess' => '商品已参与团购活动，活动期间不允许修改商品规格属性及价格');
                                                                                                                break;
                                                                                                            case 3:
                                                                                                                $value = array('status' => 0, 'mess' => '商品已参与拼团活动，活动期间不允许修改商品规格属性及价格');
                                                                                                                break;
                                                                                                        }
                                                                                                        return json($value);
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            } else {
                                                                                $value = array('status' => 0, 'mess' => '存在属性值为空，增加失败');
                                                                                return json($value);
                                                                            }
                                                                        }
                                                                    }
                                                                } else {
                                                                    $value = array('status' => 0, 'mess' => '存在属性值参数错误，增加失败');
                                                                    return json($value);
                                                                }
                                                            } elseif ($yzshuxing['attr_type'] == 0) {
                                                                if (!empty($yzval['attr_value']) && !is_array($yzval['attr_value'])) {
                                                                    if ($yzshuxing['attr_values']) {
                                                                        if (strpos(',' . $yzshuxing['attr_values'] . ',', ',' . $yzval['attr_value'] . ',') === false) {
                                                                            $value = array('status' => 0, 'mess' => '存在属性值错误，增加失败');
                                                                            return json($value);
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        } else {
                                                            $value = array('status' => 0, 'mess' => '属性参数错误，增加失败');
                                                            return json($value);
                                                        }
                                                    }
                                                }
                                            } else {
                                                $value = array('status' => 0, 'mess' => '商品类型参数错误，增加失败');
                                                return json($value);
                                            }

                                            if (!empty($data['ypic_id'])) {
                                                $count1 = Db::name('goods_pic')->where('goods_id', $data['id'])->where('id', 'in', $data['ypic_id'])->count();
                                            } else {
                                                $count1 = 0;
                                            }

                                            if (!empty($data['picres_id'])) {
                                                $count2 = count($data['picres_id']);
                                            } else {
                                                $count2 = 0;
                                            }

                                            $countnum = $count1 + $count2;

                                            $webconfig = $this->webconfig;

                                            if ($countnum <= $webconfig['goodsimg_maxnum']) {

                                                if (!empty($data['fuwu'])) {
                                                    $fuwures = $data['fuwu'];
                                                    if (is_array($fuwures)) {
                                                        foreach ($fuwures as $vur) {
                                                            $sertions = Db::name('sertion')->where('id', $vur)->where('is_show', 1)->find();
                                                            if (!$sertions) {
                                                                $value = array('status' => 0, 'mess' => '所选服务项信息错误');
                                                                return json($value);
                                                            }
                                                        }
                                                        $data['fuwu'] = implode(',', $fuwures);
                                                    } else {
                                                        $value = array('status' => 0, 'mess' => '所选服务项参数错误');
                                                        return json($value);
                                                    }
                                                } else {
                                                    $data['fuwu'] = '';
                                                }


                                                $data['search_keywords'] = str_replace('，', ',', $data['search_keywords']);

                                                if($data['zkj'] > $data['shop_price']){
                                                    $value = array('status' => 0, 'mess' => '折扣金不能大于销售金额');
                                                    return json($value);
                                                }
                                                // 启动事务
                                                Db::startTrans();
                                                try {
                                                    Db::name('goods')->update(array(
                                                        'id' => $data['id'],
                                                        'type' => $data['type'],
                                                        'zkj' => $data['zkj'],
                                                        'goods_name' => $data['goods_name'],
                                                        'thumb_url' => $data['thumb_url'],
                                                        'market_price' => $data['market_price'],
                                                        'shop_price' => $data['shop_price'],
                                                        'onsale' => $data['onsale'],
                                                        'cate_id' => $data['cate_id'],
                                                        'brand_id' => $data['brand_id'],
                                                        'type_id' => $data['type_id'],
                                                        'goods_desc' => $data['goods_desc'],
                                                        'search_keywords' => $data['search_keywords'],
                                                        'keywords' => $data['keywords'],
                                                        'sort' => $data['sort'],
                                                        'goods_brief' => $data['goods_brief'],
                                                        'fuwu' => $data['fuwu'],
                                                        'is_free' => $data['is_free'],
                                                        'is_recycle' => $data['is_recycle'],
                                                        'is_special' => $data['is_special'],
                                                        'is_new' => $data['is_new'],
                                                        'is_hot' => $data['is_hot'],
                                                        'fictitious_sale_num' => $data['fictitious_sale_num'],
                                                        'shcate_id' => $data['shcate_id'],
                                                        'is_recommend' => $data['is_recommend'],
                                                        'vip_price' => $data['vip_price'],
                                                        'distribute_price' => $data['distribute_price'],
                                                    ));

                                                    $goods_id = $data['id'];


                                                    //编辑商品图片
                                                    if (!empty($data['ypic_id'])) {
                                                        $sort2 = $data['sort2'];
                                                        foreach ($data['ypic_id'] as $keypic => $valpic) {
                                                            if (empty($sort2[$keypic])) {
                                                                $sort2[$keypic] = 0;
                                                            }
                                                            $goodspics = Db::name('goods_pic')->where('id', $valpic)->where('goods_id', $goods_id)->find();
                                                            if ($goodspics) {
                                                                Db::name('goods_pic')->where('id', $valpic)->where('goods_id', $goods_id)->update(array('sort' => $sort2[$keypic]));
                                                            }
                                                        }
                                                    }

                                                    if (!empty($data['picres_id'])) {
                                                        $sort3 = $data['sort3'];
                                                        foreach ($data['picres_id'] as $key => $val) {
                                                            $img_url = Db::name('huamu_zsduopic')->where('id', $val)->where('admin_id', $admin_id)->value('img_url');
                                                            if ($img_url) {
                                                                if (empty($sort3[$key])) {
                                                                    $sort3[$key] = 0;
                                                                }
                                                                Db::name('goods_pic')->insert(array('img_url' => $img_url, 'sort' => $sort3[$key], 'goods_id' => $goods_id));
                                                            }
                                                        }
                                                    }

                                                    //编辑商品属性
                                                    //若改变了商品类型，则删除商品原来的所有商品属性并删除商品原来单选属性对应的所有库存
                                                    if ($goodss['type_id'] != $data['type_id']) {
                                                        $gaids = Db::name('goods_attr')->alias('a')->field('a.id')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goods_id)->where('b.attr_type', 1)->select();
                                                        Db::name('goods_attr')->where('goods_id', $goods_id)->delete();
                                                        if ($gaids) {
                                                            foreach ($gaids as $va) {
                                                                Db::name('product')->where('goods_id', $goods_id)->where('find_in_set(' . $va['id'] . ',goods_attr)')->delete();
                                                            }
                                                        } else {
                                                            $dan_attrs = Db::name('attr')->where('type_id', $data['type_id'])->where('attr_type', 1)->find();
                                                            if ($dan_attrs) {
                                                                Db::name('product')->where('goods_id', $goods_id)->delete();
                                                            }
                                                        }
                                                    }

                                                    if (!empty($data['goods_attr'])) {
                                                        $goods_attr = $data['goods_attr'];
                                                    } else {
                                                        $goods_attr = '';
                                                    }

                                                    if ($goods_attr) {
                                                        foreach ($goods_attr as $key2 => $val2) {
                                                            $attrshuxing = Db::name('attr')->where('id', $key2)->where('type_id', $data['type_id'])->find();
                                                            if ($attrshuxing['attr_type'] == 1) {
                                                                if (!empty($val2['attr_value']) && is_array($val2['attr_value'])) {
                                                                    for ($i = 0; $i < count($val2['attr_value']); $i++) {
                                                                        if (!empty(trim($val2['attr_value'][$i]))) {
                                                                            if (!empty($val2['id'][$i])) {
                                                                                $goodshuxing1 = Db::name('goods_attr')->where('id', $val2['id'][$i])->where('goods_id', $goods_id)->find();
                                                                                $goodshuxing2 = Db::name('goods_attr')->where('id', 'neq', $val2['id'][$i])->where('attr_id', $key2)->where('attr_value', trim($val2['attr_value'][$i]))->where('goods_id', $goods_id)->find();
                                                                                if ($goodshuxing1 && !$goodshuxing2) {
                                                                                    if ($attrshuxing['is_upload'] == 1 && !empty($val2['attrpic_id'][$i])) {
                                                                                        $attr_pic = Db::name('ptadmin_zsattrpic')->where('id', $val2['attrpic_id'][$i])->value('img_url');
                                                                                    } else {
                                                                                        if ($goodshuxing1['attr_pic']) {
                                                                                            $attr_pic = $goodshuxing1['attr_pic'];
                                                                                        } else {
                                                                                            $attr_pic = '';
                                                                                        }
                                                                                    }
                                                                                    Db::name('goods_attr')->where('id', $val2['id'][$i])->where('goods_id', $goods_id)->update(array('attr_id' => $key2, 'attr_value' => trim($val2['attr_value'][$i]), 'attr_price' => trim($val2['attr_price'][$i]), 'attr_pic' => $attr_pic));
                                                                                }
                                                                            } else {
                                                                                $goodshuxings = Db::name('goods_attr')->where('attr_id', $key2)->where('attr_value', trim($val2['attr_value'][$i]))->where('goods_id', $goods_id)->find();
                                                                                if (!$goodshuxings) {
                                                                                    if ($attrshuxing['is_upload'] == 1 && !empty($val2['attrpic_id'][$i])) {
                                                                                        $attr_pic = Db::name('ptadmin_zsattrpic')->where('id', $val2['attrpic_id'][$i])->value('img_url');
                                                                                    } else {
                                                                                        $attr_pic = '';
                                                                                    }
                                                                                    Db::name('goods_attr')->insert(array('attr_id' => $key2, 'attr_value' => trim($val2['attr_value'][$i]), 'attr_price' => trim($val2['attr_price'][$i]), 'attr_pic' => $attr_pic, 'goods_id' => $goods_id));
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            } elseif ($attrshuxing['attr_type'] == 0) {
                                                                if (!empty($val2['attr_value']) && !is_array($val2['attr_value'])) {
                                                                    if (!empty(trim($val2['attr_value']))) {
                                                                        if (!empty($val2['id'])) {
                                                                            $goodshuxings = Db::name('goods_attr')->where('id', $val2['id'])->where('goods_id', $goods_id)->find();
                                                                            if ($goodshuxings) {
                                                                                Db::name('goods_attr')->where('id', $val2['id'])->where('goods_id', $goods_id)->update(array('attr_id' => $key2, 'attr_value' => trim($val2['attr_value'])));
                                                                            }
                                                                        } else {
                                                                            $goodshuxings = Db::name('goods_attr')->where('attr_id', $key2)->where('goods_id', $goods_id)->find();
                                                                            if (!$goodshuxings) {
                                                                                Db::name('goods_attr')->insert(array('attr_id' => $key2, 'attr_value' => trim($val2['attr_value']), 'goods_id' => $goods_id));
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }

                                                    //更新商品属性集合
                                                    $goods_shuxing = Db::name('goods_attr')->where('goods_id', $goods_id)->field('attr_id,attr_value')->select();
                                                    if ($goods_shuxing) {
                                                        $shuxings = '';
                                                        foreach ($goods_shuxing as $kcv => $gcv) {
                                                            if ($kcv == 0) {
                                                                $shuxings = $gcv['attr_id'] . ':' . $gcv['attr_value'];
                                                            } else {
                                                                $shuxings = $shuxings . ',' . $gcv['attr_id'] . ':' . $gcv['attr_value'];
                                                            }
                                                        }
                                                        Db::name('goods')->update(array('id' => $goods_id, 'shuxings' => $shuxings));
                                                    }

                                                    $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,a.attr_price')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goods_id)->where('b.attr_type', 1)->select();
                                                    if ($radiores) {
                                                        $radioattr = array();
                                                        foreach ($radiores as $cv) {
                                                            $radioattr[$cv['attr_id']][] = $cv;
                                                        }

                                                        $radioprice = array();
                                                        foreach ($radioattr as $zk => $zv) {
                                                            foreach ($zv as $zval) {
                                                                $radioprice[$zk][] = $zval['attr_price'];
                                                            }
                                                        }

                                                        $min_attr_price = 0;
                                                        $max_attr_price = 0;

                                                        foreach ($radioprice as $rv) {
                                                            $min_attr_price += min($rv);
                                                            $max_attr_price += max($rv);
                                                        }

//                                                        $min_shop_price = $data['shop_price'] + $min_attr_price;
//                                                        $max_shop_price = $data['shop_price'] + $max_attr_price;
//                                                        $min_market_price = $data['market_price'] + $min_attr_price;
//                                                        $max_market_price = $data['market_price'] + $max_attr_price;
                                                        $min_shop_price = $data['shop_price'];
                                                        $max_shop_price = $data['shop_price'];
                                                        $min_market_price = $data['market_price'];
                                                        $max_market_price = $data['market_price'];
                                                    } else {
                                                        $min_shop_price = $data['shop_price'];
                                                        $max_shop_price = $data['shop_price'];
                                                        $min_market_price = $data['market_price'];
                                                        $max_market_price = $data['market_price'];
                                                    }

                                                    $zs_price = $min_shop_price;
                                                    $is_activity = 0;

                                                    //秒杀信息
                                                    $rushs = Db::name('rush_activity')->where('goods_id', $goods_id)->where('checked', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,price')->order('price asc')->find();
                                                    if ($rushs)
                                                    {
                                                        $zs_price = $rushs['price'];
                                                        $is_activity = 1;
                                                    }
                                                    else
                                                    {
                                                        //团购信息
                                                        $groups = Db::name('group_buy')->where('goods_id', $goods_id)->where('checked', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,price')->order('price asc')->find();
                                                        if ($groups) {
                                                            $zs_price = $groups['price'];
                                                            $is_activity = 2;
                                                        } else {
                                                            //拼团信息
                                                            $assembles = Db::name('assemble')->where('goods_id', $goods_id)->where('checked', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,price')->order('price asc')->find();
                                                            if ($assembles) {
                                                                $zs_price = $assembles['price'];
                                                                $is_activity = 3;
                                                            }
                                                        }
                                                    }

                                                    Db::name('goods')->update(array('min_market_price' => $min_market_price, 'max_market_price' => $max_market_price, 'min_price' => $min_shop_price, 'max_price' => $max_shop_price, 'zs_price' => $zs_price, 'is_activity' => $is_activity, 'id' => $goods_id));

                                                    if ($data['cate_id'] != $goodss['cate_id']) {
                                                        $managements = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $data['cate_id'])->find();
                                                        if (!$managements) {
                                                            Db::name('shop_management')->insert(array('shop_id' => $shop_id, 'cate_id' => $data['cate_id']));
                                                        }

                                                        $ymanages = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $goodss['cate_id'])->find();
                                                        if ($ymanages) {
                                                            $good_manages = Db::name('goods')->where('shop_id', $shop_id)->where('cate_id', $goodss['cate_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                                                            if (!$good_manages) {
                                                                Db::name('shop_management')->where('id', $ymanages['id'])->delete();
                                                            }
                                                        }
                                                    } else {
                                                        $ymanages = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $goodss['cate_id'])->find();
                                                        if (!$ymanages) {
                                                            Db::name('shop_management')->insert(array('shop_id' => $shop_id, 'cate_id' => $goodss['cate_id']));
                                                        }
                                                    }

                                                    if ($data['brand_id'] != $goodss['brand_id']) {
                                                        $managebrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $data['brand_id'])->find();
                                                        if (!$managebrands) {
                                                            Db::name('shop_managebrand')->insert(array('shop_id' => $shop_id, 'brand_id' => $data['brand_id']));
                                                        }

                                                        $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $goodss['brand_id'])->find();
                                                        if ($yrbrands) {
                                                            $good_brands = Db::name('goods')->where('shop_id', $shop_id)->where('brand_id', $goodss['brand_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                                                            if (!$good_brands) {
                                                                Db::name('shop_managebrand')->where('id', $yrbrands['id'])->delete();
                                                            }
                                                        }
                                                    } else {
//                                                        $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $goodss['brand_id'])->find();
//                                                        if (!$yrbrands) {
//                                                            Db::name('shop_managebrand')->insert(array('shop_id' => $shop_id, 'brand_id' => $goodss['brand_id']));
//                                                        }
                                                    }

                                                    //提交事务
                                                    Db::commit();
                                                    if (!empty($zssjpics) && $zssjpics['img_url']) {
                                                        Db::name('huamu_zspic')->where('id', $zssjpics['id'])->update(array('img_url' => ''));
                                                        if ($goodss['thumb_url'] && file_exists('./' . $goodss['thumb_url'])) {
                                                            @unlink('./' . $goodss['thumb_url']);
                                                        }
                                                    }

                                                    $zsattrpics = Db::name('ptadmin_zsattrpic')->where('admin_id', $admin_id)->field('id,img_url')->select();
                                                    if ($zsattrpics) {
                                                        foreach ($zsattrpics as $v) {
                                                            Db::name('ptadmin_zsattrpic')->delete($v['id']);
                                                        }
                                                    }

                                                    $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id', $admin_id)->field('id,img_url')->select();
                                                    if ($zsinduspics) {
                                                        foreach ($zsinduspics as $v) {
                                                            Db::name('huamu_zsduopic')->delete($v['id']);
                                                        }
                                                    }

                                                    ys_admin_logs('编辑商品', 'goods', $data['id']);
                                                    $value = array('status' => 1, 'mess' => '编辑成功');
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                    $value = array('status' => 0, 'mess' => '编辑失败'.$e->getMessage());
                                                }
                                            } else {
                                                $value = array('status' => 0, 'mess' => '商品图片最多上传' . $webconfig['goodsimg_maxnum'] . '张');
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
                $shop_id = session('shop_id');
                $admin_id = session('admin_id');
                $goodss = Db::name('goods')->where('id', input('id'))->where('shop_id', $shop_id)->where('is_recycle', 0)->find();
                if ($goodss) {
                    $zssjpics = Db::name('huamu_zspic')->where('admin_id', $admin_id)->find();
                    if ($zssjpics && $zssjpics['img_url']) {
                        Db::name('huamu_zspic')->where('id', $zssjpics['id'])->update(array('img_url' => ''));
                        if ($zssjpics['img_url'] && file_exists('./' . $zssjpics['img_url'])) {
                            @unlink('./' . $zssjpics['img_url']);
                        }
                    }

                    $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id', $admin_id)->field('id,img_url')->select();
                    if ($zsinduspics) {
                        foreach ($zsinduspics as $v) {
                            Db::name('huamu_zsduopic')->delete($v['id']);
                            if ($v['img_url'] && file_exists('./' . $v['img_url'])) {
                                @unlink('./' . $v['img_url']);
                            }
                        }
                    }

                    $zsattrpics = Db::name('ptadmin_zsattrpic')->where('admin_id', $admin_id)->field('id,img_url')->select();
                    if ($zsattrpics) {
                        foreach ($zsattrpics as $v) {
                            Db::name('ptadmin_zsattrpic')->delete($v['id']);
                            if ($v['img_url'] && file_exists('./' . $v['img_url'])) {
                                @unlink('./' . $v['img_url']);
                            }
                        }
                    }

                    $cateres = Db::name('category')->field('id,cate_name,tjgd,pid')->order('sort asc')->select();
                    $shcateres = Db::name('shop_cate')->where('shop_id', $shop_id)->field('id,cate_name,pid')->order('sort asc')->select();
                    $brandres = Db::name('brand')->where('find_in_set(' . $goodss['cate_id'] . ',cate_id_list)')->field('id,brand_name')->select();
                    $levres = Db::name('member_level')->field('id,level_name')->order('id asc')->select();
                    $sertionres = Db::name('sertion')->where('is_show', 1)->field('id,ser_name')->order('sort asc')->select();

                    $types = Db::name('type')->where('id', $goodss['type_id'])->field('id,type_name')->find();

                    $goodpicres = Db::name('goods_pic')->where('goods_id', input('id'))->order('sort asc')->select();
                    $mpres = Db::name('member_price')->where('goods_id', input('id'))->select();
                    $attres = Db::name('attr')->where('type_id', $goodss['type_id'])->order('sort asc')->select();

                    $arr = Db::name('goods_attr')->where('goods_id', input('id'))->select();
                    $gares = array();
                    if ($arr) {
                        foreach ($arr as $key => $val) {
                            $gares[$val['attr_id']][] = $val;
                        }
                    }

                    if (input('s')) {
                        $this->assign('search', input('s'));
                    }

                    $this->assign('pnum', input('page'));
                    $this->assign('filter', input('filter'));

                    if (input('cate_id')) {
                        $this->assign('cate_id', input('cate_id'));
                    }
                    $this->assign('cateres', recursive($cateres));
                    $this->assign('shcateres', recursive($shcateres));
                    $this->assign('brandres', $brandres);
                    $this->assign('levres', $levres);
                    $this->assign('sertionres', $sertionres);
                    $this->assign('types', $types);
                    $this->assign('goodpicres', $goodpicres);
                    $this->assign('mpres', $mpres);
                    $this->assign('attres', $attres);
                    $this->assign('gares', $gares);
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

    //根据分类获取品牌和类型
    public function getbrandtype()
    {
        if (request()->isPost()) {
            $cate_id = input('post.cate_id');
            if ($cate_id) {
                $cates = Db::name('category')->where('id', $cate_id)->field('id,type_id,pid')->find();
                if ($cates) {
                    $brandres = Db::name('brand')->where('find_in_set(' . $cate_id . ',cate_id_list)')->field('id,brand_name')->select();
                    $types = Db::name('type')->where('id', $cates['type_id'])->field('id,type_name')->find();
                } else {
                    $brandres = '';
                    $types = '';
                }
            } else {
                $brandres = '';
                $types = '';
            }
            if (empty($brandres) && empty($types)) {
                $value = array();
            } else {
                $value = array('brandres' => $brandres, 'types' => $types);
            }
            return json($value);
        }
    }


    //商品单选属性库存列表
    public function product()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $shop_id = session('shop_id');
            if (!empty($data['goods_number']) && !empty($data['goods_id'])) {
                $goods_number = $data['goods_number'];
                $goods_id = $data['goods_id'];
                $goodsinfo = Db::name('goods')->where('id', $goods_id)->where('shop_id', $shop_id)->where('is_recycle', 0)->find();
                if ($goodsinfo) {
                    if (!empty($data['product_id'])) {
                        $product_id = $data['product_id'];
                    }

                    if (!empty($data['goods_attr'])) {
                        $goods_attr = $data['goods_attr'];
                        $zyzarr = array();
                        foreach ($goods_number as $yk => $yv) {
                            if (isset($yv) && preg_match("/^[0-9]+$/", $yv)) {
                                $yzarr = array();
                                foreach ($goods_attr as $key => $val) {
                                    if (empty($val[$yk])) {
                                        $value = array('status' => 0, 'mess' => '有商品属性为空，保存失败');
                                        return json($value);
                                    } else {
                                        $yzshuxings = Db::name('goods_attr')->alias('a')->field('a.id')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', $val[$yk])->where('a.goods_id', $goods_id)->where('b.attr_type', 1)->find();
                                        if ($yzshuxings) {
                                            $yzarr[] = $val[$yk];
                                        } else {
                                            $value = array('status' => 0, 'mess' => '有商品属性参数错误，保存失败');
                                            return json($value);
                                        }
                                    }
                                }
                                if (!empty($yzarr)) {
                                    $yzarr = implode(',', $yzarr);
                                    $zyzarr[] = $yzarr;
                                }
                            } else {
                                $value = array('status' => 0, 'mess' => '有库存为空或不为数字，保存失败');
                                return json($value);
                            }
                        }

                        if (count($zyzarr) != count(array_unique($zyzarr))) {
                            $value = array('status' => 0, 'mess' => '存在相同的商品属性组合库存，保存失败');
                            return json($value);
                        }

                        foreach ($goods_number as $k => $v) {
                            if (empty($v) || !preg_match("/^[0-9]+$/", $v)) {
                                $v = 0;
                            }

                            $goodsAttr = array();
                            foreach ($goods_attr as $key => $val) {
                                if (empty($val[$k])) {
                                    continue 2;
                                }
                                $goodshuxings = Db::name('goods_attr')->alias('a')->field('a.id')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', $val[$k])->where('a.goods_id', $goods_id)->where('b.attr_type', 1)->find();
                                if ($goodshuxings) {
                                    $goodsAttr[] = $val[$k];
                                } else {
                                    continue 2;
                                }
                            }
                            if (!empty($goodsAttr)) {
                                $goodsAttr = implode(',', $goodsAttr);
                                // 启动事务
                                Db::startTrans();
                                try {
                                    if (!empty($product_id[$k])) {
                                        $product1 = Db::name('product')->where('id', $product_id[$k])->where('goods_id', $goods_id)->find();
                                        $product2 = Db::name('product')->where('id', 'neq', $product_id[$k])->where('goods_attr', $goodsAttr)->where('goods_id', $goods_id)->find();
                                        if ($product1 && !$product2) {
                                            Db::name('product')->where('id', $product_id[$k])->where('goods_id', $goods_id)->update(array('goods_attr' => $goodsAttr, 'goods_number' => $v));
                                            ys_admin_logs('保存商品库存', 'product', $product_id[$k]);
                                        }
                                    } else {
                                        $products = Db::name('product')->where('goods_attr', $goodsAttr)->where('goods_id', $goods_id)->find();
                                        if (!$products) {
                                            $kc_id = Db::name('product')->insertGetId(array('goods_attr' => $goodsAttr, 'goods_number' => $v, 'goods_id' => $goods_id, 'shop_id' => $shop_id));
                                            ys_admin_logs('保存商品库存', 'product', $kc_id);
                                        }
                                    }
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status' => 1, 'mess' => '保存成功');
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status' => 0, 'mess' => '保存失败');
                                }
                            }
                        }
                    } else {
                        foreach ($goods_number as $k => $v) {
                            if (empty($v) || !preg_match("/^[0-9]+$/", $v)) {
                                $v = 0;
                            }

                            // 启动事务
                            Db::startTrans();
                            try {
                                if (!empty($product_id[$k])) {
                                    $products = Db::name('product')->where('id', $product_id[$k])->where('goods_id', $goods_id)->find();
                                    if ($products) {
                                        Db::name('product')->where('id', $product_id[$k])->where('goods_id', $goods_id)->update(array('goods_number' => $v));
                                        ys_admin_logs('保存商品库存', 'product', $product_id[$k]);
                                    }
                                } else {
                                    $products = Db::name('product')->where('goods_id', $goods_id)->find();
                                    if (!$products) {
                                        $kc_id = Db::name('product')->insertGetId(array('goods_attr' => '', 'goods_number' => $v, 'goods_id' => $goods_id, 'shop_id' => $shop_id));
                                        ys_admin_logs('保存商品库存', 'product', $kc_id);
                                    }
                                }
                                // 提交事务
                                Db::commit();
                                $value = array('status' => 1, 'mess' => '保存成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status' => 0, 'mess' => '保存失败');
                            }
                        }
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '找不到相关商品信息，保存失败');
                }
            } else {
                $value = array('status' => 0, 'mess' => '保存失败');
            }
            return json($value);
        } else {
            $id = input('id');
            $shop_id = session('shop_id');
            $goodsinfo = Db::name('goods')->where('id', input('id'))->where('shop_id', $shop_id)->where('is_recycle', 0)->find();
            if ($goodsinfo) {
                $_radioAttrRes = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,b.attr_name,b.attr_type')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $id)->where('b.attr_type', 1)->select();

                $radioAttrRes = array();
                if ($_radioAttrRes) {
                    foreach ($_radioAttrRes as $v) {
                        $radioAttrRes[$v['attr_id']][] = $v;
                    }
                }

                $goods_name = Db::name('goods')->where('id', input('id'))->value('goods_name');
                $prores = Db::name('product')->where('goods_id', input('id'))->select();
                if (input('pnum')) {
                    $this->assign('pnum', input('pnum'));
                }
                $this->assign('prores', $prores);
                $this->assign('goods_name', $goods_name);
                $this->assign('goods_id', input('id'));
                $this->assign('radioAttrRes', $radioAttrRes);
                return $this->fetch();
            } else {
                $this->error('找不到相关信息');
            }
        }
    }


    //删除商品属性
    public function deletega()
    {
        if (request()->isPost()) {
            if (input('post.id') && input('post.goods_id')) {
                $shop_id = session('shop_id');
                $id = input('post.id');
                $goods_id = input('post.goods_id');
                $goodsinfo = Db::name('goods')->where('id', $goods_id)->where('shop_id', $shop_id)->where('is_recycle', 0)->field('id')->find();
                if ($goodsinfo) {
                    //活动信息
                    $huodong = 0;
                    $activitys = Db::name('rush_activity')->where('goods_id', $goods_id)->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                    if (!$activitys) {
                        $activitys = Db::name('group_buy')->where('goods_id', $goods_id)->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                        if (!$activitys) {
                            $activitys = Db::name('assemble')->where('goods_id', $goods_id)->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                            if ($activitys) {
                                $huodong = 3;
                            }
                        } else {
                            $huodong = 2;
                        }
                    } else {
                        $huodong = 1;
                    }

                    if ($huodong) {
                        switch ($huodong) {
                            case 1:
                                $value = array('status' => 0, 'mess' => '商品已参与秒杀活动，活动期间不允许删除商品规格属性');
                                break;
                            case 2:
                                $value = array('status' => 0, 'mess' => '商品已参与团购活动，活动期间不允许删除商品规格属性');
                                break;
                            case 3:
                                $value = array('status' => 0, 'mess' => '商品已参与拼团活动，活动期间不允许删除商品规格属性');
                                break;
                        }
                        return json($value);
                    }

                    $pro = Db::name('product')->where('goods_id', $goods_id)->where('find_in_set(' . $id . ',goods_attr)')->field('id')->limit(1)->find();
                    if ($pro) {
                        $value = array('status' => 0, 'mess' => '该商品库存中正在使用此商品属性，删除失败');
                    } else {
                        // 启动事务
                        Db::startTrans();
                        try {
                            Db::name('goods_attr')->where('id', $id)->where('goods_id', $goods_id)->delete();
                            //更新商品属性集合
                            $goods_shuxing = Db::name('goods_attr')->where('goods_id', $goods_id)->field('attr_id,attr_value')->select();
                            $shuxings = '';
                            foreach ($goods_shuxing as $kcv => $gcv) {
                                if ($kcv == 0) {
                                    $shuxings = $gcv['attr_id'] . ':' . $gcv['attr_value'];
                                } else {
                                    $shuxings = $shuxings . ',' . $gcv['attr_id'] . ':' . $gcv['attr_value'];
                                }
                            }
                            Db::name('goods')->update(array('id' => $goods_id, 'shuxings' => $shuxings));
                            // 提交事务
                            Db::commit();
                            $value = array('status' => 1, 'mess' => '删除成功');
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status' => 0, 'mess' => '删除失败');
                        }
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '商品信息有误，删除失败');
                }
            } else {
                $value = array('status' => 0, 'mess' => '缺少参数，删除失败');
            }
            return json($value);
        }
    }

    //删除库存信息
    public function delproduct()
    {
        if (request()->isPost()) {
            if (input('post.id') && input('post.goods_id')) {
                $shop_id = session('shop_id');
                $id = input('post.id');
                $goods_id = input('post.goods_id');
                $goodsinfo = Db::name('goods')->where('id', $goods_id)->where('shop_id', $shop_id)->where('is_recycle', 0)->field('id')->find();
                if ($goodsinfo) {
                    $products = Db::name('product')->where('id', $id)->where('goods_id', $goods_id)->find();
                    if ($products) {
                        $count = Db::name('product')->where('id', $id)->where('goods_id', $goods_id)->delete();
                        if ($count > 0) {
                            ys_admin_logs('删除商品库存', 'product', $id);
                            $value = array('status' => 1, 'mess' => '删除成功');
                        } else {
                            $value = array('status' => 0, 'mess' => '删除失败');
                        }
                    } else {
                        $value = array('status' => 0, 'mess' => '删除失败');
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '商品信息有误，删除失败');
                }
            } else {
                $value = array('status' => 0, 'mess' => '缺少参数，删除失败');
            }
            return json($value);
        }
    }

    //放入回收站
    public function recycle()
    {
        $id = input('id');
        $shop_id = session('shop_id');
        if (!empty($id) && !is_array($id)) {
            $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shop_id)->where('is_recycle', 0)->find();
            if ($goods) {
                // 启动事务
                Db::startTrans();
                try {
                    Db::name('goods')->where('id', $id)->update(array('is_recycle' => 1, 'onsale' => 0));
                    $ymanages = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $goods['cate_id'])->find();
                    if ($ymanages) {
                        $good_manages = Db::name('goods')->where('shop_id', $shop_id)->where('cate_id', $goods['cate_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                        if (!$good_manages) {
                            Db::name('shop_management')->where('id', $ymanages['id'])->delete();
                        }
                    }

                    $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $goods['brand_id'])->find();
                    if ($yrbrands) {
                        $good_brands = Db::name('goods')->where('shop_id', $shop_id)->where('brand_id', $goods['brand_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                        if (!$good_brands) {
                            Db::name('shop_managebrand')->where('id', $yrbrands['id'])->delete();
                        }
                    }

                    Db::name('shops')->where('id', $shop_id)->setDec('goods_num', 1);
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('商品加入回收站', 'goods', $id);
                    $value = array('status' => 1, 'mess' => '加入回收站成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status' => 0, 'mess' => '加入回收站失败');
                }
            } else {
                $value = array('status' => 0, 'mess' => '找不到相关信息');
            }
        } else {
            $value = array('status' => 0, 'mess' => '未选中任何数据');
        }
        return json($value);
    }

    //取出回收站商品
    public function recovery()
    {
        $id = input('id');
        $shop_id = session('shop_id');
        if (!empty($id) && !is_array($id)) {
            $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shop_id)->where('is_recycle', 1)->where('onsale', 0)->find();
            if ($goods) {
                if ($goods['checked'] == 1) {
                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('goods')->where('id', $id)->update(array('is_recycle' => 0));

                        $ymanages = Db::name('shop_management')->where('shop_id', $shop_id)->where('cate_id', $goods['cate_id'])->find();
                        if (!$ymanages) {
                            Db::name('shop_management')->insert(array('shop_id' => $shop_id, 'cate_id' => $goods['cate_id']));
                        }

                        $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shop_id)->where('brand_id', $goods['brand_id'])->find();
                        if (!$yrbrands) {
                            Db::name('shop_managebrand')->insert(array('shop_id' => $shop_id, 'brand_id' => $goods['brand_id']));
                        }

                        Db::name('shops')->where('id', $shop_id)->setInc('goods_num', 1);
                        // 提交事务
                        Db::commit();
                        ys_admin_logs('商品从回收站恢复', 'goods', $id);
                        $value = array('status' => 1, 'mess' => '恢复商品成功');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status' => 0, 'mess' => '恢复商品失败');
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '违规商品不可恢复');
                }
            } else {
                $value = array('status' => 0, 'mess' => '找不到相关信息');
            }
        } else {
            $value = array('status' => 0, 'mess' => '未选中任何数据');
        }
        return json($value);
    }

    //删除商品
    public function delete()
    {
        if (input('post.id')) {
            $id = array_filter(explode(',', input('post.id')));
        } else {
            $id = input('id');
        }

        $shop_id = session('shop_id');

        if (!empty($id)) {
            if (!is_array($id)) {
                $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shop_id)->where('is_recycle', 1)->where('onsale', 0)->find();
                if ($goods) {
                    $good_picres = Db::name('goods_pic')->where('goods_id', $id)->field('id,img_url')->select();
                    $attr_picres = Db::name('goods_attr')->where('goods_id', $id)->field('id,attr_pic')->select();

                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('goods')->where('id', $id)->delete();
                        Db::name('goods_attr')->where('goods_id', $id)->delete();
                        Db::name('product')->where('goods_id', $id)->delete();

                        if ($goods['thumb_url'] && file_exists('./' . $goods['thumb_url'])) {
                            @unlink('./' . $goods['thumb_url']);
                        }

                        if ($good_picres) {
                            foreach ($good_picres as $v) {
                                if ($v['img_url'] && file_exists('./' . $v['img_url'])) {
                                    @unlink('./' . $v['img_url']);
                                }
                            }
                        }

                        if ($attr_picres) {
                            foreach ($attr_picres as $val) {
                                if ($val['attr_pic'] && file_exists('./' . $val['attr_pic'])) {
                                    @unlink('./' . $val['attr_pic']);
                                }
                            }
                        }

                        // 提交事务
                        Db::commit();
                        ys_admin_logs('删除商品成功', 'goods', $id);
                        $value = array('status' => 1, 'mess' => '删除商品成功');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status' => 0, 'mess' => '删除商品失败');
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '找不到相关信息');
                }
            } else {
                $idarr = $id;
                foreach ($idarr as $vd) {
                    $goods = Db::name('goods')->where('id', $vd)->where('shop_id', $shop_id)->where('is_recycle', 1)->where('onsale', 0)->find();
                    if ($goods) {
                        $good_picres = Db::name('goods_pic')->where('goods_id', $vd)->field('id,img_url')->select();
                        $attr_picres = Db::name('goods_attr')->where('goods_id', $vd)->field('id,attr_pic')->select();

                        // 启动事务
                        Db::startTrans();
                        try {
                            Db::name('goods')->where('id', $vd)->delete();
                            Db::name('goods_attr')->where('goods_id', $vd)->delete();
                            Db::name('product')->where('goods_id', $vd)->delete();

                            if ($goods['thumb_url'] && file_exists('./' . $goods['thumb_url'])) {
                                @unlink('./' . $goods['thumb_url']);
                            }

                            if ($good_picres) {
                                foreach ($good_picres as $v) {
                                    if ($v['img_url'] && file_exists('./' . $v['img_url'])) {
                                        @unlink('./' . $v['img_url']);
                                    }
                                }
                            }

                            if ($attr_picres) {
                                foreach ($attr_picres as $val) {
                                    if ($val['attr_pic'] && file_exists('./' . $val['attr_pic'])) {
                                        @unlink('./' . $val['attr_pic']);
                                    }
                                }
                            }

                            // 提交事务
                            Db::commit();
                            ys_admin_logs('删除商品成功', 'goods', $vd);
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        }
                    }
                }
                $value = array('status' => 1, 'mess' => '删除商品成功');
            }
        } else {
            $value = array('status' => 0, 'mess' => '未选中任何数据');
        }
        return json($value);
    }


    //搜索商品
    public function search()
    {
        $shop_id = session('shop_id');

        if (input('post.keyword') != '') {
            cookie('goods_name', input('post.keyword'), 3600);
        } else {
            cookie('goods_name', null);
        }

        if (input('post.cate_id') != '') {
            cookie('goods_cate_id', input('post.cate_id'), 3600);
        }

        if (input('post.brand_id') != '') {
            cookie('goods_brand_id', input('post.brand_id'), 3600);
        }

        if (input('post.attr') != '') {
            cookie('goods_attr', input('post.attr'), 3600);
        }

        if (input('post.onsale') != '') {
            cookie('goods_onsale', input('post.onsale'), 3600);
        }


        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();

        $where = array();
        $where['a.shop_id'] = $shop_id;
        $where['a.is_recycle'] = 0;

        if (cookie('goods_name')) {
            $where['a.goods_name'] = array('like', '%' . cookie('goods_name') . '%');
        }

        if (cookie('goods_cate_id') != '') {
            //(int)将cookie字符串强制转换成整型
            $cid = (int)cookie('goods_cate_id');
            if ($cid != 0) {
                $cateId = array();
                $cateId = get_all_child($cateres, $cid);
                $cateId[] = $cid;
                $cateId = implode(',', $cateId);
                $where['a.cate_id'] = array('in', $cateId);
            }
        }

        if (cookie('goods_brand_id') != '') {
            //(int)将cookie字符串强制转换成整型
            $bid = (int)cookie('goods_brand_id');
            if ($bid != 0) {
                $where['a.brand_id'] = $bid;
            }
        }

        if (cookie('goods_attr') != '') {
            if (cookie('goods_attr') != '0') {
                $where['a.' . cookie('goods_attr')] = 1;
            }
        }

        if (cookie('goods_onsale') != '') {
            //(int)将cookie字符串强制转换成整型
            $sale = (int)cookie('goods_onsale');
            if ($sale != 0) {
                if ($sale == 1) {
                    $where['a.onsale'] = 1;
                } elseif ($sale == 2) {
                    $where['a.onsale'] = 0;
                }
            }
        }

        $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name,a.type')->join('sp_category b', 'a.cate_id = b.id', 'LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();

        $brandres = Db::name('brand')->field('id,brand_name')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $search = 1;

        $filter = 3;

        if (cookie('goods_name')) {
            $this->assign('goods_name', cookie('goods_name'));
        }
        $this->assign('cate_id', $cid);
        $this->assign('brand_id', $bid);
        $this->assign('attr', cookie('goods_attr'));
        $this->assign('onsale', $sale);
        $this->assign('cateres', recursive($cateres));
        $this->assign('brandres', $brandres);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('search', $search);
        $this->assign('filter', $filter);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }


    //搜索商品
    public function hssearch()
    {
        $shop_id = session('shop_id');

        if (input('post.keyword') != '') {
            cookie('hsgoods_name', input('post.keyword'), 3600);
        } else {
            cookie('hsgoods_name', null);
        }

        if (input('post.cate_id') != '') {
            cookie('hsgoods_cate_id', input('post.cate_id'), 3600);
        }

        if (input('post.brand_id') != '') {
            cookie('hsgoods_brand_id', input('post.brand_id'), 3600);
        }

        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();

        $where = array();
        $where['a.shop_id'] = $shop_id;
        $where['a.is_recycle'] = 1;
        $where['a.onsale'] = 0;

        if (cookie('hsgoods_name')) {
            $where['a.goods_name'] = array('like', '%' . cookie('hsgoods_name') . '%');
        }

        if (cookie('hsgoods_cate_id') != '') {
            //(int)将cookie字符串强制转换成整型
            $cid = (int)cookie('hsgoods_cate_id');
            if ($cid != 0) {
                $cateId = array();
                $cateId = get_all_child($cateres, $cid);
                $cateId[] = $cid;
                $cateId = implode(',', $cateId);
                $where['a.cate_id'] = array('in', $cateId);
            }
        }

        if (cookie('hsgoods_brand_id') != '') {
            //(int)将cookie字符串强制转换成整型
            $bid = (int)cookie('hsgoods_brand_id');
            if ($bid != 0) {
                $where['a.brand_id'] = $bid;
            }
        }

        $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name')->join('sp_category b', 'a.cate_id = b.id', 'LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();

        $brandres = Db::name('brand')->field('id,brand_name')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $search = 1;

        if (cookie('hsgoods_name')) {
            $this->assign('goods_name', cookie('hsgoods_name'));
        }
        $this->assign('cate_id', $cid);
        $this->assign('brand_id', $bid);
        $this->assign('cateres', recursive($cateres));
        $this->assign('brandres', $brandres);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('search', $search);
        if (request()->isAjax()) {
            return $this->fetch('hsajaxpage');
        } else {
            return $this->fetch('hslst');
        }
    }
    
    public function crowdfunding(){
        
        
        return $this->fetch();
    }
    
    public function generateCrowd(){
        $input = input();
        
        if(!$input['id'] || !$input['value']){
            $value = array('status' => 0, 'mess' => '参数不存在');
            return json($value);
        }
        
        $goods_info = Db::name('goods')->where('id', $input['id'])->find();
        if(is_null($goods_info)){
            $value = array('status' => 0, 'mess' => '商品不存在');
            return json($value);
        }
        
        $goods_info['goods_id'] = $goods_info['id'];
        $goods_info['crowd_value'] = $input['value'];
        $goods_info['crowd_mark'] = uniqid().$goods_info['id'];
        $goods_info['addtime'] = time();
        unset($goods_info['id']);
        
        $res = Db::name('crowd_goods')->insert($goods_info);
        if(!$res){
            $value = array('status' => 0, 'mess' => '众筹创建失败');
        }
        else{
            $value = array('status' => 1, 'mess' => '众筹创建成功');
        }
        return json($value);
    }


























}

?>