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
namespace TencentCloud\Mmps\V20200710\Models;
use TencentCloud\Common\AbstractModel;

/**
 * 应用隐私合规诊断任务数据
 *
 * @method string getTaskID() 获取任务id
 * @method void setTaskID(string $TaskID) 设置任务id
 * @method integer getTaskType() 获取任务类型, 0:基础版, 1:专家版, 2:本地化
 * @method void setTaskType(integer $TaskType) 设置任务类型, 0:基础版, 1:专家版, 2:本地化
 * @method integer getTaskStatus() 获取0:默认值(待检测/待咨询), 1.检测中, 2:待评估, 3:评估中, 4:任务完成/咨询完成, 5:任务失败, 6:咨询中;
 * @method void setTaskStatus(integer $TaskStatus) 设置0:默认值(待检测/待咨询), 1.检测中, 2:待评估, 3:评估中, 4:任务完成/咨询完成, 5:任务失败, 6:咨询中;
 * @method string getTaskErrMsg() 获取错误信息
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setTaskErrMsg(string $TaskErrMsg) 设置错误信息
注意：此字段可能返回 null，表示取不到有效值。
 * @method integer getSource() 获取来源,0:默认值(私域), 1:灵犀, 2:灵鲲
 * @method void setSource(integer $Source) 设置来源,0:默认值(私域), 1:灵犀, 2:灵鲲
 * @method AppInfoItem getAppInfo() 获取应用信息
 * @method void setAppInfo(AppInfoItem $AppInfo) 设置应用信息
 * @method string getStartTime() 获取任务启动时间
 * @method void setStartTime(string $StartTime) 设置任务启动时间
 * @method string getEndTime() 获取任务完成时间(更新时间)
 * @method void setEndTime(string $EndTime) 设置任务完成时间(更新时间)
 */
class AppTaskData extends AbstractModel
{
    /**
     * @var string 任务id
     */
    public $TaskID;

    /**
     * @var integer 任务类型, 0:基础版, 1:专家版, 2:本地化
     */
    public $TaskType;

    /**
     * @var integer 0:默认值(待检测/待咨询), 1.检测中, 2:待评估, 3:评估中, 4:任务完成/咨询完成, 5:任务失败, 6:咨询中;
     */
    public $TaskStatus;

    /**
     * @var string 错误信息
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $TaskErrMsg;

    /**
     * @var integer 来源,0:默认值(私域), 1:灵犀, 2:灵鲲
     */
    public $Source;

    /**
     * @var AppInfoItem 应用信息
     */
    public $AppInfo;

    /**
     * @var string 任务启动时间
     */
    public $StartTime;

    /**
     * @var string 任务完成时间(更新时间)
     */
    public $EndTime;

    /**
     * @param string $TaskID 任务id
     * @param integer $TaskType 任务类型, 0:基础版, 1:专家版, 2:本地化
     * @param integer $TaskStatus 0:默认值(待检测/待咨询), 1.检测中, 2:待评估, 3:评估中, 4:任务完成/咨询完成, 5:任务失败, 6:咨询中;
     * @param string $TaskErrMsg 错误信息
注意：此字段可能返回 null，表示取不到有效值。
     * @param integer $Source 来源,0:默认值(私域), 1:灵犀, 2:灵鲲
     * @param AppInfoItem $AppInfo 应用信息
     * @param string $StartTime 任务启动时间
     * @param string $EndTime 任务完成时间(更新时间)
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
        if (array_key_exists("TaskID",$param) and $param["TaskID"] !== null) {
            $this->TaskID = $param["TaskID"];
        }

        if (array_key_exists("TaskType",$param) and $param["TaskType"] !== null) {
            $this->TaskType = $param["TaskType"];
        }

        if (array_key_exists("TaskStatus",$param) and $param["TaskStatus"] !== null) {
            $this->TaskStatus = $param["TaskStatus"];
        }

        if (array_key_exists("TaskErrMsg",$param) and $param["TaskErrMsg"] !== null) {
            $this->TaskErrMsg = $param["TaskErrMsg"];
        }

        if (array_key_exists("Source",$param) and $param["Source"] !== null) {
            $this->Source = $param["Source"];
        }

        if (array_key_exists("AppInfo",$param) and $param["AppInfo"] !== null) {
            $this->AppInfo = new AppInfoItem();
            $this->AppInfo->deserialize($param["AppInfo"]);
        }

        if (array_key_exists("StartTime",$param) and $param["StartTime"] !== null) {
            $this->StartTime = $param["StartTime"];
        }

        if (array_key_exists("EndTime",$param) and $param["EndTime"] !== null) {
            $this->EndTime = $param["EndTime"];
        }
    }
}
