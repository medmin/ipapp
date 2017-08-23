<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Users;

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
    $("#users-userliaisonid").change(function(){
        var liaison = $(this).find("option:selected").text();
        $("#users-userliaison").val(liaison);
    });
',\yii\web\View::POS_END);
?>

<div class="users-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'userUsername')->textInput(['maxlength' => true]) ?>

    <?php
        if (Yii::$app->controller->action->id == 'create') {
            echo $form->field($model, 'userPassword')->textInput(['maxlength' => true]);
        }
   ?>

    <?= $form->field($model, 'userOrganization')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userFullname')->textInput(['maxlength' => true]) ?>

<!--    <?//= $form->field($model, 'userFirstname')->textInput(['maxlength' => true]) ?>-->

<!--    <?//= $form->field($model, 'userGivenname')->textInput(['maxlength' => true]) ?>-->

<!--    <?//= $form->field($model, 'userNationality')->textInput(['maxlength' => true, 'value' => 'CHINA']) ?>-->

    <?= $form->field($model, 'userCitizenID')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userEmail')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userCellphone')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userLandline')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'userAddress')->textInput(['maxlength' => true]) ?>

    <?php
        // 可以取消掉这个if
        if (Yii::$app->user->can('createEmployee')) {
            echo $form->field($model, 'userRole')->dropDownList([Users::ROLE_SECONDARY_ADMIN => Yii::t('app', 'Controller'), Users::ROLE_EMPLOYEE => Yii::t('app', 'Employee'), Users::ROLE_CLIENT => Yii::t('app', 'Client')], ['prompt' => Yii::t('app', 'Select User Type')]);

            $employees = Users::find()->select(['userID', 'userFullname'])->where(['userRole' => Users::ROLE_EMPLOYEE])->asArray()->all();
            $employees = [0 => 'N/A'] + array_column($employees, 'userFullname', 'userID');

            echo $form->field($model, 'userLiaisonID', ['options' => ['style' => ($model->userRole == Users::ROLE_CLIENT ? 'display:block' : 'display:none')]])->dropDownList($employees, ['prompt' => Yii::t('app','Select An Employee')])->label(Yii::t('app', 'User Liaison'));
        }
    ?>
    <?= $form->field($model, 'userLiaison')->hiddenInput()->label(false) ?>

    <?= $form->field($model, 'userNote')->textarea(['rows' => 4]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
