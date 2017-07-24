<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Patentevents */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="patentevents-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'eventRwslID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'eventContentID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'eventContent')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'eventNote')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'patentAjxxbID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'eventUserID')->textInput() ?>

    <?= $form->field($model, 'eventUsername')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'eventUserLiaisonID')->textInput() ?>

    <?= $form->field($model, 'eventUserLiaison')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'eventCreatPerson')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'eventCreatUnixTS')->textInput() ?>

    <?= $form->field($model, 'eventFinishPerson')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'eventFinishUnixTS')->textInput() ?>

    <?= $form->field($model, 'eventSatus')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
