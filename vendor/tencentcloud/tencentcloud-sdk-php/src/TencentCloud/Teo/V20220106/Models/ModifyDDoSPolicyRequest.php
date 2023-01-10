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
namespace TencentCloud\Teo\V20220106\Models;
use TencentCloud\Common\AbstractModel;

/**
 * ModifyDDoSPolicy请求参数结构体
 *
 * @method integer getPolicyId() 获取策略组ID
 * @method void setPolicyId(integer $PolicyId) 设置策略组ID
 * @method string getZoneId() 获取一级域名
 * @method void setZoneId(string $ZoneId) 设置一级域名
 * @method DdosRule getDdosRule() 获取DDoS具体防护配置
 * @method void setDdosRule(DdosRule $DdosRule) 设置DDoS具体防护配置
 */
class ModifyDDoSPolicyRequest extends AbstractModel
{
    /**
     * @var integer 策略组ID
     */
    public $PolicyId;

    /**
     * @var string 一级域名
     */
    public $ZoneId;

    /**
     * @var DdosRule DDoS具体防护配置
     */
    public $DdosRule;

    /**
     * @param integer $PolicyId 策略组ID
     * @param string $ZoneId 一级域名
     * @param DdosRule $DdosRule DDoS具体防护配置
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
        if (array_key_exists("PolicyId",$param) and $param["PolicyId"] !== null) {
            $this->PolicyId = $param["PolicyId"];
        }

        if (array_key_exists("ZoneId",$param) and $param["ZoneId"] !== null) {
            $this->ZoneId = $param["ZoneId"];
        }

        if (array_key_exists("DdosRule",$param) and $param["DdosRule"] !== null) {
            $this->DdosRule = new DdosRule();
            $this->DdosRule->deserialize($param["DdosRule"]);
        }
    }
}
