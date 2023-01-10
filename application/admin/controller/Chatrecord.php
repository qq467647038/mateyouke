<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use think\Loader;
use think\Validate;

class Chatrecord extends Common{
    //栏目列表
    public function lst(){
        $list = Db::name('chat_customer')
            ->alias('c')
            ->join('customer_service s','c.shop_id=s.shop_id','left')
            ->join('rxin r','r.user_id = s.user_id')
            ->where('c.status',1)
            ->where('s.status',1)
            ->where('is_delete','-1')
            ->select();
//        $list = Db::name('member')->where(['id'=>1])->order('id asc')->select();
        foreach($list as $key=>&$value){
            $value['headimgurl'] = $this->webconfig['weburl'].'/'.$value['headimgurl'];
        }
//        dump($this->dealEmoji());die;
        $this->assign('list', $list);
        return $this->fetch();     
    }




    /**
     * 聊天记录
     */
    public function chatlist(){
        $id = input('param.id');
        $token = db('rxin')->where('user_id',$id)->value('token');
        $member = db('chat_message')
            ->alias('m')
            ->field('r.user_id as id,u.user_name,u.headimgurl,u.summary,m.fromid as token,m.toid')
            ->join('rxin r','m.fromid = r.token','left')
            ->join('member u','r.user_id = u.id','left')
            ->where(['toid'=>$token])
            ->group('fromid')
            ->select();

        $this->assign([
            'cid'=>1,
           'member'=>$member,
        ]);
        return $this->fetch();
    }


