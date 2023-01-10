<?php

namespace app\index\controller;

use app\index\controller\Common;
use app\index\model\Gongyong as GongyongMx;
use think\Db;

class Cart extends Common {

    //获取购物车商品列表接口
    public function index() {
        $user_id = $this->user_id;
        $page = input('get.page') ? input('get.page') : 1;
        if ($page && preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            $webconfig = $this->webconfig;
            $perpage = 20;
            $offset = ($page - 1) * $perpage;

            $cartres = Db::name('cart')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.num,a.shop_id,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')->join('sp_goods b', 'a.goods_id = b.id', 'INNER')->join('sp_shops c', 'a.shop_id = c.id', 'INNER')->where('a.user_id', $user_id)->where('b.onsale', 1)->where('c.open_status', 1)->order('a.add_time desc')->limit($offset, $perpage)->select();

            $cartinfores = array();

            if ($cartres) {
                foreach ($cartres as $k => $v) {
                    $cartres[$k]['icon'] = 0;
                    $cartres[$k]['thumb_url'] = $webconfig['weburl'] . '/' . $v['thumb_url'];

                    $ruinfo = array('id' => $v['goods_id'], 'shop_id' => $v['shop_id']);
                    $ru_attr = $v['goods_attr'];

                    $gongyong = new GongyongMx();
                    $activitys = $gongyong->pdrugp($ruinfo, $ru_attr);

                    if ($activitys) {
                        if ($activitys['ac_type'] == 3) {
                            unset($cartres[$k]);
                            continue;
                        }

                        $cartres[$k]['is_activity'] = $activitys['ac_type'];

                        if ($activitys['ac_type'] == 1) {
                            $cartres[$k]['xznum'] = $activitys['xznum'];
                        }

                        $cartres[$k]['sytime'] = time2string($activitys['end_time'] - time());

                        if ($v['goods_attr']) {
                            $cartres[$k]['goods_attr_str'] = '';
                            $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $v['goods_attr'])->where('a.goods_id', $v['goods_id'])->where('b.attr_type', 1)->select();
                            if ($gares) {
                                foreach ($gares as $key => $val) {
                                    if ($key == 0) {
                                        $cartres[$k]['goods_attr_str'] = $val['attr_name'] . ':' . $val['attr_value'];
                                    } else {
                                        $cartres[$k]['goods_attr_str'] = $cartres[$k]['goods_attr_str'] . ' ' . $val['attr_name'] . ':' . $val['attr_value'];
                                    }
                                }
                            }
                        } else {
                            $gares = array();
                            $cartres[$k]['goods_attr_str'] = '';
                        }

                        $cartres[$k]['shop_price'] = $activitys['price'];
                    } else {
                        $cartres[$k]['is_activity'] = 0;

                        if ($v['goods_attr']) {
                            $cartres[$k]['goods_attr_str'] = '';
                            $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $v['goods_attr'])->where('a.goods_id', $v['goods_id'])->where('b.attr_type', 1)->select();
                            if ($gares) {
                                foreach ($gares as $key => $val) {
                                    $cartres[$k]['shop_price'] += $val['attr_price'];
                                    if ($key == 0) {
                                        $cartres[$k]['goods_attr_str'] = $val['attr_name'] . ':' . $val['attr_value'];
                                    } else {
                                        $cartres[$k]['goods_attr_str'] = $cartres[$k]['goods_attr_str'] . ' ' . $val['attr_name'] . ':' . $val['attr_value'];
                                    }
                                }
                                $cartres[$k]['shop_price'] = sprintf("%.2f", $cartres[$k]['shop_price']);
                            }
                        } else {
                            $gares = array();
                            $cartres[$k]['goods_attr_str'] = '';
                        }
                    }
                }

                foreach ($cartres as $cr) {
                    $cartinfores[$cr['shop_id']]['goodres'][] = $cr;
                }

                foreach ($cartinfores as $kc => $vc) {
                    $cartinfores[$kc]['couponinfos'] = array('is_show' => 0, 'infos' => '');
                    $cartinfores[$kc]['promotions'] = array('is_show' => 0, 'infos' => '');
                    $cartinfores[$kc]['icon'] = 0;

                    $coupons = Db::name('coupon')->where('shop_id', $kc)->where('start_time', 'elt', time())->where('end_time', 'gt', time() - 3600 * 24)->where('onsale', 1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                    if ($coupons) {
                        $cartinfores[$kc]['couponinfos'] = array('is_show' => 1, 'infos' => '用优惠券可享满' . $coupons['man_price'] . '减' . $coupons['dec_price']);
                    }

                    $proarr = array();

                    foreach ($vc['goodres'] as $vp) {
                        $promotions = Db::name('promotion')->where("find_in_set('" . $vp['goods_id'] . "',info_id)")->where('shop_id', $vp['shop_id'])->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,start_time,end_time')->find();
                        if ($promotions) {
                            $prom_typeres = Db::name('prom_type')->where('prom_id', $promotions['id'])->select();
                            if ($prom_typeres) {
                                foreach ($prom_typeres as $kcp => $vcp) {
                                    $zhekou = $vcp['discount'] / 10;
                                    if ($kcp == 0) {
                                        $proarr[$promotions['id']] = '部分商品满 ' . $vcp['man_num'] . '件 享' . $zhekou . '折';
                                    } else {
                                        $proarr[$promotions['id']] = $proarr[$promotions['id']] . '  满 ' . $vcp['man_num'] . '件 享' . $zhekou . '折';
                                    }
                                }
                            }
                        }
                    }

                    if ($proarr) {
                        $proarr = array_values($proarr);
                        $cartinfores[$kc]['promotions'] = array('is_show' => 1, 'infos' => $proarr);
                    }
                }

                $cartinfores = array_values($cartinfores);
            }
            $this->assign('cartinfores', $cartinfores);
            return $this->fetch();
        } else {
            $this->error('缺少页数参数', $this->gourl);
        }
    }

    //加入购物车
    public function addcart() {
        if (request()->isPost()) {
            $user_id = $this->user_id;
            $data = input('post.');
            if (!empty($data['goods_id']) && !empty($data['num'])) {
                $goods_id = $data['goods_id'];
                $num = $data['num'];

                if (preg_match("/^\\+?[1-9][0-9]*$/", $num)) {
                    $goods = Db::name('goods')->alias('a')->field('a.id,a.shop_price,a.zs_price,a.shop_id')->join('sp_shops b', 'a.shop_id = b.id', 'INNER')->where('a.id', $goods_id)->where('a.onsale', 1)->where('b.open_status', 1)->find();
                    if ($goods) {
                        $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goods_id)->where('b.attr_type', 1)->select();
                        if ($radiores) {
                            if (!empty($data['goods_attr']) && !is_array($data['goods_attr'])) {
                                $data['goods_attr'] = trim($data['goods_attr']);
                                $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
                                $data['goods_attr'] = rtrim($data['goods_attr'], ',');

                                if ($data['goods_attr']) {
                                    $gattr = explode(',', $data['goods_attr']);
                                    $gattr = array_unique($gattr);

                                    if ($gattr && is_array($gattr)) {
                                        $radioattr = array();
                                        foreach ($radiores as $va) {
                                            $radioattr[$va['attr_id']][] = $va['id'];
                                        }

                                        $gattres = array();

                                        foreach ($gattr as $ga) {
                                            if (!empty($ga)) {
                                                $goodsxs = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', $ga)->where('a.goods_id', $goods_id)->where('b.attr_type', 1)->find();
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
                            if (empty($data['goods_attr'])) {
                                $goods_attr = '';
                            } else {
                                $value = array('status' => 400, 'mess' => '参数错误', 'data' => array('status' => 400));
                                return json($value);
                            }
                        }


                        $ruinfo = array('id' => $goods_id, 'shop_id' => $goods['shop_id']);
                        $ru_attr = $goods_attr;

                        $gongyong = new GongyongMx();
                        $activitys = $gongyong->pdrugp($ruinfo, $ru_attr);

                        if ($activitys) {
                            if ($activitys['ac_type'] == 1) {
                                $goods_number = $activitys['kucun'];
                            } else {
                                if ($activitys['ac_type'] == 3) {
                                    $value = array('status' => 400, 'mess' => '拼团活动商品不允许加入购物车', 'data' => array('status' => 400));
                                    return json($value);
                                }

                                if (!empty($goods_attr)) {
                                    $prores = Db::name('product')->where('goods_attr', $goods_attr)->where('goods_id', $goods_id)->field('goods_number')->find();
                                } else {
                                    $prores = Db::name('product')->where('goods_id', $goods_id)->where('goods_attr', '')->field('goods_number')->find();
                                }

                                if ($prores) {
                                    $goods_number = $prores['goods_number'];
                                } else {
                                    $goods_number = 0;
                                }
                            }
                        } else {
                            if (!empty($goods_attr)) {
                                $prores = Db::name('product')->where('goods_attr', $goods_attr)->where('goods_id', $goods_id)->field('goods_number')->find();
                            } else {
                                $prores = Db::name('product')->where('goods_id', $goods_id)->where('goods_attr', '')->field('goods_number')->find();
                            }

                            if ($prores) {
                                $goods_number = $prores['goods_number'];
                            } else {
                                $goods_number = 0;
                            }
                        }

                        if ($goods_number > 0) {
                            if ($num > 0 && $num <= $goods_number) {
                                $cgoods = Db::name('cart')->where('user_id', $user_id)->where('goods_id', $goods_id)->where('goods_attr', $goods_attr)->where('shop_id', $goods['shop_id'])->find();
                                $datainfo = array();

                                if (!$cgoods) {
                                    if ($activitys && $activitys['ac_type'] == 1) {
                                        if ($num > $activitys['xznum']) {
                                            $value = array('status' => 400, 'mess' => '该秒杀商品限购' . $activitys['xznum'] . '件', 'data' => array('status' => 400));
                                            return json($value);
                                        }
                                    }

                                    $datainfo['goods_id'] = $goods_id;
                                    $datainfo['goods_attr'] = $goods_attr;
                                    $datainfo['num'] = $num;
                                    $datainfo['shop_id'] = $goods['shop_id'];
                                    $datainfo['user_id'] = $user_id;
                                    $datainfo['add_time'] = time();
                                    $lastId = Db::name('cart')->insert($datainfo);
                                    if ($lastId) {
                                        $value = array('status' => 200, 'mess' => '加入购物车成功', 'data' => array('status' => 200));
                                    } else {
                                        $value = array('status' => 400, 'mess' => '操作失败，请重试', 'data' => array('status' => 400));
                                    }
                                } else {
                                    if ($cgoods['num'] + $num <= $goods_number) {
                                        if ($activitys && $activitys['ac_type'] == 1) {
                                            if ($cgoods['num'] + $num > $activitys['xznum']) {
                                                $value = array('status' => 400, 'mess' => '该秒杀商品限购' . $activitys['xznum'] . '件', 'data' => array('status' => 400));
                                                return json($value);
                                            }
                                        }


                                        $datainfo['num'] = $cgoods['num'] + $num;
                                        $datainfo['id'] = $cgoods['id'];
                                        $count = Db::name('cart')->update($datainfo);
                                        if ($count > 0) {
                                            $value = array('status' => 200, 'mess' => '加入购物车成功', 'data' => array('status' => 200));
                                        } else {
                                            $value = array('status' => 400, 'mess' => '操作失败，请重试', 'data' => array('status' => 400));
                                        }
                                    } else {
                                        $value = array('status' => 400, 'mess' => '商品库存不足', 'data' => array('status' => 400));
                                    }
                                }
                            } else {
                                $value = array('status' => 400, 'mess' => '商品库存不足', 'data' => array('status' => 400));
                            }
                        } else {
                            $value = array('status' => 400, 'mess' => '商品库存不足', 'data' => array('status' => 400));
                        }
                    } else {
                        $value = array('status' => 400, 'mess' => '商品已下架或不存在', 'data' => array('status' => 400));
                    }
                } else {
                    $value = array('status' => 400, 'mess' => '商品数量参数格式错误，加入购物车失败', 'data' => array('status' => 400));
                }
            } else {
                $value = array('status' => 400, 'mess' => '缺少参数，加入购物车失败', 'data' => array('status' => 400));
            }
        } else {
            $value = array('status' => 400, 'mess' => '请求方式不正确', 'data' => array('status' => 400));
        }
        return json($value);
    }

    //修改购物车商品信息
    public function editcart() {
        if (request()->isPost()) {
            $user_id = $this->user_id;
            if (input('post.cart_id')) {
                if (input('post.num')) {
                    $cart_id = input('post.cart_id');
                    $num = input('post.num');
                    if (preg_match("/^\\+?[1-9][0-9]*$/", $num)) {
                        $carts = Db::name('cart')->alias('a')->field('a.*')->join('sp_goods b', 'a.goods_id = b.id', 'INNER')->join('sp_shops c', 'a.shop_id = c.id', 'INNER')->where('a.id', $cart_id)->where('a.user_id', $user_id)->where('b.onsale', 1)->where('c.open_status', 1)->find();
                        if ($carts) {

                            $ruinfo = array('id' => $carts['goods_id'], 'shop_id' => $carts['shop_id']);
                            $ru_attr = $carts['goods_attr'];

                            $gongyong = new GongyongMx();
                            $activitys = $gongyong->pdrugp($ruinfo, $ru_attr);

                            if ($activitys) {
                                if ($activitys['ac_type'] == 1) {
                                    $goods_number = $activitys['kucun'];
                                } else {
                                    if ($activitys['ac_type'] == 3) {
                                        $value = array('status' => 400, 'mess' => '拼团活动商品不允许操作购物车', 'data' => array('status' => 400));
                                        return json($value);
                                    }

                                    if (!empty($carts['goods_attr'])) {
                                        $prores = Db::name('product')->where('goods_attr', $carts['goods_attr'])->where('goods_id', $carts['goods_id'])->field('goods_number')->find();
                                    } else {
                                        $prores = Db::name('product')->where('goods_id', $carts['goods_id'])->where('goods_attr', '')->field('goods_number')->find();
                                    }

                                    if ($prores) {
                                        $goods_number = $prores['goods_number'];
                                    } else {
                                        $goods_number = 0;
                                    }
                                }
                            } else {
                                if (!empty($carts['goods_attr'])) {
                                    $prores = Db::name('product')->where('goods_attr', $carts['goods_attr'])->where('goods_id', $carts['goods_id'])->field('goods_number')->find();
                                } else {
                                    $prores = Db::name('product')->where('goods_id', $carts['goods_id'])->where('goods_attr', '')->field('goods_number')->find();
                                }

                                if ($prores) {
                                    $goods_number = $prores['goods_number'];
                                } else {
                                    $goods_number = 0;
                                }
                            }

                            if ($num < $carts['num']) {
                                $count = Db::name('cart')->where('id', $cart_id)->where('user_id', $user_id)->update(array('num' => $num));
                                if ($count > 0) {
                                    $value = array('status' => 200, 'mess' => '操作成功', 'data' => array('status' => 200));
                                } else {
                                    $value = array('status' => 400, 'mess' => '操作失败', 'data' => array('status' => 400));
                                }
                            } elseif ($num == $carts['num']) {
                                $value = array('status' => 400, 'mess' => '操作失败', 'data' => array('status' => 400));
                            } elseif ($num > $carts['num']) {
                                if ($num <= $goods_number) {
                                    if ($activitys && $activitys['ac_type'] == 1) {
                                        if ($num > $activitys['xznum']) {
                                            $value = array('status' => 400, 'mess' => '该秒杀商品限购' . $activitys['xznum'] . '件', 'data' => array('status' => 400));
                                            return json($value);
                                        }
                                    }

                                    $count = Db::name('cart')->where('id', $cart_id)->where('user_id', $user_id)->update(array('num' => $num));
                                    if ($count > 0) {
                                        $value = array('status' => 200, 'mess' => '操作成功', 'data' => array('status' => 200));
                                    } else {
                                        $value = array('status' => 400, 'mess' => '操作失败', 'data' => array('status' => 400));
                                    }
                                } else {
                                    $value = array('status' => 400, 'mess' => '商品库存不足', 'data' => array('status' => 400));
                                }
                            }
                        } else {
                            $value = array('status' => 400, 'mess' => '找不到相关购物车信息', 'data' => array('status' => 400));
                        }
                    } else {
                        $value = array('status' => 400, 'mess' => '商品数量参数格式错误', 'data' => array('status' => 400));
                    }
                } else {
                    $value = array('status' => 400, 'mess' => '商品数量参数错误', 'data' => array('status' => 400));
                }
            } else {
                $value = array('status' => 400, 'mess' => '缺少购物车参数', 'data' => array('status' => 400));
            }
        } else {
            $value = array('status' => 400, 'mess' => '请求方式不正确', 'data' => array('status' => 400));
        }
        return json($value);
    }

    //删除购物车信息
    public function delcart() {
        if (request()->isPost()) {
            $user_id = $this->user_id;
            if (!$user_id) {
                return json(array('status' => 400, 'mess' => '没有user_id', 'data' => array('status' => 400)));
            }
            if (input('post.cart_id') && !is_array(input('post.cart_id'))) {
                $cart_id = input('post.cart_id');
                $cart_id = trim($cart_id);
                $cart_id = str_replace('，', ',', $cart_id);
                $cart_id = rtrim($cart_id, ',');

                if ($cart_id) {
                    if (strpos($cart_id, ',') !== false) {
                        $cartres = explode(',', $cart_id);
                        $cartres = array_unique($cartres);

                        if ($cartres && is_array($cartres)) {
                            foreach ($cartres as $v) {
                                if (!empty($v)) {
                                    $carts = Db::name('cart')->where('id', $v)->where('user_id', $user_id)->find();
                                    if (!$carts) {
                                        $value = array('status' => 400, 'mess' => '购物车参数错误', 'data' => array('status' => 400));
                                        return json($value);
                                    }
                                } else {
                                    $value = array('status' => 400, 'mess' => '购物车参数错误', 'data' => array('status' => 400));
                                    return json($value);
                                }
                            }

                            $cartstr = implode(',', $cartres);
                            $count = Db::name('cart')->where('id', 'in', $cartstr)->delete();
                        } else {
                            $value = array('status' => 400, 'mess' => '购物车参数错误', 'data' => array('status' => 400));
                            return json($value);
                        }
                    } else {
                        $carts = Db::name('cart')->where('id', $cart_id)->where('user_id', $user_id)->find();
                        if ($carts) {
                            $count = Db::name('cart')->where('id', $cart_id)->delete();
                        } else {
                            $value = array('status' => 400, 'mess' => '购物车参数错误', 'data' => array('status' => 400));
                            return json($value);
                        }
                    }

                    if ($count > 0) {
                        $value = array('status' => 200, 'mess' => '删除成功', 'data' => array('status' => 200));
                    } else {
                        $value = array('status' => 400, 'mess' => '删除失败', 'data' => array('status' => 400));
                    }
                } else {
                    $value = array('status' => 400, 'mess' => '购物车参数错误', 'data' => array('status' => 400));
                }
            } else {
                $value = array('status' => 400, 'mess' => '缺少购物车参数', 'data' => array('status' => 400));
            }
        } else {
            $value = array('status' => 400, 'mess' => '请求方式不正确', 'data' => array('status' => 400));
        }
        return json($value);
    }

}
