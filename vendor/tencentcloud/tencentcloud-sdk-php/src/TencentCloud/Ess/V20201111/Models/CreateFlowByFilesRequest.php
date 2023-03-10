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
namespace TencentCloud\Ess\V20201111\Models;
use TencentCloud\Common\AbstractModel;

/**
 * CreateFlowByFiles请求参数结构体
 *
 * @method string getFlowName() 获取签署流程名称,最大长度200个字符
 * @method void setFlowName(string $FlowName) 设置签署流程名称,最大长度200个字符
 * @method array getApprovers() 获取签署参与者信息
 * @method void setApprovers(array $Approvers) 设置签署参与者信息
 * @method array getFileIds() 获取签署pdf文件的资源编号列表，通过UploadFiles接口获取
 * @method void setFileIds(array $FileIds) 设置签署pdf文件的资源编号列表，通过UploadFiles接口获取
 * @method UserInfo getOperator() 获取调用方用户信息
 * @method void setOperator(UserInfo $Operator) 设置调用方用户信息
 * @method Agent getAgent() 获取应用号信息
 * @method void setAgent(Agent $Agent) 设置应用号信息
 * @method array getComponents() 获取经办人内容控件配置。可选类型为：
TEXT - 内容文本控件
MULTI_LINE_TEXT - 多行文本控件
CHECK_BOX - 勾选框控件
注：默认字体大小为 字号12
 * @method void setComponents(array $Components) 设置经办人内容控件配置。可选类型为：
TEXT - 内容文本控件
MULTI_LINE_TEXT - 多行文本控件
CHECK_BOX - 勾选框控件
注：默认字体大小为 字号12
 * @method integer getDeadline() 获取签署流程的签署截止时间。
值为unix时间戳,精确到秒,不传默认为当前时间一年后
 * @method void setDeadline(integer $Deadline) 设置签署流程的签署截止时间。
值为unix时间戳,精确到秒,不传默认为当前时间一年后
 * @method boolean getUnordered() 获取发送类型：
true：无序签
false：有序签
注：默认为false（有序签）
 * @method void setUnordered(boolean $Unordered) 设置发送类型：
true：无序签
false：有序签
注：默认为false（有序签）
 * @method boolean getNeedPreview() 获取是否需要预览，true：预览模式，false：非预览（默认）；
预览链接有效期300秒；
 * @method void setNeedPreview(boolean $NeedPreview) 设置是否需要预览，true：预览模式，false：非预览（默认）；
预览链接有效期300秒；
 * @method string getFlowDescription() 获取签署流程描述,最大长度1000个字符
 * @method void setFlowDescription(string $FlowDescription) 设置签署流程描述,最大长度1000个字符
 * @method string getFlowType() 获取签署流程的类型(如销售合同/入职合同等)，最大长度200个字符
 * @method void setFlowType(string $FlowType) 设置签署流程的类型(如销售合同/入职合同等)，最大长度200个字符
 * @method array getCcInfos() 获取被抄送人的信息列表。
注:此功能为白名单功能，若有需要，请联系电子签客服开白使用
 * @method void setCcInfos(array $CcInfos) 设置被抄送人的信息列表。
注:此功能为白名单功能，若有需要，请联系电子签客服开白使用
 * @method string getCustomShowMap() 获取合同显示的页卡模板，说明：只支持{合同名称}, {发起方企业}, {发起方姓名}, {签署方N企业}, {签署方N姓名}，且N不能超过签署人的数量，N从1开始
 * @method void setCustomShowMap(string $CustomShowMap) 设置合同显示的页卡模板，说明：只支持{合同名称}, {发起方企业}, {发起方姓名}, {签署方N企业}, {签署方N姓名}，且N不能超过签署人的数量，N从1开始
 */
class CreateFlowByFilesRequest extends AbstractModel
{
    /**
     * @var string 签署流程名称,最大长度200个字符
     */
    public $FlowName;

    /**
     * @var array 签署参与者信息
     */
    public $Approvers;

    /**
     * @var array 签署pdf文件的资源编号列表，通过UploadFiles接口获取
     */
    public $FileIds;

    /**
     * @var UserInfo 调用方用户信息
     */
    public $Operator;

    /**
     * @var Agent 应用号信息
     */
    public $Agent;

    /**
     * @var array 经办人内容控件配置。可选类型为：
TEXT - 内容文本控件
MULTI_LINE_TEXT - 多行文本控件
CHECK_BOX - 勾选框控件
注：默认字体大小为 字号12
     */
    public $Components;

    /**
     * @var integer 签署流程的签署截止时间。
值为unix时间戳,精确到秒,不传默认为当前时间一年后
     */
    public $Deadline;

