<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "notification".
 *
 * @property integer $id
 * @property integer $sender
 * @property integer $receiver
 * @property string $content
 * @property integer $type
 * @property integer $createdAt
 * @property integer $status
 */
class Notification extends \yii\db\ActiveRecord
{
    const TYPE_FEEDBACK = 1;
    const TYPE_EMAIL = 2;
    const TYPE_NOTICE = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notification';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sender', 'receiver', 'content'], 'required'],
            [['sender', 'receiver', 'type'], 'integer'],
            [['content'], 'string', 'max' => 1000],
            ['createdAt', 'default', 'value' => time()],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sender' => Yii::t('app', 'Sender'),
            'receiver' => Yii::t('app', 'Receiver'),
            'content' => Yii::t('app', 'Content'),
            'type' => Yii::t('app', 'Type'),
            'createdAt' => Yii::t('app', 'Created At'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    public static function ignore()
    {
        Notification::updateAll(['status' => 1], ['receiver' => Yii::$app->user->id, 'status' => 0]);
    }

    public function getUser()
    {
        return $this->hasOne(Users::className(), ['userID' => 'sender']);
    }
}
