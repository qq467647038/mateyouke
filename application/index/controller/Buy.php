<?php
namespace app\index\controller;

use app\index\controller\Common;
use app\index\model\Gongyong as GongyongMx;
use think\Db;

class Buy extends Common{
    
    public function checklogin() {
        if (request()->isPost()) {
            if (!$this->user_id) {
                return json(array('status' => 0, 'mess' => 'fail', 'data' => ''));
            }
            return json(array('status' => 1, 'mess' => 'success', 'data' => ''));
        }
    }
    
    //商品详情页立即购买调用接口
    public function purbuy(){
        if (request()->isPost()) {
            $user_id = $this->user_id;
            if (input('post.goods_id') && input('post.num')) {
                if (input('post.fangshi') && in_array(input('post.fangshi'), array(1, 2))) {
                    $goods_id = input('post.goods_id');
                    $num = input('post.num');
                    $fangshi = input('post.fangshi');

                    if (preg_match("/^\\+?[1-9][0-9]*$/", $num)) {
                        $goods = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.shop_id')->join('sp_shops b', 'a.shop_id = b.id', 'INNER')->where('a.id', $goods_id)->where('a.onsale', 1)->where('b.open_status', 1)->find();
                        if ($goods) {
                            $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goods['id'])->where('b.attr_type', 1)->select();
                            if ($radiores) {
                                if (input('post.goods_attr') && !is_array(input('post.goods_attr'))) {
                                    $gattr = trim(input('post.goods_attr'));
                                    $gattr = str_replace('，', ',', $gattr);
                                    $gattr = rtrim($gattr, ',');

                                    if ($gattr) {
                                        $gattr = explode(',', $gattr);
                                        $gattr = array_unique($gattr);

                                        if ($gattr && is_array($gattr)) {
                                            $radioattr = array();
                                            foreach ($radiores as $va) {
                                                $radioattr[$va['attr_id']][] = $va['id'];
                                            }

                                            $gattres = array();

                                            foreach ($gattr as $ga) {
                                                if (!empty($ga)) {
                                                    $goodsxs = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', $ga)->where('a.goods_id', $goods['id'])->where('b.attr_type', 1)->find();
                                                    if ($goodsxs) {
                                                        $gattres[$goodsxs['attr_id']] = $goodsxs['id'];
                                                    } else {
                                                        $value = array('status' => 400, 'mess' => '商品属性参数错误', 'data' => array('status' => 400));
                                                        return json($value);
                                                    }
                                                } else {
                                                    $value = array('status' => 400, 'mess' => '商品属性参数错误', 'data' => array('status' => 400));
                                                    return json($value);
                                                }
                                            }

                                            foreach ($radioattr as $key => $val) {
                                                if (empty($gattres[$key]) || !in_array($gattres[$key], $val)) {
                                                    $value = array('status' => 400, 'mess' => '请选择商品属性', 'data' => array('status' => 400));
                                                    return json($value);
                                                }
                                            }

                                            foreach ($gattres as $key2 => $val2) {
                                                if (empty($radioattr[$key2]) || !in_array($val2, $radioattr[$key2])) {
                                                    $value = array('status' => 400, 'mess' => '商品属性参数错误', 'data' => array('status' => 400));
                                                    return json($value);
                                                }
                                            }

                                            $goods_attr = implode(',', $gattr);
                                        } else {
                                            $value = array('status' => 400, 'mess' => '商品属性参数错误', 'data' => array('status' => 400));
                                            return json($value);
                                        }
                                    } else {
                                        $value = array('status' => 400, 'mess' => '商品属性参数错误', 'data' => array('status' => 400));
                                        return json($value);
                                    }
                                } else {
                                    $value = array('status' => 400, 'mess' => '请选择商品属性', 'data' => array('status' => 400));
                                    return json($value);
                                }
                            } else {
                                if (!input('post.goods_attr')) {
                                    $goods_attr = '';
                                } else {
                                    $value = array('status' => 400, 'mess' => '参数错误', 'data' => array('status' => 400));
                                    return json($value);
                                }
                            }

                            $ruinfo = array('id' => $goods['id'], 'shop_id' => $goods['shop_id']);
                            $ru_attr = $goods_attr;

                            $gongyong = new GongyongMx();
                            $activitys = $gongyong->pdrugp($ruinfo, $ru_attr);

                            if ((!$activitys) || ($activitys && $activitys['ac_type'] == 3 && $fangshi == 1)) {
                                if (!empty($goods_attr)) {
                                    $prores = Db::name('product')->where('goods_attr', $goods_attr)->where('goods_id', $goods['id'])->field('goods_number')->lock(true)->find();
                                } else {
                                    $prores = Db::name('product')->where('goods_id', $goods['id'])->where('goods_attr', '')->field('goods_number')->lock(true)->find();
                                }

                                if ($prores) {
                                    $goods_number = $prores['goods_number'];
                                } else {
                                    $goods_number = 0;
                                }
                            } else {
                                if ($activitys['ac_type'] == 1) {
                                    if ($num > $activitys['xznum']) {
                                        $value = array('status' => 400, 'mess' => '商品限购' . $activitys['xznum'] . '件', 'data' => array('status' => 400));
                                        return json($value);
                                    }

                                    $goods_number = $activitys['kucun'];
                                } else {
                                    if (!empty($goods_attr)) {
                                        $prores = Db::name('product')->where('goods_attr', $goods_attr)->where('goods_id', $goods['id'])->field('goods_number')->lock(true)->find();
                                    } else {
                                        $prores = Db::name('product')->where('goods_id', $goods['id'])->where('goods_attr', '')->field('goods_number')->lock(true)->find();
                                    }

                                    if ($prores) {
                                        $goods_number = $prores['goods_number'];
                                    } else {
                                        $goods_number = 0;
                                    }
                                }
                            }

                            if ($num <= 0 || $num > $goods_number) {
                                $value = array('status' => 400, 'mess' => $goods['goods_name'] . '库存不足', 'data' => array('status' => 400));
                                return json($value);
                            }

                            $purchs = Db::name('purch')->where('user_id', $user_id)->find();
                            if ($purchs) {
                                $count = Db::name('purch')->where('id', $purchs['id'])->where('user_id', $user_id)->update(array('goods_id' => $goods['id'], 'goods_attr' => $goods_attr, 'num' => $num, 'shop_id' => $goods['shop_id']));
                                if ($count !== false) {
                                    $value = array('status' => 200, 'mess' => '操作成功', 'data' => array('pur_id' => $purchs['id']));
                                } else {
                                    $value = array('status' => 400, 'mess' => '操作失败，请重试', 'data' => array('status' => 400));
                                }
                            } else {
                                $pur_id = Db::name('purch')->insertGetId(array('goods_id' => $goods['id'], 'goods_attr' => $goods_attr, 'num' => $num, 'user_id' => $user_id, 'shop_id' => $goods['shop_id']));
                                if ($pur_id) {
                                    $value = array('status' => 200, 'mess' => '操作成功', 'data' => array('pur_id' => $pur_id, 'fangshi' => $fangshi));
                                } else {
                                    $value = array('status' => 400, 'mess' => '操作失败，请重试', 'data' => array('status' => 400));
                                }
                            }
                        } else {
                            $value = array('status' => 400, 'mess' => '商品已下架或不存在', 'data' => array('status' => 400));
                        }
                    } else {
                        $value = array('status' => 400, 'mess' => '商品数量参数格式错误', 'data' => array('status' => 400));
                    }
                } else {
                    $value = array('status' => 400, 'mess' => '缺少订单类型参数', 'data' => array('status' => 400));
                }
            } else {
                $value = array('status' => 400, 'mess' => '缺少购买商品参数', 'data' => array('status' => 400));
            }
        } else {
            $value = array('status' => 400, 'mess' => '请求方式不正确', 'data' => array('status' => 400));
        }
        return json($value);
    }
    
    //立即购买成功后跳转接口
    public function buy_step_one() {
        $pur_id = input('pur_id');
        $user_id = $this->user_id;
        if (!$pur_id) {
            $this->error('参数错误', $this->gourl);
        }
        $addressList = $this->getAddress($user_id);
        $morenAddress = $this->getAddress($user_id, 1);
        $has_address = false;
        if (!empty($addressList)) {
            $has_address = true;
        }
        $is_moren = false;
        if (!empty($morenAddress)) {
            $is_moren = true;
        }
        $province = Db::name('province')->field('id,pro_name,zm')->where('checked',1)->where('pro_zs',1)->order('sort asc')->select();
        $pur_goods = $this->pursure($pur_id);
        if ($pur_goods['status'] != 200) {
            $this->error($pur_goods['mess'], $this->gourl);
        }
        $this->assign('addressList', $addressList);
        $this->assign('is_moren', $is_moren);
        $this->assign('has_address', $has_address);
        $this->assign('morenAddress', $morenAddress);
        $this->assign('province', $province);
        $this->assign('pur_goods', $pur_goods['data']);
        return $this->fetch();
    }
    
    //立即购买确认订单信息获取
    protected function pursure($pur_id, $fangshi = 1) {
        $user_id = $this->user_id;
        if ($pur_id) {
            if ($fangshi && in_array($fangshi, array(1, 2))) {
                $wallets = Db::name('wallet')->where('user_id', $user_id)->find();
                $purchs = Db::name('purch')->alias('a')->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_free,c.shop_name')->join('sp_goods b', 'a.goods_id = b.id', 'INNER')->join('sp_shops c', 'a.shop_id = c.id', 'INNER')->where('a.id', $pur_id)->where('a.user_id', $user_id)->where('b.onsale', 1)->where('c.open_status', 1)->find();
                if ($purchs) {
                    $goodinfos = array();

                    $webconfig = $this->webconfig;
                    $purchs['thumb_url'] = $webconfig['weburl'] . '/' . $purchs['thumb_url'];

                    $ruinfo = array('id' => $purchs['goods_id'], 'shop_id' => $purchs['shop_id']);
                    $ru_attr = $purchs['goods_attr'];

                    $gongyong = new GongyongMx();
                    $activitys = $gongyong->pdrugp($ruinfo, $ru_attr);

                    if ((!$activitys) || ($activitys && $activitys['ac_type'] == 3 && $fangshi == 1)) {
                        if (!empty($purchs['goods_attr'])) {
                            $prores = Db::name('product')->where('goods_attr', $purchs['goods_attr'])->where('goods_id', $purchs['goods_id'])->field('goods_number')->lock(true)->find();
                            if ($prores) {
                                $goods_number = $prores['goods_number'];
                            } else {
                                $goods_number = 0;
                            }
                            if ($purchs['num'] > 0 && $purchs['num'] <= $goods_number) {
                                $gasxres = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $purchs['goods_attr'])->where('a.goods_id', $purchs['goods_id'])->where('b.attr_type', 1)->select();
                                $goods_attr_str = '';
                                if ($gasxres) {
                                    foreach ($gasxres as $k => $v) {
                                        $purchs['shop_price'] += $v['attr_price'];
                                        if ($k == 0) {
                                            $goods_attr_str = $v['attr_name'] . ':' . $v['attr_value'];
                                        } else {
                                            $goods_attr_str = $goods_attr_str . ' ' . $v['attr_name'] . ':' . $v['attr_value'];
                                        }
                                    }
                                    $purchs['shop_price'] = sprintf("%.2f", $purchs['shop_price']);
                                }

                                $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'shop_id' => $purchs['shop_id'], 'shop_name' => $purchs['shop_name']);
                            } else {
                                return array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                            }
                        } else {
                            $prores = Db::name('product')->where('goods_id', $purchs['goods_id'])->where('goods_attr', '')->field('goods_number')->lock(true)->find();
                            if ($prores) {
                                $goods_number = $prores['goods_number'];
                            } else {
                                $goods_number = 0;
                            }
                            if ($purchs['num'] > 0 && $purchs['num'] <= $goods_number) {
                                $goods_attr_str = '';

                                $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'shop_id' => $purchs['shop_id'], 'shop_name' => $purchs['shop_name']);
                            } else {
                                return array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                            }
                        }
                    } else {
                        if ($activitys['ac_type'] == 1) {
                            $goods_number = $activitys['kucun'];
                        } else {
                            if (!empty($purchs['goods_attr'])) {
                                $prores = Db::name('product')->where('goods_attr', $purchs['goods_attr'])->where('goods_id', $purchs['goods_id'])->field('goods_number')->lock(true)->find();
                            } else {
                                $prores = Db::name('product')->where('goods_id', $purchs['goods_id'])->where('goods_attr', '')->field('goods_number')->lock(true)->find();
                            }

                            if ($prores) {
                                $goods_number = $prores['goods_number'];
                            } else {
                                $goods_number = 0;
                            }
                        }

                        if ($purchs['num'] > 0 && $purchs['num'] <= $goods_number) {
                            if (!empty($purchs['goods_attr'])) {
                                $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $purchs['goods_attr'])->where('a.goods_id', $purchs['goods_id'])->where('b.attr_type', 1)->select();
                                $goods_attr_str = '';
                                if ($gares) {
                                    foreach ($gares as $key => $val) {
                                        if ($key == 0) {
                                            $goods_attr_str = $val['attr_name'] . ':' . $val['attr_value'];
                                        } else {
                                            $goods_attr_str = $goods_attr_str . ' ' . $val['attr_name'] . ':' . $val['attr_value'];
                                        }
                                    }
                                }
                            } else {
                                $gares = array();
                                $goods_attr_str = '';
                            }

                            $purchs['shop_price'] = $activitys['price'];

                            $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'shop_id' => $purchs['shop_id'], 'shop_name' => $purchs['shop_name']);
                        } else {
                            return array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                        }
                    }

                    if ($goodinfos) {
                        $goodinfos['coupon_str'] = '';
                        $goodinfos['cxhuodong'] = array();
                        $goodinfos['youhui_price'] = 0;
                        $goodinfos['freight'] = 0;
                        $goodinfos['xiaoji_price'] = 0;

                        $xiaoji = sprintf("%.2f", $goodinfos['shop_price'] * $goodinfos['goods_num']);

                        $coupons = Db::name('coupon')->where('shop_id', $goodinfos['shop_id'])->where('start_time', 'elt', time())->where('end_time', 'gt', time() - 3600 * 24)->where('onsale', 1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                        if ($coupons) {
                            $couinfos = Db::name('member_coupon')->alias('a')->field('a.*,b.man_price,b.dec_price')->join('sp_coupon b', 'a.coupon_id = b.id', 'INNER')->where('a.user_id', $user_id)->where('a.is_sy', 0)->where('a.shop_id', $goodinfos['shop_id'])->where('b.start_time', 'elt', time())->where('b.end_time', 'gt', time() - 3600 * 24)->where('b.onsale', 1)->where('b.man_price', 'elt', $xiaoji)->order('b.man_price desc')->find();

                            if ($couinfos) {
                                $goodinfos['youhui_price'] += $couinfos['dec_price'];
                                $goodinfos['coupon_str'] = '满' . $couinfos['man_price'] . '减' . $couinfos['dec_price'] . '  已优惠' . $couinfos['dec_price'];
                            }
                        }

                        $promotionres = Db::name('promotion')->where('shop_id', $goodinfos['shop_id'])->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,start_time,end_time,info_id')->select();
                        if ($promotionres) {
                            foreach ($promotionres as $prv) {
                                $prom_typeres = Db::name('prom_type')->where('prom_id', $prv['id'])->select();
                                if ($prom_typeres) {
                                    $prohdsort = array();

                                    if (strpos(',' . $prv['info_id'] . ',', ',' . $goodinfos['id'] . ',') !== false) {
                                        foreach ($prom_typeres as $krp => $vrp) {
                                            if ($goodinfos['goods_num'] && $goodinfos['goods_num'] >= $vrp['man_num']) {
                                                $prohdsort[] = $vrp;
                                            }
                                        }

                                        if ($prohdsort) {
                                            $prohdsort = arraySort($prohdsort, 'man_num');
                                            $promhdinfo = $prohdsort[0];

                                            $zhekou = $promhdinfo['discount'] / 100;
                                            $zhekouprice = sprintf("%.2f", $goodinfos['shop_price'] * $zhekou);
                                            $youhui_price = ($goodinfos['shop_price'] - $zhekouprice) * $goodinfos['goods_num'];
                                            $youhui_price = sprintf("%.2f", $youhui_price);
                                            $goodinfos['youhui_price'] += $youhui_price;

                                            $zhe = $promhdinfo['discount'] / 10;
                                            $goodinfos['cxhuodong'][] = '部分商品满' . $promhdinfo['man_num'] . '件' . $zhe . '折  已优惠' . $youhui_price;
                                        }
                                        break;
                                    }
                                }
                            }
                        }

                        $goodinfos['youhui_price'] = sprintf("%.2f", $goodinfos['youhui_price']);

                        $goodinfos['xiaoji_price'] = sprintf("%.2f", $xiaoji - $goodinfos['youhui_price']);

                        //邮费
                        $baoyou = 1;
                        $goodinfos['freight_str'] = '普通配送 快递免邮';

                        if ($goodinfos['is_free'] == 0) {
                            $baoyou = 0;
                        }

                        if ($baoyou == 0) {
                            $shopinfos = Db::name('shops')->where('id', $goodinfos['shop_id'])->field('freight,reduce')->find();
                            $goodinfos['freight_str'] = '普通配送 运费' . $shopinfos['freight'] . '订单满' . $shopinfos['reduce'] . '免运费';
                            if ($goodinfos['xiaoji_price'] < $shopinfos['reduce']) {
                                $goodinfos['freight'] = $shopinfos['freight'];
                                $goodinfos['xiaoji_price'] = sprintf("%.2f", $goodinfos['xiaoji_price'] + $shopinfos['freight']);
                            }
                        }

                        $zong_num = $goodinfos['goods_num'];

                        $zsprice = $goodinfos['xiaoji_price'];

                        $goodinfores = array();
                        $hqgoodsinfos = array();

                        $goodinfores[] = $goodinfos;

                        foreach ($goodinfores as $kd => $vd) {
                            $hqgoodsinfos[$vd['shop_id']]['goodres'][] = array('id' => $vd['id'], 'goods_name' => $vd['goods_name'], 'thumb_url' => $vd['thumb_url'], 'goods_attr_str' => $vd['goods_attr_str'], 'shop_price' => $vd['shop_price'], 'goods_num' => $vd['goods_num'], 'is_free' => $vd['is_free'], 'shop_id' => $vd['shop_id'], 'shop_name' => $vd['shop_name']);
                            $hqgoodsinfos[$vd['shop_id']]['coupon_str'] = $vd['coupon_str'];
                            $hqgoodsinfos[$vd['shop_id']]['cxhuodong'] = $vd['cxhuodong'];
                            $hqgoodsinfos[$vd['shop_id']]['youhui_price'] = $vd['youhui_price'];
                            $hqgoodsinfos[$vd['shop_id']]['freight'] = $vd['freight'];
                            $hqgoodsinfos[$vd['shop_id']]['shopgoods_num'] = $vd['goods_num'];
                            $hqgoodsinfos[$vd['shop_id']]['xiaoji_price'] = $vd['xiaoji_price'];
                        }

                        $hqgoodsinfos = array_values($hqgoodsinfos);

                        $dizis = Db::name('address')->alias('a')->field('a.id,a.contacts,a.phone,a.address,b.pro_name,c.city_name,d.area_name')->join('sp_province b', 'a.pro_id = b.id', 'LEFT')->join('sp_city c', 'a.city_id = c.id', 'LEFT')->join('sp_area d', 'a.area_id = d.id', 'LEFT')->where('a.user_id', $user_id)->where('a.moren', 1)->find();

                        $value = array('status' => 200, 'mess' => '获取商品信息成功', 'data' => array('goodinfo' => $hqgoodsinfos, 'zong_num' => $zong_num, 'zsprice' => $zsprice, 'address' => $dizis, 'wallet_price' => $wallets['price'], 'pur_id' => $pur_id));
                    } else {
                        $value = array('status' => 400, 'mess' => '找不到相关商品信息', 'data' => array('status' => 400));
                    }
                } else {
                    $value = array('status' => 400, 'mess' => '找不到相关商品信息', 'data' => array('status' => 400));
                }
            } else {
                $value = array('status' => 400, 'mess' => '缺少订单类型参数', 'data' => array('status' => 400));
            }
        } else {
            $value = array('status' => 400, 'mess' => '缺少购买商品参数', 'data' => array('status' => 400));
        }
        return $value;
    }
    
    public function get_city_list() {
        $pro_id = input('post.pro_id');
        if (!$pro_id || !is_numeric($pro_id)) {
            return json(array('status' => 0, 'mess' => '参数错误', 'data' => ''));
        }
        $city_list = Db::name('city')->where('pro_id',$pro_id)->where('checked',1)->where('city_zs',1)->field('id,city_name,zm')->order('sort asc')->select();
        if (empty($city_list)) {
            return json(array('status' => 0, 'mess' => 'no data', 'data' => ''));
        }
        return json(array('status' => 1, 'mess' => 'success', 'data' => $city_list));
    }
    
    public function get_area_list() {
        $city_id = input('post.city_id');
        if (!$city_id || !is_numeric($city_id)) {
            return json(array('status' => 0, 'mess' => '参数错误', 'data' => ''));
        }
        $area_list = Db::name('area')->where('city_id',$city_id)->where('checked',1)->field('id,area_name,zm')->order('sort asc')->select();
        if (empty($area_list)) {
            return json(array('status' => 0, 'mess' => 'no data', 'data' => ''));
        }
        return json(array('status' => 1, 'mess' => 'success', 'data' => $area_list));
    }
    
    public function update_morem_address() {
        $user_id = $this->user_id;
        $address_id = input('post.address_id');
        if (!$address_id) {
            return json(array('status' => 0, 'mess' => '参数错误', 'data' => ''));
        }
        $addressList = $this->getAddress($user_id);
        if (!$addressList) {
            return json(array('status' => 0, 'mess' => '找不到相关地址信息', 'data' => ''));
        }
        
        Db::startTrans();
        try{
            foreach ($addressList as $address) {
                if ($address['id'] == $address_id && !$address['moren']) {
                    Db::name('address')->where('id', $address_id)->where('user_id', $user_id)->update(array('moren' => 1));
                }
                if ($address['id'] != $address_id && $address['moren']) {
                    Db::name('address')->where('id', $address['id'])->where('user_id', $user_id)->update(array('moren' => 0));
                }
            }
            // 提交事务
            Db::commit();
            $value = array('status' => 1, 'mess' => '编辑地址成功', 'data' => '');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $value = array('status' => 0, 'mess' => '编辑地址失败', 'data' => '');
        }
        return json($value);
    }
    
    public function add_address() {
        if (request()->isPost()) {
            $user_id = $this->user_id;
            $data = input('post.');
            if (empty($data['moren']) || !in_array($data['moren'], array(0, 1))) {
                $data['moren'] = 0;
            }
            
            if (!trim($data['contacts'])) {
                return json(array('status' => 0, 'mess' => '请选填写收货人姓名', 'data' => ''));
            }
            
            $pro_id = isset($data['pro_id']) ? $data['pro_id'] : 0;
            $city_id = isset($data['city_id']) ? $data['city_id'] : 0;
            $area_id = isset($data['area_id']) ? $data['area_id'] : 0;
            
            if (!$pro_id) {
                return json(array('status' => 0, 'mess' => '请选择省份', 'data' => ''));
            }
            
            if (!$city_id) {
                return json(array('status' => 0, 'mess' => '请选择城市', 'data' => ''));
            }
            
            if (!$area_id) {
                return json(array('status' => 0, 'mess' => '请选择区县', 'data' => ''));
            }
            
            if (!trim($data['address'])) {
                return json(array('status' => 0, 'mess' => '请选填写详细地址', 'data' => ''));
            }
            $phone = trim($data['phone']);
            if (!$phone) {
                return json(array('status' => 0, 'mess' => '请选填写手机号', 'data' => ''));
            }
            $string = "^((13[0-9])|(14[0-9])|(15[0,1,2,3,4,5,6,7,8,9])|(16[0-9])|(19[0-9])|(18[0-9])|(17[0,1,3,5,6,7,8,9]))\\d{8}$^";
            $mathch = preg_match($string, $phone);
            if (!$mathch) {
                return json(array("status" => 0, "mess" => "手机号格式错误", "data" => ""));
            }

            $pros = Db::name('province')->where('id', $pro_id)->field('id')->find();
            if ($pros) {
                $citys = Db::name('city')->where('id', $city_id)->where('pro_id', $pros['id'])->field('id')->find();
                if ($citys) {
                    $areas = Db::name('area')->where('id', $area_id)->where('city_id', $citys['id'])->field('id')->find();
                    if (!$areas) {
                        return json(array('status' => 0, 'mess' => '请选择区县', 'data' => ''));
                    }
                } else {
                    return json(array('status' => 0, 'mess' => '请选择城市', 'data' => ''));
                }
            } else {
                return json(array('status' => 0, 'mess' => '请选择省份', 'data' => ''));
            }

            // 启动事务
            Db::startTrans();
            try {
                $dz_id = Db::name('address')->insertGetId(array('contacts' => $data['contacts'], 'phone' => $data['phone'], 'pro_id' => $data['pro_id'], 'city_id' => $data['city_id'], 'area_id' => $data['area_id'], 'address' => $data['address'], 'user_id' => $user_id, 'addtime' => time(), 'moren' => $data['moren']));
                if ($dz_id && $data['moren'] == 1) {
                    $dizhires = Db::name('address')->where('user_id', $user_id)->where('moren', 1)->where('id', 'neq', $dz_id)->select();
                    if ($dizhires) {
                        foreach ($dizhires as $v) {
                            Db::name('address')->where('id', $v['id'])->where('user_id', $user_id)->update(array('moren' => 0));
                        }
                    }
                }
                // 提交事务
                Db::commit();
                $value = array('status' => 1, 'mess' => '增加地址成功', 'data' => '');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $value = array('status' => 0, 'mess' => '增加地址失败', 'data' => '');
            }
        } else {
            $value = array('status' => 0, 'mess' => '请求方式不正确', 'data' => '');
        }
        return json($value);
    }
    
    public function del_address() {
        if(!request()->isPost()){
            return json(array('status' => 0, 'mess' => '请求方式不正确', 'data' => ''));
        }
        $user_id = $this->user_id;
        $address_id = input('post.address_id');
        if (!$address_id) {
            return json(array('status' => 0, 'mess' => '参数错误', 'data' => ''));
        }
        $addressinfo = Db::name('address')->where('id', $address_id)->where('user_id',$user_id)->find();
        if (!$addressinfo) {
            return json(array('status' => 0, 'mess' => '找不到相关地址信息', 'data' => ''));
        }
        $res = Db::name('address')->where('id', $address_id)->where('user_id',$user_id)->delete();
        if (!$res) {
            return json(array('status' => 0, 'mess' => '删除失败', 'data' => ''));
        }
        return json(array('status' => 1, 'mess' => '删除成功', 'data' => ''));
    }
    
    public function getAddress($user_id, $moren = 0) {
        $address = Db::name('address')->alias('a')->field('a.id,a.contacts,a.phone,a.address,a.moren,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','INNER')->join('sp_city c','a.city_id = c.id','INNER')->join('sp_area d','a.area_id = d.id','INNER')->order('a.addtime desc')->where('a.user_id',$user_id);
        if ($moren) {
            return $address->where('moren', $moren)->find();
        }
        return $address->select();
    }
}