<?php
namespace app\admin\validate;
use think\Validate;

class WineContract extends Validate
{
    protected $rule = [
        'goods_name|商品名称' => 'require',
        // 'goods_desc|级别'=>'require',
        // 'expends|消耗'=>'require',
        // 'value|价值'=>'require',
        // 'rate|利率'=>'require|number|egt:0|elt:100',
        // 'day|天数'=>'require|number',
        // 'deposit|燃料使用费'=>'require|number',
        // 'best_max_amount|封顶金额'=>'require|integer|egt:0',
        // 'best_max_day|最大保留天数'=>'require|integer|egt:0',
        // 'adopt|领养时间'=>'require',
        // 'onsale|在售'=>'require|in:0,1',
    ];

    protected $message = [

    ];

}