<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "patentevents".
 *
 * @property integer $eventID
 * @property string $eventRwslID
 * @property string $eventContentID
 * @property string $eventContent
 * @property string $eventNote
 * @property string $patentAjxxbID
 * @property integer $eventUserID
 * @property string $eventUsername
 * @property integer $eventUserLiaisonID
 * @property string $eventUserLiaison
 * @property string $eventCreatPerson
 * @property integer $eventCreatUnixTS
 * @property string $eventFinishPerson
 * @property integer $eventFinishUnixTS
 * @property string $eventStatus
 *
 * @property Patents $patentAjxxb
 */
class Patentevents extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'patentevents';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['eventRwslID', 'eventContentID', 'patentAjxxbID', 'eventContent'], 'required'],
            [['eventUserID', 'eventUserLiaisonID', 'eventCreatUnixTS', 'eventFinishUnixTS'], 'integer'],
            [['eventRwslID', 'eventContentID'], 'string', 'max' => 32],
            [['eventContent', 'eventNote'], 'string', 'max' => 1000],
            [['patentAjxxbID'], 'string', 'max' => 20],
            [['eventUsername'], 'string', 'max' => 16],
            [['eventUserLiaison', 'eventCreatPerson', 'eventFinishPerson'], 'string', 'max' => 24],
            [['eventStatus'], 'string', 'max' => 8],
            [['patentAjxxbID'], 'exist', 'skipOnError' => true, 'targetClass' => Patents::className(), 'targetAttribute' => ['patentAjxxbID' => 'patentAjxxbID']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'eventID' => Yii::t('app', 'Event ID'),
            'eventRwslID' => Yii::t('app', 'Event Rwsl ID'),
            'eventContentID' => Yii::t('app', 'Event Content ID'),
            'eventContent' => Yii::t('app', 'Event Content'),
            'eventNote' => Yii::t('app', 'Event Note'),
            'patentAjxxbID' => Yii::t('app', 'Patent Ajxxb ID'),
            'eventUserID' => Yii::t('app', 'Event User ID'),
            'eventUsername' => Yii::t('app', 'Event Username'),
            'eventUserLiaisonID' => Yii::t('app', 'Event User Liaison ID'),
            'eventUserLiaison' => Yii::t('app', 'Event User Liaison'),
            'eventCreatPerson' => Yii::t('app', 'Event Creat Person'),
            'eventCreatUnixTS' => Yii::t('app', 'Event Creat Unix Ts'),
            'eventFinishPerson' => Yii::t('app', 'Event Finish Person'),
            'eventFinishUnixTS' => Yii::t('app', 'Event Finish Unix Ts'),
            'eventStatus' => Yii::t('app', 'Event Status'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPatentAjxxb()
    {
        return $this->hasOne(Patents::className(), ['patentAjxxbID' => 'patentAjxxbID']);
    }
}
