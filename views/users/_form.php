<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Users */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="users-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'userUsername')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userPassword')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userOrganization')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userFullname')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userFirstname')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userGivenname')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userNationality')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userCitizenID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userEmail')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userCellphone')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userLandline')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userAddress')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userLiasion')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userLiasionID')->textInput() ?>

    <?= $form->field($model, 'userRole')->textInput() ?>

    <?= $form->field($model, 'userNote')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'authKey')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'UnixTimestamp')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
