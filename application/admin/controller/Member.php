<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Member as MemberMx;
use think\Exception;
// require_once dirname(dirname(dirname(__FILE__))).'/common/util/ExcelUtil.php';

class Member extends Common{
    // 会员树
    public function member_tree(){
        $input = input();

        $list = Db::name('member')->alias('m')
                ->field('m.one_level, m.id, m.team_id, m.user_name, m.phone, m.reg_enable')
                ->where('m.id', $input['id'])->whereOr('m.jiedian_team_id', 'like', '%,'.$input['id'])->whereOr('m.jiedian_team_id', 'like', '%,'.$input['id'].',%')->select();
                
        // $effective_team_num = Db::name('member')->alias('m')
        //         ->where('m.id', 'neq', $input['id'])->where('reg_enable', 1)->where(function($query){
        //             $query->where('m.team_id', 'like', '%,'.$input['id'])->whereOr('m.team_id', 'like', '%,'.$input['id'].',%');
        //         )->count();
        // $effective_direct_num = Db::name('member')->where('reg_enable', 1)->where('one_level', $input['id'])->count();
        $this->assign('list', statistics_count(recursiveMember($list, $input['id'], 0)));
        
        $one_level = Db::name('member')->where('id', $input['id'])->value('one_level');
        $up_info = Db::name('member')->where('id', $one_level)->find();
        $this->assign('uplevel', $up_info);
        // $this->assign('effective_team_num', $effective_team_num);
        $this->assign('id', $input['id']);
        return $this->fetch('member_tree');
    }
    
    public function set_level(){
        $id = input('id');
        if(request()->isAjax()){
            $level = input('level');
            if(!in_array($level, [0,1,2,3])){
                $value = array('status'=>0,'mess'=>'请先设置等级');
                return json($value);
            }
                
            if(md5(input('pass')) != 'f1f5f885a799245161c8f30811e3852d'){
                $value = array('status'=>0,'mess'=>'密码错误');
                return json($value);
            }
            
            Db::name('member')->where('id', $id)->update([
                'set_level' => $level,
                'agent_type' => $level,
            ]);
            $value = array('status'=>1,'mess'=>'更新成功');
            return json($value);
        }
        else{
            $user = Db::name('member')->where('id', $id)->find();
            $this->assign('user', $user);
            return $this->fetch();
        }
    }
    
    public function setvip(){
        $id = input('id');
        $val = input('val');
        if($val == 0){
            $res = Db::name('member')->where('id', $id)->update([
                'vip_time' => time()-60
            ]);
            if($res){
                $value = array('status'=>0,'mess'=>'更新成功');
            }
            else{
                $value = array('status'=>0,'mess'=>'更新失败');
            }
            return json($value);
        }
        // $wine_apply_vip = Db::name('wine_apply_vip')->where('id', $id)->where('status', 0)->find();
        
        // if(!is_null($wine_apply_vip)){
        if(true){
        
            $info = Db::name('member')->where('id', $id)->find();
            // var_dump($info);exit;
            if(is_null($info)){
                $value = array('status'=>0,'mess'=>'用户信息不存在');
            }else{
                // $vip_enjoy_day = Db::name('config')->where('ename', 'vip_enjoy_day')->value('value');
                $vip_enjoy_day = 100000;
                
                $time = strtotime('+'.$vip_enjoy_day.' day');
                Db::startTrans();
                try{
                    if(!$info['vip_time']){
                        $res = Db::name('member')->where('id', $id)->update([
                            'vip_time' => $time
                        ]);
                        if(!$res){
                            throw new \Exception('失败');
                        }
                    }else{
                        $addtime = $vip_enjoy_day*24*60*60;
                        
                        if($info['vip_time'] > time()){
                            $res = Db::name('member')->where('id', $id)->setInc('vip_time', $addtime);
                            if(!$res){
                                throw new \Exception('失败');
                            }
                        }else{
                            $res = Db::name('member')->where('id', $id)->update([
                                'vip_time'=>$time
                            ]);
                            
                            if(!$res){
                                throw new \Exception('失败');
                            }
                        }
                    }
                    
                    // $res = Db::name('wine_apply_vip')->where('id', $id)->update(['status'=>1]);
                    // if(!$res){
                    //     throw new Exception('失败');
                    // }
                    
                    $value = array('status'=>1,'mess'=>'成功');
                    Db::commit();
                }
                catch(Exception $e){
                    $value = array('status'=>0,'mess'=>'失败');
                    Db::rollback();
                }
            }
        }
        else{
            $value = array('status'=>0,'mess'=>'信息不存在');
        }
    }
    
