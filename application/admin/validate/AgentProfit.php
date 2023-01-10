<?php
namespace app\admin\validate;
use think\Validate;

class AgentProfit extends Validate

{
    protected $rule = [
        'open|代理提现' => 'require|in:0,1',
        'withdrawal_day|提现日' => ['require', 'integer'],
    ];

    protected $message = [

    ];

    protected $scene = [

    ];
}