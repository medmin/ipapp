<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\PatenteventsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="patentevents-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'eventID') ?>

    <?= $form->field($model, 'eventRwslID') ?>

    <?= $form->field($model, 'eventContentID') ?>

    <?= $form->field($model, 'eventContent') ?>

    <?= $form->field($model, 'eventNote') ?>

    <?php // echo $form->field($model, 'patentAjxxbID') ?>

    <?php // echo $form->field($model, 'eventUserID') ?>

    <?php // echo $form->field($model, 'eventUsername') ?>

    <?php // echo $form->field($model, 'eventUserLiasionID') ?>

    <?php // echo $form->field($model, 'eventUserLiasion') ?>

    <?php // echo $form->field($model, 'eventCreatPerson') ?>

    <?php // echo $form->field($model, 'eventCreatUnixTS') ?>

    <?php // echo $form->field($model, 'eventFinishPerson') ?>

    <?php // echo $form->field($model, 'eventFinishUnixTS') ?>

    <?php // echo $form->field($model, 'eventSatus') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
