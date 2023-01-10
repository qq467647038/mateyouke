<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;
use app\apicloud\model\Goods as GoodsModel;
use think\Request;
/**
 * @title 首页
 * @description 首页相关接口
 */
class WechatCard extends Common{



    //测试
    //wx85f3b0cca7e0cf0b
    //79371fd2889c75ce6405e08d037060aa

    //正式
    //wxb0e56eff07b8272d
    //a4b96fbb7a98d82b60a4ac9e7e5759fe
    protected $config = [
        'app_id' => 'wxb0e56eff07b8272d',
        'secret' => 'a4b96fbb7a98d82b60a4ac9e7e5759fe',
        'response_type' => 'array',
    ];

    protected $dataCube;
    protected $material;
    protected $card;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $factory = new \EasyWeChat\Factory();
        $app = $factory::officialAccount($this->config);
        $this->card = new \EasyWeChat\OfficialAccount\Card\Client($app,null);
        $this->dataCube = new \EasyWeChat\OfficialAccount\DataCube\Client($app,null);
        $this->material = new \EasyWeChat\OfficialAccount\Material\Client($app,null);
    }

    public function uploadWechat()
    {
        $res = $this->material->upload('news_image','./uploads/20201111/41c17b1c9747160578a15b06c0d77491.png',[]);
       halt($res);
    }

    public function up(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
                echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                echo $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                echo $info->getFilename();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    //拉取会员卡概况数据接口
    public function getCardIndfo(){
        $res = $this->dataCube->memberCardSummary('2020-11-01','2020-11-08',0);
        halt($res);
    }

    public function cardDetail($cardId = null){
        $res = $this->card->get($cardId);
        halt($res);
    }

    public function createMemberCard(){
        $this->card->create('','');
    }
    //查看会员卡/优惠券列表
    public function cardList(){
        $pagenum = 1;
        $perpage = 10;
        $offset = ($pagenum-1)*$perpage;
        $data = [
            'status_list' => ['CARD_STATUS_VERIFY_OK']
        ];
        $res = $this->card->list('list',$pagenum,$offset,$data);
        if ($res['errcode'] == 0 && $res['errmsg'] == 'ok'){

        }
        dump($this->cardDetail('p8AVHs03gmK1aK-RBmrSdh8MTQ2U'));die;
        halt($res);
    }
    public function categories(){
        $data = $this->card->categories();
        halt($data);
    }

    protected function getCardType($cardNumber){
        $list = [
            '团购券'=> 'GROUPON',
            '折扣券'=> 'DISCOUNT',
            '礼品券'=> 'GIFT',
            '代金券'=> 'CASH',
            '通用券'=> 'GENERAL_COUPON',
            '会员卡'=> 'MEMBER_CARD',
            '景点门票'=> 'SCENIC_TICKET',
            '电影票'=> 'MOVIE_TICKET',
            '飞机票'=> 'BOARDING_PASS',
            '会议门票'=> 'MEETING_TICKET',
            '汽车票'=> 'BUS_TICKET',
        ];
        foreach ($list as $k=>$v){
            if ($cardNumber == $v){
                return $k;
            }
        }
    }









}
