<?php
namespace app\common\model;
use think\Model;
use think\Db;

class SendSmsLog extends Model
{
    public static function add($data)
    {
        $data['add_time'] = date("Y-m-d H:i:s",time());
        return self::create($data);
    }
}