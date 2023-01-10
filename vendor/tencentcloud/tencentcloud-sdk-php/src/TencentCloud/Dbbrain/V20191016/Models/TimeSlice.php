<?php
/*
 * Copyright (c) 2017-2018 THL A29 Limited, a Tencent company. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace TencentCloud\Dbbrain\V20191016\Models;
use TencentCloud\Common\AbstractModel;

/**
 * 单位时间间隔内的慢日志统计
 *
 * @method integer getCount() 获取总数
 * @method void setCount(integer $Count) 设置总数
 * @method integer getTimestamp() 获取统计开始时间
 * @method void setTimestamp(integer $Timestamp) 设置统计开始时间
 */
class TimeSlice extends AbstractModel
{
    /**
     * @var integer 总数
     */
    public $Count;

    /**
     * @var integer 统计开始时间
     */
    public $Timestamp;

    /**
     * @param integer $Count 总数
     * @param integer $Timestamp 统计开始时间
     */
    function __construct()
    {

    }

    /**
     * For internal only. DO NOT USE IT.
     */
    public function deserialize($param)
    {
        if ($param === null) {
            return;
        }
        if (array_key_exists("Count",$param) and $param["Count"] !== null) {
            $this->Count = $param["Count"];
        }

        if (array_key_exists("Timestamp",$param) and $param["Timestamp"] !== null) {
            $this->Timestamp = $param["Timestamp"];
        }
    }
}
