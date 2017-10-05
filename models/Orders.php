<?php

namespace app\models;

use Yii;
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
 * @property integer $goods_type
 * @property string $amount
 * @property integer $created_at
 * @property integer $updated_at
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
            [['trade_no', 'payment_type', 'user_id', 'goods_id', 'amount', 'created_at', 'updated_at', 'status'], 'required'],
            [['payment_type', 'user_id', 'goods_type', 'created_at', 'updated_at', 'status'], 'integer'],
            [['amount'], 'number'],
            [['trade_no', 'out_trade_no'], 'string', 'max' => 100],
            [['goods_id'], 'string', 'max' => 255],
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
            'goods_type' => Yii::t('app', 'Goods Type'),
            'amount' => Yii::t('app', 'Amount'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
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

    //订单处理成功之后更新专利信息
    public function successProcess()
    {
        $ids = json_decode($this->goods_id,true)['patents']; // 注意格式统一
        foreach ($ids as $id) {
            $isolationLevel = Transaction::SERIALIZABLE;
            $innerTransaction = Yii::$app->db->beginTransaction($isolationLevel);
            try {
                $patent = Patents::findOne(['patentAjxxbID' => $id]);
                // 更新unpaid表,注意参数
                $unpaid = $patent->generateExpiredItems(90,false);
                $count = UnpaidAnnualFee::updateAll(['status' => UnpaidAnnualFee::PAID, 'paid_at' => $_SERVER['REQUEST_TIME']],['in', 'id', array_column($unpaid,'id')]); // 这个返回值是更改的行数
                if (!$count) {
                    throw new ServerErrorHttpException('专利费用更新出错');
                }
                // 更新patent
                $next_fee_date = ((int)substr($patent->patentFeeDueDate,0,4) + 1) . substr($patent->patentFeeDueDate,4);
                $patent->patentFeeDueDate = $next_fee_date; //TODO 如果一个专利缴完最后一年的保护费，那么他的下一年会更新到第 21 年，这样如果用户处于第 20 年的最后三个月，他的专利颜色也是会变，但是没有缴费按钮(因为没有相应的信息)，这个其实可以不用考虑，颜色变了但没有缴费说明他的专利保护期就要到了。 - -
                if (!$patent->save()) {
                    throw new ServerErrorHttpException('专利状态更新失败');
                }
                $innerTransaction->commit();
            } catch (\Exception $e) {
                $innerTransaction->rollBack();
                throw $e;
            }
        }
        return true;
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

    /**
     * 获取该订单相关的所有费用信息
     *
     * 返回格式：
     * [
     *     'patentAjxxbID' => [
     *          ['id' => xxx, 'amount' => xxx, ...],
     *          ['id' => xxx, 'amount' => xxx, ...],
     *     ],
     *     ...
     * ]
     *
     * @return array
     */
    public function getFees()
    {
        $fees_id = json_decode($this->goods_id,true)['fees'];
        $result = UnpaidAnnualFee::find()->where(['in', 'id', $fees_id])->asArray()->all();
        $fees = [];
        array_walk($result, function($value, $key) use (&$fees) {
            $fees[$value['patentAjxxbID']][] = $value;
        });
        return $fees;
    }
}
