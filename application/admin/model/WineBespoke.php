<?php
namespace app\admin\model;
use think\Model;

class WineBespoke extends Model
{    
    // 设置当前模型对应的完整数据表名称
    protected $name = 'wine_order_record';
    
    public function wineBespoke(){
        return $this->hasMany('WineBespoke', 'buy_id', 'buy_id');
    }
    
    public function wineDistribution(){
        return $this->hasMany('wineDistribution', 'buy_id', 'buy_id');
    }
    
    
}