<?php
namespace app\admin\validate;
use think\Validate;

class OrderTimeout extends Validate

{
    protected $rule = [
        'normal_out_order' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>24],
        'rushactivity_out_order' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>60],
        'group_out_order' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>60],
        'assemorder_timeout' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>60],
        'assem_timeout' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>24],
        'zdqr_sh_time' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>30],
        'check_timeout' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>30],
        'shoptui_timeout' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>30],
        'yhfh_timeout' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>30],
        'yhshou_timeout' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1,'elt'=>30],
    ];

    protected $message = [
        'normal_out_order.require' => '缺少普通订单关闭时间',
        'normal_out_order.regex' => '普通订单关闭时间格式错误',
        'normal_out_order.egt' => '普通订单关闭时间需在1到24小时之间',
        'normal_out_order.elt' => '普通订单关闭时间需在1到24小时之间',
        'rushactivity_out_order.require' => '缺少秒杀订单关闭时间',
        'rushactivity_out_order.regex' => '秒杀订单关闭时间格式错误',
        'rushactivity_out_order.egt' => '秒杀订单关闭时间需在1到60分钟之间',
        'rushactivity_out_order.elt' => '秒杀订单关闭时间需在1到60分钟之间',
        'group_out_order.require' => '缺少团购订单关闭时间',
        'group_out_order.regex' => '团购订单关闭时间格式错误',
        'group_out_order.egt' => '团购订单关闭时间需在1到60分钟之间',
        'group_out_order.elt' => '团购订单关闭时间需在1到60分钟之间',
        'assemorder_timeout.require' => '缺少拼团订单关闭时间',
        'assemorder_timeout.regex' => '拼团订单关闭时间格式错误',
        'assemorder_timeout.egt' => '拼团订单关闭时间需在1到60分钟之间',
        'assemorder_timeout.elt' => '拼团订单关闭时间需在1到60分钟之间',
        'assem_timeout.require' => '缺少拼团订单未成团过期关闭时间',
        'assem_timeout.regex' => '拼团订单未成团过期关闭时间格式错误',
        'assem_timeout.egt' => '拼团订单未成团过期关闭时间需在1到24小时之间',
        'assem_timeout.elt' => '拼团订单未成团过期关闭时间需在1到24小时之间',
        'zdqr_sh_time.require' => '缺少过期自动确认收货时间',
        'zdqr_sh_time.regex' => '过期自动确认收货时间格式错误',
        'zdqr_sh_time.egt' => '过期自动确认收货时间需在1到30天之间',
        'zdqr_sh_time.elt' => '过期自动确认收货时间需在1到30天之间',
        'check_timeout.require' => '缺少商家审核售后订单过期时间',
        'check_timeout.regex' => '商家审核售后订单过期时间格式错误',
        'check_timeout.egt' => '商家审核售后订单过期时间需在1到30天之间',
        'check_timeout.elt' => '商家审核售后订单过期时间需在1到30天之间',
        'shoptui_timeout.require' => '缺少商家退款过期时间',
        'shoptui_timeout.regex' => '商家退款过期时间格式错误',
        'shoptui_timeout.egt' => '商家退款过期时间需在1到30天之间',
        'shoptui_timeout.elt' => '商家退款过期时间需在1到30天之间',
        'yhfh_timeout.require' => '缺少用户发货过期时间',
        'yhfh_timeout.regex' => '用户发货过期时间格式错误',
        'yhfh_timeout.egt' => '用户发货过期时间需在1到30天之间',
        'yhfh_timeout.elt' => '用户发货过期时间需在1到30天之间',
        'yhshou_timeout.require' => '缺少用户确认收货过期时间',
        'yhshou_timeout.regex' => '用户确认收货过期时间格式错误',
        'yhshou_timeout.egt' => '用户确认收货过期时间需在1到30天之间',
        'yhshou_timeout.elt' => '用户确认收货过期时间需在1到30天之间',
    ];
    

}