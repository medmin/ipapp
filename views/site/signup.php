<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \app\models\SignupForm */

$this->title = '注册';

$fieldOptions = function($icon){
    return [
        'options' => ['class' => 'form-group has-feedback'],
        'inputTemplate' => "{input}<span class='glyphicon glyphicon-" . $icon . " form-control-feedback'></span>"
    ];
};

$this->registerJs("
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%' // optional
    });
    $('.monitor,.iCheck-helper').click(function(){
        check_disabled();
    })
    function check_disabled() {
        var check_div = $('.monitor').find('.icheckbox_square-blue');
        if (check_div.hasClass('checked') && check_div.attr('aria-checked') == 'true') {
            $('input[name=\"register-button\"]').attr('disabled', false);
        }else{
            $('input[name=\"register-button\"]').attr('disabled', true);
        }
    }
", \yii\web\View::POS_END);
?>
<div class="register-box">
    <div class="register-logo">
        <a href="javascript:;"><?= Yii::$app->name ?></a>
    </div>
    <div class="register-box-body">
        <!--        <p class="login-box-msg">Register a new membership</p>-->
        <?php $form = ActiveForm::begin(['id' => 'login-form', 'enableClientValidation' => true]); ?>


        <?= $form
            ->field($model, 'email', $fieldOptions('envelope'))
            ->label(false)
            ->textInput(['placeholder' => '必填：' . $model->getAttributeLabel('email')]) ?>

        <?= $form
            ->field($model, 'password', $fieldOptions('lock'))
            ->label(false)
            ->passwordInput(['placeholder' => '必填：' .$model->getAttributeLabel('password')]) ?>

        <?= $form
            ->field($model, 'repeatPassword', $fieldOptions('log-in'))
            ->label(false)
            ->passwordInput(['placeholder' => '必填：' .$model->getAttributeLabel('repeatPassword')]) ?>

        <?= $form
            ->field($model,'cellPhone', $fieldOptions('phone'))
            ->label(false)
            ->textInput(['placeholder' => '必填：' .$model->getAttributeLabel('cellPhone')]) ?>

        <?= $form
            ->field($model,'name', $fieldOptions('user'))
            ->label(false)
            ->textInput(['placeholder' => '必填：' . $model->getAttributeLabel('name')]) ?>

        <?= $form
            ->field($model, 'citizenID',$fieldOptions('info-sign'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('citizenID')]) ?>

        <?= $form
            ->field($model,'organization', $fieldOptions('info-sign'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('organization')]) ?>

<!--        --><?//= $form
//            ->field($model,'landLine', $fieldOptions('phone-alt'))
//            ->label(false)
//            ->textInput(['placeholder' => $model->getAttributeLabel('landLine')]) ?>

<!--        --><?//= $form
//            ->field($model,'address', $fieldOptions('map-marker'))
//            ->label(false)
//            ->textInput(['placeholder' => $model->getAttributeLabel('address')]) ?>

        <div class="row">
            <div class="col-xs-8" style="padding-top: 10px">
<!--                <div class="checkbox icheck">-->
<!--                    <label class="monitor">-->
<!--                        <input type="checkbox" id="agree"> 我同意 <a href="javascript:;">注册条款</a>-->
<!--                    </label>-->
<!--                </div>-->
                <?= Html::a('已有账号,点此登录', 'site/login') ?>
            </div>
            <!-- /.col -->
            <div class="col-xs-4">
                <?= Html::submitInput('注册', ['class' => 'btn btn-primary btn-block btn-flat', 'name' => 'register-button', 'disabled' => false]) ?>
            </div>
            <!-- /.col -->
        </div>
        <?php ActiveForm::end()?>
    </div>
</div>
