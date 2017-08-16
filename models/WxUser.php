<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "wx_user".
 *
 * @property integer $userID
 * @property string $unionid
 * @property integer $createdAt
 */
class WxUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wx_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userID', 'unionid', 'createdAt'], 'required'],
            [['userID', 'createdAt'], 'integer'],
            [['unionid'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'userID' => Yii::t('app', 'User ID'),
            'unionid' => Yii::t('app', 'Unionid'),
            'createdAt' => Yii::t('app', 'Created At'),
        ];
    }

    /**
     * 关联用户
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['userID' => 'userID']);
    }

    /**
     * 关联用户微信信息
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWxUserinfo()
    {
        return $this->hasOne(WxUserinfo::className(), ['unionid' => 'unionid']);
    }
}
