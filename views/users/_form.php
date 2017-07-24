<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Users */
/* @var $form yii\widgets\ActiveForm */

$this->registerJs('
    $("#users-userrole").change(function(){
        var target_type = $(this).val();
        var field_div = $(".field-users-userliaisonid");
        if (target_type == 1) {
            field_div.show();
        }else {
            $("#users-userliaisonid").val(0);
            field_div.hide();
        }
    });
',\yii\web\View::POS_END);
?>

<div class="users-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'userUsername')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userPassword')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userOrganization')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userFullname')->textInput(['maxlength' => true]) ?>

    <?//= $form->field($model, 'userFirstname')->textInput(['maxlength' => true]) ?>

    <?//= $form->field($model, 'userGivenname')->textInput(['maxlength' => true]) ?>

    <?//= $form->field($model, 'userNationality')->textInput(['maxlength' => true, 'value' => 'CHINA']) ?>

    <?= $form->field($model, 'userCitizenID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userEmail')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userCellphone')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userLandline')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userAddress')->textInput(['maxlength' => true]) ?>

    <?php
        if (Yii::$app->user->can('createEmployee')) {
            echo $form->field($model, 'userRole')->dropDownList([2 => Yii::t('app', 'Employee'), 1 => Yii::t('app', 'Client')], ['prompt' => Yii::t('app', 'Select User Type')]);

            $employees = \app\models\Users::find()->select(['userID', 'userFullname'])->where(['userRole' => 2])->asArray()->all();
            $employees = array_merge([0 => 'N/A'], array_column($employees, 'userFullname', 'userID'));
            echo $form->field($model, 'userLiaisonID', ['options' => ['style' => 'display:none']])->dropDownList($employees, ['prompt' => Yii::t('app','Select An Employee')]);
        }
    ?>

    <?= $form->field($model, 'userNote')->textarea(['rows' => 4]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
