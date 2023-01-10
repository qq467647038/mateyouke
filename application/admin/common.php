<?php
/**
 * Created by PhpStorm.
 * User: zhengpeng
 * Date: 2018/5/16
 * Time: 下午4:20
 */

/**
 * 添加后台日志
 * @param $log 操作类型
 * @param $tables 操作数据表
 * @param $opid 操作主键ID
 */
require_once "delevorperlx.php";
function ys_admin_logs($log,$tables,$opid,$remark='')
{
    return \app\admin\controller\AdminLog::add($log,$tables,$opid,$remark);
}

