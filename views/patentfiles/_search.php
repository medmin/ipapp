<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\PatentfilesSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="patentfiles-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'fileID') ?>

    <?= $form->field($model, 'patentAjxxbID') ?>

    <?= $form->field($model, 'fileName') ?>

    <?= $form->field($model, 'filePath') ?>

    <?= $form->field($model, 'fileUploadUserID') ?>

    <?php // echo $form->field($model, 'fileUploadedAt') ?>

    <?php // echo $form->field($model, 'filehUpdateUserID') ?>

    <?php // echo $form->field($model, 'fileUpdatedAt') ?>

    <?php // echo $form->field($model, 'fileNote') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
