<?php
namespace app\admin\validate;
use think\Validate;

class Distribution extends Validate

{
    protected $rule = [
        'is_open' => 'require|in:0,1',
        'one_profit' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>0,'elt'=>100],
        'two_profit' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>0,'elt'=>100],
        'goods_id'  => 'require',

        'open|分销' => 'require|in:0,1',
        'direct_profit|直推代理' => ['require', 'number','egt'=>0,'elt'=>100],
        'indirect_profit|间推代理' => ['require', 'number','egt'=>0,'elt'=>100],
        'peoson_profit|个人代理' => ['require', 'number','egt'=>0,'elt'=>100],
        'area_profit|区级代理' => ['require', 'number','egt'=>0,'elt'=>100],
        'city_profit|城市代理' => ['require', 'number','egt'=>0,'elt'=>100],
        'province_profit|省级代理' => ['require', 'number','egt'=>0,'elt'=>100],
    ];

    protected $message = [
        'goods_id.require'  => '请选择分销商品',
        'is_open.require' => '请选择是否开启分销',
        'is_open.in' => '是否开启分销参数错误',
        'one_profit.require' => '缺少一级上线订单分成参数',
        'one_profit.regex' => '一级上线订单分成参数格式错误',
        'one_profit.egt' => '一级上线订单分成参数需在0到100之间',
        'one_profit.elt' => '一级上线订单分成参数需在0到100之间',
        'two_profit.require' => '缺少二级上线订单分成参数',
        'two_profit.regex' => '二级上线订单分成参数格式错误',
        'two_profit.egt' => '二级上线订单分成参数需在0到100之间',
        'two_profit.elt' => '二级上线订单分成参数需在0到100之间',
    ];

    protected $scene = [
        'travel'  =>  ['direct_profit','indirect_profit','peoson_profit','area_profit','city_profit','province_profit','open'],
    ];
}