<?php
namespace app\shop\validate;
use think\Validate;

class Goods extends Validate
{
    protected $rule = [
        'goods_name' => 'require|max:50',
        'cate_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'market_price'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','gt'=>0],
        'shop_price'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','gt'=>0,'lt'=>'market_price'],
        'goods_desc'=>'require',
        'search_keywords' => 'require|max:100',
        'onsale'=>'require|in:0,1',
        'is_free'=>'require|in:0,1',
        'is_new'=>'require|in:0,1',
        'is_special'=>'require|in:0,1',
        'is_hot'=>'require|in:0,1',
        'is_recommend'=>'require|in:0,1',
        'is_recycle'=>'require|in:0,1',
        'type_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];
    
    protected $message = [
        'goods_name.require' => '商品名称不能为空',
        'goods_name.max' => '商品名称最多50个字符',
        'cate_id.require' => '请选择商品分类',
        'cate_id.regex' => '商品分类参数错误',
        'market_price.require' => '市场价格不能为空',
        'market_price.regex' => '市场价格格式错误',
        'market_price.gt' => '市场价格需大于0',
        'shop_price.require' => '商品价格不能为空',
        'shop_price.regex' => '商品价格格式错误',
        'shop_price.gt' => '商品价格需大于0',
        'shop_price.lt'=> '商品员价格需小于市场价格',
        'goods_desc.require' => '商品详情不能为空',
        'search_keywords.require' => '搜索关键词不能为空',
        'search_keywords.max' => '搜索关键词最多100个字符',
        'onsale.require' => '请选择上架或下架',
        'onsale.in' => '上下架参数错误',
        'is_free.require' => '请选择是否设为包邮',
        'is_free.in' => '设为包邮参数错误',
        'is_new.require' => '请选择是否设为新品',
        'is_new.in' => '设为新品参数错误',
        'is_hot.require' => '请选择是否设为热销',
        'is_hot.in' => '设为热销参数错误',
        'is_recommend.require' => '请选择是否设为推荐',
        'is_recommend.in' => '设为推荐参数错误',
        'is_recycle.require' => '请选择是否放入回收站',
        'is_recycle.in' => '是否放入回收站参数错误',
        'type_id.require' => '缺少商品类型参数',
        'type_id.regex' => '缺少商品类型参数',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

}