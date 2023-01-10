<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/17
 * Time: 19:55
 */

namespace app\admin\controller;

use think\Db;

class WineBuyDayrateContract extends Common
{
    public function lst(){
        $list = Db::name('wine_contract_day')->select();

        $this->assign('list', $list);
        return $this->fetch();
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('wine_contract_day')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }

    public function view(){
        $input = input();
        $id = $input['id'];
        if(request()->isAjax()){
            $day_rate = $input['day_rate'];
            if($day_rate <= 0){
                $value = array('status'=>0,'mess'=>'利率设置异常');
                return json($value);
            }
            if(empty($input['deal_area'])){
                $value = array('status'=>0,'mess'=>'时间段设置失败');
                return json($value);
            }
            if($input['deposit']<=0 || $input['service_cost']<=0){
                $value = array('status'=>0,'mess'=>'预约保证金或抢购服务费设置异常');
                return json($value);
            }

            $res = Db::name('wine_contract_day')->where('id', $id)->update([
                'day_rate' => $day_rate,
                'updatetime' => time(),
                'deal_area' => $input['deal_area'],
                'deposit' => $input['deposit'],
                'service_cost' => $input['service_cost'],
                'price_area' => $input['price_area']
            ]);
            if($res > 0){
                $value = array('status'=>1,'mess'=>'修改成功');
            }
            else{
                $value = array('status'=>0,'mess'=>'设置失败');
            }
            
            ys_admin_logs('合约天数','wine_contract_day',$id);
            return json($value);
        }else{
            $list = Db::name('wine_contract_day')->where('id', $id)->find();

            $this->assign('list', $list);
            return $this->fetch();
        }
    }
    
