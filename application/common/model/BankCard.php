<?php
namespace app\common\model;
use think\Model;
use think\Db;

class BankCard extends Model
{
    public static function add($data)
    {
        $map = [
            'name' => $data['name'],
            'telephone' => $data['iphone'],
            'card_number' => $data['card_number'],
            'bank_name' => $data['bank_name'],
            'province' => '',
            'city' => '',
            'area' => '',
            'user_id' => $data['user_id']
        ];

        return self::create($map);
    }

    public static function updateData($data)
    {
        $map = [
            'name' => $data['name'],
            'telephone' => $data['iphone'],
            'card_number' => $data['card_number'],
            'bank_name' => $data['bank_name'],
            'province' => '',
            'city' => '',
            'area' => '',
        ];
        return self::where('user_id',$data['user_id'])->update($map);
    }
}