    /**
     * 获取个人的聊天记录
     */
    public function getmessage(){
        $token = input('post.token');
        $user_name = input('post.user_name');
        $dataid = input('post.dataid');
        $toid = input('post.toid');
        $pageNum = input('post.pageNum');
        $pageSize = input('post.pageSize');
//        $toid = 'cxy365';
        $sqlstr="SELECT id,fromid,toid,message,createtime, CASE WHEN fromid = '$token' THEN 'you' ELSE 'me' END as usertype  FROM `sp_chat_message` WHERE fromid IN('".$token."') AND toid IN('".$toid."') or fromid IN('".$toid."') AND toid IN('".$token."') ORDER BY createtime DESC ";
        $list = Db::query($sqlstr);
//        echo $sqlstr;die;

        $html = '';
        $html.=' <div class="top"><span>To: <span class="name">'.$user_name.'</span></span></div>';
        $html.= "<div id=\"chat{$dataid}\" class=\"chat chat{$dataid}\" data-chat=\"person{$dataid}\">";
        foreach($list as $key=>$value){
            $flag = $value['usertype'];

            $html .= '<div class="bubble '.$flag.'">';
            $html .= $this->dealEmoji($value['message']);
            $html .= '<div style="color:#5f5f5f;font-size:10px;margin-top:10px;margin-left:5px;">'.date('Y-m-d H:i:s',$value['createtime']).'</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return json(['status'=>1,'data'=>$html]);


    }

    /**
     * @function处理聊天的表情以及图片输出
     * @param $msg内容
     * @author Feifan.Chen <1057286925@qq.com>
     * @return string
     */
    public function dealEmoji($msg){
        $url = 'https://cxy365-file.obs.cn-south-1.myhuaweicloud.com/static/images/img/face/emoji/';
        $msg_list = explode(']',$msg);
        $emojiList = [
            "face[微笑]"=>"0.gif" ,
            "face[嘻嘻]"=>"1.gif" ,
            "face[哈哈]"=>"2.gif" ,
            "face[可爱]"=>"3.gif" ,
            "face[可怜]"=>"4.gif" ,
            "face[挖鼻]"=>"5.gif" ,
            "face[吃惊]"=>"6.gif" ,
            "face[害羞]"=>"7.gif" ,
            "face[挤眼]"=>"8.gif" ,
            "face[闭嘴]"=>"9.gif" ,
            "face[鄙视]"=>"10.gif" ,
            "face[爱你]"=>"11.gif" ,
            "face[泪]"=>"12.gif" ,
            "face[偷笑]"=>"13.gif" ,
            "face[亲亲]"=>"14.gif" ,
            "face[生病]"=>"15.gif" ,
            "face[太开心]"=>"16.gif" ,
            "face[白眼]"=>"17.gif" ,
            "face[右哼哼]"=>"18.gif" ,
            "face[左哼哼]"=>"19.gif" ,
            "face[嘘]"=>"20.gif" ,
            "face[衰]"=>"21.gif" ,
            "face[委屈]"=>"22.gif" ,
            "face[吐]"=>"23.gif" ,
            "face[哈欠]"=>"24.gif" ,
            "face[抱抱]"=>"25.gif" ,
            "face[怒]"=>"26.gif" ,
            "face[疑问]"=>"27.gif" ,
            "face[馋嘴]"=>"28.gif" ,
            "face[拜拜]"=>"29.gif" ,
            "face[思考]"=>"30.gif" ,
            "face[汗]"=>"31.gif" ,
            "face[困]"=>"32.gif" ,
            "face[睡]"=>"33.gif" ,
            "face[钱]"=>"34.gif" ,
            "face[失望]"=>"35.gif" ,
            "face[酷]"=>"36.gif" ,
            "face[色]"=>"37.gif" ,
            "face[哼]"=>"38.gif" ,
            "face[鼓掌]"=>"39.gif" ,
            "face[晕]"=>"40.gif" ,
            "face[悲伤]"=>"41.gif" ,
            "face[抓狂]"=>"42.gif" ,
            "face[黑线]"=>"43.gif" ,
            "face[阴险]"=>"44.gif" ,
            "face[怒骂]"=>"45.gif" ,
            "face[互粉]"=>"46.gif" ,
            "face[心]"=>"47.gif" ,
            "face[伤心]"=>"48.gif" ,
            "face[猪头]"=>"49.gif" ,
            "face[熊猫]"=>"50.gif" ,
            "face[兔子]"=>"51.gif" ,
            "face[ok]"=>"52.gif" ,
            "face[耶]"=>"53.gif" ,
            "face[good]"=>"54.gif" ,
            "face[NO]"=>"55.gif" ,
            "face[赞]"=>"56.gif" ,
            "face[来]"=>"57.gif" ,
            "face[弱]"=>"58.gif" ,
            "face[草泥马]"=>"59.gif" ,
            "face[神马]"=>"60.gif" ,
            "face[囧]"=>"61.gif" ,
            "face[浮云]"=>"62.gif" ,
            "face[给力]"=>"63.gif" ,
            "face[围观]"=>"64.gif" ,
            "face[威武]"=>"65.gif" ,
            "face[奥特曼]"=>"66.gif" ,
            "face[礼物]"=>"67.gif" ,
            "face[钟]"=>"68.gif" ,
            "face[话筒]"=>"69.gif" ,
            "face[蜡烛]"=>"70.gif" ,
            "face[蛋糕]"=>"71.gif"
        ];
        $arr = [];
        $html = '<div>';
        foreach ($msg_list as $k=>&$v){
                //判断是否为表情 是则拼接链接输出
                if(strpos($v,'face') !== false){
                    $v.=']';
                    $arr[] = $url.$emojiList[$v];
                    $emoji_url = $url.$emojiList[$v];
                    $html.="<image src=\"$emoji_url\"></image>";
                }else{
                    $arr[] = $v;
                    //判断是否是图片url 反之为文字
                    if (filter_var($v,FILTER_VALIDATE_URL)){
                        $html.="<image src=\"$v\" style='width: 50%;height: auto'></image>";
                    }else{
                        //是图文消息即商品信息
                        $tuwen = json_decode($v,1);
                        if ($tuwen){
                            $html.=$v;
                        }else{
                            $html.=$v;
                        }

                    }
                }
            }
            $html.='</div>';

        return $html;
    }

}