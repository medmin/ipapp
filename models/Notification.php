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
    const TYPE_WECHAT_NOTICE = 4;

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

    /**
     * 微信模板消息的日志记录
     *
     * @param $id
     * @param $application_no
     * @param array $data
     */
    public static function saveWechatNoticeLog($id, $application_no, array $data)
    {
        $model = new self();
        $model->sender = 0;
        $model->receiver = $id;
        $model->type = self::TYPE_WECHAT_NOTICE;
        $model->content = '专利年费缴费提醒，专利号：'.$application_no.'，专利名称：'.$data['keyword1'].'，缴费年次：'.$data['keyword2'].'，应缴金额：'.$data['keyword3'].'，最迟缴费日：'.$data['keyword4'].'，剩余天数：'.$data['keyword5'];
//        $model->status = 1;
        $model->save();
    }

    /**
     * 获取发送方用户信息
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['userID' => 'sender']);
    }
}