    /**
     * @var boolean 发送类型：
true：无序签
false：有序签
注：默认为false（有序签）
     */
    public $Unordered;

    /**
     * @var boolean 是否需要预览，true：预览模式，false：非预览（默认）；
预览链接有效期300秒；
     */
    public $NeedPreview;

    /**
     * @var string 签署流程描述,最大长度1000个字符
     */
    public $FlowDescription;

    /**
     * @var string 签署流程的类型(如销售合同/入职合同等)，最大长度200个字符
     */
    public $FlowType;

    /**
     * @var array 被抄送人的信息列表。
注:此功能为白名单功能，若有需要，请联系电子签客服开白使用
     */
    public $CcInfos;

    /**
     * @var string 合同显示的页卡模板，说明：只支持{合同名称}, {发起方企业}, {发起方姓名}, {签署方N企业}, {签署方N姓名}，且N不能超过签署人的数量，N从1开始
     */
    public $CustomShowMap;

    /**
     * @param string $FlowName 签署流程名称,最大长度200个字符
     * @param array $Approvers 签署参与者信息
     * @param array $FileIds 签署pdf文件的资源编号列表，通过UploadFiles接口获取
     * @param UserInfo $Operator 调用方用户信息
     * @param Agent $Agent 应用号信息
     * @param array $Components 经办人内容控件配置。可选类型为：
TEXT - 内容文本控件
MULTI_LINE_TEXT - 多行文本控件
CHECK_BOX - 勾选框控件
注：默认字体大小为 字号12
     * @param integer $Deadline 签署流程的签署截止时间。
值为unix时间戳,精确到秒,不传默认为当前时间一年后
     * @param boolean $Unordered 发送类型：
true：无序签
false：有序签
注：默认为false（有序签）
     * @param boolean $NeedPreview 是否需要预览，true：预览模式，false：非预览（默认）；
预览链接有效期300秒；
     * @param string $FlowDescription 签署流程描述,最大长度1000个字符
     * @param string $FlowType 签署流程的类型(如销售合同/入职合同等)，最大长度200个字符
     * @param array $CcInfos 被抄送人的信息列表。
注:此功能为白名单功能，若有需要，请联系电子签客服开白使用
     * @param string $CustomShowMap 合同显示的页卡模板，说明：只支持{合同名称}, {发起方企业}, {发起方姓名}, {签署方N企业}, {签署方N姓名}，且N不能超过签署人的数量，N从1开始
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

        if (array_key_exists("Approvers",$param) and $param["Approvers"] !== null) {
            $this->Approvers = [];
            foreach ($param["Approvers"] as $key => $value){
                $obj = new ApproverInfo();
                $obj->deserialize($value);
                array_push($this->Approvers, $obj);
            }
        }

        if (array_key_exists("FileIds",$param) and $param["FileIds"] !== null) {
            $this->FileIds = $param["FileIds"];
        }

        if (array_key_exists("Operator",$param) and $param["Operator"] !== null) {
            $this->Operator = new UserInfo();
            $this->Operator->deserialize($param["Operator"]);
        }

        if (array_key_exists("Agent",$param) and $param["Agent"] !== null) {
            $this->Agent = new Agent();
            $this->Agent->deserialize($param["Agent"]);
        }

        if (array_key_exists("Components",$param) and $param["Components"] !== null) {
            $this->Components = [];
            foreach ($param["Components"] as $key => $value){
                $obj = new Component();
                $obj->deserialize($value);
                array_push($this->Components, $obj);
            }
        }

        if (array_key_exists("Deadline",$param) and $param["Deadline"] !== null) {
            $this->Deadline = $param["Deadline"];
        }

        if (array_key_exists("Unordered",$param) and $param["Unordered"] !== null) {
            $this->Unordered = $param["Unordered"];
        }

        if (array_key_exists("NeedPreview",$param) and $param["NeedPreview"] !== null) {
            $this->NeedPreview = $param["NeedPreview"];
        }

        if (array_key_exists("FlowDescription",$param) and $param["FlowDescription"] !== null) {
            $this->FlowDescription = $param["FlowDescription"];
        }

        if (array_key_exists("FlowType",$param) and $param["FlowType"] !== null) {
            $this->FlowType = $param["FlowType"];
        }

        if (array_key_exists("CcInfos",$param) and $param["CcInfos"] !== null) {
            $this->CcInfos = [];
            foreach ($param["CcInfos"] as $key => $value){
                $obj = new CcInfo();
                $obj->deserialize($value);
                array_push($this->CcInfos, $obj);
            }
        }

        if (array_key_exists("CustomShowMap",$param) and $param["CustomShowMap"] !== null) {
            $this->CustomShowMap = $param["CustomShowMap"];
        }
    }
}
