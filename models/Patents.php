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
 * @property integer $UnixTimestamp
 *
 * @property Patentevents[] $patentevents
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
            [['patentUserID', 'patentUserLiaisonID', 'UnixTimestamp'], 'integer'],
            [['patentAjxxbID', 'patentEacCaseNo'], 'string', 'max' => 20],
            [['patentType'], 'string', 'max' => 8],
            [['patentUsername'], 'string', 'max' => 16],
            [['patentUserLiaison', 'patentAgent', 'patentProcessManager'], 'string', 'max' => 24],
            [['patentTitle', 'patentApplicationNo', 'patentPatentNo'], 'string', 'max' => 40],
            [['patentNote'], 'string', 'max' => 1000],
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

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (!$insert) {
                if (isset($this->dirtyAttributes['patentUserID'])) {
                    $user = Users::findOne($this->patentUserID);
                    if (!$user) return false;
                    $this->patentUsername = $user->userFullname;
                    Patentevents::updateAll(['eventUserID' => $user->userID, 'eventUsername' => $user->userFullname], ['patentAjxxbID' => $this->patentAjxxbID, 'eventUserID' => 0]);
                }
                if (isset($this->dirtyAttributes['patentUserLiaisonID']) && $this->dirtyAttributes['patentUserLiaisonID'] != 0) {
                    $liaison = Users::findOne($this->patentUserLiaisonID);
                    if (!$liaison) return false;
                    $this->patentUserLiaison = $liaison->userFullname;
                    Patentevents::updateAll(['eventUserLiaisonID' => $liaison->userID, 'eventUserLiaison' => $liaison->userFullname], ['patentAjxxbID' => $this->patentAjxxbID, 'eventUserLiaisonID' => 0]);
                }
            }
            return true;
        } else {
            return false;
        }
    }
}
