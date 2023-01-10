<?php

namespace app\apicloud\controller;

use think\Db;

class Version extends Common

{

    /**

     *

     * 对比所有版本

     */

    public function versions()

    {

        $data = input();

        empty($data['version']) && datamsg(0, '非法操作');

        $infos = db('app_versions')->where(array('versions' => ['>', $data['version']]))->order('versions desc')->field('id,urls,ios_url,content')->find();

        empty($infos) && datamsg(0, '该版本为最新版本');

        empty($data['client']) && datamsg(0, '非法操作');
        
        if ($data['client'] == 'android') {
            $result['urls'] = "https://" . $_SERVER['SERVER_NAME'] .'/'. $infos['urls'];
        } elseif ($data['client'] == 'ios') {
            if (!empty($infos['ios_url'])) {
                $result['urls'] = $infos['ios_url'];
            } else {
                datamsg(0, '该版本为最新版本');
            }
        } else {
            $result['urls'] = "";
        }

        $result['content']=$infos['content'];

        datamsg(1, '成功', $result);

    }

}