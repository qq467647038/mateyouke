<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\services\Upush;
use think\Db;



class Message extends Common{
    //消息列表
    public function lst(){
        $list = Db::name('notification')->order('edit_time desc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
           'pnum'=>$pnum,
           'list'=>$list,
           'page'=>$page
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
	
	//推送消息
	public function send(){
		
	    if(request()->isAjax()){
	        $data = input('post.');
			//print_r($data);die();
	        $admin_id = session('admin_id');
	        $result = $this->validate($data,'Message');
	        if(true !== $result){
	            $value = array('status'=>0,'mess'=>$result);
	        }else{
	            if(!empty($data['pic_id'])){
	                $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
	                if($zssjpics && $zssjpics['img_url']){
	                    $data['cover'] = $zssjpics['img_url'];
	                }
	            }

	            if (isset($data['user_name']))
	            {
                    $to_user_id = Db::name('member')->where('user_name', $data['user_name'])->value('id');

                    if($to_user_id)
                    {
                        $data['user_id'] = $to_user_id;
                        unset($data['user_name']);
                    }
                    else
                    {
                        $value = array('status'=>0,'mess'=>'用户不存在');
                        return json($value);
                    }
                }
				
				unset($data['pic_id']);
	            
	            if(!empty($data['create_time'])){
	                $data['create_time'] = strtotime($data['addtime']);
	            }else{
	                $data['create_time'] = time();
	            }
				$data['introduce'] = $data['introduce'] ? $data['introduce'] : "您有一条新的消息，请注意查收！";
				$data['edit_time'] = time();
				
	            $lastId = Db::name('notification')->insertGetId($data);
	            if($lastId){
	                
					//推送所有人
					if($data['type'] != 1){
						
						$data1 = array('title'=>$data['title'],'content'=>$data['introduce']);
						$this->push($data1);
					}
					 
					
	                $value = array('status'=>1,'mess'=>'发送成功');
	            }else{
	                $value = array('status'=>0,'mess'=>'发送失败');
	            }
	        }
	        return json($value);
	    }else{
	        $admin_id = session('admin_id');
	        $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
	        if($zssjpics && $zssjpics['img_url']){
	            Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
	            if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
	                @unlink('./'.$zssjpics['img_url']);
	            }
	        }
	        
	        return $this->fetch();
	    }
	}
	
	public function edit(){
	    if(request()->isPost()){
	        if(input('post.id')){
	            $data = input('post.');
				$admin_id = session('admin_id');
	            $result = $this->validate($data,'Message');
	            if(true !== $result){
	                $value = array('status'=>0,'mess'=>$result);
	            }else{
	                $msginfos = Db::name('notification')->where('id',$data['id'])->find();
	                if($msginfos){
	                    $data['edit_time'] = time();
						
						if(!empty($data['pic_id'])){
						    $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
							//dump( Db::getLastSql());
						    
							if($zssjpics && $zssjpics['img_url']){
						        $data['cover'] = $zssjpics['img_url'];
						    }
						}else{
							$data['cover'] = $msginfos['cover'];
						}
						unset($data['pic_id']);
						//print_r($data);die();
						//$count = $msg->allowField(true)->save($data,array('id'=>$data['id']));
						$count = db('notification')->where(['id'=>$data['id']])->update($data);
						
	                    if($count !== false){
	                        ys_admin_logs('消息编辑成功','message',$data['id']);
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
			
			$admin_id = session('admin_id');
			
	        if(input('id')){
	            $id = input('id');
	            $info = Db::name('notification')->find($id);
	            //print_r($info);die();
				if($info){
	                $this->assign('ars', $info);
	                return $this->fetch();
	            }else{
	                $this->error('找不到相关信息');
	            }
	        }else{
	            $this->error('缺少参数');
	        }
	    }
		
		return json($value); 
	}
	
	public function delete(){
	    if(input('post.id')){
	        $id= array_filter(explode(',', input('post.id')));
	    }else{
	        $id = input('id');
	    }
	    if(!empty($id)){
	        $count = db('notification')->delete($id);
	        if($count > 0){
	            if(is_array($id)){
	                foreach ($id as $v2){
	                    ys_admin_logs('删除消息','message',$v2);
	                }
	            }else{
	                ys_admin_logs('删除消息','message',$id);
	            }
	            $value = array('status'=>1,'mess'=>'删除成功');
	        }else{
	            $value = array('status'=>0,'mess'=>'编辑失败');
	        }
	    }else{
	        $value = array('status'=>0,'mess'=>'请选择删除项');
	    }
	    return $value;
	}
	
	//处理上传图片
	public function uploadify(){
	    $admin_id = session('admin_id');
	    $file = request()->file('filedata');
	    if($file){
	        $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'message');
	        if($info){
	            $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
	            if($zssjpics && $zssjpics['img_url']){
	                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
	                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
	                    @unlink('./'.$zssjpics['img_url']);
	                }
	            }
	            $original = 'uploads/message/'.$info->getSaveName();
	            if($zssjpics){
	                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
	                $zspic_id = $zssjpics['id'];
	            }else{
	                $zspic_id = Db::name('huamu_zspic')->insertGetId(array('admin_id'=>$admin_id,'img_url'=>$original));
	            }
	            $picarr = array('img_url'=>$original,'pic_id'=>$zspic_id);
	            $value = array('status'=>1,'path'=>$picarr);
	        }else{
	            $value = array('status'=>0,'msg'=>$file->getError());
	        }
	    }else{
	        $value = array('status'=>0,'msg'=>'文件不存在');
	    }
	    return json($value);
	}
	
	//手动删除未保存的上传图片手机
	public function delfile(){
	    if(input('post.zspic_id')){
	        $admin_id = session('admin_id');
	        $zspic_id = input('post.zspic_id');
	        $pics = Db::name('huamu_zspic')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
	        if($pics && $pics['img_url']){
	            $count = Db::name('huamu_zspic')->where('id',$pics['id'])->update(array('img_url'=>''));
	            if($count > 0){
	                if($pics['img_url'] && file_exists('./'.$pics['img_url'])){
	                    @unlink('./'.$pics['img_url']);
	                }
	                $value = 1;
	            }else{
	                $value = 0;
	            }
	        }else{
	            $value = 0;
	        }
	    }else{
	        $value = 0;
	    }
	    return json($value);
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