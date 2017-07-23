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
 * @property integer $patentUserLiasionID
 * @property string $patentUserLiasion
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
            [['patentAjxxbID', 'patentEacCaseNo', 'patentType', 'patentUserID', 'patentUsername', 'patentUserLiasionID', 'patentUserLiasion', 'patentAgent', 'patentProcessManager', 'patentTitle', 'patentNote', 'UnixTimestamp'], 'required'],
            [['patentUserID', 'patentUserLiasionID', 'UnixTimestamp'], 'integer'],
            [['patentNote'], 'string'],
            [['patentAjxxbID', 'patentEacCaseNo'], 'string', 'max' => 20],
            [['patentType'], 'string', 'max' => 8],
            [['patentUsername'], 'string', 'max' => 16],
            [['patentUserLiasion', 'patentAgent', 'patentProcessManager'], 'string', 'max' => 24],
            [['patentTitle', 'patentApplicationNo', 'patentPatentNo'], 'string', 'max' => 40],
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
            'patentUserLiasionID' => Yii::t('app', 'Patent User Liasion ID'),
            'patentUserLiasion' => Yii::t('app', 'Patent User Liasion'),
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
}
