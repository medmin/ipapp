<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "annual_fee_monitors".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $patent_id
 * @property integer $created_at
 */
class AnnualFeeMonitors extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'annual_fee_monitors';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                ]
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'patent_id'], 'required'],
            [['user_id', 'patent_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'patent_id' => Yii::t('app', 'Patent ID'),
            'created_at' => Yii::t('app', 'Created At'),
        ];
    }
}
