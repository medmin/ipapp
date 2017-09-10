<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "unpaid_annual_fee".
 *
 * @property string $patentAjxxbID
 * @property string $patentApplicationNo
 * @property integer $amount
 * @property string $fee_type
 * @property string $due_date
 */
class UnpaidAnnualFee extends \yii\db\ActiveRecord
{
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
            [['patentAjxxbID', 'patentApplicationNo', 'amount', 'fee_type', 'due_date'], 'required'],
            [['amount'], 'integer'],
            [['patentAjxxbID'], 'string', 'max' => 20],
            [['patentApplicationNo'], 'string', 'max' => 40],
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
            'patentAjxxbID' => Yii::t('app', 'Patent Ajxxb ID'),
            'patentApplicationNo' => Yii::t('app', 'Patent Application No'),
            'amount' => Yii::t('app', 'Amount'),
            'fee_type' => Yii::t('app', 'Fee Type'),
            'due_date' => Yii::t('app', 'Due Date'),
        ];
    }
}
