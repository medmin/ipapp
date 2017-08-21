<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Patents */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="patents-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'patentAjxxbID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'patentEacCaseNo')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'patentType')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'patentUserID')->textInput() ?>

    <?= $form->field($model, 'patentUsername')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'patentAgent')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'patentProcessManager')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'patentTitle')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'patentApplicationNo')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'patentPatentNo')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'patentNote')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'patentApplicationDate')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'UnixTimestamp')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
