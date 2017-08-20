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

<!--    --><?//= $form->field($model, 'fileID', ['options' => ['class' => 'col-md-3']]) ?>

    <?= $form->field($model, 'patentAjxxbID', ['options' => ['class' => 'col-md-3']]) ?>

    <?= $form->field($model, 'fileName', ['options' => ['class' => 'col-md-3']]) ?>

<!--    --><?//= $form->field($model, 'filePath') ?>

    <?= $form->field($model, 'fileUploadUserID', ['options' => ['class' => 'col-md-3']]) ?>

    <?php // echo $form->field($model, 'fileUploadedAt') ?>

    <?= $form->field($model, 'fileUpdateUserID', ['options' => ['class' => 'col-md-3']] ) ?>

    <?php // echo $form->field($model, 'fileUpdatedAt') ?>

    <?php // echo $form->field($model, 'fileNote') ?>

    <div class="form-group col-md-12">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
