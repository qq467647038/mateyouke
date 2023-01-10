<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\cache\driver\Redis;
use app\admin\services\Upush;
use think\Db;

class UniPush extends Common{
    
    public function lst(){
        $list = Db::name('push')->order('created desc')->paginate(15);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

    public function add(){
        if(request()->isPost()) {
            $data = input('post.');
            if(empty($data['title'])){
                $ret_push = array('status'=>0,'mess'=>'请发布标题');
                return json($ret_push);
            }
            $data['created']=date('Y-m-d H:i:s',time());
            $result = db('push')->insert($data);
            if($result){
                $this->push($data);
                $value = array('status'=>1,'mess'=>'增加成功','data'=>$ret_data);
            }else{
                $value = array('status'=>0,'mess'=>'增加失败');
            }
            //加入推送队列中执行推送
            // $redis = new Redis();
            // $redis->lpush('pushtest','unipush');
            return json($value);
        }else{
            return $this->fetch();

        }
    }

    /***
     * 直接进行推送任务
     */
    private function push($data){
        $data['payload'] = '{"title":"'.$data['title'].'","content":"'.$data['content'].'","sound":"default","payload":"test"}';
        $model = new Upush();
        $model->pushAll($data);
    }
}

?>