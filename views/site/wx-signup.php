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

$check_user_url = \yii\helpers\Url::to(['/users/check-exist']);
$wx_signup_bind = \yii\helpers\Url::to(['/site/wx-signup-bind']);
$js = <<<JS
$('#wxsignupform-email').blur(function() {
  let email = $(this).val().trim();
  if (email === '') return;
  console.log(email);
  $.post('$check_user_url', {username:email}, function(data) {
    if (data.code === true && confirm('该邮箱已注册,是否跳转到绑定页面？')) {
        window.location.href = '$wx_signup_bind' + '?user=' + email;
    }
  },'json');
})
JS;

if ($action == 'wx-signup-bind') {
    $this->title = '绑定账号 | 阳光惠远';
} else {
    $this->title = '创建帐号 | 阳光惠远 ';
    $this->registerJs($js, \yii\web\View::POS_END);
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

        <?php 
        if (Yii::$app->request->queryParams) {
            
        }
        ?>
        <?= $form
            ->field($model, 'username', $fieldOptions('user'))
            ->label(false)
            ->textInput(['placeholder' => '请输入要绑定的用户名或者邮箱', 'value' => Yii::$app->request->queryParams['user'] ?? '']) ?>

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

        <?php $form = ActiveForm::begin(['id' => 'wx-signup-form', 'enableClientValidation' => true]); ?>

        <div class="row">
            <div class="col-xs-12 form-group">
                <?= Html::a('已有账号,请点此按钮进行绑定', ['wx-signup-bind'], ['class' => 'btn btn-danger btn-block btn-flat text-black'])?>
            </div>
        </div>

        <?= $form
            ->field($model, 'email', $fieldOptions('envelope'))
            ->label(false)
            ->textInput(['placeholder' => '请输入注册邮箱']) ?>

        <?= $form
            ->field($model, 'email', $fieldOptions('mobile-phone'))
            ->label(false)
            ->textInput(['placeholder' => '请输入手机号码']) ?>

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

            <div class="col-xs-12">
                <?= Html::submitInput('注册新账号', ['class' => 'btn btn-primary btn-block btn-flat']) ?>
            </div>
        </div>
        <?php ActiveForm::end()?>
    </div>
    <?php endif; ?>
</div>
