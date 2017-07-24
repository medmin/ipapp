<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\UsersSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="users-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'userID') ?>

    <?= $form->field($model, 'userUsername') ?>

    <?= $form->field($model, 'userPassword') ?>

    <?= $form->field($model, 'userOrganization') ?>

    <?= $form->field($model, 'userFullname') ?>

    <?php // echo $form->field($model, 'userFirstname') ?>

    <?php // echo $form->field($model, 'userGivenname') ?>

    <?php // echo $form->field($model, 'userNationality') ?>

    <?php // echo $form->field($model, 'userCitizenID') ?>

    <?php // echo $form->field($model, 'userEmail') ?>

    <?php // echo $form->field($model, 'userCellphone') ?>

    <?php // echo $form->field($model, 'userLandline') ?>

    <?php // echo $form->field($model, 'userAddress') ?>

    <?php // echo $form->field($model, 'userLiaison') ?>

    <?php // echo $form->field($model, 'userLiaisonID') ?>

    <?php // echo $form->field($model, 'userRole') ?>

    <?php // echo $form->field($model, 'userNote') ?>

    <?php // echo $form->field($model, 'authKey') ?>

    <?php // echo $form->field($model, 'UnixTimestamp') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
