<?php

namespace app\models;

use Yii;

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
            [['patentNote'], 'string', 'max' => 1000],
            [['patentApplicationDate'], 'string', 'max' => 14],
            [['patentAjxxbID'], 'unique'],
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
}
