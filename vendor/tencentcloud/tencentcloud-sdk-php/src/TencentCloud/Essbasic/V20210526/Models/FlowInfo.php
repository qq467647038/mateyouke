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
namespace TencentCloud\Essbasic\V20210526\Models;
use TencentCloud\Common\AbstractModel;

/**
 * 此结构体 (FlowInfo) 用于描述签署流程信息。
 *
 * @method string getFlowName() 获取合同名字，最大长度200个字符
 * @method void setFlowName(string $FlowName) 设置合同名字，最大长度200个字符
 * @method integer getDeadline() 获取签署截止时间戳，超过有效签署时间则该签署流程失败，默认一年
 * @method void setDeadline(integer $Deadline) 设置签署截止时间戳，超过有效签署时间则该签署流程失败，默认一年
 * @method string getTemplateId() 获取模板ID
 * @method void setTemplateId(string $TemplateId) 设置模板ID
 * @method array getFlowApprovers() 获取多个签署人信息，最大支持50个签署方
 * @method void setFlowApprovers(array $FlowApprovers) 设置多个签署人信息，最大支持50个签署方
 * @method array getFormFields() 获取表单K-V对列表
 * @method void setFormFields(array $FormFields) 设置表单K-V对列表
 * @method string getCallbackUrl() 获取回调地址，最大长度1000个字符
 * @method void setCallbackUrl(string $CallbackUrl) 设置回调地址，最大长度1000个字符
 * @method string getFlowType() 获取合同类型，如：1. “劳务”；2. “销售”；3. “租赁”；4. “其他”，最大长度200个字符
 * @method void setFlowType(string $FlowType) 设置合同类型，如：1. “劳务”；2. “销售”；3. “租赁”；4. “其他”，最大长度200个字符
 * @method string getFlowDescription() 获取合同描述，最大长度1000个字符
 * @method void setFlowDescription(string $FlowDescription) 设置合同描述，最大长度1000个字符
 * @method string getCustomerData() 获取渠道的业务信息，最大长度1000个字符
 * @method void setCustomerData(string $CustomerData) 设置渠道的业务信息，最大长度1000个字符
 * @method string getCustomShowMap() 获取合同显示的页卡模板，说明：只支持{合同名称}, {发起方企业}, {发起方姓名}, {签署方N企业}, {签署方N姓名}，且N不能超过签署人的数量，N从1开始
 * @method void setCustomShowMap(string $CustomShowMap) 设置合同显示的页卡模板，说明：只支持{合同名称}, {发起方企业}, {发起方姓名}, {签署方N企业}, {签署方N姓名}，且N不能超过签署人的数量，N从1开始
 * @method array getCcInfos() 获取被抄送人的信息列表，抄送功能暂不开放
 * @method void setCcInfos(array $CcInfos) 设置被抄送人的信息列表，抄送功能暂不开放
 */
class FlowInfo extends AbstractModel
{
    /**
     * @var string 合同名字，最大长度200个字符
     */
    public $FlowName;

    /**
     * @var integer 签署截止时间戳，超过有效签署时间则该签署流程失败，默认一年
     */
    public $Deadline;

    /**
     * @var string 模板ID
     */
    public $TemplateId;

    /**
     * @var array 多个签署人信息，最大支持50个签署方
     */
    public $FlowApprovers;

    /**
     * @var array 表单K-V对列表
     */
    public $FormFields;

    /**
     * @var string 回调地址，最大长度1000个字符
     */
    public $CallbackUrl;

    /**
     * @var string 合同类型，如：1. “劳务”；2. “销售”；3. “租赁”；4. “其他”，最大长度200个字符
     */
    public $FlowType;

    /**
     * @var string 合同描述，最大长度1000个字符
     */
    public $FlowDescription;

    /**
     * @var string 渠道的业务信息，最大长度1000个字符
     */
    public $CustomerData;

    /**
     * @var string 合同显示的页卡模板，说明：只支持{合同名称}, {发起方企业}, {发起方姓名}, {签署方N企业}, {签署方N姓名}，且N不能超过签署人的数量，N从1开始
     */
    public $CustomShowMap;

    /**
     * @var array 被抄送人的信息列表，抄送功能暂不开放
     */
    public $CcInfos;

    /**
     * @param string $FlowName 合同名字，最大长度200个字符
     * @param integer $Deadline 签署截止时间戳，超过有效签署时间则该签署流程失败，默认一年
     * @param string $TemplateId 模板ID
     * @param array $FlowApprovers 多个签署人信息，最大支持50个签署方
     * @param array $FormFields 表单K-V对列表
     * @param string $CallbackUrl 回调地址，最大长度1000个字符
     * @param string $FlowType 合同类型，如：1. “劳务”；2. “销售”；3. “租赁”；4. “其他”，最大长度200个字符
     * @param string $FlowDescription 合同描述，最大长度1000个字符
     * @param string $CustomerData 渠道的业务信息，最大长度1000个字符
     * @param string $CustomShowMap 合同显示的页卡模板，说明：只支持{合同名称}, {发起方企业}, {发起方姓名}, {签署方N企业}, {签署方N姓名}，且N不能超过签署人的数量，N从1开始
     * @param array $CcInfos 被抄送人的信息列表，抄送功能暂不开放
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
        if (array_key_exists("FlowName",$param) and $param["FlowName"] !== null) {
            $this->FlowName = $param["FlowName"];
        }

        if (array_key_exists("Deadline",$param) and $param["Deadline"] !== null) {
            $this->Deadline = $param["Deadline"];
        }

        if (array_key_exists("TemplateId",$param) and $param["TemplateId"] !== null) {
            $this->TemplateId = $param["TemplateId"];
        }

        if (array_key_exists("FlowApprovers",$param) and $param["FlowApprovers"] !== null) {
            $this->FlowApprovers = [];
            foreach ($param["FlowApprovers"] as $key => $value){
                $obj = new FlowApproverInfo();
                $obj->deserialize($value);
                array_push($this->FlowApprovers, $obj);
            }
        }

        if (array_key_exists("FormFields",$param) and $param["FormFields"] !== null) {
            $this->FormFields = [];
            foreach ($param["FormFields"] as $key => $value){
                $obj = new FormField();
                $obj->deserialize($value);
                array_push($this->FormFields, $obj);
            }
        }

        if (array_key_exists("CallbackUrl",$param) and $param["CallbackUrl"] !== null) {
            $this->CallbackUrl = $param["CallbackUrl"];
        }

        if (array_key_exists("FlowType",$param) and $param["FlowType"] !== null) {
            $this->FlowType = $param["FlowType"];
        }

        if (array_key_exists("FlowDescription",$param) and $param["FlowDescription"] !== null) {
            $this->FlowDescription = $param["FlowDescription"];
        }

        if (array_key_exists("CustomerData",$param) and $param["CustomerData"] !== null) {
            $this->CustomerData = $param["CustomerData"];
        }

        if (array_key_exists("CustomShowMap",$param) and $param["CustomShowMap"] !== null) {
            $this->CustomShowMap = $param["CustomShowMap"];
        }

        if (array_key_exists("CcInfos",$param) and $param["CcInfos"] !== null) {
            $this->CcInfos = [];
            foreach ($param["CcInfos"] as $key => $value){
                $obj = new CcInfo();
                $obj->deserialize($value);
                array_push($this->CcInfos, $obj);
            }
        }
    }
}
