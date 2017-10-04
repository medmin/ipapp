<?php

namespace app\models;

use Yii;
use yii\helpers\Json;

/**
 * This is the model class for table "patents".
 *
 * @property integer $patentID
 * @property string $patentAjxxbID
 * @property string $patentEacCaseNo
 * @property string $patentType
 * @property integer $patentUserID
 * @property string $patentUsername
 * @property integer $patentUserLiaisonID
 * @property string $patentUserLiaison
 * @property string $patentAgent
 * @property string $patentProcessManager
 * @property string $patentTitle
 * @property string $patentApplicationNo
 * @property string $patentPatentNo
 * @property string $patentNote
 * @property string $patentApplicationDate
 * @property integer $patentFeeManagerUserID
 * @property string $patentCaseStatus
 * @property string $patentApplicationInstitution
 * @property string $patentInventors
 * @property string $patentAgency
 * @property string $patentAgencyAgent
 * @property string $patentFeeDueDate
 * @property string $patentAlteredItems
 * @property integer $UnixTimestamp
 *
 * @property Patentevents[] $patentevents
 * @property Patentfiles[] $patentfiles
 */
class Patents extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'patents';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['patentAjxxbID', 'patentEacCaseNo', 'patentType', 'UnixTimestamp'], 'required'],
            ['patentUserID', 'required', 'message' => '需要取消用户绑定请填写 0 '],
            [['patentUserID', 'patentUserLiaisonID', 'UnixTimestamp'], 'integer'],
            [['patentAjxxbID', 'patentEacCaseNo'], 'string', 'max' => 20],
            [['patentType'], 'string', 'max' => 8],
            [['patentUsername'], 'string', 'max' => 16],
            [['patentUserLiaison', 'patentAgent', 'patentProcessManager'], 'string', 'max' => 24],
            [['patentTitle', 'patentApplicationNo', 'patentPatentNo'], 'string', 'max' => 40],
            [['patentNote', 'patentInventors'], 'string', 'max' => 1000],
            [['patentApplicationDate', 'patentFeeDueDate'], 'string', 'max' => 14],
            [['patentAjxxbID'], 'unique'],
            [['patentAlteredItems'], 'string'],
            [['patentCaseStatus', 'patentApplicationInstitution', 'patentAgency', 'patentAgencyAgent'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'patentID' => Yii::t('app', 'Patent ID'),
            'patentAjxxbID' => Yii::t('app', 'Patent Ajxxb ID'),
            'patentEacCaseNo' => Yii::t('app', 'Patent Eac Case No'),
            'patentType' => Yii::t('app', 'Patent Type'),
            'patentUserID' => Yii::t('app', 'Patent User ID'),
            'patentUsername' => Yii::t('app', 'Patent Username'),
            'patentUserLiaisonID' => Yii::t('app', 'Patent User Liaison ID'),
            'patentUserLiaison' => Yii::t('app', 'Patent User Liaison'),
            'patentAgent' => Yii::t('app', 'Patent Agent'),
            'patentProcessManager' => Yii::t('app', 'Patent Process Manager'),
            'patentTitle' => Yii::t('app', 'Patent Title'),
            'patentApplicationNo' => Yii::t('app', 'Patent Application No'),
            'patentPatentNo' => Yii::t('app', 'Patent Patent No'),
            'patentNote' => Yii::t('app', 'Patent Note'),
            'patentApplicationDate' => Yii::t('app', 'Patent Application Date'),
            'patentFeeManagerUserID' => Yii::t('app', 'Patent Fee Manager User ID'),
            'patentCaseStatus' => Yii::t('app', 'Patent Case Status'),
            'patentApplicationInstitution' => Yii::t('app', 'Patent Application Institution'),
            'patentInventors' => Yii::t('app', 'Patent Inventors'),
            'patentAgency' => Yii::t('app', 'Patent Agency'),
            'patentAgencyAgent' => Yii::t('app', 'Patent Agency Agent'),
            'patentFeeDueDate' => Yii::t('app', 'Patent Fee Due Date'),
            'patentAlteredItems' => Yii::t('app', 'Patent Altered Items'),
            'UnixTimestamp' => Yii::t('app', 'Unix Timestamp'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPatentevents()
    {
        return $this->hasMany(Patentevents::className(), ['patentAjxxbID' => 'patentAjxxbID']);
    }

    /**
     * 获取主办人联系方式
     * @return \yii\db\ActiveQuery
     */
    public function getAgentContact()
    {
        return $this->hasOne(Users::className(), ['userFullname' => 'patentAgent'])->select(['userCellphone', 'userLandline']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPatentfiles()
    {
        return $this->hasMany(Patentfiles::className(), ['patentAjxxbID' => 'patentAjxxbID']);
    }


    public function getUserOrganization()
    {
        return $this->hasOne(Users::className(), ['userID' => 'patentUserID'])->select(['userOrganization']);
    }

    /**
     * 获取年费监管用户
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeeManagers()
    {
        return $this->hasMany(Users::className(), ['userID' => 'user_id'])->viaTable('annual_fee_monitors', ['patent_id' => 'patentID']);
    }

    /**
     * 更改专利顺带更待专利事件
     *
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (!$insert) {
                if (isset($this->dirtyAttributes['patentUserID'])) {
                    if ($this->dirtyAttributes['patentUserID'] == 0) {
                        // 取消所有相关字段的值
                        $this->patentUsername = '';
                        $this->patentUserLiaisonID = 0;
                        $this->patentUserLiaison = '';
                        Patentevents::updateAll(['eventUserID' => 0, 'eventUsername' => '', 'eventUserLiaisonID' => 0, 'eventUserLiaison' => ''], ['patentAjxxbID' => $this->patentAjxxbID]);
                    } else {
                        $user = Users::findOne($this->dirtyAttributes['patentUserID']);
                        if (!$user) return false;
                        // 更新所有相关的名字的字段 - -!
                        $liaison_id = $user->userLiaisonID;
                        $liaison_name = $liaison_id ? $user->userLiaison : '';
                        $this->patentUsername = $user->userFullname;
                        $this->patentUserLiaison = $liaison_name;
                        $this->patentUserLiaisonID = $liaison_id;
                        Patentevents::updateAll(['eventUserID' => $user->userID, 'eventUsername' => $user->userFullname, 'eventUserLiaisonID' => $liaison_id, 'eventUserLiaison' => $liaison_name], ['patentAjxxbID' => $this->patentAjxxbID]);
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 生成下一年待缴年费,返回JSON数据
     *
     * 【 已弃用 】
     *
     * @return string
     */
    public function generateUnpaidAnnualFee()
    {
        $date = substr(trim($this->patentApplicationDate),-4);
        if ($date) {
            $fee_due_date = (string)((int)$date > date('md') ? date('Y') : (date('Y') + 1)) . $date;
            $fee_due_info = UnpaidAnnualFee::findOne(['patentAjxxbID' => $this->patentAjxxbID, 'due_date' => $fee_due_date]);
            if (!$fee_due_info) {
                return Json::encode(['status' => false, 'msg' => 'FAIL']);
            }
            if ($fee_due_info->status === UnpaidAnnualFee::PAID) {
                return Json::encode(['status' => false, 'msg' => 'PAID']);
            }
            return Json::encode(['status' => true, 'data' => ['amount' => $fee_due_info->amount, 'fee_type' => $fee_due_info->fee_type, 'due_date' => $fee_due_date]]);
        }
        return Json::encode(['status' => false, 'msg' => 'FAIL']);

    }

    /**
     * 获取即将到期的缴费信息(包含过期),默认90天
     *
     * @param int $days
     * @param boolean $paid  是否查找已支付（不表示已完成）的信息,默认查（方便给用户展示我们正在处理）
     * @param boolean $only_paid 是否只查找已支付的信息（这个参数是后来加上的,很鸡肋）
     * @return array
     */
    public function generateExpiredItems(int $days = 90, bool $paid = true, bool $only_paid = false)
    {
        $target_date = date('Ymd',strtotime('+' . $days . ' day'));
        if ($paid === true) {
            $pay_condition = $only_paid ? ['status' => UnpaidAnnualFee::PAID] : ['in','status',[UnpaidAnnualFee::UNPAID,UnpaidAnnualFee::PAID]];
        } else {
            $pay_condition = ['status' => UnpaidAnnualFee::UNPAID];
        }
        // where条件过滤了滞纳金，这样暂时不处理滞纳金的条目
        $items = UnpaidAnnualFee::find()
            ->where(['patentAjxxbID' => $this->patentAjxxbID])
            ->andWhere($pay_condition)
            ->andWhere(['<=','due_date',$target_date])
            ->andWhere(['<>','fee_category',UnpaidAnnualFee::OVERDUE_FINE])
            ->asArray()
            ->all();
        return $items;
    }

    /**
     * 获取过期缴费信息
     *
     * @return array
     */
    public function generateOverdueItems()
    {
        $items = UnpaidAnnualFee::find()->where(['patentAjxxbID' => $this->patentAjxxbID, 'status' => UnpaidAnnualFee::UNPAID])->andWhere(['<','due_date',date('Ymd')])->asArray()->all();
        return $items;
    }
}
