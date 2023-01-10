<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/7/1
 * Time: 7:21
 */

namespace app\admin\controller;

use think\Db;

class WineMemberDeposit extends Common
{
    //会员保证金记录
    public function lst(){

        $filter = input('filter');$post = input();
        
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }

        $list = Db::name('reg_enable_amount')->alias('rea')
                    ->field('rea.*, m.phone phone, m.user_name, m.regtime')
                    ->where('rea.updatetime', 'between time', $whereTime)
                    ->join('member m', 'rea.user_id = m.id', 'inner')
                    ->order('rea.id desc')->paginate(50, false, ['query'=>request()->param()]);
        $count = Db::name('reg_enable_amount')->count();
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'count'=>$count,
            'where_time'=>$whereTime,
            'filter'=>input('filter'),
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function success_pass(){
        $input = input();
        
        if(isset($input['id']) && $input['id']>0){
            $info = Db::name('reg_enable_amount')->alias('rea')
                    ->field('rea.*,m.agent_type')
                    ->join('member m', 'm.id=rea.user_id', 'inner')
                    ->where('rea.id', $input['id'])->where('rea.status', 0)->find();
            if($info){
                Db::startTrans();
                try{
                    $res = Db::name('reg_enable_amount')->where('id', $input['id'])->where('status', 0)->update([
                        'status'=>1
                    ]);
                    if(!$res)throw new Exception('失败');
                    
                    $res = Db::name('member')->where('id', $info['user_id'])->inc('reg_enable_deposit', $info['amount'])->update([
                        'reg_enable'=>1
                    ]);
                    if(!$res)throw new Exception('失败');
                    
                    $detail = [
                        'de_type' => 1,
                        'sr_type' => 90,
                        'price' => $info['amount'],
                        'user_id' => $info['user_id'],
                        'wat_id' => Db::name('wallet')->where('user_id', $info['user_id'])->value('id'),
                        'time' => time(),
                        'agent_type'=>$info['agent_type']
                    ];
                    $res = Db::name('detail')->insert($detail);
                    if(!$res)throw new Exception('失败');
                    
                    Db::commit();
                    $this->wineGoodsUpgrade($info['user_id']);
                    return 1;
                }
                catch(\Exception $e){
                    Db::rollback();
                    return 0;
                }
            }
            else{
                return 0;
            }
        }
        else{
            return 0;
        }
    }
    
    
    // 导出 Excel
    public function export() {
        $post = input();
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }

        $list = Db::name('reg_enable_amount')->alias('rea')
                    ->field('rea.*, m.phone phone, m.user_name, m.regtime')
                    ->where('rea.updatetime', 'between time', $whereTime)
                    ->join('member m', 'rea.user_id = m.id', 'inner')
                    ->order('rea.id desc')->select();
     
        $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'单号', 'C'=>'用户名', 'D'=>'手机号', 'E'=>'金额', 'F'=>'注册时间', 'G'=>'激活时间', 'H'=>'类型', 'I'=>'状态']);
        foreach($list as $k=>$v){
            array_push($arr, ['A'=>$v['id'], 'B'=>$v['odd'], 'C'=>$v['user_name'], 'D'=>$v['phone'], 'E'=>$v['amount'], 'F'=>date('Y-m-d H:i:s', $v['regtiem']), 'G'=>date('Y-m-d H:i:s', $v['updatetime']), 'H'=>($v['type']==0?'支付宝':''), 'I'=>($v['status']==1?'已付款':'未付款')]);
        }
    
        vendor('PHPExcel.Classes.PHPExcel');
    
        $Excel = new \PHPExcel();
        // 设置
        $Excel
            ->getProperties()
            ->setCreator("dee")
            ->setLastModifiedBy("dee")
            ->setTitle("会员激活列表")
            ->setSubject("会员激活数据EXCEL导出")
            ->setDescription("会员激活数据EXCEL导出")
            ->setKeywords("excel")
            ->setCategory("result file");
    
        foreach($arr as $key => $val) { // 注意 key 是从 0 还是 1 开始，此处是 0
            $num = $key + 1;
    
            $Excel ->setActiveSheetIndex(0)
             //Excel的第A列，uid是你查出数组的键值，下面以此类推
              ->setCellValue('A'.$num, $val['A'])    
              ->setCellValue('B'.$num, $val['B'])
              ->setCellValue('C'.$num, $val['C'])
              ->setCellValue('D'.$num, $val['D'])
              ->setCellValue('E'.$num, $val['E'])
              ->setCellValue('F'.$num, $val['F'])
              ->setCellValue('G'.$num, $val['G'])
              ->setCellValue('H'.$num, $val['H'])
              ->setCellValue('I'.$num, $val['I']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '会员激活列表'.date('Y-m-d').'.xlsx';
    
        header('Content-Type: application/vnd.ms-excel');
    
        header('Content-Disposition: attachment; filename='.$name);
    
        header('Cache-Control: max-age=0');
    
        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
    
        $ExcelWriter->save('php://output');
    
        exit;       
    
    }
}
