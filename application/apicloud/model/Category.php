<?php
/**
 * Created by PhpStorm.
 * @anthor: Pupil_Chen
 * Date: 2020/9/22 0022
 * Time: 10:27
 */

namespace app\apicloud\model;


use think\Model;

class Category extends Model
{
    public function goods(){
        return $this->hasMany('Goods', 'cate_id', 'id');
    }
}