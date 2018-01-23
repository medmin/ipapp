<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\NotificationSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="notification-search">

    <?php $form = ActiveForm::begin([
        'action' => [isset($action) ? $action : 'index'],
        'method' => 'get',
    ]); ?>

    <?php
    if ($model->scenario == 'wechat_log') {

        echo $form->field($model, 'username', ['options' => ['class' => 'col-md-5']])
            ->label('用户名')
            ->input('text', ['placeholder' => '请输入完整准确的用户名']);

        echo $form->field($model, 'content', ['options' => ['class' => 'col-md-7']]);

    } else {

//        echo $form->field($model, 'id');

//        echo $form->field($model, 'sender');

//        echo $form->field($model, 'receiver');

        echo $form->field($model, 'content');

//        echo $form->field($model, 'type');

        // echo $form->field($model, 'createdAt');

        // echo $form->field($model, 'status');
    }
    ?>

    <div class="form-group col-md-12">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
