<?php

namespace app\apicloud\controller;

use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use app\apicloud\model\Member as MemberModel;
use app\apicloud\model\MemberBrowse;
use think\Db;

/**
 * 购买掌柜卡控制器
 */
class BuyMember extends Common
{
    //VIP商品详情
    public function findVipInfo()
    {
        if (!request()->isPost()) return returnJson(400, '请求方式不正确', ['status' => 400]);
        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate(0);
        if ($result['status'] !== 200) return json($result);
        if (!empty($result['user_id'])) {
            $user_id = $result['user_id'];
            bandPid($user_id, input('post.shareid'));
        } else {
            $user_id = 0;
        }

        $pin_id = '';
        $tuan_id = '';
        $memberpinres = array();

        if (!input('post.goods_id')) return returnJson(400, '缺少商品ID', ['status' => 400]);
        $goods_id = input('post.goods_id');
        $goods = Db::name('goods')
            ->alias('a')
            ->field('a.id,a.goods_name,a.thumb_url,a.shop_price,a.min_market_price,a.max_market_price,a.min_price,a.max_price,a.zs_price,a.goods_desc,a.fuwu,a.is_free,a.leixing,a.is_activity,a.shop_id')
            ->join('sp_shops b', 'a.shop_id = b.id', 'INNER')
            ->where('a.id', $goods_id)
            // ->where('a.onsale', 1)
            // ->where('b.open_status', 1)
            ->find();
        if (!$goods) return returnJson(400, '商品已下架或不存在', ['status' => 400]);
        MemberBrowse::addBrowse($goods_id, $user_id);
        $webconfig = $this->webconfig;
        $goods['thumb_url'] = $webconfig['weburl'] . '/' . $goods['thumb_url'];
        $goods['goods_desc'] = str_replace("/public/", $webconfig['weburl'] . "/public/", $goods['goods_desc']);
        $goods['goods_desc'] = str_replace("<img", "<img style='width:100%;'", $goods['goods_desc']);

        if ($goods['min_market_price'] != $goods['max_market_price']) {
            $goods['zs_market_price'] = $goods['min_market_price'] . '-' . $goods['max_market_price'];
        } else {
            $goods['zs_market_price'] = $goods['min_market_price'];
        }

        if ($user_id) {
            $colls = Db::name('coll_goods')->where('user_id', $user_id)->where('goods_id', $goods['id'])->find();
            if ($colls) {
                $goods['coll_goods'] = 1;
            } else {
                $goods['coll_goods'] = 0;
            }
        } else {
            $goods['coll_goods'] = 0;
        }

        $goods['shop_token'] = 'cxy365'; //默认的自营token
        $member_shops = Db::name('member')->where('shop_id', $goods['shop_id'])->field('id')->find();
        if ($member_shops) {
            $shoptoken_infos = Db::name('rxin')->where('user_id', $member_shops['id'])->field('token')->find();
            if ($shoptoken_infos) {
                $goods['shop_token'] = $shoptoken_infos['token'];
            }
        }

        $onetime = date('Y-m-d', time() - 3600 * 24 * 30);
        $oneriqi = strtotime($onetime);

        $goods['sale_number'] = Db::name('order_goods')->alias('a')->join('sp_order b', 'a.order_id = b.id', 'INNER')->where('a.goods_id', $goods['id'])->where('b.state', 1)->where('b.addtime', 'egt', $oneriqi)->sum('a.goods_num');

        $gpres = Db::name('goods_pic')->where('goods_id', $goods['id'])->field('id,img_url,sort')->order('sort asc')->select();
        foreach ($gpres as $kp => $vp) {
            $gpres[$kp]['img_url'] = $webconfig['weburl'] . '/' . $vp['img_url'];
        }

        $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,a.attr_pic,b.attr_name,b.attr_type')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goods['id'])->where('b.attr_type', 1)->select();

        $guige = array();
        $colores = array();

        $radioattr = array();
        if ($radiores) {
            foreach ($radiores as $kra => $vra) {
                if ($vra['attr_pic']) {
                    $radiores[$kra]['attr_pic'] = $webconfig['weburl'] . '/' . $vra['attr_pic'];
                }

                $radiores[$kra]['check'] = 'false';

                if ($vra['attr_name'] == '颜色分类') {
                    if ($vra['attr_pic']) {
                        $colores[] = $webconfig['weburl'] . '/' . $vra['attr_pic'];
                    } else {
                        $colores[] = '';
                    }
                }
            }

            foreach ($radiores as $v) {
                $radioattr[$v['attr_id']][] = $v;
            }

            foreach ($radioattr as $kad => $vad) {
                $guige[] = $vad[0]['attr_name'];
            }
        }

        $radioattr = array_values($radioattr);

        $uniattr = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,b.attr_name,b.attr_type')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goods['id'])->where('b.attr_type', 0)->select();

        $goods_attr = '';
        $goods_attr_str = '';
        $activitys = array();

        if (input('post.rush_id') && !input('post.group_id') && !input('post.assem_id')) {
            //秒杀
            $rush_id = input('post.rush_id');
            $activitys = Db::name('rush_activity')->where('id', $rush_id)->where('goods_id', $goods['id'])->where('shop_id', $goods['shop_id'])->where('checked', 1)->where('recommend', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,goods_id,goods_attr,price,num,xznum,kucun,sold,start_time,end_time')->find();
            if ($activitys) {
                $activitys['ac_type'] = 1;
            }
        } elseif (input('post.group_id') && !input('post.rush_id') && !input('post.assem_id')) {
            //团购
            $group_id = input('post.group_id');
            $activitys = Db::name('group_buy')->where('id', $group_id)->where('goods_id', $goods['id'])->where('shop_id', $goods['shop_id'])->where('checked', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,goods_id,goods_attr,price,start_time,end_time')->find();
            if ($activitys) {
                $activitys['ac_type'] = 2;
            }
        } elseif (input('post.assem_id') && !input('post.rush_id') && !input('post.group_id')) {
            //拼团
            $assem_id = input('post.assem_id');
            $activitys = Db::name('assemble')->where('id', $assem_id)->where('goods_id', $goods['id'])->where('shop_id', $goods['shop_id'])->where('checked', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')->find();
            if ($activitys) {
                $activitys['ac_type'] = 3;
            }
        }

        if (empty($activitys)) {
            $ruinfo = array('id' => $goods['id'], 'shop_id' => $goods['shop_id']);
            $gongyong = new GongyongMx();
            $activitys = $gongyong->pdrugp($ruinfo);
        }

        $activity_info = array();

        if ($activitys) {
            $goods['is_activity'] = $activitys['ac_type'];

            if (!empty($activitys['goods_attr'])) {
                $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $activitys['goods_attr'])->where('a.goods_id', $goods['id'])->where('b.attr_type', 1)->select();
                if ($gares) {
                    foreach ($gares as $key => $val) {
                        if ($key == 0) {
                            $goods_attr_str = $val['attr_name'] . ':' . $val['attr_value'];
                        } else {
                            $goods_attr_str = $goods_attr_str . ' ' . $val['attr_name'] . ':' . $val['attr_value'];
                        }
                    }
                    $goods_attr = $activitys['goods_attr'];
                    $goods['goods_name'] = $goods['goods_name'] . ' ' . $goods_attr_str;
                }
            }

            $goods['zs_shop_price'] = $activitys['price'];

            if ($activitys['ac_type'] == 1) {
                $pronum = $activitys['kucun'];

                $yslv = sprintf("%.2f", $activitys['sold'] / $activitys['num']) * 100;
                $activity_info = array(
                    'yslv' => $yslv . '%',
                    'xznum' => $activitys['xznum'],
                    'start_time' => $activitys['start_time'],
                    'end_time' => $activitys['end_time'],
                    'dqtime' => time()
                );
            } else {
                if (!empty($activitys['goods_attr'])) {
                    $prores = Db::name('product')->where('goods_id', $goods['id'])->where('goods_attr', $activitys['goods_attr'])->field('goods_number')->find();
                    if ($prores) {
                        $pronum = $prores['goods_number'];
                    } else {
                        $pronum = 0;
                    }
                } else {
                    $prores = Db::name('product')->where('goods_id', $goods['id'])->field('goods_number')->select();
                    if ($prores) {
                        $pronum = 0;
                        foreach ($prores as $v3) {
                            $pronum += $v3['goods_number'];
                        }
                    } else {
                        $pronum = 0;
                    }
                }

                if ($activitys['ac_type'] == 2) {
                    $activity_info = array(
                        'start_time' => $activitys['start_time'],
                        'end_time' => $activitys['end_time'],
                        'dqtime' => time()
                    );
                } elseif ($activitys['ac_type'] == 3) {
                    $assem_type = 1;
                    $zhuangtai = 0;
                    $member_assem = array();

                    if (input('post.pin_number')) {
                        if ($user_id) {
                            $assem_number = input('post.pin_number');
                            $pintuans = Db::name('pintuan')->where('assem_number', $assem_number)->where('state', 1)->where('pin_status', 'in', '0,1')->find();
                            if ($pintuans) {
                                $pthdinfos = Db::name('assemble')->where('id', $pintuans['hd_id'])->where('goods_id', $goods['id'])->where('shop_id', $goods['shop_id'])->where('checked', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')->find();
                                if ($pthdinfos) {
                                    $order_assembles = Db::name('order_assemble')->where('pin_id', $pintuans['id'])->where('user_id', $user_id)->where('state', 1)->where('tui_status', 0)->find();
                                    if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                        if ($order_assembles) {
                                            $assem_type = 3;
                                            $zhuangtai = 1;
                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b', 'a.user_id = b.id', 'INNER')->where('a.pin_id', $pintuans['id'])->where('a.state', 1)->where('a.tui_status', 0)->order('a.addtime asc')->select();
                                        } else {
                                            $assem_type = 2;
                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b', 'a.user_id = b.id', 'INNER')->where('a.pin_id', $pintuans['id'])->where('a.state', 1)->where('a.tui_status', 0)->order('a.addtime asc')->select();
                                        }
                                    } elseif ($pintuans['pin_status'] == 1) {
                                        if ($order_assembles) {
                                            $zhuangtai = 2;
                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b', 'a.user_id = b.id', 'INNER')->where('a.pin_id', $pintuans['id'])->where('a.state', 1)->where('a.tui_status', 0)->order('a.addtime asc')->select();
                                        }
                                    }
                                }
                            } else {
                                if (!empty($activitys['goods_attr'])) {
                                    $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $goods['id'])->where('goods_attr', $activitys['goods_attr'])->where('shop_id', $goods['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                                } else {
                                    $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $goods['id'])->where('shop_id', $goods['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                                }
                                if ($order_assembles) {
                                    $pintuans = Db::name('pintuan')->where('id', $order_assembles['pin_id'])->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                                    if ($pintuans) {
                                        if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                            $assem_type = 3;
                                            $zhuangtai = 1;
                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b', 'a.user_id = b.id', 'INNER')->where('a.pin_id', $pintuans['id'])->where('a.state', 1)->where('a.tui_status', 0)->order('a.addtime asc')->select();
                                        } elseif ($pintuans['pin_status'] == 1) {
                                            $zhuangtai = 2;
                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b', 'a.user_id = b.id', 'INNER')->where('a.pin_id', $pintuans['id'])->where('a.state', 1)->where('a.tui_status', 0)->order('a.addtime asc')->select();
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        if ($user_id) {
                            if (!empty($activitys['goods_attr'])) {
                                $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $goods['id'])->where('goods_attr', $activitys['goods_attr'])->where('shop_id', $goods['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                            } else {
                                $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $goods['id'])->where('shop_id', $goods['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                            }
                            if ($order_assembles) {
                                $pintuans = Db::name('pintuan')->where('id', $order_assembles['pin_id'])->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                                if ($pintuans) {
                                    if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b', 'a.user_id = b.id', 'INNER')->where('a.pin_id', $pintuans['id'])->where('a.state', 1)->where('a.tui_status', 0)->order('a.addtime asc')->select();
                                    } elseif ($pintuans['pin_status'] == 1) {
                                        $zhuangtai = 2;
                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b', 'a.user_id = b.id', 'INNER')->where('a.pin_id', $pintuans['id'])->where('a.state', 1)->where('a.tui_status', 0)->order('a.addtime asc')->select();
                                    }
                                }
                            }
                        }
                    }

                    if ($assem_type == 3) {
                        $pin_id = $pintuans['id'];
                        $tuan_id = $order_assembles['id'];
                    }

                    if (!empty($pthdinfos) && $pthdinfos['id'] != $activitys['id']) {
                        $ptactivitys = $pthdinfos;

                        if (!empty($ptactivitys['goods_attr'])) {
                            $ptgares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $ptactivitys['goods_attr'])->where('a.goods_id', $goods['id'])->where('b.attr_type', 1)->select();
                            if ($ptgares) {
                                $ptgoods_attr_str = '';

                                foreach ($ptgares as $kac => $vac) {
                                    if ($kac == 0) {
                                        $ptgoods_attr_str = $vac['attr_name'] . ':' . $vac['attr_value'];
                                    } else {
                                        $ptgoods_attr_str = $ptgoods_attr_str . ' ' . $vac['attr_name'] . ':' . $vac['attr_value'];
                                    }
                                }

                                $goods_attr = $ptactivitys['goods_attr'];
                                $goods['goods_name'] = $goods['goods_name'] . ' ' . $ptgoods_attr_str;
                            }
                        }

                        $goods['zs_shop_price'] = $ptactivitys['price'];

                        if (!empty($ptactivitys['goods_attr'])) {
                            $prores = Db::name('product')->where('goods_id', $goods['id'])->where('goods_attr', $ptactivitys['goods_attr'])->field('goods_number')->find();
                            if ($prores) {
                                $pronum = $prores['goods_number'];
                            } else {
                                $pronum = 0;
                            }
                        } else {
                            $prores = Db::name('product')->where('goods_id', $goods['id'])->field('goods_number')->select();
                            if ($prores) {
                                $pronum = 0;
                                foreach ($prores as $v3) {
                                    $pronum += $v3['goods_number'];
                                }
                            } else {
                                $pronum = 0;
                            }
                        }
                    } else {
                        $ptactivitys = $activitys;
                    }

                    if (!empty($ptactivitys['goods_attr'])) {
                        $gavdres = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_price')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $ptactivitys['goods_attr'])->where('a.goods_id', $goods['id'])->where('b.attr_type', 1)->select();
                        if ($gavdres) {
                            $dandu_price = $goods['shop_price'];
                            foreach ($gavdres as $vrp) {
                                $dandu_price += $vrp['attr_price'];
                            }
                            $dandu_price = sprintf("%.2f", $dandu_price);
                        } else {
                            if ($goods['min_price'] != $goods['max_price']) {
                                $dandu_price = $goods['min_price'] . '-' . $goods['max_price'];
                            } else {
                                $dandu_price = $goods['min_price'];
                            }
                        }
                    } else {
                        if ($goods['min_price'] != $goods['max_price']) {
                            $dandu_price = $goods['min_price'] . '-' . $goods['max_price'];
                        } else {
                            $dandu_price = $goods['min_price'];
                        }
                    }

                    if (in_array($assem_type, array(1, 3))) {
                        if (!empty($ptactivitys['goods_attr'])) {
                            $userassem = Db::name('order_assemble')->alias('a')->field('a.pin_id')->join('sp_pintuan b', 'a.pin_id = b.id', 'INNER')->where('a.user_id', $user_id)->where('a.goods_id', $goods['id'])->where('a.goods_attr', $ptactivitys['goods_attr'])->where('a.shop_id', $goods['shop_id'])->where('a.hd_id', $ptactivitys['id'])->where('a.state', 1)->where('a.tui_status', 0)->where('b.state', 1)->where('b.hd_id', $ptactivitys['id'])->where('b.pin_status', 0)->where('b.timeout', 'gt', time())->group('a.pin_id')->select();
                            if ($userassem) {
                                $userpinid = array();
                                foreach ($userassem as $vur) {
                                    $userpinid[] = $vur['pin_id'];
                                }
                                $userpinid = array_unique($userpinid);
                                $userpinid = implode(',', $userpinid);
                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b', 'a.tz_id = b.id', 'INNER')->where('a.id', 'not in', $userpinid)->where('a.hd_id', $ptactivitys['id'])->where('a.state', 1)->where('a.pin_status', 0)->where('a.timeout', 'gt', time())->order('a.tuan_num desc')->limit(3)->select();
                            } else {
                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b', 'a.tz_id = b.id', 'INNER')->where('a.hd_id', $ptactivitys['id'])->where('a.state', 1)->where('a.pin_status', 0)->where('a.timeout', 'gt', time())->order('a.tuan_num desc')->limit(3)->select();
                            }
                        } else {
                            $userassem = Db::name('order_assemble')->alias('a')->field('a.pin_id')->join('sp_pintuan b', 'a.pin_id = b.id', 'INNER')->where('a.user_id', $user_id)->where('a.goods_id', $goods['id'])->where('a.shop_id', $goods['shop_id'])->where('a.hd_id', $ptactivitys['id'])->where('a.state', 1)->where('a.tui_status', 0)->where('b.state', 1)->where('b.hd_id', $ptactivitys['id'])->where('b.pin_status', 0)->where('b.timeout', 'gt', time())->group('a.pin_id')->select();
                            if ($userassem) {
                                $userpinid = array();
                                foreach ($userassem as $vur) {
                                    $userpinid[] = $vur['pin_id'];
                                }
                                $userpinid = array_unique($userpinid);
                                $userpinid = implode(',', $userpinid);
                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b', 'a.tz_id = b.id', 'INNER')->where('a.id', 'not in', $userpinid)->where('a.hd_id', $ptactivitys['id'])->where('a.state', 1)->where('a.pin_status', 0)->where('a.timeout', 'gt', time())->order('a.tuan_num desc')->limit(3)->select();
                            } else {
                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b', 'a.tz_id = b.id', 'INNER')->where('a.hd_id', $ptactivitys['id'])->where('a.state', 1)->where('a.pin_status', 0)->where('a.timeout', 'gt', time())->order('a.tuan_num desc')->limit(3)->select();
                            }
                        }

                        if ($memberpinres) {
                            foreach ($memberpinres as $kpc => $vpc) {
                                $memberpinres[$kpc]['headimgurl'] = $webconfig['weburl'] . '/' . $vpc['headimgurl'];
                                $memberpinres[$kpc]['pin_time_out'] = time2string($vpc['timeout'] - time());
                                $memberpinres[$kpc]['goods_id'] = $goods['id'];
                            }
                        }
                    }

                    if ($assem_type == 1 && $zhuangtai == 0) {
                        if ($user_id) {
                            $member_picinfos  = Db::name('member')->where('id', $user_id)->field('user_name,headimgurl')->find();
                            $member_pic = $member_picinfos['headimgurl'];
                            if ($member_pic) {
                                $member_pic = $webconfig['weburl'] . '/' . $member_pic;
                            } else {
                                $member_pic = $webconfig['weburl'] . '/static/admin/img/nopic.jpg';
                            }
                            $member_assem[] = array('pin_type' => 2, 'user_name' => $member_picinfos['user_name'], 'headimgurl' => $member_pic);
                        } else {
                            $member_pic = $webconfig['weburl'] . '/static/admin/img/nopic.jpg';
                            $member_assem[] = array('pin_type' => 2, 'user_name' => '', 'headimgurl' => $member_pic);
                        }
                    } else {
                        if (!empty($member_assem)) {
                            foreach ($member_assem as $kas => $vas) {
                                if ($vas['headimgurl']) {
                                    $member_assem[$kas]['headimgurl'] = $webconfig['weburl'] . '/' . $vas['headimgurl'];
                                } else {
                                    $member_assem[$kas]['headimgurl'] = $webconfig['weburl'] . '/static/admin/img/nopic.jpg';
                                }
                            }
                        }
                    }

                    $activity_info = array(
                        'assem_type' => $assem_type,
                        'zhuangtai' => $zhuangtai,
                        'pin_num' => $ptactivitys['pin_num'],
                        'dandu_price' => $dandu_price,
                        'member_assem' => $member_assem,
                        'start_time' => $ptactivitys['start_time'],
                        'end_time' => $ptactivitys['end_time'],
                        'dqtime' => time()
                    );
                }
            }
        } else {
            $goods['is_activity'] = 0;

            if ($goods['min_price'] != $goods['max_price']) {
                $goods['zs_shop_price'] = $goods['min_price'] . '-' . $goods['max_price'];
            } else {
                $goods['zs_shop_price'] = $goods['min_price'];
            }

            $prores = Db::name('product')->where('goods_id', $goods['id'])->field('goods_number')->select();
            if ($prores) {
                $pronum = 0;
                foreach ($prores as $v3) {
                    $pronum += $v3['goods_number'];
                }
            } else {
                $pronum = 0;
            }
        }

        //邮费
        if ($goods['is_free'] == 0) {
            $shopinfos = Db::name('shops')->where('id', $goods['shop_id'])->field('freight,reduce')->find();
            $freight = '运费' . $shopinfos['freight'] . ' 订单满' . $shopinfos['reduce'] . '免运费';
        } else {
            $freight = '包邮';
        }

        //优惠券
        $couponinfos = array('is_show' => 0, 'infos' => '');
        //商品活动信息
        $huodong = array('is_show' => 0, 'infos' => '', 'prom_id' => 0);

        $couponres = Db::name('coupon')->where('shop_id', $goods['shop_id'])->where('start_time', 'elt', time())->where('end_time', 'gt', time() - 3600 * 24)->where('onsale', 1)->field('man_price,dec_price')->order('man_price asc')->limit(3)->select();
        if ($couponres) {
            $couponinfos = array('is_show' => 1, 'infos' => $couponres);
        }

        $promotions = Db::name('promotion')->where("find_in_set('" . $goods['id'] . "',info_id)")->where('shop_id', $goods['shop_id'])->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,start_time,end_time')->find();
        if ($promotions) {
            $prom_typeres = Db::name('prom_type')->where('prom_id', $promotions['id'])->select();
        } else {
            $prom_typeres = array();
        }

        $goods_promotion = '';

        if (!empty($promotions) && !empty($prom_typeres)) {
            $start_time = date('Y年m月d日 H时', $promotions['start_time']);
            $end_time = date('Y年m月d日 H时', $promotions['end_time']);
            foreach ($prom_typeres as $kcp => $vcp) {
                $zhekou = $vcp['discount'] / 10;
                if ($kcp == 0) {
                    $goods_promotion = '商品满 ' . $vcp['man_num'] . '件 享' . $zhekou . '折';
                } else {
                    $goods_promotion = $goods_promotion . '  满 ' . $vcp['man_num'] . '件 享' . $zhekou . '折';
                }
            }
            $huodong = array('is_show' => 1, 'infos' => $goods_promotion, 'prom_id' => $promotions['id']);
        }

        //服务项
        $sertions = array('is_show' => 0, 'infos' => '');

        if (!empty($goods['fuwu'])) {
            $sertionres = Db::name('sertion')->where('id', 'in', $goods['fuwu'])->where('is_show', 1)->field('ser_name')->order('sort asc')->limit(2)->select();
            if ($sertionres) {
                $sertions = array('is_show' => 1, 'infos' => $sertionres);
            }
        }
        //获取商品的佣金信息
        $commission_arr = getCommissionPrice($goods['zs_shop_price']);
        $goodsinfo = array(
            'id' => $goods['id'],
            'goods_name' => $goods['goods_name'],
            'thumb_url' => $goods['thumb_url'],
            'goods_desc' => $goods['goods_desc'],
            'freight' => $freight,
            'salenum' => 0,
            'leixing' => $goods['leixing'],
            'shop_id' => $goods['shop_id'],
            'zs_market_price' => $goods['zs_market_price'],
            'zs_shop_price' => $goods['zs_shop_price'],
            'is_activity' => $goods['is_activity'],
            'coll_goods' => $goods['coll_goods'],
            'sale_number' => $goods['sale_number'],
            'shop_token' => $goods['shop_token'],
            'commission_one' => $commission_arr['commission_one'], //一级佣金
            'commission_two' => $commission_arr['commission_two'], //二级佣金
        );

        $shopinfos = Db::name('shops')->where('id', $goods['shop_id'])->where('open_status', 1)->field('id,shop_name,shop_desc,logo,goods_fen,fw_fen,wuliu_fen')->find();
        $shopinfos['logo'] = $webconfig['weburl'] . '/' . $shopinfos['logo'];

        $shop_customs = Db::name('shop_custom')->where('shop_id', $goods['shop_id'])->where('type', 1)->field('info_id')->find();

        $remgoodres = array();

        if ($shop_customs) {
            $remgoodres = Db::name('goods')->where('id', 'in', $shop_customs['info_id'])->where('shop_id', $goods['shop_id'])->where('onsale', 1)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order('zonghe_lv desc,id asc')->select();

            if ($remgoodres) {
                foreach ($remgoodres as $k2 => $v2) {
                    $remgoodres[$k2]['thumb_url'] = $webconfig['weburl'] . '/' . $v2['thumb_url'];

                    $reruinfo = array('id' => $v2['id'], 'shop_id' => $v2['shop_id']);
                    $regongyong = new GongyongMx();
                    $reactivitys = $regongyong->pdrugp($reruinfo);

                    if ($reactivitys) {
                        if (!empty($reactivitys['goods_attr'])) {
                            $regoods_attr_str = '';
                            $regares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $reactivitys['goods_attr'])->where('a.goods_id', $v2['id'])->where('b.attr_type', 1)->select();
                            if ($regares) {
                                foreach ($regares as $key2 => $val2) {
                                    if ($key2 == 0) {
                                        $regoods_attr_str = $val2['attr_name'] . ':' . $val2['attr_value'];
                                    } else {
                                        $regoods_attr_str = $regoods_attr_str . ' ' . $val2['attr_name'] . ':' . $val2['attr_value'];
                                    }
                                }
                                $remgoodres[$k2]['goods_name'] = $v2['goods_name'] . ' ' . $regoods_attr_str;
                            }
                        }

                        $remgoodres[$k2]['zs_price'] = $reactivitys['price'];
                    } else {
                        $remgoodres[$k2]['zs_price'] = $v2['min_price'];
                    }
                }
            }
        }

        $goodinfores = array(
            'goodsinfo' => $goodsinfo,
            'activity_info' => $activity_info,
            'goods_attr' => $goods_attr,
            'goods_attr_str' => $goods_attr_str,
            'pronum' => $pronum,
            'gpres' => $gpres,
            'radioattr' => $radioattr,
            'uniattr' => $uniattr,
            'guige' => $guige,
            'colores' => $colores,
            'couponinfos' => $couponinfos,
            'huodong' => $huodong,
            'sertions' => $sertions,
            'shopinfos' => $shopinfos,
            'remgoodres' => $remgoodres,
            'pin_id' => $pin_id,
            'tuan_id' => $tuan_id,
            'memberpinres' => $memberpinres
        );
        $value = array('status' => 200, 'mess' => '获取商品详情信息成功', 'data' => $goodinfores);




        return json($value);
    }

    //立即购买VIP确认订单接口
    public function findMemberToOrder()
    {
        if (!request()->isPost()) return returnJson(400, '请求方式不正确', ['status' => 400]);
        if (!input('post.token')) return returnJson(400, '缺少用户令牌', ['status' => 400]);
        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();
        if ($result['status'] !== 200) return json($result);
        $user_id = $result['user_id'];
        $userInfo = MemberModel::findById($user_id);
        if($userInfo['level'] != 0) return returnJson(400,'您已是掌柜会员',['status'=>400]);
        if (!input('post.goods_id') && !input('post.num')) return returnJson(400, '缺少购买商品参数', ['status' => 400]);
        if (!input('post.fangshi')) return returnJson(400, '缺少购买方式参数', ['status' => 400]);
        $goods_id = input('post.goods_id');
        $num = input('post.num');
        $fangshi = input('post.fangshi');
        $assem_number = '';

        if (!preg_match("/^\\+?[1-9][0-9]*$/", $num)) return returnJson(400, '商品数量参数格式错误', ['status' => 400]);
        $goods = Db::name('goods')
            ->alias('a')
            ->field('a.id,a.goods_name,a.shop_id')
            ->join('sp_shops b', 'a.shop_id = b.id', 'INNER')
            ->where('a.id', $goods_id)
            // ->where('a.onsale', 1)
            // ->where('b.open_status', 1)
            ->find();
        if (!$goods) return returnJson(400, '商品已下架或不存在', ['status' => 400]);
        $radiores = Db::name('goods_attr')
            ->alias('a')
            ->field('a.id,a.attr_id')
            ->join('sp_attr b', 'a.attr_id = b.id', 'INNER')
            ->where('a.goods_id', $goods['id'])
            ->where('b.attr_type', 1)
            ->select();
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
            $prores = Db::name('product')->where('goods_id', $goods['id'])->where('goods_attr', $goods_attr)->field('goods_number')->find();
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
                $prores = Db::name('product')->where('goods_id', $goods['id'])->where('goods_attr', $goods_attr)->field('goods_number')->find();
                if ($prores) {
                    $goods_number = $prores['goods_number'];
                } else {
                    $goods_number = 0;
                }
            }

            if ($num > 0 && $num <= $goods_number) {
                if ($activitys['ac_type'] == 3) {
                    $assem_type = 1;
                    $zhuangtai = 0;

                    if (input('post.pin_number')) {
                        $assem_number = input('post.pin_number');
                        $pintuans = Db::name('pintuan')->where('assem_number', $assem_number)->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                        if ($pintuans) {
                            $order_assembles = Db::name('order_assemble')->where('pin_id', $pintuans['id'])->where('user_id', $user_id)->where('state', 1)->where('tui_status', 0)->find();
                            if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                if ($order_assembles) {
                                    $assem_type = 3;
                                    $zhuangtai = 1;
                                } else {
                                    $assem_type = 2;
                                }
                            } elseif ($pintuans['pin_status'] == 1) {
                                if ($order_assembles) {
                                    $zhuangtai = 2;
                                }
                            }
                        } else {
                            if (!empty($activitys['goods_attr'])) {
                                $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $goods['id'])->where('goods_attr', $goods_attr)->where('shop_id', $goods['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                            } else {
                                $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $goods['id'])->where('shop_id', $goods['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                            }
                            if ($order_assembles) {
                                $pintuans = Db::name('pintuan')->where('id', $order_assembles['pin_id'])->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                                if ($pintuans) {
                                    if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                    } elseif ($pintuans['pin_status'] == 1) {
                                        $zhuangtai = 2;
                                    }
                                }
                            }
                        }
                    } else {
                        if (!empty($activitys['goods_attr'])) {
                            $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $goods['id'])->where('goods_attr', $goods_attr)->where('shop_id', $goods['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                        } else {
                            $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $goods['id'])->where('shop_id', $goods['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                        }
                        if ($order_assembles) {
                            $pintuans = Db::name('pintuan')->where('id', $order_assembles['pin_id'])->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                            if ($pintuans) {
                                if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                    $assem_type = 3;
                                    $zhuangtai = 1;
                                } elseif ($pintuans['pin_status'] == 1) {
                                    $zhuangtai = 2;
                                }
                            }
                        }
                    }

                    if ($assem_type == 3) {
                        $value = array('status' => 400, 'mess' => '您已参与商品拼团，下单失败', 'data' => array('status' => 400));
                        return json($value);
                    }
                }
            }
        }

        if ($num <= 0 || $num > $goods_number) {
            //$value = array('status'=>400,'mess'=>$goods['goods_name'].'库存不足或您已经下过订单，请前往订单中心完成支付','data'=>array('status'=>400));
            $value = array('status' => 400, 'mess' => $goods['goods_name'] . '库存不足', 'data' => array('status' => 400));
            return json($value);
        }

        $purchs = Db::name('purch')->where('user_id', $user_id)->find();
        if ($purchs) {
            $count = Db::name('purch')->where('id', $purchs['id'])->where('user_id', $user_id)->update(array('goods_id' => $goods['id'], 'goods_attr' => $goods_attr, 'num' => $num, 'shop_id' => $goods['shop_id']));
            if ($count !== false) {
                $value = array('status' => 200, 'mess' => '操作成功', 'data' => array('pur_id' => $purchs['id'], 'fangshi' => $fangshi, 'pin_number' => $assem_number));
            } else {
                $value = array('status' => 400, 'mess' => '操作失败，请重试', 'data' => array('status' => 400));
            }
        } else {
            $pur_id = Db::name('purch')->insertGetId(array('goods_id' => $goods['id'], 'goods_attr' => $goods_attr, 'num' => $num, 'user_id' => $user_id, 'shop_id' => $goods['shop_id']));
            if ($pur_id) {
                $value = array('status' => 200, 'mess' => '操作成功', 'data' => array('pur_id' => $pur_id, 'fangshi' => $fangshi, 'pin_number' => $assem_number));
            } else {
                $value = array('status' => 400, 'mess' => '操作失败，请重试', 'data' => array('status' => 400));
            }
        }
        return json($value);
    }

    //立即购买VIP确认订单详情接口
    public function pursure()
    {
        if (!request()->isPost()) return returnJson(400, '请求方式不正确', ['status' => 400]);
        if (!input('post.token')) return returnJson(400, '缺少用户令牌', ['status' => 400]);
        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();
        if ($result['status'] !== 200) return json($result);
        $user_id = $result['user_id'];
        $userInfo = MemberModel::findById($user_id);
        if($userInfo['level'] != 0) return returnJson(400,'您已是掌柜会员',['status'=>400]);
        if (!input('post.pur_id')) return returnJson(400, '缺少购买商品参数', ['status' => 400]);
        if (!input('post.fangshi')) return returnJson(400, '缺少购买方式参数', ['status' => 400]);
        $webconfig = $this->webconfig;

        $wallets = Db::name('wallet')->where('user_id', $user_id)->find();
        $pur_id = input('post.pur_id');
        $fangshi = input('post.fangshi');
        $assem_number = '';

        $purchs = Db::name('purch')
            ->alias('a')
            ->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_free,c.shop_name')
            ->join('sp_goods b', 'a.goods_id = b.id', 'INNER')
            ->join('sp_shops c', 'a.shop_id = c.id', 'INNER')
            ->where('a.id', $pur_id)
            ->where('a.user_id', $user_id)
            // ->where('b.onsale', 1)
            // ->where('c.open_status', 1)
            ->find();
        if ($purchs) {
            $goodinfos = array();
            if ($webconfig['cos_file'] == '开启') {
                $domain = config('tengxunyun')['cos_domain'];
            } else {
                $domain = $webconfig['weburl'];
            }
            $purchs['thumb_url'] = $domain . '/' . $purchs['thumb_url'];

            $ruinfo = array('id' => $purchs['goods_id'], 'shop_id' => $purchs['shop_id']);
            $ru_attr = $purchs['goods_attr'];

            $gongyong = new GongyongMx();
            $activitys = $gongyong->pdrugp($ruinfo, $ru_attr);

            if ((!$activitys) || ($activitys && $activitys['ac_type'] == 3 && $fangshi == 1)) {
                $prores = Db::name('product')->where('goods_id', $purchs['goods_id'])->where('goods_attr', $purchs['goods_attr'])->field('goods_number')->find();
                if ($prores) {
                    $goods_number = $prores['goods_number'];
                } else {
                    $goods_number = 0;
                }

                if ($purchs['num'] > 0 && $purchs['num'] <= $goods_number) {
                    if (!empty($purchs['goods_attr'])) {
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
                    } else {
                        $goods_attr_str = '';
                    }

                    $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'shop_id' => $purchs['shop_id'], 'shop_name' => $purchs['shop_name']);
                } else {
                    $value = array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                    return json($value);
                }
            } else {
                if ($activitys['ac_type'] == 1) {
                    $goods_number = $activitys['kucun'];
                } else {
                    $prores = Db::name('product')->where('goods_id', $purchs['goods_id'])->where('goods_attr', $purchs['goods_attr'])->field('goods_number')->find();
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

                    if ($activitys['ac_type'] == 3) {
                        $assem_type = 1;
                        $zhuangtai = 0;

                        if (input('post.pin_number')) {
                            $assem_number = input('post.pin_number');
                            $pintuans = Db::name('pintuan')->where('assem_number', $assem_number)->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                            if ($pintuans) {
                                $order_assembles = Db::name('order_assemble')->where('pin_id', $pintuans['id'])->where('user_id', $user_id)->where('state', 1)->where('tui_status', 0)->find();
                                if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                    if ($order_assembles) {
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                    } else {
                                        $assem_type = 2;
                                    }
                                } elseif ($pintuans['pin_status'] == 1) {
                                    if ($order_assembles) {
                                        $zhuangtai = 2;
                                    }
                                }
                            } else {
                                if (!empty($activitys['goods_attr'])) {
                                    $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $purchs['goods_id'])->where('goods_attr', $purchs['goods_attr'])->where('shop_id', $purchs['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                                } else {
                                    $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $purchs['goods_id'])->where('shop_id', $purchs['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                                }
                                if ($order_assembles) {
                                    $pintuans = Db::name('pintuan')->where('id', $order_assembles['pin_id'])->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                                    if ($pintuans) {
                                        if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                            $assem_type = 3;
                                            $zhuangtai = 1;
                                        } elseif ($pintuans['pin_status'] == 1) {
                                            $zhuangtai = 2;
                                        }
                                    }
                                }
                            }
                        } else {
                            if (!empty($activitys['goods_attr'])) {
                                $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $purchs['goods_id'])->where('goods_attr', $purchs['goods_attr'])->where('shop_id', $purchs['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                            } else {
                                $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $purchs['goods_id'])->where('shop_id', $purchs['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                            }
                            if ($order_assembles) {
                                $pintuans = Db::name('pintuan')->where('id', $order_assembles['pin_id'])->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                                if ($pintuans) {
                                    if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                    } elseif ($pintuans['pin_status'] == 1) {
                                        $zhuangtai = 2;
                                    }
                                }
                            }
                        }

                        if ($assem_type == 3) {
                            $value = array('status' => 400, 'mess' => '您已参与商品拼团，下单失败', 'data' => array('status' => 400));
                            return json($value);
                        }
                    }

                    $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'shop_id' => $purchs['shop_id'], 'shop_name' => $purchs['shop_name']);
                } else {
                    $value = array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                    return json($value);
                }
            }

            $ordouts = Db::name('order_timeout')->where('id', 1)->find();

            if ($activitys && $activitys['ac_type'] == 3 && $fangshi == 2) {
                $assem_zt = array('is_show' => 1, 'time_out' => $ordouts['assem_timeout']);
            } else {
                $assem_zt = array('is_show' => 0, 'time_out' => '');
            }

            if ($goodinfos) {
                $goodinfos['coupon_str'] = '';
                $goodinfos['cxhuodong'] = array();
                $goodinfos['youhui_price'] = 0;
                $goodinfos['freight'] = 0;
                $goodinfos['xiaoji_price'] = 0;

                $xiaoji = sprintf("%.2f", $goodinfos['shop_price'] * $goodinfos['goods_num']);

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
                if (!$dizis) {
                    $dizis = '';
                }

                $value = array('status' => 200, 'mess' => '获取商品信息成功', 'data' => array('goodinfo' => $hqgoodsinfos, 'zong_num' => $zong_num, 'zsprice' => $zsprice, 'address' => $dizis, 'wallet_price' => $wallets['price'], 'pur_id' => $pur_id, 'assem_zt' => $assem_zt, 'fangshi' => $fangshi, 'pin_number' => $assem_number));
            } else {
                $value = array('status' => 400, 'mess' => '找不到相关1商品信息', 'data' => array('status' => 400));
            }
        } else {
            $value = array('status' => 400, 'mess' => '找不到相关商品信息', 'data' => array('status' => 400));
        }
        return json($value);
    }

    //立即购买创建订单接口
    public function puraddorder()
    {
        if (request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $userInfo = MemberModel::findById($user_id);
                    if($userInfo['level'] != 0) return returnJson(400,'您已是掌柜会员',['status'=>400]);
                    if (input('post.pur_id')) {
                        if (input('post.fangshi') && in_array(input('post.fangshi'), array(1, 2))) {
                            if (input('post.dz_id')) {
                                if (input('post.zf_type') && in_array(input('post.zf_type'), array(1, 2, 3, 4, 5, 6, 7))) {
                                    $zf_type = input('post.zf_type');
                                    $fangshi = input('post.fangshi');

                                    /*if(input('post.beizhu')){
                                         if(mb_strlen(input('post.beizhu'),'utf8') <= 100){
                                         $beizhu = input('post.beizhu');
                                         }else{
                                         $value = array('status'=>400,'mess'=>'备注信息在100个字符内','data'=>array('status'=>400));
                                         return json($value);
                                         }
                                         }else{
                                         $beizhu = '';
                                        }*/

                                    $dizis = Db::name('address')->alias('a')->field('a.*,b.pro_name,c.city_name,d.area_name')->join('sp_province b', 'a.pro_id = b.id', 'LEFT')->join('sp_city c', 'a.city_id = c.id', 'LEFT')->join('sp_area d', 'a.area_id = d.id', 'LEFT')->where('a.id', input('post.dz_id'))->where('a.user_id', $user_id)->find();
                                    if ($dizis) {
                                        $pur_id = input('post.pur_id');

                                        $purchs = Db::name('purch')
                                            ->alias('a')
                                            ->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_free,c.shop_name')
                                            ->join('sp_goods b', 'a.goods_id = b.id', 'INNER')
                                            ->join('sp_shops c', 'a.shop_id = c.id', 'INNER')
                                            ->where('a.id', $pur_id)
                                            ->where('a.user_id', $user_id)
                                            ->find();
                                        if ($purchs) {
                                            $total_price = 0;
                                            $order_type = 1;
                                            $pin_type = 0;
                                            $goodinfos = array();

                                            $ruinfo = array('id' => $purchs['goods_id'], 'shop_id' => $purchs['shop_id']);
                                            $ru_attr = $purchs['goods_attr'];

                                            $gongyong = new GongyongMx();
                                            $activitys = $gongyong->pdrugp($ruinfo, $ru_attr);

                                            if ((!$activitys) || ($activitys && $activitys['ac_type'] == 3 && $fangshi == 1)) {
                                                $purchs['hd_type'] = 0;
                                                $purchs['hd_id'] = 0;

                                                $prores = Db::name('product')->where('goods_id', $purchs['goods_id'])->where('goods_attr', $purchs['goods_attr'])->field('goods_number')->find();
                                                if ($prores) {
                                                    $goods_number = $prores['goods_number'];
                                                } else {
                                                    $goods_number = 0;
                                                }

                                                if ($purchs['num'] > 0 && $purchs['num'] <= $goods_number) {
                                                    if ($purchs['goods_attr']) {
                                                        $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $purchs['goods_attr'])->where('a.goods_id', $purchs['goods_id'])->where('b.attr_type', 1)->select();
                                                        $goods_attr_str = '';
                                                        if ($gares) {
                                                            foreach ($gares as $key => $val) {
                                                                $purchs['shop_price'] += $val['attr_price'];
                                                                if ($key == 0) {
                                                                    $goods_attr_str = $val['attr_name'] . ':' . $val['attr_value'];
                                                                } else {
                                                                    $goods_attr_str = $goods_attr_str . ' ' . $val['attr_name'] . ':' . $val['attr_value'];
                                                                }
                                                            }
                                                            $purchs['shop_price'] = sprintf("%.2f", $purchs['shop_price']);
                                                        }
                                                    } else {
                                                        $goods_attr_str = '';
                                                    }

                                                    $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_id' => $purchs['goods_attr'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'hd_type' => $purchs['hd_type'], 'hd_id' => $purchs['hd_id'], 'shop_id' => $purchs['shop_id']);
                                                } else {
                                                    $value = array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                                                    return json($value);
                                                }
                                            } else {
                                                $purchs['hd_type'] = $activitys['ac_type'];
                                                $purchs['hd_id'] = $activitys['id'];

                                                if ($activitys['ac_type'] == 1) {
                                                    $goods_number = $activitys['kucun'];
                                                } else {
                                                    $prores = Db::name('product')->where('goods_id', $purchs['goods_id'])->where('goods_attr', $purchs['goods_attr'])->field('goods_number')->find();
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

                                                    if ($activitys['ac_type'] == 3) {
                                                        $assem_type = 1;
                                                        $zhuangtai = 0;

                                                        if (input('post.pin_number')) {
                                                            $assem_number = input('post.pin_number');
                                                            $pintuans = Db::name('pintuan')->where('assem_number', $assem_number)->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                                                            if ($pintuans) {
                                                                $order_assembles = Db::name('order_assemble')->where('pin_id', $pintuans['id'])->where('user_id', $user_id)->where('state', 1)->where('tui_status', 0)->find();
                                                                if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                                                    if ($order_assembles) {
                                                                        $assem_type = 3;
                                                                        $zhuangtai = 1;
                                                                    } else {
                                                                        $assem_type = 2;
                                                                    }
                                                                } elseif ($pintuans['pin_status'] == 1) {
                                                                    if ($order_assembles) {
                                                                        $zhuangtai = 2;
                                                                    }
                                                                }
                                                            } else {
                                                                if (!empty($activitys['goods_attr'])) {
                                                                    $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $purchs['goods_id'])->where('goods_attr', $purchs['goods_attr'])->where('shop_id', $purchs['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                                                                } else {
                                                                    $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $purchs['goods_id'])->where('shop_id', $purchs['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                                                                }
                                                                if ($order_assembles) {
                                                                    $pintuans = Db::name('pintuan')->where('id', $order_assembles['pin_id'])->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                                                                    if ($pintuans) {
                                                                        if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                                                            $assem_type = 3;
                                                                            $zhuangtai = 1;
                                                                        } elseif ($pintuans['pin_status'] == 1) {
                                                                            $zhuangtai = 2;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        } else {
                                                            if (!empty($activitys['goods_attr'])) {
                                                                $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $purchs['goods_id'])->where('goods_attr', $purchs['goods_attr'])->where('shop_id', $purchs['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                                                            } else {
                                                                $order_assembles = Db::name('order_assemble')->where('user_id', $user_id)->where('goods_id', $purchs['goods_id'])->where('shop_id', $purchs['shop_id'])->where('hd_id', $activitys['id'])->where('state', 1)->where('tui_status', 0)->order('addtime desc')->find();
                                                            }
                                                            if ($order_assembles) {
                                                                $pintuans = Db::name('pintuan')->where('id', $order_assembles['pin_id'])->where('state', 1)->where('pin_status', 'in', '0,1')->where('hd_id', $activitys['id'])->find();
                                                                if ($pintuans) {
                                                                    if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                                                        $assem_type = 3;
                                                                        $zhuangtai = 1;
                                                                    } elseif ($pintuans['pin_status'] == 1) {
                                                                        $zhuangtai = 2;
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        if ($assem_type == 3) {
                                                            $value = array('status' => 400, 'mess' => '您已参与商品拼团，下单失败', 'data' => array('status' => 400));
                                                            return json($value);
                                                        }
                                                    }

                                                    $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_id' => $purchs['goods_attr'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'hd_type' => $purchs['hd_type'], 'hd_id' => $purchs['hd_id'], 'shop_id' => $purchs['shop_id']);
                                                } else {
                                                    $value = array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                                                    return json($value);
                                                }
                                            }

                                            $ordouts = Db::name('order_timeout')->where('id', 1)->find();
                                            if ($ordouts) {
                                                if ($goodinfos) {
                                                    if ($goodinfos['hd_type'] == 3 && $fangshi == 2) {
                                                        if ($assem_type == 1) {
                                                            $order_type = 2;
                                                            $pin_type = 1;
                                                        } elseif ($assem_type == 2) {
                                                            $order_type = 2;
                                                            $pin_type = 2;
                                                        }
                                                    }

                                                    $goodinfos['coupon_id'] = 0;
                                                    $goodinfos['coupon_price'] = 0;
                                                    $goodinfos['coupon_str'] = '';
                                                    $goodinfos['youhui_price'] = 0;
                                                    $goodinfos['freight'] = 0;
                                                    $goodinfos['xiaoji_price'] = 0;
                                                    $cxgoods = array();

                                                    $xiaoji = sprintf("%.2f", $goodinfos['shop_price'] * $goodinfos['goods_num']);

                                                    $goodinfos['goods_price'] = $xiaoji;
                                                    $goodinfos['youhui_price'] = sprintf("%.2f", $goodinfos['youhui_price']);
                                                    $goodinfos['xiaoji_price'] = sprintf("%.2f", $xiaoji - $goodinfos['youhui_price']);

                                                    //邮费
                                                    $baoyou = 1;

                                                    if ($goodinfos['is_free'] == 0) {
                                                        $baoyou = 0;
                                                    }

                                                    if ($baoyou == 0) {
                                                        $shopinfos = Db::name('shops')->where('id', $goodinfos['shop_id'])->field('freight,reduce')->find();
                                                        if ($goodinfos['xiaoji_price'] < $shopinfos['reduce']) {
                                                            $goodinfos['freight'] = $shopinfos['freight'];
                                                            $goodinfos['xiaoji_price'] = sprintf("%.2f", $goodinfos['xiaoji_price'] + $shopinfos['freight']);
                                                        }
                                                    }

                                                    $total_price = sprintf("%.2f", $goodinfos['xiaoji_price']);

                                                    $order_number = 'Z' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                    $dingdan = Db::name('order_zong')->where('order_number', $order_number)->find();
                                                    if (!$dingdan) {
                                                        $datainfo = array();
                                                        $datainfo['order_number'] = $order_number;
                                                        $datainfo['total_price'] = $total_price;
                                                        $datainfo['state'] = 0;
                                                        $datainfo['zf_type'] = 0;
                                                        $datainfo['user_id'] = $user_id;
                                                        $datainfo['addtime'] = time();
                                                        $datainfo['time_out'] = 0;

                                                        // 启动事务
                                                        Db::startTrans();
                                                        try {
                                                            $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                                                            if ($zong_id) {
                                                                $time_out = time() + $ordouts['normal_out_order'] * 3600;

                                                                if ($goodinfos['hd_type'] == 1) {
                                                                    $time_out = time() + $ordouts['rushactivity_out_order'] * 60;
                                                                } elseif ($goodinfos['hd_type'] == 2) {
                                                                    $time_out = time() + $ordouts['group_out_order'] * 60;
                                                                } elseif ($goodinfos['hd_type'] == 3) {
                                                                    $time_out = time() + $ordouts['assemorder_timeout'] * 60;
                                                                }

                                                                $shop_ordernum = 'D' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);


                                                                $order_id = Db::name('order')->insertGetId(array(
                                                                    'ordernumber' => $shop_ordernum,
                                                                    'contacts' => $dizis['contacts'],
                                                                    'telephone' => $dizis['phone'],
                                                                    'pro_id' => $dizis['pro_id'],
                                                                    'city_id' => $dizis['city_id'],
                                                                    'area_id' => $dizis['area_id'],
                                                                    'province' => $dizis['pro_name'],
                                                                    'city' => $dizis['city_name'],
                                                                    'area' => $dizis['area_name'],
                                                                    'address' => $dizis['address'],
                                                                    'dz_id' => $dizis['id'],
                                                                    'goods_price' => $goodinfos['goods_price'],
                                                                    'freight' => $goodinfos['freight'],
                                                                    'coupon_id' => $goodinfos['coupon_id'],
                                                                    'coupon_price' => $goodinfos['coupon_price'],
                                                                    'coupon_str' => $goodinfos['coupon_str'],
                                                                    'youhui_price' => $goodinfos['youhui_price'],
                                                                    'total_price' => $goodinfos['xiaoji_price'],
                                                                    'state' => 0,
                                                                    'zf_type' => 0,
                                                                    'fh_status' => 0,
                                                                    'order_status' => 0,
                                                                    'user_id' => $user_id,
                                                                    'zong_id' => $zong_id,
                                                                    'order_type' => $order_type,
                                                                    'pin_type' => $pin_type,
                                                                    'pin_id' => 0,
                                                                    'shop_id' => $goodinfos['shop_id'],
                                                                    'addtime' => time(),
                                                                    'time_out' => $time_out
                                                                ));

                                                                //------------分销信息begin--------

                                                                $distributions = Db::name('distribution')
                                                                    ->where('id', 1)
                                                                    ->find();
                                                                $shops = Db::name('shops')
                                                                    ->where('id', $goodinfos['shop_id'])
                                                                    ->field('id,indus_id,fenxiao')
                                                                    ->find();
                                                                if ($distributions['is_open'] == 1 && $shops['fenxiao'] == 1) {
                                                                    $levelinfos = Db::name('member')
                                                                        ->where('id', $user_id)
                                                                        ->field('id,one_level,two_level')
                                                                        ->find();
                                                                    if ($levelinfos['one_level']) {
                                                                        $onefen_price = sprintf("%.2f", $goodinfos['xiaoji_price'] * ($distributions['one_profit'] / 100));
                                                                        Db::name('order')
                                                                            ->where('id', $order_id)
                                                                            ->update(array('onefen_id' => $levelinfos['one_level'], 'onefen_price' => $onefen_price));
                                                                    }
                                                                    if ($levelinfos['two_level']) {
                                                                        $twofen_price = sprintf("%.2f", $goodinfos['xiaoji_price'] * ($distributions['two_profit'] / 100));
                                                                        Db::name('order')
                                                                            ->where('id', $order_id)
                                                                            ->update(array('twofen_id' => $levelinfos['two_level'], 'twofen_price' => $twofen_price));
                                                                    }
                                                                }
                                                                //------------分销信息end--------
                                                                if ($goodinfos['coupon_id']) {
                                                                    Db::name('member_coupon')->where('user_id', $user_id)->where('coupon_id', $goodinfos['coupon_id'])->where('is_sy', 0)->where('shop_id', $goodinfos['shop_id'])->update(array('is_sy' => 1));
                                                                    $goodyh_price = sprintf("%.2f", $goodinfos['goods_price'] - $goodinfos['coupon_price']);
                                                                }

                                                                $goodzs_price = $goodinfos['shop_price'];
                                                                $jian_price = 0;
                                                                $prom_id = 0;
                                                                $prom_str = '';

                                                                if ($goodinfos['coupon_id']) {
                                                                    $dan_price = sprintf("%.2f", ($goodyh_price / $goodinfos['goods_price']) * $goodinfos['shop_price']);
                                                                    $goodzs_price = $dan_price;
                                                                    $jian_price = sprintf("%.2f", $goodinfos['shop_price'] - $dan_price);
                                                                }

                                                                if (!empty($cxgoods)) {
                                                                    if ($goodinfos['id'] == $cxgoods['cxgds']) {
                                                                        $zklv = $cxgoods['discount'] / 100;
                                                                        $zkprice = sprintf("%.2f", $goodinfos['shop_price'] * $zklv);
                                                                        $goodzs_price = sprintf("%.2f", $zkprice - $jian_price);
                                                                        $prom_id = $cxgoods['promo_id'];
                                                                        $zhenum = $cxgoods['discount'] / 10;
                                                                        $prom_str = '满' . $cxgoods['man_num'] . '件' . $zhenum . '折';
                                                                    }
                                                                }

                                                                $orgoods_id = Db::name('order_goods')->insertGetId(array(
                                                                    'goods_id' => $goodinfos['id'],
                                                                    'goods_name' => $goodinfos['goods_name'],
                                                                    'thumb_url' => $goodinfos['thumb_url'],
                                                                    'goods_attr_id' => $goodinfos['goods_attr_id'],
                                                                    'goods_attr_str' => $goodinfos['goods_attr_str'],
                                                                    'real_price' => $goodinfos['shop_price'],
                                                                    'price' => $goodzs_price,
                                                                    'goods_num' => $goodinfos['goods_num'],
                                                                    'hd_type' => $goodinfos['hd_type'],
                                                                    'hd_id' => $goodinfos['hd_id'],
                                                                    'prom_id' => $prom_id,
                                                                    'prom_str' => $prom_str,
                                                                    'is_free' => $goodinfos['is_free'],
                                                                    'shop_id' => $goodinfos['shop_id'],
                                                                    'order_id' => $order_id
                                                                ));

                                                                if (in_array($goodinfos['hd_type'], array(0, 2, 3))) {
                                                                    $prokcs = Db::name('product')->lock(true)->where('goods_id', $goodinfos['id'])->where('goods_attr', $goodinfos['goods_attr_id'])->find();
                                                                    if ($prokcs) {
                                                                        Db::name('product')->where('goods_id', $goodinfos['id'])->where('goods_attr', $goodinfos['goods_attr_id'])->setDec('goods_number', $goodinfos['goods_num']);
                                                                    }
                                                                } elseif ($goodinfos['hd_type'] == 1) {
                                                                    $hdactivitys = Db::name('rush_activity')->lock(true)->where('id', $goodinfos['hd_id'])->find();
                                                                    if ($hdactivitys) {
                                                                        Db::name('rush_activity')->where('id', $goodinfos['hd_id'])->setDec('kucun', $goodinfos['goods_num']);
                                                                        Db::name('rush_activity')->where('id', $goodinfos['hd_id'])->setInc('sold', $goodinfos['goods_num']);
                                                                    }
                                                                }

                                                                Db::name('order_zong')->update(array('id' => $zong_id, 'time_out' => $time_out));
                                                                Db::name('purch')->where('id', $pur_id)->where('user_id', $user_id)->delete();

                                                                if ($goodinfos['hd_type'] == 3 && $fangshi == 2) {
                                                                    if ($assem_type == 1 || $assem_type == 2) {
                                                                        if ($assem_type == 1) {
                                                                            $assem_number = 'P' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                                            $assem_timeout = time() + $ordouts['assem_timeout'] * 3600;
                                                                            $pin_id = Db::name('pintuan')->insertGetId(array(
                                                                                'assem_number' => $assem_number,
                                                                                'state' => 0,
                                                                                'pin_num' => $activitys['pin_num'],
                                                                                'tuan_num' => 0,
                                                                                'goods_id' => $goodinfos['id'],
                                                                                'pin_status' => 0,
                                                                                'tz_id' => $user_id,
                                                                                'hd_id' => $goodinfos['hd_id'],
                                                                                'shop_id' => $goodinfos['shop_id'],
                                                                                'time' => time(),
                                                                                'timeout' => $assem_timeout
                                                                            ));

                                                                            if ($pin_id) {
                                                                                Db::name('order_assemble')->insert(array(
                                                                                    'pin_type' => 1,
                                                                                    'goods_id' => $goodinfos['id'],
                                                                                    'goods_attr' => $goodinfos['goods_attr_id'],
                                                                                    'shop_id' => $goodinfos['shop_id'],
                                                                                    'user_id' => $user_id,
                                                                                    'hd_id' => $goodinfos['hd_id'],
                                                                                    'pin_id' => $pin_id,
                                                                                    'order_id' => $order_id,
                                                                                    'state' => 0,
                                                                                    'tui_status' => 0,
                                                                                    'addtime' => time()
                                                                                ));

                                                                                Db::name('order')->update(array('id' => $order_id, 'pin_id' => $pin_id));
                                                                            }
                                                                        } elseif ($assem_type == 2) {
                                                                            Db::name('order_assemble')->insert(array(
                                                                                'pin_type' => 2,
                                                                                'goods_id' => $goodinfos['id'],
                                                                                'goods_attr' => $goodinfos['goods_attr_id'],
                                                                                'shop_id' => $goodinfos['shop_id'],
                                                                                'user_id' => $user_id,
                                                                                'hd_id' => $goodinfos['hd_id'],
                                                                                'pin_id' => $pintuans['id'],
                                                                                'order_id' => $order_id,
                                                                                'state' => 0,
                                                                                'tui_status' => 0,
                                                                                'addtime' => time()
                                                                            ));

                                                                            Db::name('order')->update(array('id' => $order_id, 'pin_id' => $pintuans['id']));
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            //直接通过商品id 去判断，如果存在购物车里的信息，则删除
                                                            $tdata = Db::name('cart')->where('goods_id', $purchs['goods_id'])->where('user_id', $user_id)->find();
                                                            if ($tdata) {
                                                                Db::name('cart')->where('goods_id', $purchs['goods_id'])->where('user_id', $user_id)->delete();
                                                            }
                                                            // 提交事务
                                                            Db::commit();
                                                            $orderinfos = array('order_number' => $order_number, 'zf_type' => $zf_type);
                                                            $value = array('status' => 200, 'mess' => '创建订单成功', 'data' => $orderinfos);
                                                        } catch (\Exception $e) {
                                                            // 回滚事务
                                                            Db::rollback();
                                                            $value = array('status' => 400, 'mess' => '创建订单失败', 'data' => array('status' => 400));
                                                        }
                                                    } else {
                                                        $value = array('status' => 400, 'mess' => '创建订单失败', 'data' => array('status' => 400));
                                                    }
                                                } else {
                                                    $value = array('status' => 400, 'mess' => '商品信息参数错误', 'data' => array('status' => 400));
                                                }
                                            } else {
                                                $value = array('status' => 400, 'mess' => '创建订单失败', 'data' => array('status' => 400));
                                            }
                                        } else {
                                            $value = array('status' => 400, 'mess' => '找不到相关商品信息', 'data' => array('status' => 400));
                                        }
                                    } else {
                                        $value = array('status' => 400, 'mess' => '地址信息错误', 'data' => array('status' => 400));
                                    }
                                } else {
                                    $value = array('status' => 400, 'mess' => '支付方式参数错误', 'data' => array('status' => 400));
                                }
                            } else {
                                $value = array('status' => 400, 'mess' => '缺少地址信息', 'data' => array('status' => 400));
                            }
                        } else {
                            $value = array('status' => 400, 'mess' => '缺少购买方式参数', 'data' => array('status' => 400));
                        }
                    } else {
                        $value = array('status' => 400, 'mess' => '缺少立即购买商品参数', 'data' => array('status' => 400));
                    }
                } else {
                    $value = $result;
                }
            } else {
                $value = array('status' => 400, 'mess' => '缺少用户令牌', 'data' => array('status' => 400));
            }
        } else {
            $value = array('status' => 400, 'mess' => '请求方式不正确', 'data' => array('status' => 400));
        }
        return json($value);
    }
}
