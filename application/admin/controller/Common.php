<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use Qcloud\Cos\Client;

class Common extends Controller{
    public $webconfig;
    Public function _initialize(){
        // $this->redirect('Login/index');
        if(!session('admin_id') || !session('shop_id')){
            $this->redirect('Login/index');
        }
       
        $this->_getconfig();
        
        /*if(request()->module()=='Admin' && request()->controller()=='Index'){
            return true;
        }
        
        if(request()->module()=='Admin' && request()->controller()=='Admin' && request()->action()=='loginOut'){
            return true;
        }
        
        if(session('privilege') == "*"){
            return true;
        }
        
        if(session('privilege') != '*' && !in_array(request()->module().'/'.request()->controller().'/'.request()->action(), session('privilege'))){
            echo '您没有权限访问该方法！';
            die;
        }*/
    }

    public function _getconfig(){
        $_configres = Db::name('config')->where('ca_id','in','1,2,4,5,10,15')->field('ename,value')->select();
        $configres = array();
        foreach ($_configres as $v){
            $configres[$v['ename']] = $v['value'];
        }
        $this->webconfig=$configres;
        $this->assign('configres',$configres);
    }

     /**
     * 腾讯对象存储-文件上传
     * @datatime 2018/05/17 09:20
     * @author lgp
     */
    public function qcloudCosUpload($file,$mkdirname,$numpic){
        if(empty($file)){
            datamsg(400,'请上传图片');
         }
         $cosClient = new Client(config("tengxunyun"));
         if(is_array($file)){
            if(count($file) >= $numpic){
                datamsg(LOSE,'最多上传'.$numpic.'张图片');
            }
            $picarr=[];
            foreach($file as $key=>$value){


                $info = $file[$key]->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->getInfo();
                $key = $mkdirname."/".date("Y-m-d") . "/" .$info['name'];
                $data = array( 'Bucket' => 'xiaoquhenhuo-1259494372', 'Key'  => $key, 'Body' => fopen($info['tmp_name'], 'rb') );
                //判断文件大小 大于5M就分块上传
                $result = $cosClient->Upload( $data['Bucket'] , $data['Key'] , $data['Body']  );

                if($result){
                    $original['dz'] = $key;
                    $original['wz'] = config('tengxunyun')['cos_domain'].'/'.$key;
                    $picarr[]=$original;
                }else{
                    $picarr[]=0;
                }

            }
            return $picarr;
        }else{

            try {
                $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->getInfo();
                $key = $mkdirname."/".date("Y-m-d") . "/" .$info['name'];
                $data = array( 'Bucket' => 'xiaoquhenhuo-1259494372', 'Key'  => $key, 'Body' => fopen($info['tmp_name'], 'rb') );
                //判断文件大小 大于5M就分块上传
                $result = $cosClient->Upload( $data['Bucket'] , $data['Key'] , $data['Body']  );

                //上传成功，自己编码
                if( $result ){
                    $original['dz'] = $key;
                    $original['wz'] = config('tengxunyun')['cos_domain'].'/'.$key;
                    $picarr[]=$original;
                }else{
                    datamsg(LOSE,'图片上传失败');
                }
                return $original;
            } catch (\Exception $e) {
                datamsg(LOSE,'图片上传失败');
            }
            
        }

    }
    
