<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "unpaid_annual_fee".
 *
 * @property integer $id
 * @property string $patentAjxxbID
 * @property integer $amount
 * @property string $fee_type
 * @property integer $fee_category
 * @property string $due_date
 * @property integer $status
 * @property integer $paid_at
 */
class UnpaidAnnualFee extends \yii\db\ActiveRecord
{
    /**
     * 未支付
     */
    const UNPAID = 0;

    /**
     * 已支付
     */
    const PAID = 1;

    /**
     * 已完成
     */
    const FINISHED = 2;

    // category 1=年费 2=滞纳金 10=其他 新增分类可以依次排号
    const ANNUAL_FEE = 1;
    const OVERDUE_FINE = 2;
    const OTHER_FEE = 10;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'unpaid_annual_fee';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['patentAjxxbID', 'amount', 'fee_type', 'due_date'], 'required'],
            [['amount', 'fee_category', 'status', 'paid_at'], 'integer'],
            [['patentAjxxbID'], 'string', 'max' => 20],
            [['fee_type'], 'string', 'max' => 100],
            [['due_date'], 'string', 'max' => 14],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'patentAjxxbID' => Yii::t('app', 'Patent Ajxxb ID'),
            'amount' => Yii::t('app', 'Amount'),
            'fee_type' => Yii::t('app', 'Fee Type'),
            'fee_category' => Yii::t('app', 'Fee Category'),
            'due_date' => Yii::t('app', 'Due Date'),
            'status' => Yii::t('app', 'Status'),
            'paid_at' => Yii::t('app', 'Paid At'),
        ];
    }

    /**
     * 获取相关专利
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPatent()
    {
        return $this->hasOne(Patents::className(), ['patentAjxxbID' => 'patentAjxxbID']);
    }
}
