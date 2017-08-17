<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \app\models\LoginForm */

$this->title = '登录 | 阳光惠远';

$fieldOptions1 = [
    'options' => ['class' => 'form-group has-feedback'],
    'inputTemplate' => "{input}<span class='glyphicon glyphicon-envelope form-control-feedback'></span>"
];

$fieldOptions2 = [
    'options' => ['class' => 'form-group has-feedback'],
    'inputTemplate' => "{input}<span class='glyphicon glyphicon-lock form-control-feedback'></span>"
];
$this->registerCss('
.more-sign {
    margin-top: 30px;
    text-align: center;
}
.more-sign h6 {
    position: relative;
    margin: 0 0 20px;
    font-size: 12px;
    color: #b5b5b5;
}
:after {
    box-sizing: border-box;
}
.more-sign h6:after, .more-sign h6:before {
    content: "";
    border-top: 1px solid #b5b5b5;
    display: block;
    position: absolute;
    width: 60px;
    top: 5px;
}
.more-sign h6:before {
    left: 40px;
}
.more-sign h6:after {
    right: 40px;
}
.more-sign a {
    margin: 0 auto;
    background-color: #00bb29;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    line-height: 50px;
    display: block;
}
.more-sign i {
    font-size: 24px;
    line-height: inherit;
}
.wx-icon {
    color: #fff
}
');
?>

<div class="login-box">
    <div class="login-logo">
        <a href="#"><?= Yii::$app->name ?></a>
    </div>
    <!-- /.login-logo -->
    <div class="login-box-body">
        <!--        <p class="login-box-msg">Sign in to start your session</p>-->

        <?php
        if (Yii::$app->getSession()->hasFlash('error')) {
            // TODO 授权失败显示
        }
        ?>
        <?php $form = ActiveForm::begin(['id' => 'login-form', 'enableClientValidation' => false]); ?>

        <?= $form
            ->field($model, 'username', $fieldOptions1)
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('username')]) ?>

        <?= $form
            ->field($model, 'password', $fieldOptions2)
            ->label(false)
            ->passwordInput(['placeholder' => $model->getAttributeLabel('password')]) ?>

        <div class="row">
            <div class="col-xs-8">
                <?= $form->field($model, 'rememberMe')->checkbox()->label('记住我') ?>
            </div>
            <!-- /.col -->
            <div class="col-xs-4">
                <?= Html::submitButton('登录', ['class' => 'btn btn-primary btn-block btn-flat', 'name' => 'login-button']) ?>
            </div>
            <!-- /.col -->
        </div>


        <?php ActiveForm::end(); ?>

        <!-- /.social-auth-links -->

        <!--        <a href="#">I forgot my password</a><br>-->
        <?= Html::a('注册一个新用户', ['site/signup'])?>
        <div class="more-sign">
            <h6>社交账号登录</h6>
            <a href="<?= \yii\helpers\Url::to('/site/wx-login') ?>"><i class="fa fa-weixin wx-icon"></i></a>
        </div>

    </div>
    <!-- /.login-box-body -->
</div><!-- /.login-box -->
