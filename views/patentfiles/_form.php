<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Patentfiles */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="patentfiles-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'patentAjxxbID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'fileName')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'filePath')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'fileUploadUserID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'fileUploadedAt')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'filehUpdateUserID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'fileUpdatedAt')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'fileNote')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
