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
 * @property string $due_date
 * @property integer $status
 * @property integer $paid_at
 */
class UnpaidAnnualFee extends \yii\db\ActiveRecord
{
    const UNPAID = 0;
    const PAID = 1;

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
            [['amount', 'status', 'paid_at'], 'integer'],
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
            'due_date' => Yii::t('app', 'Due Date'),
            'status' => Yii::t('app', 'Status'),
            'paid_at' => Yii::t('app', 'Paid At'),
        ];
    }
}
