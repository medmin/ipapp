<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\web\ServerErrorHttpException;
use yii\db\Transaction;

/**
 * This is the model class for table "orders".
 *
 * @property string $trade_no
 * @property string $out_trade_no
 * @property integer $payment_type
 * @property integer $user_id
 * @property string $goods_id
 * @property string $detailed_expenses
 * @property integer $goods_type
 * @property string $amount
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $paid_at
 * @property integer $status
 */
class Orders extends \yii\db\ActiveRecord
{
    /**
     * 微信支付
     */
    const TYPE_WXPAY = 1;

    /**
     * 支付宝支付
     */
    const TYPE_ALIPAY = 2;

    /**
     * 专利付款
     */
    const USE_PATENT = 1;

    /**
     * 商标付款 trademark
     */
    const USE_TM = 2;

    /**
     * 待支付
     */
    const STATUS_PENDING = 0;

    /**
     * 已付款
     */
    const STATUS_PAID = 1;

    /**
     * 未支付(过期)
     */
    const STATUS_UNPAID = 2;

    /**
     * 已完成(我方已缴费)
     */
    const STATUS_FINISHED = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'orders';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['trade_no', 'user_id', 'goods_id', 'amount'], 'required'],
            [['payment_type', 'user_id', 'goods_type', 'status'], 'integer'],
            [['amount'], 'number'],
            [['trade_no', 'out_trade_no'], 'string', 'max' => 100],
            [['goods_id'], 'string', 'max' => 255],
            [['detailed_expenses'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'trade_no' => Yii::t('app', 'Trade No'),
            'out_trade_no' => Yii::t('app', 'Out Trade No'),
            'payment_type' => Yii::t('app', 'Payment Type'),
            'user_id' => Yii::t('app', 'User ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'detailed_expenses' => Yii::t('app', 'Detailed Expenses'),
            'goods_type' => Yii::t('app', 'Goods Type'),
            'amount' => Yii::t('app', 'Amount'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'paid_at' => Yii::t('app', 'Paid At'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    public static function status()
    {
        return [
            self::STATUS_PENDING => '未付款',
            self::STATUS_PAID => '已支付',
            self::STATUS_UNPAID => '已过期',
            self::STATUS_FINISHED =>'已完成',
        ];
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * 关联用户
     * 
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['userID' => 'user_id']);
    }
}
