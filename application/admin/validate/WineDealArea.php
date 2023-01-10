<?php
namespace app\admin\validate;
use think\Validate;

class WineDealArea extends Validate
{
    protected $rule = [
        'deal_area' => 'require',
        'desc' => 'require',
        'odd_num' => 'require|integer|gt:0',
        'deposit' => 'require|egt:0',
    ];

    protected $message = [
        'deal_area.require' => '交易时间段不能为空',
        'desc.require' => '描述不能为空',
        'odd_num.require' => '单数不能为空',
        'odd_num.integer' => '单数只能是整数',
        'odd_num.gt' => '单数必须大于0',
        'deposit.require' => '保证金不能为空',
        'deposit.egt' => '保证金必须大于等于0'
    ];
    
    protected $scene = [
        'add' => ['deal_area', 'desc', 'odd_num', 'deposit']
    ];
}