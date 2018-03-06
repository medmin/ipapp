<?php
/**
 * User: Mr-mao
 * Date: 2017/7/25
 * Time: 18:48
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $model \app\models\Users */

$this->title = Yii::t('app','Personal Settings');
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs('
    $("form[name=\'passwordForm\']").submit(function(){
        $(".old-password,.new-password,.confirm-password").removeClass("has-error").find(".help-block").text("");
        var old_p = $("#oldPassword").val();
        var new_p = $("#newPassword").val();
        var confirm_p = $("#confirmPassword").val();
        if ($.trim(old_p) == "" || new_p.length < 6) return false;
        if (confirm_p !== new_p) {
            $(".confirm-password").addClass("has-error").find(".help-block").text("两次密码不一致");
            return false;
        }
        $.post(
        "' . \yii\helpers\Url::to('reset-password') . '",
        {oldPassword:old_p,newPassword:new_p,confirmPassword:confirm_p},
        function(data){
            if(data.code == -1) {
                $(".old-password").addClass("has-error").find(".help-block").text(data.message);
            }else if(data.code == -2) {
                $(".confirm-password").addClass("has-error").find(".help-block").text(data.message);
            }else if(data.code == -3) {
                $(".new-password").addClass("has-error").find(".help-block").text(data.message);
            }else {
                alert("修改成功");
                location.reload();
            }
        },"json");
        return false;
    });
',\yii\web\View::POS_END);
?>
<div class="users-profile">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#profile" data-toggle="tab" aria-expanded="true"><?= Yii::t('app','Profile') ?></a>
            </li>
            <li>
                <a href="#password" data-toggle="tab" aria-expanded="false"><?= Yii::t('app','Change Password') ?></a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="profile">
                <?php if (Yii::$app->session->hasFlash('profile')) { ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?= Yii::$app->session->getFlash('profile') ?>
                    </div>
                <?php } ?>
                <?php $form = ActiveForm::begin() ?>

                <?= $form->field($model, 'userUsername')->textInput()->label('用户名(可用作登录账号)') ?>

                <?= $form->field($model, 'userCitizenID')->textInput() ?>

                <?= $form->field($model, 'userFullname')->textInput() ?>

                <?= $form->field($model, 'userCellphone')->textInput() ?>

                <?= $form->field($model, 'userLandline')->textInput() ?>

                <?= $form->field($model, 'userAddress')->textInput() ?>

                <div class="from-group">
                    <?= Html::submitButton(Yii::t('yii','Update'), ['class' => 'btn btn-success btn-flat', 'id' => 'profile-submit']) ?>
                </div>

                <?php ActiveForm::end() ?>
            </div>
            <div class="tab-pane" id="password">
                <form role="form" name="passwordForm">
                    <div class="form-group old-password">

                        <?= Html::label(Yii::t('app','Old Password'), 'oldPassword') ?>
                        <?= Html::textInput('oldPassword','', ['class' => 'form-control', 'id' => 'oldPassword']) ?>
                        <div class="help-block"></div>

                    </div>
                    <div class="form-group new-password">

                        <?= Html::label(Yii::t('app','New Password'), 'newPassword') ?>
                        <?= Html::passwordInput('newPassword', '', ['class' => 'form-control', 'id' => 'newPassword'])?>
                        <div class="help-block"></div>

                    </div>
                    <div class="form-group confirm-password">

                        <?= Html::label(Yii::t('app','Confirm New Password'), 'confirmPassword') ?>
                        <?= Html::passwordInput('confirmPassword', '', ['class' => 'form-control', 'id' => 'confirmPassword'])?>
                        <div class="help-block"></div>

                    </div>
                    <div class="from-group">
                        <?= Html::submitButton(Yii::t('app','Submit'), ['class' => 'btn btn-success btn-flat', 'id' => 'password-submit']) ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
