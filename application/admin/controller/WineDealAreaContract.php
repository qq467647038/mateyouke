<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class WineDealAreaContract extends Common{
    //
    public function lst(){
        $post = input();
        
        $list = Db::name('wine_deal_area_contract')->paginate(50);
        $count = Db::name('member')->count();
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
            'count'=>$count
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

    // 添加交易时间段
    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'WineDealAreaContract.add');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['addtime'] = time();
                $wineDealArea = Db::name('wine_deal_area_contract')->insert($data);
                if(!$wineDealArea){
                    $value = array('status'=>0,'mess'=>'添加失败');
                }
                else{
                    $value = array('status'=>1,'mess'=>'添加成功');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }
    

    //处理多图片上传
    public function uploadifys(){
        $admin_id = session('admin_id');
    
        $file = request()->file('filedata');
        if($file){
            $info = aliyunOSS($_FILES);
//            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'ad_pic');
            if($info){
//                $original = 'uploads/ad_pic/'.$info->getSaveName();
                $original = $info['name'];
                $picarr = array('pic_url'=>$original,'id'=>$pic_id);;
                $value = array('status'=>1, 'path'=>$picarr);
            }else{
                $value = array('status'=>0,'msg'=>$file->getError());
            }
        }else{
            $value = array('status'=>0,'msg'=>'文件不存在');
        }
        return json($value);
    }
    
    // 编辑交易时间段
    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'WineDealAreaContract.add');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    // ys_admin_logs('编辑用户','member',$data['id']);
                    unset($data['id']);
                    $res = Db::name('wine_deal_area_contract')->where('id', input('id'))->update($data);
                    if(!$res){
                        $value = array('status'=>0,'mess'=>'更新失败');
                    }
                    else{
                        $value = array('status'=>1,'mess'=>'更新成功');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $wine_deal_area = Db::name('wine_deal_area_contract')->find(input('id'));
                if($wine_deal_area){
                    $this->assign('wine_deal_area', $wine_deal_area);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function team_count(){
        $input = input();
        $buy_id = $input['id'];
        // exit;
        $list = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->paginate(50);
        
        $count = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%');
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
        ));
        if(request()->isAjax()){
            return $this->fetch('team_count_ajaxpage');
        }else{
            return $this->fetch('team_count_lst');
        }
    }
    
    public function team_record_count(){
        $input = input();
        $buy_id = $input['id'];
        // exit;
        $id_arr = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->column('id');
        // var_dump($id_arr);exit;
        $list = Db::name('wine_order_record_contract')->alias('wor')->where('wor.buy_id', 'in', $id_arr)->where('wor.wine_goods_id', $input['wine_goods_id'])->where('wor.wine_deal_area_id', $input['wine_deal_area_id'])->field('wor.*, m.true_name,m.user_name,m.phone')
                ->join('member m', 'm.id = wor.buy_id', 'left')->where('wor.addtime', '>=', strtotime('today'))->paginate(50);
        
        $count = Db::name('wine_order_record_contract')->where('buy_id', 'in', $id_arr)->where('wine_goods_id', $input['wine_goods_id'])->where('wine_deal_area_id', $input['wine_deal_area_id'])->where('addtime', '>=', strtotime('today'))->count();
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
        ));
        if(request()->isAjax()){
            return $this->fetch('team_record_count_ajaxpage');
        }else{
            return $this->fetch('team_record_count_lst');
        }
    }
    
    public function team_canyu_count(){
        $input = input();
        $buy_id = $input['id'];
        // exit;
        $id_arr = Db::name('member')->where(function($query) use ($buy_id){
            $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%');
        })->where('delete', 0)->column('id');
        // var_dump($id_arr);exit;
        $list = Db::name('wine_yuyue_return_contract')->alias('wyr')->where('wyr.user_id', 'in', $id_arr)->where('wyr.wine_goods_id', $input['wine_goods_id'])->where('wyr.wine_deal_area_id', $input['wine_deal_area_id'])->field('wyr.*, m.true_name,m.user_name,m.phone')
                ->join('member m', 'm.id = wyr.user_id', 'left')->where('wyr.addtime', '>=', strtotime('today'))->paginate(50);
        
        $count = Db::name('wine_yuyue_return_contract')->where('user_id', 'in', $id_arr)->where('wine_goods_id', $input['wine_goods_id'])->where('wine_deal_area_id', $input['wine_deal_area_id'])->where('addtime', '>=', strtotime('today'))->count();
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
        ));
        if(request()->isAjax()){
            return $this->fetch('team_canyu_count_ajaxpage');
        }else{
            return $this->fetch('team_canyu_count_lst');
        }
    }
    
    public function yuyue(){
        $input = input();
        $wine_deal_area_id = $input['id'];
        $keyword = $input['keyword'];
        $time = strtotime('today');
        $end_time = strtotime('tomorrow');
        $day = $input['date'];
        if($day > 0){
            $time -= $day * 86400;
            $end_time -= $day * 86400;
        }

        $list = Db::name('wine_order_record_contract')->alias('wor')
        ->where('wor.wine_deal_area_id', $wine_deal_area_id)->where(function ($query)use($keyword){
            if($keyword){
                $query->where('wor.id', $keyword)->whereOr('m.phone', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.true_name', $keyword);
            }
        })->field('wor.*, m.user_name,m.true_name,m.phone,wyr.id wyr_id,wyr.addtime wyr_addtime')->join('member m', 'm.id=wor.buy_id', 'left')->where('wor.addtime', '>=', $time)->where('wor.addtime', '<', $end_time)->order('wor.id desc')
        ->join('wine_yuyue_return_contract wyr', 'wyr.user_id = wor.buy_id and wyr.wine_goods_id=wor.wine_goods_id and wyr.wine_deal_area_id=wor.wine_deal_area_id and wyr.addtime < '.$end_time, ' and wyr.addtime>='.$time, 'left')
        ->paginate(30, false, ['query'=>request()->param()]);
        // var_dump(Db::name('wine_order_record')->getLastSql());exit;
        $count = Db::name('wine_order_record_contract')->where('wine_deal_area_id', $wine_deal_area_id)->where('addtime', '>=', $time)->where('addtime', '<', $end_time)->count();
        $page = $list->render();
        
        $list = $list->toArray()['data'];
        foreach ($list as $k=>&$v){
            $buy_id = $v['buy_id'];
            $column_id = Db::name('member')->where(function($query) use ($buy_id){
            
                $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%');
            })->where('delete', 0)->column('id');
            // var_dump(Db::name('member')->getLastSql());exit;
            $v['team_count'] = count($column_id);
            
            $v['team_record_count'] = Db::name('wine_order_record_contract')->where('buy_id', 'in', $column_id)->where('addtime', '>=', $time)->where('addtime', '<', $end_time)->where('wine_goods_id', $v['wine_goods_id'])->where('wine_deal_area_id', $v['wine_deal_area_id'])->count();
            $v['team_canyu_count'] = Db::name('wine_yuyue_return_contract')->where('user_id', 'in', $column_id)->where('addtime', '>=', $time)->where('addtime', '<', $end_time)->where('wine_goods_id', $v['wine_goods_id'])->where('wine_deal_area_id', $v['wine_deal_area_id'])->count();
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
            'keyword'=>$keyword,
            'pnum'=>$pnum,
            'count'=>$count,
            'wine_deal_area_id'=>$wine_deal_area_id
        ));
        if(request()->isAjax()){
            return $this->fetch('yuyue_ajaxpage');
        }else{
            return $this->fetch('yuyue_lst');
        }
    }
    
    public function export(){
        $input = input();
        $wine_deal_area_id = $input['id'];
        $keyword = $input['keyword'];
        // var_dump(input());
        $time = strtotime('today');
        // var_dump($wine_deal_area_id);exit;
        $list = Db::name('wine_order_record_contract')->alias('wor')
        ->where('wor.wine_deal_area_id', $wine_deal_area_id)->where(function ($query)use($keyword){
            if($keyword){
                $query->where('wor.id', $keyword)->whereOr('m.phone', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.true_name', $keyword);
            }
        })->field('wor.*, m.user_name,m.true_name,m.phone,wyr.id wyr_id,wyr.addtime wyr_addtime')
        ->join('member m', 'm.id=wor.buy_id', 'left')
        ->where('wor.addtime', '>=', $time)->order('wor.id desc')
        ->join('wine_yuyue_return_contract wyr', 'wyr.user_id = wor.buy_id and wyr.wine_goods_id=wor.wine_goods_id and wyr.wine_deal_area_id=wor.wine_deal_area_id and wyr.addtime>='.$time, 'left')
        // ->where('wyr.addtime', '>=', $time)
        // ->paginate(30, false, ['query'=>request()->param()])->toArray()['data'];
        ->select();
 
        // var_dump($list);exit;
        foreach ($list as $k=>&$v){
            $buy_id = $v['buy_id'];
            $column_id = Db::name('member')->where(function($query) use ($buy_id){
            
                $query->where('team_id', 'like', '%,'.$buy_id)->whereOr('team_id', 'like', '%,'.$buy_id.',%');
            })->where('delete', 0)->column('id');
            // var_dump(Db::name('member')->getLastSql());exit;
            $v['team_count'] = count($column_id);
            
            $v['team_record_count'] = Db::name('wine_order_record_contract')->where('buy_id', 'in', $column_id)->where('addtime', '>=', $time)->where('wine_goods_id', $v['wine_goods_id'])->where('wine_deal_area_id', $v['wine_deal_area_id'])->count();
            $v['team_canyu_count'] = Db::name('wine_order_record_contract')->where('buy_id', 'in', $column_id)->where('addtime', '>=', $time)->where('wine_goods_id', $v['wine_goods_id'])->where('wine_deal_area_id', $v['wine_deal_area_id'])->count();
        }

        $arr = [];
        array_push($arr, ['A'=>'会员编号', 'B'=>'会员名称', 'C'=>'会员手机号', 'D'=>'预约金额', 'E'=>'预约日期', 'F'=>'团队/预约/参与', 'G'=>'是否参与', 'H'=>'参与时间', 'I'=>'是否返还', 'J'=>'返还时间']);
        
        for($i=0; $i<count($list); $i++){
            array_push($arr, ['A'=>$list[$i]['id'], 'B'=>$list[$i]['true_name']??$list[$i]['user_name'], 'C'=>$list[$i]['phone'], 'D'=>$list[$i]['frozen_fuel'], 'E'=>$list[$i]['addtime'] ? date('Y-m-d H:i:s', $list[$i]['addtime']) : '/', 'F'=>$list[$i]['team_count'].'/'.$list[$i]['team_record_count'].'/'.$list[$i]['team_canyu_count'], 'G'=>$list[$i]['wyr_id']>0?'是':'否', 'H'=>$list[$i]['wyr_addtime'] ? date('Y-m-d H:i:s', $list[$i]['wyr_addtime']) : '/', 'I'=>$list[$i]['status']==1?'是':'否', 'J'=>$list[$i]['updatetime'] ? date('Y-m-d H:i:s', $list[$i]['updatetime']) : '/']);
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
              ->setCellValue('I'.$num, $val['I'])
              ->setCellValue('J'.$num, $val['J']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '合约竞拍预约记录'.date('Y-m-d').'.xlsx';
    
        header('Content-Type: application/vnd.ms-excel');
    
        header('Content-Disposition: attachment; filename='.$name);
    
        header('Cache-Control: max-age=0');
    
        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
    
        $ExcelWriter->save('php://output');
    
        exit;
    }
    
    // 删除交易时间段
    public function delete(){$value = array('status'=>0,'mess'=>'删除失败');return json($value);
        $wine_deal_area = Db::name('wine_deal_area')->find(input('id'));
        if(!$wine_deal_area){
            $value = array('status'=>0,'mess'=>'找不到相关信息');
        }
        else{
            $res = Db::name('wine_deal_area')->where('id', input('id'))->delete();
            if(!$res){
                $value = array('status'=>0,'mess'=>'删除失败');
            }
            else{
                $value = array('status'=>1,'mess'=>'删除成功');
            }
        }
        return json($value);
    }
}
?>