<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\common\model\Member as MemberModel;
use app\common\model\SoldierCheck as SoldierCheckModel;
use app\common\service\SoldierService;
use think\Db;

class Soldier extends Common
{
    public function index()
    {
        $list = [];
        $size = input('size/d',20);
        $where = [
            'soldier' => ['neq',0]
        ];
        $list = MemberModel::queryPage($where,$size);
        $page = $list->render();
        $this->assign([
            'list' => $list,
            'page' => $page
        ]);
        return $this->fetch();
    }

    /**
     * 审核列表
     *
     * @return void
     */
    public function checkList()
    {
        $where = [
            'is_del' => 0,
            'status' => 0
        ];
        $size = input('size/d',20);
        $list = SoldierCheckModel::queryPage($where,$size);
        $page = $list->render();
        $status = [
            '未审核','已通过','已拒绝'
        ];
        $this->assign([
            'list' => $list,
            'page' => $page,
            'status' => $status
        ]);
        return $this->fetch();
    }

    /**
     * 审核通过
     *
     * @return void
     */
    public function pass()
    {
        $id = input('id/d');
        $info = SoldierCheckModel::findById($id);
        $service = new SoldierService($info['user_id']);
        $result = $service->pass($info);
        if($result){
            return returnJson(200,'编辑成功');
        }
        return returnJson(400,'编辑失败');
    }

    /**
     * 审核拒绝
     *
     * @return void
     */
    public function refuse()
    {
        $id = input('id/d');
        try {
            SoldierCheckModel::refuse($id);
            return returnJson(200,'编辑成功');
        } catch (\Throwable $th) {
            return returnJson(400,'编辑失败');
        }
    }

}