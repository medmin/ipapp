<?php
/**
 * User: Mr-mao
 * Date: 2017/8/16
 * Time: 22:36
 */
/* @var $model \app\models\WxSignupForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$action = Yii::$app->controller->action->id;
if ($action == 'wx-signup-bind') {
    $this->title = '绑定账号 | 阳光惠远';
} else {
    $this->title = '创建帐号 | 阳光惠远 ';
}
$fieldOptions = function($icon){
    return [
        'options' => ['class' => 'form-group has-feedback'],
        'inputTemplate' => "{input}<span class='glyphicon glyphicon-" . $icon . " form-control-feedback'></span>"
    ];
};

?>
<div class="register-box">
    <div class="register-logo">
        <a href="javascript:;"><?= Yii::$app->name ?></a>
    </div>
    <?php if ($action == 'wx-signup-bind'): ?>
    <div class="register-box-body" id="wx-bind">
        <p class="login-box-msg">微信账号绑定</p>
        <?php $form = ActiveForm::begin(['id' => 'wx-signup-bind-form', 'enableClientValidation' => true]); ?>

        <?= $form
            ->field($model, 'username', $fieldOptions('user'))
            ->label(false)
            ->textInput(['placeholder' => '请输入要绑定的用户名或者邮箱']) ?>

        <?= $form
            ->field($model, 'password', $fieldOptions('lock'))
            ->label(false)
            ->passwordInput(['placeholder' => '请输入密码']) ?>

        <div class="row">
            <div class="col-xs-4">
                <?= Html::submitInput('绑定', ['class' => 'btn btn-primary btn-block btn-flat']) ?>
            </div>
        </div>

        <?php ActiveForm::end()?>

    </div>
    <?php else: ?>
    <div class="register-box-body" id="wx-register">
        <p class="login-box-msg">注册微信绑定账号</p>
        <?php $form = ActiveForm::begin(['id' => 'wx-signup-form', 'enableClientValidation' => true]); ?>

        <?= $form
            ->field($model, 'email', $fieldOptions('envelope'))
            ->label(false)
            ->textInput(['placeholder' => '请输入注册邮箱']) ?>

        <?= $form
            ->field($model, 'fullname', $fieldOptions('heart'))
            ->label(false)
            ->textInput(['placeholder' => '请输入您的真实姓名,以便我们核对专利']) ?>

        <?= $form
            ->field($model, 'password', $fieldOptions('lock'))
            ->label(false)
            ->passwordInput(['placeholder' => '请输入密码']) ?>

        <?= $form
            ->field($model, 'repeatPassword', $fieldOptions('log-in'))
            ->label(false)
            ->passwordInput(['placeholder' => '请确认密码']) ?>

        <div class="row">
            <div class="col-xs-4">
                <?= Html::submitInput('注册', ['class' => 'btn btn-primary btn-block btn-flat']) ?>
            </div>
        </div>
        <?php ActiveForm::end()?>
    </div>
    <?php endif; ?>
</div>
