<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\OrdersSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="orders-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'fieldConfig' => [
            'options' => [
                'class' => 'col-md-3'
            ]
        ],
    ]); ?>

    <?= $form->field($model, 'trade_no') ?>

    <?= $form->field($model, 'out_trade_no') ?>

    <?= $form->field($model, 'payment_type') ?>

    <?= $form->field($model, 'user_id') ?>

    <?= $form->field($model, 'goods_id') ?>

    <?php // echo $form->field($model, 'goods_type') ?>

    <?php // echo $form->field($model, 'amount') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'status') ?>

    <div class="form-group col-md-12">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