    //会员列表
    public function lst(){

        $filter = input('filter');$post = input();$where=[];
        
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
//        if($filter == 11)
//        {
//            $wheres['a.is_vip'] = 1;
//        }
//        else if($filter == 12)
//        {
//            $wheres['a.is_vip'] = 0;
//        }
        $list = Db::name('member')->alias('a')->where('a.delete', 0)
//                    ->where($wheres)
                    ->field('a.agent_type,a.reg_enable,a.false_agent_type,a.qiandan,b.manager_reward,b.brand,b.credit_value,b.buy_ticket,b.total_stock,a.id,a.user_name,a.true_name,a.headimgurl,a.phone,a.integral,a.oauth,a.regtime,a.checked,b.price,c.price as profit_price,a.add_way, m.phone m_phone, m.user_name m_user_name,m.true_name m_true_name,j.true_name j_true_name,j.user_name j_user_name,j.phone j_phone,b.fuel,b.commission,b.earnings,a.vip_time,a.zenren_frozen,b.zkj,b.point,a.generate_phone,a.generate_price,a.qiandantequan,a.sale_earnings,b.point_ticket,b.point_credit,b.ticket_burn,a.nick_name')
                    ->where('a.regtime', 'between time', $whereTime)
                    ->where($where)
                    ->join('member m', 'a.one_level = m.id', 'LEFT')
                    ->join('member j', 'a.jiedianid = j.id', 'LEFT')
                    ->join('sp_wallet b','a.id = b.user_id','LEFT')->join('sp_profit c','a.id = c.user_id','LEFT')->order('a.id desc')->paginate(50, false, ['query'=>request()->param()]);
                    // var_dump($list);exit;
        $count = Db::name('member')->where('delete', 0)->count();
        $reg_enable_count = Db::name('member')->where('delete', 0)->where('reg_enable', 1)->count();
        $page = $list->render();
        
        $moren = ['0'=>'经销商'];
        $level = Db::name('wine_level')->column('id, level_name');
        $level = array_merge($moren, $level);
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        // var_dump($post['level_id']);exit;
        $this->assign(array(
            'list'=>$list,
            'level'=>$level,
            'level_id'=>$post['level_id'] ?? '-1',
            'page'=>$page,
            'reg_enable'=>$reg_enable,
            'pnum'=>$pnum,
            'reg_enable_count'=>$reg_enable_count,
            'count'=>$count,
             'qiandantequan'=> -1,
            'where_time'=>$whereTime,
            'filter'=>input('filter'),
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    //搜索
    public function search(){
        $post = input();
        if(input('post.keyword') != ''){
            cookie('yh_telephone',input('keyword'),7200);
        }else{
            cookie('yh_telephone',null);
        }
        $keyword = input('keyword');
        $where = array();
        if($keyword){
            if(strlen($keyword) > 10){
                $where['a.phone'] = $keyword;
            }else{
                $where['a.id'] = $keyword;
            }
            // $where['a.phone'] = cookie('yh_telephone');
            // $where['a.id'] = cookie('yh_telephone');
//            $whereor['a.user_name'] = ['like', cookie('yh_telephone')];
            if(!is_numeric($keyword)){
                $whereor['a.user_name'] = ['LIKE', '%' . trim(cookie('yh_telephone')) . '%'];
                $whereor['a.true_name'] = ['LIKE', '%' . trim(cookie('yh_telephone')) . '%'];
            }
            
        }
        
        
        $reg_enable = -1;
        if(isset($post['reg_enable']) && in_array($post['reg_enable'], [0, 1])){
            $where['a.reg_enable'] = $post['reg_enable'];
            $reg_enable = $post['reg_enable'];
        }
        
        $qiandantequan = -1;
        if(isset($post['qiandantequan']) && in_array($post['qiandantequan'], [0, 1])){
            $where['a.qiandantequan'] = $post['qiandantequan'];
            $qiandantequan = $post['qiandantequan'];
        }
        
        $filter = input('filter');$post = input();
        
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
        $level_id = input('level_id');
        $whereand = [];
        $whereand1 = [];
        if(trim($level_id) != -1){
            // $whereand = [];
            $whereand = ['a.false_agent_type'=>['<=',$level_id],'a.agent_type'=>$level_id];
            $whereand1 = ['a.agent_type'=>['<=',$level_id],'a.false_agent_type'=>$level_id];
            
            
        }
        
//        if($filter == 11)
//        {
//            $wheres['a.is_vip'] = 1;
//        }
//        else if($filter == 12)
//        {
//            $wheres['a.is_vip'] = 0;
//        }

        $list = Db::name('member')->alias('a')->where('a.delete', 0)
        ->where(function ($q) use($whereand,$whereand1,$level_id) {
          if($level_id != -1){
                $q->where(function ($q1) use($whereand,$whereand1) {
                   $q1->where($whereand);
                })->whereOr(function ($q2) use($whereand,$whereand1) {
                   $q2->where($whereand1);
                });
            }
            }
        )
        ->field('a.vip_time,a.reg_enable,a.id,a.agent_type,a.false_agent_type,a.qiandan,b.manager_reward,b.brand,b.credit_value,b.buy_ticket,b.total_stock,a.user_name,a.true_name,a.headimgurl,a.phone,a.integral,a.oauth,a.regtime,a.checked,b.price,c.price as profit_price,m.user_name m_user_name,m.phone m_phone,m.true_name m_true_name,j.true_name j_true_name,j.user_name j_user_name,j.phone j_phone,b.fuel,b.commission,b.earnings,b.zkj,b.point,a.zenren_frozen,a.qiandantequan,a.sale_earnings')->join('sp_wallet b','a.id = b.user_id','LEFT')->join('sp_profit c','a.id = c.user_id','LEFT')
//            ->where(function ($query){
//                $query->where('is_vip', 1);
//            })
//            ->where($wheres)
//            ->where($where)
            ->where(function ($query) use($whereor, $where){
                $query->where($where)->whereOr($whereor);
            })
            ->join('member m', 'a.one_level = m.id', 'LEFT')
            ->join('member j', 'a.jiedianid = j.id', 'LEFT')
            ->where('a.regtime', 'between time', $whereTime)
//            ->whereOr($whereor)
            ->order('a.regtime desc')->paginate(50, false, ['query'=>request()->param()])->each(function($v){
                if($v['false_agent_type'] > $v['agent_type']){
                    $v['guize_agent_type'] = $v['false_agent_type'];
                }
                else{
                    $v['guize_agent_type'] = $v['agent_type'];
                }
                
                return $v;
            });
        $count = Db::name('member')->alias('a')->where('a.delete', 0)->where(function ($q) use($whereand,$whereand1,$level_id) {
          if($level_id != -1){
                $q->where(function ($q1) use($whereand,$whereand1) {
                   $q1->where($whereand);
                })->whereOr(function ($q2) use($whereand,$whereand1) {
                   $q2->where($whereand1);
                });
            }
            }
        )->field('a.id,a.agent_type,b.manager_reward,b.brand,b.credit_value,b.buy_ticket,b.total_stock,a.user_name,a.headimgurl,a.phone,a.integral,a.oauth,a.regtime,a.checked,b.price,c.price as profit_price')->join('sp_wallet b','a.id = b.user_id','LEFT')->join('sp_profit c','a.id = c.user_id','LEFT')
//            ->where(function ($query){
//                $query->where('is_vip', 1);
//            })
//            ->where($wheres)
//            ->where($where)
            ->where(function ($query) use($whereor, $where){
                $query->where($where)->whereOr($whereor);
            })
            ->where('a.regtime', 'between time', $whereTime)->count();

        $reg_enable_count = Db::name('member')->where('a.delete', 0)->alias('a')->where('a.reg_enable', 1)->where(function ($q) use($whereand,$whereand1,$level_id) {
          if($level_id != -1){
                $q->where(function ($q1) use($whereand,$whereand1) {
                   $q1->where($whereand);
                })->whereOr(function ($q2) use($whereand,$whereand1) {
                   $q2->where($whereand1);
                });
            }
            }
        )->field('a.id,a.agent_type,b.manager_reward,b.brand,b.credit_value,b.buy_ticket,b.total_stock,a.user_name,a.headimgurl,a.phone,a.integral,a.oauth,a.regtime,a.checked,b.price,c.price as profit_price')->join('sp_wallet b','a.id = b.user_id','LEFT')->join('sp_profit c','a.id = c.user_id','LEFT')
            ->where(function ($query) use($whereor, $where){
                $query->where($where)->whereOr($whereor);
            })
            ->where('a.regtime', 'between time', $whereTime)->count();
        
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('yh_telephone')){
            $this->assign('keyword',cookie('yh_telephone'));
        }
        
        
        $moren = ['0'=>'经销商'];
        $level = Db::name('wine_level')->column('id, level_name');
        $level = array_merge($moren, $level);
        
        $this->assign('search',$search);
        $this->assign('level', $level);
        $this->assign('level_id', $level_id);
        $this->assign('where_time', $whereTime);
        $this->assign('filter',$filter);
        $this->assign('pnum', $pnum);
        $this->assign('reg_enable', $reg_enable);
        $this->assign('qiandantequan', $qiandantequan);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('count',$count);
        $this->assign('reg_enable_count',$reg_enable_count);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    // 导出
    public function export(){
        $post = input();
        if(input('post.keyword') != ''){
            cookie('yh_telephone',input('post.keyword'),7200);
        }else{
            cookie('yh_telephone',null);
        }
        $where = array();$where1 = array();
        if(cookie('yh_telephone')){
            $where['a.phone'] = cookie('yh_telephone');
            $whereor['a.user_name'] = ['LIKE', '%' . trim(cookie('yh_telephone')) . '%'];
        }
        
        $reg_enable = -1;
        if(isset($post['reg_enable']) && in_array($post['reg_enable'], [0, 1])){
            $where['a.reg_enable'] = $post['reg_enable'];
            $reg_enable = $post['reg_enable'];
        }
        
        $qiandantequan = -1;
        if(isset($post['qiandantequan']) && in_array($post['qiandantequan'], [0, 1])){
            $where['a.qiandantequan'] = $post['qiandantequan'];
            $qiandantequan = $post['qiandantequan'];
        }
        
        
        $filter = input('filter');$post = input();
        
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
        $level_id = input('level_id');
        $whereand = [];
        $whereand1 = [];
        if(trim($level_id) != -1){
            // $whereand = [];
            $whereand = ['a.false_agent_type'=>['<=',$level_id],'a.agent_type'=>$level_id];
            $whereand1 = ['a.agent_type'=>['<=',$level_id],'a.false_agent_type'=>$level_id];
            
        }
        if(isset($post['enable']) && in_array($post['enable'], [0, 1])){
            $where1['a.reg_enable'] = $post['enable'];
        }
        if(isset($post['qiandantequan']) && in_array($post['qiandantequan'], [0, 1])){
            $where1['a.qiandantequan'] = $post['qiandantequan'];
        }

        $list = Db::name('member')->alias('a')->where('a.delete', 0)->where(function ($q) use($whereand,$whereand1,$level_id) {
          if($level_id != -1){
                $q->where(function ($q1) use($whereand,$whereand1) {
                   $q1->where($whereand);
                })->whereOr(function ($q2) use($whereand,$whereand1) {
                   $q2->where($whereand1);
                });
            }
            }
        )->field('a.vip_time,a.reg_enable,a.id,a.agent_type,a.false_agent_type,b.manager_reward,b.brand,b.credit_value,b.buy_ticket,b.total_stock,a.user_name,a.true_name,a.headimgurl,a.phone,a.integral,a.oauth,a.regtime,a.checked,b.price,c.price as profit_price,m.user_name m_user_name,m.phone m_phone,m.true_name m_true_name,j.true_name j_true_name,j.user_name j_user_name,j.phone j_phone,b.fuel,b.commission,b.earnings,a.qiandantequan,a.sale_earnings')->join('sp_wallet b','a.id = b.user_id','LEFT')->join('sp_profit c','a.id = c.user_id','LEFT')
            ->where(function ($query) use($whereor, $where){
                $query->where($where)->whereOr($whereor);
            })
            ->join('member m', 'a.one_level = m.id', 'LEFT')
            ->join('member j', 'a.jiedianid = j.id', 'LEFT')
            ->where('a.regtime', 'between time', $whereTime)
            ->where($where1)
            ->order('a.id desc')->select();
        
        $moren = ['0'=>'经销商'];
        $level = Db::name('wine_level')->column('id, level_name');
        $level = array_merge($moren, $level);
        

     
        $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'用户名', 'C'=>'手机号', 'D'=>'直推人姓名', 'E'=>'直推人手机号', 'F'=>'接点人姓名', 'G'=>'接点人手机号', 'H'=>'调整后级别',
                    'I'=>'余额', 'J'=>'佣金', 'K'=>'收益', 'L'=>'注册时间', 'M'=>'状态', 'N'=>'激活', 'O'=>'是否派单']);
        foreach($list as $k=>$v){
            $v['agent_type'] = max($v['agent_type'],$v['false_agent_type']);
            array_push($arr, ['A'=>$v['id'], 'B'=>$v['true_name']??$v['user_name'], 'C'=>$v['phone'], 'D'=>$v['m_true_name']??$v['m_user_name'], 'E'=>$v['m_phone'], 'F'=>$v['j_true_name']??$v['j_user_name'], 'G'=>$v['j_phone'], 'H'=>$level[$v['agent_type']], 'I'=>$v['price'], 'J'=>$v['commission'], 'K'=>$v['earnings'], 'L'=>date('Y-m-d H:i:s', $v['regtime']), 'M'=>($v['checked']==0?'禁用':'启用'), 'N'=>($v['reg_enable']==0?'未激活':'已激活'), 'O'=>($v['qiandantequan']==0?'否':'是')]);
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
              ->setCellValue('J'.$num, $val['J'])
              ->setCellValue('K'.$num, $val['K'])
              ->setCellValue('L'.$num, $val['L'])
              ->setCellValue('M'.$num, $val['M'])
              ->setCellValue('N'.$num, $val['N'])
              ->setCellValue('O'.$num, $val['O']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '会员列表'.date('Y-m-d').'.xlsx';
    
        header('Content-Type: application/vnd.ms-excel');
    
        header('Content-Disposition: attachment; filename='.$name);
    
        header('Cache-Control: max-age=0');
    
        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
    
        $ExcelWriter->save('php://output');
    
        exit;
    }
        
    // 导出会员树
    public function export_member_tree(){
        $id = input('get.keyword');

        $list = Db::name('member')->alias('a')->where('a.id', $id)->whereOr('a.jiedian_team_id', 'like', '%,'.$id)->whereOr('a.jiedian_team_id', 'like', '%,'.$id.',%')->where('a.delete', 0)->field('a.vip_time,a.reg_enable,a.id,a.agent_type,a.false_agent_type,b.manager_reward,b.brand,b.credit_value,b.buy_ticket,b.total_stock,a.user_name,a.true_name,a.headimgurl,a.phone,a.integral,a.oauth,a.regtime,a.checked,b.price,c.price as profit_price,m.user_name m_user_name,m.phone m_phone,m.true_name m_true_name,j.true_name j_true_name,j.user_name j_user_name,j.phone j_phone,b.fuel,b.commission,b.earnings')->join('sp_wallet b','a.id = b.user_id','LEFT')->join('sp_profit c','a.id = c.user_id','LEFT')
            
            ->join('member m', 'a.one_level = m.id', 'LEFT')
            ->join('member j', 'a.jiedianid = j.id', 'LEFT')
            ->order('a.id desc')->select();
        $moren = ['0'=>'经销商'];
        $level = Db::name('wine_level')->column('id, level_name');
        $level = array_merge($moren, $level);
        

     
        $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'用户名', 'C'=>'手机号', 'D'=>'直推人姓名', 'E'=>'直推人手机号', 'F'=>'接点人姓名', 'G'=>'接点人手机号', 'H'=>'调整后级别',
                    'I'=>'余额', 'J'=>'佣金', 'K'=>'收益', 'L'=>'注册时间', 'M'=>'状态', 'N'=>'激活']);
        foreach($list as $k=>$v){
            $v['agent_type'] = max($v['agent_type'],$v['false_agent_type']);
            array_push($arr, ['A'=>$v['id'], 'B'=>$v['true_name']??$v['user_name'], 'C'=>$v['phone'], 'D'=>$v['m_true_name']??$v['m_user_name'], 'E'=>$v['m_phone'], 'F'=>$v['j_true_name']??$v['j_user_name'], 'G'=>$v['j_phone'], 'H'=>$level[$v['agent_type']], 'I'=>$v['price'], 'J'=>$v['commission'], 'K'=>$v['earnings'], 'L'=>date('Y-m-d H:i:s', $v['regtime']), 'M'=>($v['checked']==0?'禁用':'启用'), 'N'=>($v['reg_enable']==0?'未激活':'已激活')]);
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
              ->setCellValue('J'.$num, $val['J'])
              ->setCellValue('K'.$num, $val['K'])
              ->setCellValue('L'.$num, $val['L'])
              ->setCellValue('M'.$num, $val['M'])
              ->setCellValue('N'.$num, $val['N'])
              ->setCellValue('O'.$num, $val['O']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '会员列表'.date('Y-m-d').'.xlsx';
    
        header('Content-Type: application/vnd.ms-excel');
    
        header('Content-Disposition: attachment; filename='.$name);
    
        header('Cache-Control: max-age=0');
    
        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
    
        $ExcelWriter->save('php://output');
    
        exit;
    }
    
    public function del(){
        $input = input();
        
        // $res = Db::name('member')->where('id', $input['id'])->update([
        //     'delete' => 1
        // ]);
        $count = Db::name('member')->where('one_level', $input['id'])->whereOr('jiedianid', $input['id'])->count();
        if($count > 0){
            return 0;
        }
        else{
            $res = Db::name('member')->where('id', $input['id'])->delete();
        }
        
        ys_admin_logs('删除会员','member',$input['id']);
        
        if($res)return 1;
        return 0;
    }
    
    public function trade_detail(){
        $input = input();
        $list = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->join('member t', 't.id = d.target_id', 'left')
                    ->where('d.user_id', $input['id'])
                    ->where(function($query){
                        $query->where('sr_type', 'in', [27,103,26,8,120, 24,102,105,101,1000,1001,109,25,110,604,605,606,607,600,601,602,603,200,201,205])->whereOr('zc_type', 'in', [27,108,26,5, 24, 2,100,25,22,33]);
                    })
                    ->field('d.*, m.user_name m_user_name, m.true_name m_true_name, t.user_name t_user_name, t.true_name t_true_name, m.phone m_phone, t.phone t_phone')
                    ->order('d.id desc')->paginate(50)->each(function($item){
                        switch($item['sr_type']){
                            case 27:
                                 $item['remark'] = '门票后台操作';
                                 break;
                            case 103:
                                 $item['remark'] = '退款';
                                 break;
                            case 26:
                                 $item['remark'] = '积分信用后台操作';
                                 break;
                            case 24:
                                $item['remark'] = '后台余额修改';
                                break;
                            case 120:
                                $item['remark'] = '佣金转入';
                                break;
                            case 8:
                                $item['remark'] = '余额转账：'.$item['t_true_name'].'转给姓名:'.($item['m_true_name']?$item['m_true_name']:$item['m_user_name']).' - 手机号:'.$item['phone'];
                                break;
                            case 102:
                                $item['remark'] = '退款';
                                break;
                            case 105:
                                $item['remark'] = '退款';
                                break;
                            case 205:
                                $item['remark'] = 'v1-v3分润';
                                break;
                            case 201:
                                $item['remark'] = '间推';
                                break;
                            case 200:
                                $item['remark'] = '直推';
                                break;
                            case 603:
                                $item['remark'] = '前三十 三等奖';
                                break;
                            case 101:
                                $item['remark'] = '爆仓退款100%';
                                break;
                            case 1000:
                                $item['remark'] = '复购抢购';
                                break;
                            case 1001:
                                $item['remark'] = '复购预约';
                                break;
                            case 109:
                                $item['remark'] = '加权';
                                break;
                            case 25:
                                $item['remark'] = '后台操作';
                                break;
                            case 110:
                                $item['remark'] = '购买商品赠送积分券';
                                break;
                            case 604:
                                $item['remark'] = '后三十 特等奖';
                                break;
                            case 605:
                                $item['remark'] = '后三十 一等奖';
                                break;
                            case 606:
                                $item['remark'] = '后三十 二等奖';
                                break;
                            case 607:
                                $item['remark'] = '后三十 三等奖';
                                break;
                            case 600:
                                $item['remark'] = '前三十 特等奖';
                                break;
                            case 601:
                                $item['remark'] = '前三十 一等奖';
                                break;
                            case 602:
                                $item['remark'] = '前三十 二等奖';
                                break;
                        }
                        
                        switch ($item['zc_type']) {
                            case 27:
                                $item['remark'] = '门票后台操作';
                                break;
                            case 108:
                                $item['remark'] = '门票预售或购买';
                                break;
                            case 26:
                                $item['remark'] = '积分信用后台操作';
                                break;
                            case 5:
                                $item['remark'] = '余额转账：'.$item['m_user_name'].'转给姓名:'.($item['t_true_name']?$item['t_true_name']:$item['t_user_name']).' - 手机号:'.$item['t_phone'];
                                break;
                            case 24:
                                $item['remark'] = '后台余额修改';
                                break;
                            case 100:
                                $item['remark'] = '预售或购买';
                                break;
                            case 25:
                                $item['remark'] = '后台操作';
                                break;
                        }
                        
                        return $item;
                    });
        $count = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->where('d.user_id', $input['id'])->count();
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
            return $this->fetch('trade_detail_ajaxpage');
        }else{
            return $this->fetch('trade_detail_lst');
        }
    }
    
    public function fronzen_jie(){
        $input = input();
        
        $info = Db::name('member')->where('id', $input['id'])->find();
        if($info && $info['checked']==0){
            $res = Db::name('member')->where('id', $input['id'])->update([
                'checked' => 1
            ]);
            if($res){
                return 1;
            }
        }
        
        return 0;
    }

    //分销商会员列表
    public function distributor_lst(){
        $list = Db::name('vip_rights_card')->alias('vrc')->group('vrc.use_user_id')->where('vrc.use', 1)->join('member m', 'vrc.use_user_id = m.id', 'inner')->field('vrc.*, m.headimgurl, m.user_name')->order('vrc.id desc')->paginate(50);
        $count = Db::name('vip_rights_card')->alias('vrc')->group('vrc.use_user_id')->join('member m', 'vrc.use_user_id = m.id', 'inner')->count();
//        $list = Db::name('member')->alias('a')->field('a.id,a.user_name,a.headimgurl,a.phone,a.integral,a.oauth,a.regtime,a.checked,b.price,c.price as profit_price,is_vip,shop_id')->join('sp_wallet b','a.id = b.user_id','LEFT')->join('sp_profit c','a.id = c.user_id','LEFT')->order('a.id desc')->paginate(50);
//        $count = Db::name('member')->count();
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
            return $this->fetch('distributor_ajaxpage');
        }else{
            return $this->fetch('distributor_lst');
        }
    }
    
    // 更改接点人
    public function changejiedian(){
        if(request()->isAjax()){
            $data = input();
            if($data['jiedianphone']){
                $in = Db::name('member')->where('phone', $data['jiedianphone'])->find();
                if(is_null($in)){
                    $value = array('status'=>0,'mess'=>'接点人手机号码不存在','data'=>array('status'=>400));
                    return json($value);
                }
                $d['jiedianid'] = $in['id'];
                $d['jiedianphone'] = $data['jiedianphone'];
                $d['jiedian_team_id'] = $in['team_id'].','.$in['id'];
                
                $old = Db::name('member')->where('id', $data['id'])->find();
                if($old){
                    $memberIdArr = Db::name('member')->where('jiedian_team_id', 'like', '%,'.$data['id'])->whereOr('jiedian_team_id', 'like', '%,'.$data['id'].',%')->column('id');
                    $memberIdStr = trim(implode(',', $memberIdArr), ',');
                    // echo "update sp_member set jiedian_team_id = replace(jiedian_team_id, '".$old['jiedian_team_id'].",".$data['id']."', '".$d['jiedian_team_id'].",".$data['id']."') where id in ('".$memberIdStr."')";exit;
                    if(count($memberIdArr)){
                        Db::query("update sp_member set jiedian_team_id = replace(jiedian_team_id, '".$old['jiedian_team_id'].",".$data['id']."', '".$d['jiedian_team_id'].",".$data['id']."') where id in (".$memberIdStr.")");
                    }
                    
                    $res = Db::name('member')->where('id', $data['id'])->data($d)->update();
                    if($res){
                        ys_admin_logs('更改接点人','member',$data['id']);
                        $value = array('status'=>1,'mess'=>'修改成功','data'=>array('status'=>200));
                        return json($value);
                    }
                    else{
                        $value = array('status'=>0,'mess'=>'修改失败','data'=>array('status'=>400));
                        return json($value);
                    }
                }
                else{
                        $value = array('status'=>0,'mess'=>'修改失败','data'=>array('status'=>400));
                        return json($value);
                }
            }
            else{
                $value = array('status'=>0,'mess'=>'请填写完整参数','data'=>array('status'=>400));
                return json($value);
            }
        }
        else{
            $input = input();
            
            $info = Db::name('member')->where('id', $input['id'])->find();
            
            $this->assign('info', $info);
            return $this->fetch();
        }
    }

    // 拨币
    public function bobi(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                if(!in_array($data['type'], [24, 25, 26,27])){
                    $value = array('status'=>0,'mess'=>'请先选择拨币类型');
                    return json($value);
                }
                
                if(md5($data['pass']) != 'f1f5f885a799245161c8f30811e3852d'){
                    $value = array('status'=>0,'mess'=>'密码错误');
                    return json($value);
                }
                
                if(false){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $user = Db::name('member')->where('id',$data['id'])->find();
                    if($user){
                        // $user = new MemberMx();
                        $wallet_info = Db::name('wallet')->where('user_id', $data['id'])->find();
                        Db::startTrans();

                        $d = [
                            'de_type'=>1,
                            'sr_type'=>$data['type'],
                            'price' => abs($data['price']),
                            'user_id'=>$data['id'],
                            'wat_id' => Db::name('wallet')->where('user_id', $data['id'])->value('id'),
                            'time' => time(),
                            'remark' => '后台修改'
                        ];
                        try{
                            if($data['type'] == 24){
                                if($wallet_info['price'] + $data['price'] < 0){
                                    $value = array('status'=>0,'mess'=>'编辑后金额不能小于零');
                                    return json($value);
                                }
                                Db::name('wallet')->where('user_id', $data['id'])->setInc('price', $data['price']);
                                $d['before_price'] = $wallet_info['price'];
                                $d['after_price'] = $wallet_info['price']+$data['price'];
                                if($data['price']<0){
                                    $d['de_type'] = 2;
                                    $d['sr_type'] = 0;
                                    $d['zc_type'] = $data['type'];
                                }
                            }
                            elseif($data['type'] == 25){
                                if($wallet_info['point_ticket'] + $data['price'] < 0){
                                    $value = array('status'=>0,'mess'=>'编辑后金额不能小于零');
                                    return json($value);
                                }
                                Db::name('wallet')->where('user_id', $data['id'])->setInc('point_ticket', $data['price']);
                                $d['before_price'] = $wallet_info['point_ticket'];
                                $d['after_price'] = $wallet_info['point_ticket']+$data['price'];
                                if($data['price']<0){
                                    $d['de_type'] = 2;
                                    $d['sr_type'] = 0;
                                    $d['zc_type'] = $data['type'];
                                }
                            }
                            elseif($data['type'] == 26){
                                if($wallet_info['point_credit'] + $data['price'] < 0){
                                    $value = array('status'=>0,'mess'=>'编辑后金额不能小于零');
                                    return json($value);
                                }
                                Db::name('wallet')->where('user_id', $data['id'])->setInc('point_credit', $data['price']);
                                $d['before_price'] = $wallet_info['point_credit'];
                                $d['after_price'] = $wallet_info['point_credit']+$data['price'];
                                if($data['price']<0){
                                    $d['de_type'] = 2;
                                    $d['sr_type'] = 0;
                                    $d['zc_type'] = $data['type'];
                                }
                            }
                            elseif($data['type'] == 27){
                                if($wallet_info['ticket_burn'] + $data['price'] < 0){
                                    $value = array('status'=>0,'mess'=>'编辑后金额不能小于零');
                                    return json($value);
                                }
                                Db::name('wallet')->where('user_id', $data['id'])->setInc('ticket_burn', $data['price']);
                                $d['before_price'] = $wallet_info['ticket_burn'];
                                $d['after_price'] = $wallet_info['ticket_burn']+$data['price'];
                                if($data['price']<0){
                                    $d['de_type'] = 2;
                                    $d['sr_type'] = 0;
                                    $d['zc_type'] = $data['type'];
                                }
                            }
                            
                            // Db::name('detail')->insert($d);
                            $commo = new \app\apicloud\controller\Common();
                            $commo->addDetail($d);
                            
                            $value = array('status'=>1,'mess'=>'编辑成功');
                            ys_admin_logs('成功','member',$data['id']);
                            Db::commit();
                        }
                        catch(Exception $e){
                            Db::rollback();
                            $value = array('status'=>0,'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $user = Db::name('member')->find(input('id'));
                $wallet = Db::name('wallet')->where('user_id', input('id'))->find();
                if($user){
                    $this->assign('user', $user);
                    $this->assign('wallet', $wallet);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    // 修改收款方式
    public function changecollect(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                if(!in_array($data['type'], [58, 55])){
                    $value = array('status'=>0,'mess'=>'请先选择拨币类型');
                    return json($value);
                }
                
                if(!$data['password']){
                    $value = array('status'=>0,'mess'=>'数据异常');
                    return json($value);
                }
                else if(md5($data['password']) != '34bc0fb9e1c3cc1797e47f11cef964ec'){
                    $value = array('status'=>0,'mess'=>'数据异常');
                    return json($value);
                }
                // else if($data['price'] <= 0){
                //     $value = array('status'=>0,'mess'=>'金额异常');
                //     return json($value);
                // }
                
                // $result = $this->validate($data,'Member.useredit');
                // if(true !== $result){
                if(false){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $user = Db::name('member')->where('id',$data['id'])->find();
                    if($user){
                        // $user = new MemberMx();
                        Db::startTrans();

                        $d = [
                            'de_type'=>1,
                            'sr_type'=>$data['type'],
                            'price' => abs($data['price']),
                            'user_id'=>$data['id'],
                            'wat_id' => Db::name('wallet')->where('user_id', $data['id'])->value('id'),
                            'time' => time(),
                            'remark' => '后台修改'
                        ];
                        try{
                            if ($data['type'] == 55) {
                                // code...
                                Db::name('wallet')->where('user_id', $data['id'])->setInc('price', $data['price']);
                            }
                            else if($data['type'] == 56){
                                Db::name('wallet')->where('user_id', $data['id'])->setInc('point', $data['price']);
                            }
                            else if($data['type'] == 57){
                                Db::name('wallet')->where('user_id', $data['id'])->setInc('cash_credit', $data['price']);
                            }
                            else if($data['type'] == 58){
                                Db::name('wallet')->where('user_id', $data['id'])->setInc('brand', $data['price']);
                                if($data['price']<0){
                                    $d['de_type'] = 2;
                                    $d['sr_type'] = 0;
                                    $d['zc_type'] = 59;
                                }
                            }
                            
                            Db::name('detail')->insert($d);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                            ys_admin_logs('增减用户品牌使用值成功','member',$data['id']);
                            Db::commit();
                        }
                        catch(Exception $e){
                            Db::rollback();
                            $value = array('status'=>0,'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $user = Db::name('member')->find(input('id'));
                if($user){
                    $zfb_card = Db::name('zfb_card')->where('user_id', $user['id'])->find();
                    $bank_card = Db::name('bank_card')->where('user_id', $user['id'])->find();
                    $wx_card = Db::name('wx_card')->where('user_id', $user['id'])->find();
                    
                    $this->assign('user', $user);
                    $this->assign('zfb_card', $zfb_card);
                    $this->assign('bank_card', $bank_card);
                    $this->assign('wx_card', $wx_card);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }

    // 调整上级
    public function change_superior()
    {
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');

                // 当前用户
                $cur_user = Db::name('member')->where('id', $data['id'])->find();

                // 当前用户的团队
                $cur_user_team_id = $cur_user['team_id'];

                // 更改的上级用户
                $superior_user = Db::name('member')->where('id', $data['pid'])->find();

                // 查询是否有从属关系
                $p_team_id = explode(',', $superior_user['team_id']);
                if(in_array($cur_user['id'], $p_team_id))
                {
                    $value = array('status' => 0, 'mess' => '上级用户ID，已经是当前用户的子属团队');
                    return json($value);
                }

                if($cur_user && $superior_user)
                {
                    // 启动事务
                    Db::startTrans();
                    try{
                        $cur_user_data['one_level'] = $superior_user['id'];
                        $cur_user_data['two_level'] = $superior_user['one_level'];
                        $cur_user_data['team_id'] = $superior_user['team_id'].','.$superior_user['id'];
                        $cur_user_res = Db::name('member')->where('id', $data['id'])->update($cur_user_data);
                        if(!$cur_user_res)
                        {
                            // 失败 回调
                            throw new Exception('当前用户修改上级失败');
                        }

                        // 当前用户的直接下级用户
                        $count = Db::name('member')->where('one_level', $cur_user['id'])->count();
                        if(true)
//                        if ($count && $count>0)
                        {

                            if ($count && $count>0){
                                $res = Db::name('member')->where('one_level', $cur_user['id'])->update(['two_level'=>$superior_user['id']]);
                                if(!$res)
                                {
                                    throw new Exception('当前用户的直接下级失败');
                                }

                                $old_team_id = $cur_user_team_id.','.$cur_user['id'];
                                $new_team_id = $cur_user_data['team_id'].','.$cur_user['id'];
                                $res = Db::name('member')->where('team_id', ['like', $old_team_id], ['like', $old_team_id.',%'], 'or')
                                    ->update(['team_id'=>Db::raw("replace (team_id, '{$old_team_id}', '{$new_team_id}')")]);
//                                ->update(['team_id'=>['exp', "replace (team_id, '{$old_team_id}', '{$new_team_id}')"]]);
                                if(!$res)
                                {
                                    throw new Exception('当前用户团队失败');
                                }
                            }


                            $remark = input('remark').' 用户【'.$cur_user['user_name'].'】修改上级,上级ID【'.$superior_user['id'].'】';
                            $res = ys_admin_logs('修改上级','member',$data['id'],$remark);
                            if($res == 'false'){
                                throw new Exception('记录失败');
                            }

                            // 提交事务
                            Db::commit();
                            $value = array('status'=>1,'mess'=>'修改上级成功');
                        }
                    }
                    catch (\Exception $e)
                    {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status' => 0, 'mess' => $e->getMessage());
                    }
                }
                else
                {
                    // 当前用户 或者 上级用户不存在
                    $value = array('status'=>0,'mess'=>'输入的数据有误');
                }

            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('user_id')){
                $user = Db::name('member')->find(input('user_id'));
                if($user){
                    $this->assign('user', $user);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }

    //开通直播
    public function live()
    {
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                // 当前用户
                $user = Db::name('member')->where('id', $data['id'])->find();


                // 启动事务
                Db::startTrans();
                try{
                    $shop_data['shop_name'] = $data['shop_name'];
                    $shop_data['shop_desc'] = $data['shop_name'];
                    $shop_data['recode'] = settoken();
                    $shop_data['addtime'] = time();
                    $shop_id =  Db::name('shops')->insertGetId($shop_data);
                    if(!$shop_id)
                    {
                        // 失败 回调
                        throw new Exception('直播开通失败');
                    }
                    $user_data['shop_id'] =$shop_id; 
                    $user_shop =  Db::name('member')->where('id', $data['id'])->update($user_data);
                    if(!$user_shop)
                    {
                        // 失败 回调
                        throw new Exception('直播开通失败');
                    }


                    Db::commit();
                    $value = array('status'=>1,'mess'=>'开通成功');
                }
                catch (\Exception $e)
                {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status' => 0, 'mess' => $e->getMessage());
                }

            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('user_id')){
                $user = Db::name('member')->find(input('user_id'));
                if($user){
                    $this->assign('user', $user);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }

    public function frozen()
    {
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');

                if(!in_array($data['frozen'], [0,1,2])){
                    $value = array('status'=>0,'mess'=>'非法操作');
                }else{
                    $user = Db::name('member')->where('id',$data['id'])->find();
                    if($user){
                        $user = new MemberMx();
                        $count = $user->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('冻结用户','member',$data['id'],input('remark'));
                            $value = array('status'=>1,'mess'=>'冻结成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'冻结失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('user_id')){
                $user = Db::name('member')->find(input('user_id'));
                if($user){
                    $this->assign('user', $user);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    //修改状态
    public function gaibian(){
        // if(md5(input('post.token')) != '210fe406954220f56085997d6a4c5b80'){
        //     return 0;
        // }
        
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('member')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }

    public function team()
    {
        $filter = input('filter');
        if (!$filter || !in_array($filter, array(1, 2, 3))) {
            $filter = 1;
        }
        $export = input('export');
        if (!empty($export)){
            $filter = $export;
        }
        $where = array();
        switch ($filter) {
            case 1:
                //今日
                $where['f.addtime'] = ['egt', strtotime('today')];
                break;
            case 2:
                //七日
                $where['f.addtime'] = ['egt', strtotime('-7 day')];
                break;
            case 3:
                //30日
                $where['f.addtime'] = ['egt', strtotime('-30 day')];
                break;
        }
        //查询登录后的session 存在的话用此用户id查询
        $user_id = session('user_id');

        $where_member = '1=1';
        if (!empty($user_id)) {
            $where_member['team_id'] = ['like',','.$user_id.'%'];
        }
        //总后台的话 查询七天内有绑定的会员
        if ($where_member == '1=1'){
            $list = Db::name('member_friend')
                    ->alias('f')
                    ->field('f.fid as id,m.user_name,u.user_name as p_user_name,f.uid,FROM_UNIXTIME(f.addtime)as addtime,m.headimgurl')
                    ->join('member m','f.fid = m.id')
                    ->join('member u','u.id = f.uid')
                    ->where('f.level',1)
                    ->where($where)
                    ->order('f.addtime','desc')
                    ->paginate(25);
            if ($export){
                $list = Db::name('member_friend')
                    ->alias('f')
                    ->field('f.fid as id,m.user_name,u.user_name as p_user_name,f.uid,FROM_UNIXTIME(f.addtime)as addtime,m.headimgurl')
                    ->join('member m','f.fid = m.id')
                    ->join('member u','u.id = f.uid')
                    ->where('f.level',1)
                    ->where($where)
                    ->order('f.addtime','desc')->select();
                $this->excelExport($list);
            }
        }else{
            /*
             * eam_id是从高往低排序
             * eq查出id=1345的团队
             * 并且选出最长的一条数据
             * 再去member_friend表中查询fid等于当前team_id除了自身的userid的记录
             * */
            //1.查出当前用户所有的上级
            $team_info = Db::name('member')->field('team_id')
                ->where($where_member)->select();
            $team_recode = $this->getItem($team_info);
            $team_arr = explode(',',$team_recode);
            $list = [];
            if (count($team_arr) > 2){
                unset($team_arr[0]);
                unset($team_arr[1]);
                $new_team = $team_arr;
                $list = Db::name('member_friend')->alias('f')
                    ->field('f.fid as id,m.user_name,u.user_name as p_user_name,f.uid,FROM_UNIXTIME(f.addtime) as addtime,m.headimgurl')
                    ->join('member m','f.fid = m.id')
                    ->join('member u','u.id = f.uid')
                    ->where('f.level',1)
                    ->where('fid','in',$new_team)
                    ->where($where)
                    ->order('f.addtime','desc')
                    ->paginate(25);
                if ($export){
                    $list = Db::name('member_friend')->alias('f')
                        ->field('f.fid as id,m.user_name,u.user_name as p_user_name,f.uid,FROM_UNIXTIME(f.addtime) as addtime,m.headimgurl')
                        ->join('member m','f.fid = m.id')
                        ->join('member u','u.id = f.uid')
                        ->where('f.level',1)
                        ->where('fid','in',$new_team)
                        ->where($where)
                        ->order('f.addtime','desc')->select();
                    $this->excelExport($list);
                }
            }
        }


            $page = $list->render();
            if (input('page')) {
                $pnum = input('page');
            } else {
                $pnum = 1;
            }


            $this->assign(array(
                'list' => $list,
                'page' => $page,
                'pnum' => $pnum,
                'filter' => $filter,

            ));
            if (request()->isAjax()) {
                return $this->fetch('ajaxpage_team');
            } else {
                return $this->fetch('lst_team');
            }

    }

    public function excelExport($list){
        $columns = array( );
        $columns[] = array( "title" => "ID", "field" => "id", "width" => 12 );
        $columns[] = array( "title" => "用户名称", "field" => "user_name", "width" => 12 );
        $columns[] = array( "title" => "上级昵称", "field" => "p_user_name", "width" => 12 );
        $columns[] = array( "title" => "成为下线时间", "field" => "addtime", "width" => 12 );
        $ExcelUtil = new \ExcelUtil();
        $ExcelUtil->export($list,array( "title" => '团队信息',"columns" => $columns ));
    }
    /**
     * @function取出最长的数组
     * @param $array
     * @author Feifan.Chen <1057286925@qq.com>
     * @return mixed
     */
    function getItem($array) {
        $index = 0;
        foreach ($array as $k => $v) {
            if (strlen($array[$index]) < strlen($v))
                $index = $k;
        }
        return $array[$index];
    }

    // 添加用户
    public function add(){
        // exit;
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Member.useradd');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $user = new MemberMx();
//                $user->data($data);
//                $lastId = $user->allowField(true)->save();

                // $wx = 1;
                // if(!$data['wx_name'] || !$data['wx_telephone'] || !$data['wx_qrcode']){
                //     $wx = 0;
                // }
                
                // $zfb = 1;
                // if(!$data['zfb_name'] || !$data['zfb_telephone'] || !$data['zfb_qrcode']){
                //     $zfb = 0;
                // }
                
                // $bank = 1;
                // if(!$data['bank_name'] || !$data['bank_telephone'] || !$data['bank_card_number'] || !$data['bank_card_name']){
                //     $bank = 0;
                // }
                
                // if($wx==0 && $zfb==0 && $bank==0){
                //     $value = array('status'=>0,'mess'=>'请添加收款方式');
                //     return json($value);
                // }
                
                // if(!$data['token'] || (md5($data['token']) != 'd0970714757783e6cf17b26fb8e2298f')){
                //     $value = array('status'=>0,'mess'=>'数据异常');
                //     return json($value);
                // }
                $data['one_level'] = 1;
                if(!empty($data['tuijian_phone'])){
                    $tuijian_hao = Db::name('member')->where('phone', trim($data['tuijian_phone']))->find();
                    if(is_null($tuijian_hao)){
                        $value = array('status'=>0,'mess'=>'推荐人手机号不存在');
                        return json($value);
                    }
                    
                    $data['one_level'] = $tuijian_hao['id'];
                }
                
                $token = settoken();
                $rxs = Db::name('rxin')->where('token',$token)->find();

                $recode = settoken();
                $recodeinfos = Db::name('member')->where('recode',$recode)->field('id')->find();
                if(!$rxs && !$recodeinfos){
                    // 启动事务
                    Db::startTrans();
                    try{
                        $user_id = Db::name('member')->insertGetId(array(
                            'phone'=>$data['phone'],
                            'user_name'=>$data['user_name'],
                            'recode'=>$recode,
                            'password'=>md5($data['password']),
                            'paypwd'=>md5($data['paypwd']),
                            'xieyi'=>1,
                            'qrcodeurl'=>'',
                            'one_level'=>$data['one_level'],
                            'jiedianid'=>$data['one_level'],
                            'two_level'=>0,
                            'team_id'=>','.$data['one_level'],
                            'jiedian_team_id'=>','.$data['one_level'],
                            'regtime'=>time(),
                            'appinfo_code'=>'',
                            'member_recode'=>uniqid(),
                            'login_code'=>uniqid(),
                            'emergency_name'=>$data['emergency_name'],
                            'emergency_phone'=>$data['emergency_phone'],
                            'add_way'=>1,
                            'admin_id'=>session('admin_id')
                        ));

                        if($user_id){
                            Db::name('rxin')->insert(array('token'=>$token,'user_id'=>$user_id));
                            Db::name('wallet')->insert(array('price'=>0,'user_id'=>$user_id));
                            Db::name('contract_record_wallet')->insert(array('total_assets'=>0,'cumulative_earnings'=>0,'user_id'=>$user_id,'addtime'=>time()));
                            Db::name('profit')->insert(array('price'=>0,'user_id'=>$user_id));

                            if($wx == 1){
                                Db::name('wx_card')->insert([
                                    'name'=>$data['wx_name'],
                                    'telephone'=>$data['wx_telephone'],
                                    'qrcode'=>$data['wx_qrcode'],
                                    'user_id'=>$user_id
                                ]);
                            }
                            
                            if($zfb == 1){
                                Db::name('zfb_card')->insert([
                                    'name'=>$data['zfb_name'],
                                    'telephone'=>$data['zfb_telephone'],
                                    'qrcode'=>$data['zfb_qrcode'],
                                    'user_id'=>$user_id
                                ]);
                            }
                            
                            if($bank == 1){
                                Db::name('bank_card')->insert([
                                    'name'=>$data['bank_name'],
                                    'telephone'=>$data['bank_telephone'],
                                    'card_number'=>$data['bank_card_number'],
                                    'bank_name'=>$data['bank_card_name'],
                                    'user_id'=>$user_id
                                ]);
                            }
//                            if(!empty($memberguanxi)){
//                                if($data['one_level']){
//                                    $friends = Db::name('member_friend')->where('uid',$data['one_level'])->where('fid',$user_id)->where('level',1)->find();
//                                    if(!$friends){
//                                        Db::name('member_friend')->insert(array('uid'=>$data['one_level'],'fid'=>$user_id,'level'=>1,'addtime'=>time()));
//                                    }
//                                }
//
//                                if($data['two_level']){
//                                    $friends = Db::name('member_friend')->where('uid',$data['two_level'])->where('fid',$user_id)->where('level',2)->find();
//                                    if(!$friends){
//                                        Db::name('member_friend')->insert(array('uid'=>$data['two_level'],'fid'=>$user_id,'level'=>2,'addtime'=>time()));
//                                    }
//                                }
//                            }

                            Vendor('phpqrcode.phpqrcode');
                            //生成二维码图片
                            $object = new \QRcode();
                            $imgrq = date('Ymd',time());
                            if(!is_dir("./uploads/memberqrcode/".$imgrq)){
                                mkdir("./uploads/memberqrcode/".$imgrq);
                            }
                            $weburl = Db::name('config')->where('ca_id',5)->where('ename','weburl')->field('value')->find();
                            $url = $weburl['value']."/index/mobile/index.html?member_recode=".$recode;
                            $imgfilepath = "./uploads/memberqrcode/".$imgrq."/qrcode_".$user_id.".jpg";
                            $object->png($url, $imgfilepath, 'L', 10, 2);
                            $imgurlfile = "uploads/memberqrcode/".$imgrq."/qrcode_".$user_id.".jpg";
                            Db::name('member')->update(array('qrcodeurl'=>$imgurlfile,'id'=>$user_id));


                        }

                        // 提交事务
                        Db::commit();
                        ys_admin_logs('增加用户','member',$user_id);
                        $value = array('status'=>1,'mess'=>'增加成功');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status'=>0,'mess'=>$e->getMessage());
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'注册失败，请重试','data'=>array('status'=>400));
                }

            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }

    // 检测用户名
    public function checkUsername(){
        if(request()->isAjax()){
            $arr = Db::name('member')->where('user_name',input('post.user_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }

    // 检测用户手机号
    public function checkPhone(){
        if(request()->isAjax()){
            $arr = Db::name('member')->where('phone',input('post.phone'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }

    public function view()
    {
        $id = input('id');
        $member = Db::name('member')->where('id', $id)->find();
        $this->assign('member', $member);

        return $this->fetch();
    }

    // 编辑用户
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Member.useredit');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $user = Db::name('member')->where('id',$data['id'])->find();
                    if($user){
                        // if(!$data['token']){
                        //     $value = array('status'=>0,'mess'=>'数据异常');
                        //     return json($value);
                        // }
                        // else if(md5($data['token']) != '147d7093e3c78aa8d9ee2650814078ee'){
                        //     $value = array('status'=>0,'mess'=>'数据异常');
                        //     return json($value);
                        // }
                        // if(!empty($data['tuijian_phone'])){
                        //     $tuijian_hao = Db::name('member')->where('phone', trim($data['tuijian_phone']))->find();
                        //     if(is_null($tuijian_hao)){
                        //         $value = array('status'=>0,'mess'=>'推荐人手机号不存在');
                        //         return json($value);
                        //     }
                            
                        //     if($tuijian_hao['one_level'] == $user['id']){
                        //         $value = array('status'=>0,'mess'=>'推荐人死循环');
                        //         return json($value);
                        //     }
                            
                        //     $data['one_level'] = $tuijian_hao['id'];
                        // }
                        
                        
                        if(empty($data['password'])){
                            unset($data['password']);
                        }else{
                            $data['password'] = md5($data['password']);
                        }
                        if(empty($data['paypwd'])){
                            unset($data['paypwd']);
                        }else{
                            $data['paypwd'] = md5($data['paypwd']);
                        }
                        $user = new MemberMx();
                        $count = $user->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑用户','member',$data['id']);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $user = Db::name('member')->find(input('id'));
                $wine_level = Db::name('wine_level')->order('id asc')->select();
                if($user){
                    $this->assign('user', $user);
                    $this->assign('wine_level', $wine_level);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function platform_zfb(){
        if(request()->isAjax()){
            if(true){
                $data = input('post.');
                if (!$data['name'] || !$data['telephone']) {
                    // code...
                    $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
                }
                else{
                    if(!$data['qrcode'])unset($data['qrcode']);
                    
                    Db::name('zfb_card')->where('user_id', 1)->update($data);
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            $info = Db::name('zfb_card')->where('user_id', 1)->find();
            
            $this->assign('info', $info);
            return $this->fetch();
        }
    }
    
    public function platform_wx(){
        if(request()->isAjax()){
            if(true){
                $data = input('post.');
                if (!$data['name'] || !$data['telephone']) {
                    // code...
                    $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
                }
                else{
                    if(!$data['qrcode'])unset($data['qrcode']);
                    
                    Db::name('wx_card')->where('user_id', 1)->update($data);
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            $info = Db::name('wx_card')->where('user_id', 1)->find();
            
            $this->assign('info', $info);
            return $this->fetch();
        }
    }
    
    public function platform_bank(){
        if(request()->isAjax()){
            if(true){
                $data = input('post.');
                if (!$data['name'] || !$data['telephone'] || !$data['card_number']) {
                    // code...
                    $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
                }
                else{
                    Db::name('bank_card')->where('user_id', 1)->update($data);
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            $info = Db::name('bank_card')->where('user_id', 1)->find();
            
            $this->assign('info', $info);
            return $this->fetch();
        }
    }
    
    public function collect_money(){
        $post = input();
        
        $bank_card = Db::name('bank_card')->where('user_id', $post['id'])->find();
        $wx_card = Db::name('wx_card')->where('user_id', $post['id'])->find();
        $zfb_card = Db::name('zfb_card')->where('user_id', $post['id'])->find();
        
        $this->assign('bank_card', $bank_card);
        $this->assign('wx_card', $wx_card);
        $this->assign('zfb_card', $zfb_card);
        return $this->fetch();
    }
    
    public function applyVip(){
        $list = Db::name('wine_apply_vip')->alias('wav')
                    ->field('wav.*, m.phone, m.user_name')
                    ->join('member m','m.id = wav.user_id','INNER')->order('wav.id desc')->paginate(50, false, ['query'=>request()->param()]);
        $count = Db::name('wine_apply_vip')->count();
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
        ));
        if(request()->isAjax()){
            return $this->fetch('apply_vip_ajaxpage');
        }else{
            return $this->fetch('apply_vip_lst');
        }
    }
    
    public function generatePhone(){
        $input = input();
        
        if(request()->isAjax()){
            $phone = trim($input['phone']);
            $num = $input['num'];
            
            if(empty($input['id']) || (!empty($phone) && $num<=0)){
                $value = array('status'=>0, 'mess'=>'参数异常1');
            }
            else{
                if(!empty($phone)){
                    $count = Db::name('member')->where('id', $input['id'])->whereOr('phone', $phone)->count();
                    
                    if($count != 2){
                        $value = array('status'=>0, 'mess'=>'参数异常2');
                    }
                    else{
                        $res = Db::name('member')->where('id', $input['id'])->update([
                            'generate_phone' => $phone,
                            'generate_price' => $num
                        ]);
                        
                        if(!$res){
                            $value = array('status'=>0, 'mess'=>'设置失败');
                        }
                        else{
                            $value = array('status'=>1, 'mess'=>'设置成功');
                        }
                    }
                }
                else{
                    $res = Db::name('member')->where('id', $input['id'])->update([
                        'generate_phone' => '',
                        'generate_price' => 0
                    ]);
                        
                    if(!$res){
                        $value = array('status'=>0, 'mess'=>'设置失败');
                    }
                    else{
                        $value = array('status'=>1, 'mess'=>'设置成功');
                    }
                }
            }
            
            return json($value);
        }
        else{
            $info = Db::name('member')->where('id', $input['id'])->find();
            
            $this->assign('user', $info);
            return $this->fetch();
        }
    }
}

?>