<?php
namespace app\apicloud\controller;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;
class Notification extends Common {
  
    /**
     * @func 获取消息列表
     */
    public function notificationList(){
        if(request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            // dump($result);die;
            if($result['status'] == 200){
				$user_id = $result['user_id'];
                $page = input('param.page') ? input('param.page') : 1;
                $size = input('param.size') ? input('param.size') : 10;
                $isnewperson = input('param.isnewperson');
                 
                $type = (int)input('post.type');
                $where = ['type'=>$type,'status'=>1];
                if($type == 1)
                {
                    $where['user_id'] = $user_id;
                }

                // 增加系统通知
                if($type == 2){
                    $page = input('page/d', 1);
                    $pagesize = input('param.size') ? input('param.size') : 10;
                    $to_user_id = $user_id;
                    $limit = $pagesize * ($page - 1);

                    $where = "to_user_id = '$to_user_id'";
                    $count = Db::name('community_message')->where($where)->count();
                    $list = Db::name('community_message')->where($where)->order('isview asc,isreply asc,addtime desc')->limit($limit, $pagesize)->select();
                    Db::name('community_message')->where($where)->where('isview', 0)->update(['isview'=>1]);
                    foreach ($list as $key => $value) {
                        $list[$key]['format_time'] = date('H:i', $value['addtime']);
                        $comm_user = $this->memberInfo('id, user_name nickname, headimgurl', ['id'=>$value['user_id']]);
                        $list[$key]['user'] = $comm_user;
                    }

                    $arr['current_page'] = $page;
                    $arr['last_page'] = @ceil($count / $pagesize);
                    $arr['data'] = $list;
                    // $arr['pages'] = @ceil($count / $pagesize);
                    return datamsg(WIN, '获取成功', $arr);
                }
                
                $list = db('notification')->where($where)->field('id,title,introduce,cover,create_time,type,'.$user_id.' as uid')
                    ->paginate($size)
                    ->each(function ($item, $key) {
                        $item['create_time'] = time_ago(date('Y-m-d H:i:s', $item['create_time']));
                        //$item['cover'] = $item['cover'] ? $this->webconfig['weburl']."/".$item['cover'] : $domain."";
						$domain = config('tengxunyun')['cos_domain'];
						//$info['cover'] = str_replace("/liveroom/",$domain."/liveroom/",$info['cover']);
						//echo $domain; 
						if(strpos($item['cover'],'liveroom/') !== false){ 
							$item['cover'] = str_replace("liveroom/",$domain."/liveroom/",$item['cover']);
						}else{
							$item['cover'] = $item['cover'] ? $this->webconfig['weburl']."/".$item['cover'] : $domain."/liveroom/2019-09-11/位图备份 2@2x.png";
						}
						
						//判断图片是否存在
						// if(file_get_contents($item['cover']) == false){
						// 	$item['cover'] = $this->webconfig['weburl'].'/uploads/default_notice.png';
						// }
						
						//是否已读
						$reads = Db::name('notification_read')->field('id')->where('user_id',$item['uid'])->where('notification_id',$item['id'])->find();
						if($reads){
							$item['is_read'] = 1;
						}else{
							$item['is_read'] = 0;
						}
                        
                        return $item;
                    });
                return datamsg(WIN, '获取成功', $list);

            }else{
                return datamsg(LOSE,$result['mess']);
            }

            
        }else{
            return datamsg(LOSE,'请求方式不正确');
        }
    }
	
	//消息详情
	public function notificationInfo(){
	    if(request()->isPost()){
	        if(input('post.token')){
	            $gongyong = new GongyongMx();
	            $result = $gongyong->apivalidate();
	            if($result['status'] == 200){
	                $user_id = $result['user_id'];
					
					if(input('post.id')){
						
						$id = input('post.id');
						
						$info = Db::name('notification')->field('id,title,cover,introduce,content,type,status,edit_time,create_time')->where('id',$id)->find();
						if($info){
							
							//消息设为已读
							$isread = Db::name('notification_read')->field('id')->where('notification_id',$info['id'])->where('user_id',$user_id)->find();
							if(empty($isread)){
								$data['user_id'] = $user_id;
								$data['notification_id'] = $info['id'];
								$data['create_time'] = time();
								//print_r($data);die();
								Db::name('notification_read')->insert($data);
							}
							
							
							$info['cover'] = $this->webconfig['weburl']."/".$info['cover'];
							
							$info['edit_time'] = time_ago(date('Y-m-d H:i:s', $info['edit_time']));
							$info['create_time'] = time_ago(date('Y-m-d H:i:s', $info['create_time']));
							
							$info['content'] = str_replace("/public/",$this->webconfig['weburl']."/public/",$info['content']);
							
							$value = array('status'=>200,'mess'=>'获取消息详情成功','data'=>$info);
						    
						}else{
							$value = array('status'=>400,'mess'=>'获取消息详情失败','data'=>array('status'=>400));
						}
					}else{
						$value = array('status'=>400,'mess'=>'缺少ID','data'=>array('status'=>400));
					}
				}else{
					$value = $result;
				}
			}else{
				$value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
			}
		}else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
	}
	
	//客服列表
	public function serviceList(){
		if(request()->isPost()){
			if(input('post.token')){
				$gongyong = new GongyongMx();
				$result = $gongyong->apivalidate();
				if($result['status'] == 200){
					$token = input('post.token');
					$user_id = $result['user_id'];
					$msglist = Db::name('chat_message')->where('fromid',input('post.token'))->whereOr('toid',input('post.token'))->select();
					//dump( Db::getLastSql());
					//print_r($msglist);
					foreach($msglist as $k=>$v){
						if(!empty($v['fromid']) && !empty($v['toid']) && $v['toid'] != 'null'){
							$kflist[$k]['id'] = $v['id'];
							$kflist[$k]['msg'] = $v['message'];
							$info = Db::query("select * from v_kefu where token = '".$v['fromid']."' OR token='".$v['toid']."'");
							$info1 = $info[0];
							$kflist[$k]['info'] = $info1;
							
						}
						
						//dump( Db::getLastSql());
						
					}
					
					$value = array('status'=>200,'mess'=>'获取客服成功','data'=>$kflist);
				}else{
					$value = $result;
				}
			}else{
				$value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
			}
		}else{
			$value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
		}
		
		return json($value);
	}

   
}