    public function yuyue(){
        $input = input();
        $wine_contract_day_id = $input['id'];
        $keyword = $input['keyword'];
        $time = strtotime('today');
        $end_time = strtotime('tomorrow');
        $day = $input['date'];
        if($day > 0){
            $time -= $day * 86400;
            $end_time -= $day * 86400;
        }

        $list = Db::name('wine_order_record_contract')->alias('wor')
        ->where('wor.wine_contract_day_id', $wine_contract_day_id)->where(function ($query)use($keyword){
            if($keyword){
                $query->where('wor.id', $keyword)->whereOr('m.phone', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.true_name', $keyword);
            }
        })->field('wor.*, m.user_name,m.true_name,m.phone,woq.id woq_id,woq.addtime woq_addtime')->join('member m', 'm.id=wor.buy_id', 'left')->where('wor.addtime', '>=', $time)->where('wor.addtime', '<', $end_time)->order('wor.id desc')
        ->join('wine_order_qiangou_contract woq', 'woq.buy_id = wor.buy_id and woq.wine_goods_id=wor.wine_goods_id and woq.wine_contract_day_id=wor.wine_contract_day_id and woq.addtime < '.$end_time.' and woq.addtime>='.$time, 'left')
        // ->join('wine_yuyue_return_contract wyr', 'wyr.user_id = wor.buy_id and wyr.wine_goods_id=wor.wine_goods_id and wyr.wine_deal_area_id=wor.wine_deal_area_id and wyr.addtime < '.$end_time, ' and wyr.addtime>='.$time, 'left')
        ->paginate(30, false, ['query'=>request()->param()]);
        // var_dump(Db::name('wine_order_record')->getLastSql());exit;
        $count = Db::name('wine_order_record_contract')->where('wine_contract_day_id', $wine_contract_day_id)->where('addtime', '>=', $time)->where('addtime', '<', $end_time)->count();
        $page = $list->render();
        
        $list = $list->toArray()['data'];
        foreach ($list as $k=>&$v){
            $buy_id = $v['buy_id'];
            $column_id = Db::name('member')->where(function($query) use ($buy_id){
            
                $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%')->whereOr('jiedian_team_id', 'like', '%,'.$buy_id)->whereOr('jiedian_team_id', 'like', '%,'.$buy_id.',%');
            })->where('delete', 0)->column('id');
            // var_dump(Db::name('member')->getLastSql());exit;
            $v['team_count'] = count($column_id);
            
            $v['team_record_count'] = Db::name('wine_order_record_contract')->where('buy_id', 'in', $column_id)->where('addtime', '>=', $time)->where('addtime', '<', $end_time)->where('wine_goods_id', $v['wine_goods_id'])->where('wine_contract_day_id', $v['wine_contract_day_id'])->count();
            $v['team_canyu_count'] = Db::name('wine_order_qiangou_contract')->alias('woq')
                                    ->join('wine_order_record_contract worc', 'woq.buy_id = worc.buy_id', 'inner')
                                    ->where('worc.addtime', '>=', $time)->where('worc.addtime', '<', $end_time)
                                    ->where('woq.buy_id', 'in', $column_id)->where('woq.addtime', '>=', $time)->where('woq.addtime', '<', $end_time)->where('woq.wine_goods_id', $v['wine_goods_id'])->where('woq.wine_contract_day_id', $v['wine_contract_day_id'])->where('worc.wine_contract_day_id', $v['wine_contract_day_id'])->count();
        }
        // var_dump($list);exit;
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        // var_dump($pnum);exit;
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'date'=>$day,
            'keyword'=>$keyword,
            'pnum'=>$pnum,
            'count'=>$count,
            'wine_contract_day_id'=>$wine_contract_day_id,
            'time'=>$time,
            'end_time'=>$end_time,
        ));
        if(request()->isAjax()){
            return $this->fetch('yuyue_ajaxpage');
        }else{
            return $this->fetch('yuyue_lst');
        }
    }
    
    public function team_count(){
        $input = input();
        $buy_id = $input['id'];
        // exit;
        $list = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%')->whereOr('jiedian_team_id', 'like', '%,'.$buy_id)->whereOr('jiedian_team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->paginate(50);

        $count = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%')->whereOr('jiedian_team_id', 'like', '%,'.$buy_id)->whereOr('jiedian_team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->count();
        $page = $list->render();

        // var_dump($list);exit;
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
            'buy_id'=>$buy_id
        ));
        if(request()->isAjax()){
            return $this->fetch('team_count_ajaxpage');
        }else{
            return $this->fetch('team_count_lst');
        }
    }

    public function team_count_export(){
        $input = input();
        $buy_id = $input['id'];
        // exit;
        $list = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%')->whereOr('jiedian_team_id', 'like', '%,'.$buy_id)->whereOr('jiedian_team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->paginate(1000000);
        $info = Db::name('member')->where('id', $buy_id)->field('true_name, user_name')->find();

        $arr = [];
        array_push($arr, ['A'=>'会员编号', 'B'=>'会员名称', 'C'=>'会员手机号']);

        for($i=0; $i<count($list); $i++){
            array_push($arr, ['A'=>$list[$i]['id'], 'B'=>$list[$i]['true_name']??$list[$i]['user_name'], 'C'=>$list[$i]['phone']]);
        }
        vendor('PHPExcel.Classes.PHPExcel');

        $Excel = new \PHPExcel();
        // 设置
        $Excel
            ->getProperties()
            ->setCreator("dee")
            ->setLastModifiedBy("dee")
            ->setKeywords("excel")
            ->setCategory("result file");

        foreach($arr as $key => $val) { // 注意 key 是从 0 还是 1 开始，此处是 0
            $num = $key + 1;

            $Excel ->setActiveSheetIndex(0)
             //Excel的第A列，uid是你查出数组的键值，下面以此类推
              ->setCellValue('A'.$num, $val['A'])
              ->setCellValue('B'.$num, $val['B'])
              ->setCellValue('C'.$num, $val['C']);
        }

        $Excel->getActiveSheet()->setTitle('export');

        $Excel->setActiveSheetIndex(0);

        $name = ($info['true_name']??$info['user_name']) . '团队记录'.date('Y-m-d',time()).'.xlsx';

        header('Content-Type: application/vnd.ms-excel');

        header('Content-Disposition: attachment; filename='.$name);

        header('Cache-Control: max-age=0');

        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');

        $ExcelWriter->save('php://output');

        exit;
    }
    
   public function team_record_count(){
        $input = input();
        $buy_id = $input['id'];
        // exit;
        $id_arr = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%')->whereOr('jiedian_team_id', 'like', '%,'.$buy_id)->whereOr('jiedian_team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->column('id');
        // var_dump($id_arr);exit;
        $list = Db::name('wine_order_record_contract')->alias('wor')->where('wor.buy_id', 'in', $id_arr)->where('wor.wine_goods_id', $input['wine_goods_id'])->where('wor.wine_contract_day_id', $input['wine_contract_day_id'])->field('wor.*, m.true_name,m.user_name,m.phone')
                ->join('member m', 'm.id = wor.buy_id', 'left')->where('wor.addtime', '>=', $input['time'])->where('wor.addtime', '<=', $input['end_time'])->paginate(50);

        $count = Db::name('wine_order_record_contract')->where('buy_id', 'in', $id_arr)->where('wine_goods_id', $input['wine_goods_id'])->where('wine_contract_day_id', $input['wine_contract_day_id'])->where('addtime', '>=', $input['time'])->where('addtime', '<=', $input['end_time'])->count();
        $page = $list->render();

        // var_dump($list);exit;
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
            'buy_id'=>$buy_id,
            'time'=>$input['time'],
            'end_time'=>$input['end_time'],
            'wine_goods_id'=>$input['wine_goods_id'],
            'wine_contract_day_id'=>$input['wine_contract_day_id'],
        ));
        if(request()->isAjax()){
            return $this->fetch('team_record_count_ajaxpage');
        }else{
            return $this->fetch('team_record_count_lst');
        }
    }
    
    public function team_record_export(){
        $input = input();
        $buy_id = $input['id'];
        // exit;
        $id_arr = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%')->whereOr('jiedian_team_id', 'like', '%,'.$buy_id)->whereOr('jiedian_team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->column('id');
        $info = Db::name('member')->where('id', $buy_id)->field('true_name, user_name')->find();
        // var_dump($id_arr);exit;
        $list = Db::name('wine_order_record_contract')->alias('wor')->where('wor.buy_id', 'in', $id_arr)->where('wor.wine_goods_id', $input['wine_goods_id'])->where('wor.wine_contract_day_id', $input['wine_contract_day_id'])->field('wor.*, m.true_name,m.user_name,m.phone')
                ->join('member m', 'm.id = wor.buy_id', 'left')->where('wor.addtime', '>=', $input['time'])->where('wor.addtime', '<=', $input['end_time'])->paginate(100000);

        $arr = [];
        array_push($arr, ['A'=>'会员编号', 'B'=>'会员名称', 'C'=>'会员手机号', 'D'=>'预约金额', 'E'=>'预约日期']);

        for($i=0; $i<count($list); $i++){
            array_push($arr, ['A'=>$list[$i]['id'], 'B'=>$list[$i]['true_name']??$list[$i]['user_name'], 'C'=>$list[$i]['phone'], 'D'=>$list[$i]['frozen_point'], 'E'=>date('Y-m-d H:i:s', $list[$i]['addtime'])]);
        }
        vendor('PHPExcel.Classes.PHPExcel');

        $Excel = new \PHPExcel();
        // 设置
        $Excel
            ->getProperties()
            ->setCreator("dee")
            ->setLastModifiedBy("dee")
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
              ->setCellValue('E'.$num, $val['E']);
        }

        $Excel->getActiveSheet()->setTitle('export');

        $Excel->setActiveSheetIndex(0);

        $name = ($info['true_name']??$info['user_name']) . '团队预约记录'.date('Y-m-d',$input['time']).'.xlsx';

        header('Content-Type: application/vnd.ms-excel');

        header('Content-Disposition: attachment; filename='.$name);

        header('Cache-Control: max-age=0');

        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');

        $ExcelWriter->save('php://output');

        exit;
    }

    public function team_canyu_count(){
        $input = input();
        $buy_id = $input['id'];

        $id_arr = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%')->whereOr('jiedian_team_id', 'like', '%,'.$buy_id)->whereOr('jiedian_team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->column('id');

        $list = Db::name('wine_order_qiangou_contract')->alias('woq')->join('wine_order_record_contract worc', 'woq.buy_id = worc.buy_id', 'inner')->where('woq.buy_id', 'in', $id_arr)->where('woq.wine_goods_id', $input['wine_goods_id'])->where('woq.wine_contract_day_id', $input['wine_contract_day_id'])->where('worc.wine_contract_day_id', $input['wine_contract_day_id'])->field('woq.*, m.true_name,m.user_name,m.phone')
                ->join('member m', 'm.id = woq.buy_id', 'left')->where('woq.addtime', '>=', $input['time'])->where('woq.addtime', '<=', $input['end_time'])->paginate(50);

        $count = Db::name('wine_order_qiangou_contract')->alias('woq')->join('wine_order_record_contract worc', 'woq.buy_id = worc.buy_id', 'inner')->where('woq.buy_id', 'in', $id_arr)->where('woq.wine_goods_id', $input['wine_goods_id'])->where('woq.wine_contract_day_id', $input['wine_contract_day_id'])->where('worc.wine_contract_day_id', $input['wine_contract_day_id'])->where('woq.addtime', '>=', $input['time'])->where('woq.addtime', '<=', $input['end_time'])->count();
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
            'buy_id'=>$buy_id,
            'time'=>$input['time'],
            'end_time'=>$input['end_time'],
            'wine_goods_id'=>$input['wine_goods_id'],
            'wine_contract_day_id'=>$input['wine_contract_day_id'],
        ));
        if(request()->isAjax()){
            return $this->fetch('team_canyu_count_ajaxpage');
        }else{
            return $this->fetch('team_canyu_count_lst');
        }
    }
    
    public function team_canyu_export(){
        $input = input();
        $buy_id = $input['id'];
        // exit;
        $id_arr = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%')->whereOr('jiedian_team_id', 'like', '%,'.$buy_id)->whereOr('jiedian_team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->column('id');
        $info = Db::name('member')->where('id', $buy_id)->field('true_name, user_name')->find();

        $list = Db::name('wine_order_qiangou_contract')->alias('woq')->join('wine_order_record_contract worc', 'woq.buy_id = worc.buy_id', 'inner')->where('woq.buy_id', 'in', $id_arr)->where('woq.wine_goods_id', $input['wine_goods_id'])->where('woq.wine_contract_day_id', $input['wine_contract_day_id'])->where('worc.wine_contract_day_id', $input['wine_contract_day_id'])->field('woq.*, m.true_name,m.user_name,m.phone')
                ->join('member m', 'm.id = woq.buy_id', 'left')->where('woq.addtime', '>=', $input['time'])->where('woq.addtime', '<=', $input['end_time'])->paginate(100000000);

        $arr = [];
        array_push($arr, ['A'=>'会员编号', 'B'=>'会员名称', 'C'=>'会员手机号', 'D'=>'预约日期']);

        for($i=0; $i<count($list); $i++){
            array_push($arr, ['A'=>$list[$i]['id'], 'B'=>$list[$i]['true_name']??$list[$i]['user_name'], 'C'=>$list[$i]['phone'], 'D'=>date('Y-m-d H:i:s', $list[$i]['addtime'])]);
        }
        vendor('PHPExcel.Classes.PHPExcel');

        $Excel = new \PHPExcel();
        // 设置
        $Excel
            ->getProperties()
            ->setCreator("dee")
            ->setLastModifiedBy("dee")
            ->setKeywords("excel")
            ->setCategory("result file");

        foreach($arr as $key => $val) { // 注意 key 是从 0 还是 1 开始，此处是 0
            $num = $key + 1;

            $Excel ->setActiveSheetIndex(0)
             //Excel的第A列，uid是你查出数组的键值，下面以此类推
              ->setCellValue('A'.$num, $val['A'])
              ->setCellValue('B'.$num, $val['B'])
              ->setCellValue('C'.$num, $val['C'])
              ->setCellValue('D'.$num, $val['D']);
        }

        $Excel->getActiveSheet()->setTitle('export');

        $Excel->setActiveSheetIndex(0);

        $name = ($info['true_name']??$info['user_name']) . '团队参与记录'.date('Y-m-d',$input['time']).'.xlsx';

        header('Content-Type: application/vnd.ms-excel');

        header('Content-Disposition: attachment; filename='.$name);

        header('Cache-Control: max-age=0');

        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');

        $ExcelWriter->save('php://output');

        exit;
    }

    public function export(){
        $input = input();
        $wine_contract_day_id = $input['id'];
        $keyword = $input['keyword'];
        // var_dump(input());
        $time = strtotime('today');
         $day = $input['date'];
        if($day > 0){
            $time -= $day * 86400;
            $end_time = $time + 86400;
        }
        
        // var_dump($wine_deal_area_id);exit;
        $list = Db::name('wine_order_record_contract')->alias('wor')
        ->where('wor.wine_contract_day_id', $wine_contract_day_id)->where(function ($query)use($keyword){
            if($keyword){
                $query->where('wor.id', $keyword)->whereOr('m.phone', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.true_name', $keyword);
            }
        })->field('wor.*, m.user_name,m.true_name,m.phone,woq.id woq_id,woq.addtime woq_addtime')->join('member m', 'm.id=wor.buy_id', 'left')->where('wor.addtime', '>=', $input['time'])->order('wor.id desc')
        ->join('wine_order_qiangou_contract woq', 'woq.buy_id = wor.buy_id and woq.wine_goods_id=wor.wine_goods_id and woq.wine_contract_day_id=wor.wine_contract_day_id and woq.addtime < '.$end_time.' and woq.addtime>='.$input['time'], 'left')
        // ->where('wyr.addtime', '>=', $time)
        // ->paginate(30, false, ['query'=>request()->param()])->toArray()['data'];
        ->select();
        // var_dump($list);exit;
        foreach ($list as $k=>&$v){
            $buy_id = $v['buy_id'];
            $column_id = Db::name('member')->where(function($query) use ($buy_id){

                $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%')->whereOr('jiedian_team_id', 'like', '%,'.$buy_id)->whereOr('jiedian_team_id', 'like', '%,'.$buy_id.',%');
            })->where('delete', 0)->column('id');
            // var_dump(Db::name('member')->getLastSql());exit;
            $v['team_count'] = count($column_id);

            $v['team_record_count'] = Db::name('wine_order_record_contract')->where('buy_id', 'in', $column_id)->where('addtime', '>=', $input['time'])->where('addtime', '<', $end_time)->where('wine_goods_id', $v['wine_goods_id'])->where('wine_contract_day_id', $v['wine_contract_day_id'])->count();
            // $v['team_canyu_count'] = Db::name('wine_yuyue_return')->where('user_id', 'in', $column_id)->where('addtime', '>=', $time)->where('wine_goods_id', $v['wine_goods_id'])->where('wine_deal_area_id', $v['wine_deal_area_id'])->count();
            $v['team_canyu_count'] = Db::name('wine_order_qiangou_contract')->where('buy_id', 'in', $column_id)->where('addtime', '>=', $input['time'])->where('addtime', '<', $end_time)->where('wine_goods_id', $v['wine_goods_id'])->where('wine_contract_day_id', $v['wine_contract_day_id'])->count();
        }
        echo Db::name('wine_order_record_contract')->getLastSql();

        $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'会员名称', 'C'=>'会员手机号', 'D'=>'预约日期', 'E'=>'团队/预约/参与', 'F'=>'是否参与', 'G'=>'参与时间', 'H'=>'是否返还', 'I'=>'返还时间']);

        for($i=0; $i<count($list); $i++){
            array_push($arr, ['A'=>$list[$i]['id'], 'B'=>$list[$i]['true_name']??$list[$i]['user_name'], 'C'=>$list[$i]['phone'], 'D'=>$list[$i]['addtime'] ? date('Y-m-d H:i:s', $list[$i]['addtime']) : '/', 'E'=>$list[$i]['team_count'].'/'.$list[$i]['team_record_count'].'/'.$list[$i]['team_canyu_count'], 'F'=>$list[$i]['woq_id']>0?'是':'否', 'G'=>$list[$i]['woq_addtime'] ? date('Y-m-d H:i:s', $list[$i]['woq_addtime']) : '/', 'H'=>$list[$i]['status']==1?'是':'否', 'I'=>$list[$i]['updatetime'] ? date('Y-m-d H:i:s', $list[$i]['updatetime']) : '/']);
        }
        vendor('PHPExcel.Classes.PHPExcel');

        $Excel = new \PHPExcel();
        // 设置
        $Excel
            ->getProperties()
            ->setCreator("dee")
            ->setLastModifiedBy("dee")
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

        $name = '合约竞拍预约记录'.date("Y-m-d",$time).'.xlsx';

        header('Content-Type: application/vnd.ms-excel');

        header('Content-Disposition: attachment; filename='.$name);

        header('Cache-Control: max-age=0');

        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');

        $ExcelWriter->save('php://output');

        exit;
    }
}