    public function menpiao(){
        $post = input();
        $where = [];
        if($post['keyword']){
            $keyword = trim($post['keyword']);
        }
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
                if($post['sr_type']){
            $where_sr_type = [$post['sr_type']];
            $post['zc_type'] = '';
        }else{
            if($post['zc_type']){
                $where_zc_type = [$post['zc_type']];
                $post['sr_type'] = '';
            }else{
                $where_zc_type = [27,108];
                $where_sr_type = [27];
            }
        }
        
        
        $list = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->join('member t', 't.id = d.target_id', 'left')
                    // ->where('d.user_id', $input['id'])
                    ->where('d.time', 'between time', $whereTime)
                    ->where(function ($query) use($keyword){
                        if($keyword)$query->where('d.id', $keyword)->whereOr('m.true_name', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.phone', $keyword);
                    })
                    ->where(function($query) use($where_zc_type,$where_sr_type){
                        $query->where('sr_type', 'in',$where_sr_type)->whereOr('zc_type', 'in',$where_zc_type);
                    })
                    ->field('d.*, m.user_name m_user_name, m.true_name m_true_name, t.user_name t_user_name, t.true_name t_true_name, m.phone m_phone, t.phone t_phone,m.phone')
                    ->order('d.id desc')->paginate(25, false, ['query'=>request()->param()])->each(function($item){
                        switch($item['sr_type']){
                            case 27:
                                 $item['remark'] = '后台操作';
                                 break;
                        }
                        
                        switch($item['zc_type']){
                            case 27:
                                $item['remark'] = '后台操作';
                                break;
                            case 108:
                                $item['remark'] = '预售或购买';
                                break;
                        }
                        // $item['time'] = date('Y-m-d H:i:s', $item['time']);
                        
                        return $item;
                    });
        $count = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->where('d.time', 'between time', $whereTime)
                    ->where(function ($query) use($keyword){
                        if($keyword)$query->where('d.id', $keyword)->whereOr('m.true_name', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.phone', $keyword);
                    })
                    ->where(function($query){
                        $query->where('sr_type', 'in', $where_sr_type)->whereOr('zc_type', 'in', $where_zc_type);
                    })
                    ->count();
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign(array(
            'list'=>$list,
            'where_time'=>$whereTime,
            'keyword'=>$keyword,
            'page'=>$page,
            'sr_type'=>$post['sr_type'],
            'zc_type'=>$post['zc_type'],
            'pnum'=>$pnum,
            'count'=>$count
        ));
        if(request()->isAjax()){
            return $this->fetch('member/trade_detail_ajaxpage');
        }else{
            return $this->fetch('member/trade_detail_lst3');
        }
    }
    
    public function point(){
        $post = input();
        $where = [];
        if($post['keyword']){
            $keyword = trim($post['keyword']);
        }
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
                if($post['sr_type']){
            $where_sr_type = [$post['sr_type']];
            $post['zc_type'] = '';
        }else{
            if($post['zc_type']){
                $where_zc_type = [$post['zc_type']];
                $post['sr_type'] = '';
            }else{
                $where_zc_type = [26];
                $where_sr_type = [103,26];
            }
        }
        
        
        $list = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->join('member t', 't.id = d.target_id', 'left')
                    // ->where('d.user_id', $input['id'])
                    ->where('d.time', 'between time', $whereTime)
                    ->where(function ($query) use($keyword){
                        if($keyword)$query->where('d.id', $keyword)->whereOr('m.true_name', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.phone', $keyword);
                    })
                    ->where(function($query) use($where_zc_type,$where_sr_type){
                        $query->where('sr_type', 'in',$where_sr_type)->whereOr('zc_type', 'in',$where_zc_type);
                    })
                    ->field('d.*, m.user_name m_user_name, m.true_name m_true_name, t.user_name t_user_name, t.true_name t_true_name, m.phone m_phone, t.phone t_phone,m.phone')
                    ->order('d.id desc')->paginate(25, false, ['query'=>request()->param()])->each(function($item){
                        switch($item['sr_type']){
                            case 103:
                                 $item['remark'] = '退款';
                                 break;
                            case 26:
                                 $item['remark'] = '后台操作';
                                 break;
                        }
                        
                        switch($item['zc_type']){
                            case 26:
                                $item['remark'] = '后台操作';
                                break;
                        }
                        // $item['time'] = date('Y-m-d H:i:s', $item['time']);
                        
                        return $item;
                    });
        $count = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->where('d.time', 'between time', $whereTime)
                    ->where(function ($query) use($keyword){
                        if($keyword)$query->where('d.id', $keyword)->whereOr('m.true_name', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.phone', $keyword);
                    })
                    ->where(function($query){
                        $query->where('sr_type', 'in', $where_sr_type)->whereOr('zc_type', 'in', $where_zc_type);
                    })
                    ->count();
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign(array(
            'list'=>$list,
            'where_time'=>$whereTime,
            'keyword'=>$keyword,
            'page'=>$page,
            'sr_type'=>$post['sr_type'],
            'zc_type'=>$post['zc_type'],
            'pnum'=>$pnum,
            'count'=>$count
        ));
        if(request()->isAjax()){
            return $this->fetch('member/trade_detail_ajaxpage');
        }else{
            return $this->fetch('member/trade_detail_lst2');
        }
    }
    
    public function point_export(){
        $post = input();
        $where = [];
        if($post['keyword']){
            $keyword = trim($post['keyword']);
        }
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
        $list = Db::name('detail')->alias('d')
            ->join('member m', 'm.id = d.user_id', 'inner')
            ->join('member t', 't.id = d.target_id', 'left')
            // ->where('d.user_id', $input['id'])
            ->where('d.time', 'between time', $whereTime)
            ->where(function ($query) use($keyword){
                if($keyword)$query->where('d.id', $keyword)->whereOr('m.true_name', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.phone', $keyword);
            })
            ->where(function($query){
                $query->where('sr_type', 'in', [121,71,1111,25,500])->whereOr('zc_type', 'in', [70,1000,1001,110,25]);
            })
            ->field('d.*, m.user_name m_user_name, m.true_name m_true_name, t.user_name t_user_name, t.true_name t_true_name, m.phone')
            ->order('d.id desc')->paginate(100000000)->each(function($item){
                switch($item['sr_type']){
                    case 121:
                         $item['remark'] = '佣金提现积分';
                         break;
                    case 25:
                         $item['remark'] = '后台修改';
                         break;
                    case 500:
                         $item['remark'] = '购买积分商城商品';
                         break;
                    case 71:
                        $wine_deal_area_id = Db::name('wine_order_record')->where('id', $item['target_id'])->value('wine_deal_area_id');
                        $desc = Db::name('wine_deal_area')->where('id', $wine_deal_area_id)->value('desc');
                        $item['remark'] = '普通竞拍'.$desc.'预约金返还';
                         break;
                    case 1111:
                        $wine_deal_area_id = Db::name('wine_order_record_contract')->where('id', $item['target_id'])->value('wine_deal_area_id');
                        $desc = Db::name('wine_deal_area_contract')->where('id', $wine_deal_area_id)->value('desc');
                        $item['remark'] = '合约竞拍'.$desc.'预约金返还';
                        break;
                }
                
                switch($item['zc_type']){
                    case 25:
                         $item['remark'] = '后台修改';
                         break;
                    case 70: 
                        $wine_deal_area_id = Db::name('wine_order_record')->where('id', $item['target_id'])->value('wine_deal_area_id');
                        $desc = Db::name('wine_deal_area')->where('id', $wine_deal_area_id)->value('desc');
                        $item['remark'] = '普通竞拍'.$desc.'预约金';
                        break;
                    case 1000:
                        $wine_deal_area_id = Db::name('wine_order_record_contract')->where('id', $item['target_id'])->value('wine_deal_area_id');
                        $desc = Db::name('wine_deal_area_contract')->where('id', $wine_deal_area_id)->value('desc');
                        $item['remark'] = '合约竞拍'.$desc.'预约金';
                        break;
                    case 1000:
                        $item['remark'] = '合约竞拍预约冻结积分';
                        break;
                    case 1001:
                        $item['remark'] = '合约购买';
                        break;
                    case 110:
                        $item['remark'] = '寄售服务费';
                        break;
                }
                // $item['time'] = date('Y-m-d H:i:s', $item['time']);
                
                return $item;
            });
     
        $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'改变前', 'C'=>'资金', 'D'=>'改变后', 'E'=>'用户名', 'F'=>'手机', 'G'=>'时间', 'H'=>'说明']);
        foreach($list as $k=>$v){
            array_push($arr, ['A'=>$v['id'], 'B'=>$v['before_price'], 'C'=>($v['de_type']==1?'+'.$v['price']:'-'.$v['price']), 'D'=>$v['after_price'], 'E'=>$v['m_true_name'] ? $v['m_true_name'] : $v['m_user_name'], 'F'=>$v['phone'], 'G'=>date('Y-m-d H:i:s', $v['time']), 'H'=>$v['remark']]);
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
              ->setCellValue('H'.$num, $val['H']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '积分变帐信息列表'.date("Y-m-d",time()).'.xlsx';
    
        header('Content-Type: application/vnd.ms-excel');
    
        header('Content-Disposition: attachment; filename='.$name);
    
        header('Cache-Control: max-age=0');
    
        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
    
        $ExcelWriter->save('php://output');
    
        exit;
    }
    
    public function yue(){
        $post = input();
        $where = [];
        if($post['keyword']){
            $keyword = trim($post['keyword']);
        }
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }

        if($post['sr_type']){
            $where_sr_type = [$post['sr_type']];
            $post['zc_type'] = '';
        }else{
            if($post['zc_type']){
                $where_zc_type = [$post['zc_type']];
                $post['sr_type'] = '';
            }else{
                $where_zc_type = [5, 24, 2];
                $where_sr_type = [8,120, 24];
            }
        }
        
 
        
        // echo $keyword;exit;
        $list = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->join('member t', 't.id = d.target_id', 'left')
                    ->where('d.time', 'between time', $whereTime)
                    ->where(function ($query) use($keyword){
                        if($keyword)$query->where('d.id', $keyword)->whereOr('m.true_name', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.phone', $keyword);
                    })
                    ->where(function($query) use($where_sr_type,$where_zc_type){
                        $query->whereIn('sr_type', $where_sr_type)->whereOr('zc_type', 'in', $where_zc_type);
                    })
                    ->field('d.*, m.user_name m_user_name, m.true_name m_true_name, t.user_name t_user_name, t.true_name t_true_name, m.phone')
                    ->order('d.id desc')->paginate(25, false, ['query'=>request()->param()])->each(function($item){
                        // var_dump($item);
                        switch($item['sr_type']){
                            case 8:
                                $item['remark'] = '余额转账：'.$item['t_true_name'].'转给姓名:'.($item['m_true_name']?$item['m_true_name']:$item['m_user_name']).' - 手机号:'.$item['phone'];
                                break;
                            // case 1006:
                            //      $item['remark'] = '购买福利场商品奖励';
                            //      break;
                            case 24:
                                $item['remark'] = '后台余额修改';
                                break;
                            case 120:
                                $item['remark'] = '佣金转入';
                                break;
                            // case 71:
                            //     $wine_deal_area_id = Db::name('wine_order_record')->where('id', $item['target_id'])->value('wine_deal_area_id');
                            //     $desc = Db::name('wine_deal_area')->where('id', $wine_deal_area_id)->value('desc');
                            //     $item['remark'] = '普通竞拍'.$desc.'预约金返还';
                            //      break;
                            // case 1111:
                            //     $wine_deal_area_id = Db::name('wine_order_record_contract')->where('id', $item['target_id'])->value('wine_deal_area_id');
                            //     $desc = Db::name('wine_deal_area_contract')->where('id', $wine_deal_area_id)->value('desc');
                            //     $item['remark'] = '合约竞拍'.$desc.'预约金返还';
                            //     break;
                        }
                        
                        switch ($item['zc_type']) {
                            case 5:
                                $item['remark'] = '余额转账：'.$item['m_user_name'].'转给姓名:'.($item['t_true_name']?$item['t_true_name']:$item['t_user_name']).' - 手机号:'.$item['t_phone'];
                                break;
                            case 24:
                                $item['remark'] = '后台余额修改';
                                break;
                            // case 110:
                            //     $item['remark'] = '寄售服务费';
                            //     break;
                            // case 1003:
                            //     $item['remark'] = '寄售服务费';
                            //     break;
                            // case 1001:
                            //     $item['remark'] = '合约抢购扣余额';
                            //     break;
                            // case 70:
                            //     $wine_deal_area_id = Db::name('wine_order_record')->where('id', $item['target_id'])->value('wine_deal_area_id');
                            //     $desc = Db::name('wine_deal_area')->where('id', $wine_deal_area_id)->value('desc');
                            //     $item['remark'] = '普通竞拍'.$desc.'预约金';
                            //     break;
                            // case 1000:
                            //     $wine_deal_area_id = Db::name('wine_order_record_contract')->where('id', $item['target_id'])->value('wine_deal_area_id');
                            //     $desc = Db::name('wine_deal_area_contract')->where('id', $wine_deal_area_id)->value('desc');
                            //     $item['remark'] = '合约竞拍'.$desc.'预约金';
                            //     break;
                            // case 2:
                            //     $item['remark'] = '购买商品';
                            //     break;
                        }
                        
                        return $item;
                    });
                    // echo Db::name('detail')->getLastSql();
                    // die();
        $count = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->where('d.user_id', $input['id'])->count();
        $page = $list->render();
        // $page->setConfig('prev', '上一页');
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign(array(
            'list'=>$list,
            'where_time'=>$whereTime,
            'keyword'=>$keyword,
            'sr_type'=>$post['sr_type'],
            'zc_type'=>$post['zc_type'],
            'page'=>$page,
            'pnum'=>$pnum,
            'count'=>$count
        ));
        if(request()->isAjax()){
            return $this->fetch('member/trade_detail_ajaxpage');
        }else{
            return $this->fetch('member/trade_detail_lst');
        }
    }
    
    public function yue_export(){
        $post = input();
        $where = [];
        if($post['keyword']){
            $keyword = trim($post['keyword']);
        }
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        if($post['sr_type']){
            $where_sr_type = [$post['sr_type']];
            $post['zc_type'] = '';
        }else{
            if($post['zc_type']){
                $where_zc_type = [$post['zc_type']];
                $post['sr_type'] = '';
            }else{
                $where_zc_type = [5, 24, 2];
                $where_sr_type = [8,120, 24];
            }
        }
        // echo $keyword;exit;
        $list = Db::name('detail')->alias('d')
            ->join('member m', 'm.id = d.user_id', 'inner')
            ->join('member t', 't.id = d.target_id', 'left')
            ->where('d.time', 'between time', $whereTime)
            ->where(function ($query) use($keyword){
                if($keyword)$query->where('d.id', $keyword)->whereOr('m.true_name', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.phone', $keyword);
            })
             ->where(function($query) use($where_sr_type,$where_zc_type){
                        $query->whereIn('sr_type', $where_sr_type)->whereOr('zc_type', 'in', $where_zc_type);
                    })
            // ->where(function($query){
            //     $query->where('sr_type', 'in', [8, 24])->whereOr('zc_type', 'in', [5, 24, 2]);
            // })
            ->field('d.*, m.user_name m_user_name, m.true_name m_true_name, t.user_name t_user_name, t.true_name t_true_name, m.phone')
            ->order('d.id desc')->paginate(100000000)->each(function($item){
                switch($item['sr_type']){
                    case 8:
                        $item['remark'] = '余额转账：'.$item['t_user_name'].'转给姓名:'.($item['m_true_name']?$item['m_true_name']:$item['m_user_name']).' - 手机号:'.$item['m_phone'];
                        break;
                    case 24:
                        $item['remark'] = '后台余额修改';
                        break;
                }
                
                switch ($item['zc_type']) {
                    case 5:
                        $item['remark'] = '余额转账：'.$item['m_user_name'].'转给姓名:'.($item['t_true_name']?$item['t_true_name']:$item['t_user_name']).' - 手机号:'.$item['t_phone'];
                        break;
                    case 24:
                        $item['remark'] = '后台余额修改';
                        break;
                    // case 2:
                    //     $item['remark'] = '购买商品';
                    //     break;
                }
                
                return $item;
            });
    //  echo Db::name('detail')->getLastSql();die();
        $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'改变前', 'C'=>'资金', 'D'=>'改变后', 'E'=>'用户名', 'F'=>'手机', 'G'=>'时间', 'H'=>'说明']);
        foreach($list as $k=>$v){
            array_push($arr, ['A'=>$v['id'], 'B'=>$v['before_price'], 'C'=>($v['de_type']==1?'+'.$v['price']:'-'.$v['price']), 'D'=>$v['after_price'], 'E'=>$v['m_true_name'] ? $v['m_true_name'] : $v['m_user_name'], 'F'=>$v['phone'], 'G'=>date('Y-m-d H:i:s', $v['time']), 'H'=>$v['remark']]);
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
              ->setCellValue('H'.$num, $val['H']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '余额变帐信息列表'.date('Y-m-d').'.xlsx';
    
        header('Content-Type: application/vnd.ms-excel');
    
        header('Content-Disposition: attachment; filename='.$name);
    
        header('Cache-Control: max-age=0');
    
        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
    
        $ExcelWriter->save('php://output');
    
        exit;
    }
    
    public function commissions(){
        $post = input();
        $where = [];
        if($post['keyword']){
            $keyword = trim($post['keyword']);
        }
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
        if($post['sr_type']){
            $where_sr_type = [$post['sr_type']];
            $post['zc_type'] = '';
        }else{
            if($post['zc_type']){
                $where_zc_type = [$post['zc_type']];
                $post['sr_type'] = '';
            }else{
                $where_zc_type = [100,25];
                $where_sr_type = [102,105,101,1000,1001,109,25,110,604,605,606,607,600,601,602,603,200,201,205];
            }
        }
        $list = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->join('member t', 't.id = d.target_id', 'left')
                    // ->where('d.user_id', $input['id'])
                    ->where('d.time', 'between time', $whereTime)
                    ->where(function ($query) use($keyword){
                        if($keyword)$query->where('d.id', $keyword)->whereOr('m.true_name', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.phone', $keyword);
                    })
                    ->where(function($query) use($where_zc_type,$where_sr_type){
                        $query->where('sr_type', 'in', $where_sr_type)->whereOr('zc_type', 'in', $where_zc_type);
                    })
                    ->field('d.*, m.user_name m_user_name, m.true_name m_true_name, t.user_name t_user_name, t.true_name t_true_name, m.phone')
                    ->order('d.id desc')->paginate(25, false, ['query'=>request()->param()])->each(function($item){
                        switch($item['sr_type']){
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
                                $item['remark'] = '购买商品赠送';
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
                        
                        switch($item['zc_type']){
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
            'where_time'=>$whereTime,
            'keyword'=>$keyword,
            'page'=>$page,
            'sr_type'=>$post['sr_type'],
            'zc_type'=>$post['zc_type'],
            'pnum'=>$pnum,
            'count'=>$count
        ));
        if(request()->isAjax()){
            return $this->fetch('member/trade_detail_ajaxpage');
        }else{
            return $this->fetch('member/trade_detail_lst1');
        }
    }
    
    public function commissions_export(){
        $post = input();
        $where = [];
        if($post['keyword']){
            $keyword = trim($post['keyword']);
        }
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
        $list = Db::name('detail')->alias('d')
            ->join('member m', 'm.id = d.user_id', 'inner')
            ->join('member t', 't.id = d.target_id', 'left')
            // ->where('d.user_id', $input['id'])
            ->where('d.time', 'between time', $whereTime)
            ->where(function ($query) use($keyword){
                if($keyword)$query->where('d.id', $keyword)->whereOr('m.true_name', $keyword)->whereOr('m.user_name', $keyword)->whereOr('m.phone', $keyword);
            })
            ->where(function($query){
                $query->where('sr_type', 'in', [64,65,80])->whereOr('zc_type', 'in', [120]);
            })
            ->field('d.*, m.user_name m_user_name, m.true_name m_true_name, t.user_name t_user_name, t.true_name t_true_name, m.phone')
            ->order('d.id desc')->paginate(100000000)->each(function($item){
                switch($item['sr_type']){
                    case 64:
                        $item['remark'] = '直推奖';
                        break;
                    case 65:
                        $item['remark'] = '团队奖';
                        break;
                    case 80:
                        $item['remark'] = '管理奖';
                        break;
                }
                
                switch($item['zc_type']){
                    case 120:
                        $item['remark'] = '佣金提现';
                        break;
                }
                
                return $item;
            });
     
        $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'改变前', 'C'=>'资金', 'D'=>'改变后', 'E'=>'用户名', 'F'=>'手机', 'G'=>'时间', 'H'=>'说明']);
        foreach($list as $k=>$v){
            array_push($arr, ['A'=>$v['id'], 'B'=>$v['before_price'], 'C'=>($v['de_type']==1?'+'.$v['price']:'-'.$v['price']), 'D'=>$v['after_price'], 'E'=>$v['m_true_name'] ? $v['m_true_name'] : $v['m_user_name'], 'F'=>$v['phone'], 'G'=>date('Y-m-d H:i:s', $v['time']), 'H'=>$v['remark']]);
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
              ->setCellValue('H'.$num, $val['H']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '佣金变帐信息列表'.date('Y-m-d').'.xlsx';
    
        header('Content-Type: application/vnd.ms-excel');
    
        header('Content-Disposition: attachment; filename='.$name);
    
        header('Cache-Control: max-age=0');
    
        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
    
        $ExcelWriter->save('php://output');
    
        exit;
    }
}