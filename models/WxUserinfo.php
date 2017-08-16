<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "wx_userinfo".
 *
 * @property integer $id
 * @property string $openid
 * @property string $unionid
 * @property string $nickname
 * @property integer $sex
 * @property string $city
 * @property string $province
 * @property string $country
 * @property string $headimgurl
 * @property integer $createdAt
 */
class WxUserinfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wx_userinfo';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['openid', 'nickname', 'sex', 'createdAt'], 'required'],
            [['sex', 'createdAt'], 'integer'],
            [['openid', 'unionid', 'nickname', 'city', 'province', 'country'], 'string', 'max' => 50],
            [['headimgurl'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'openid' => Yii::t('app', 'Openid'),
            'unionid' => Yii::t('app', 'Unionid'),
            'nickname' => Yii::t('app', 'Nickname'),
            'sex' => Yii::t('app', 'Sex'),
            'city' => Yii::t('app', 'City'),
            'province' => Yii::t('app', 'Province'),
            'country' => Yii::t('app', 'Country'),
            'headimgurl' => Yii::t('app', 'Headimgurl'),
            'createdAt' => Yii::t('app', 'Created At'),
        ];
    }
}
