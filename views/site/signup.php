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
    $('.monitor').click(function(){
        var check_div = $(this).find('.icheckbox_square-blue');
        if (check_div.hasClass('checked') && check_div.attr('aria-checked') == 'true') {
            $('button[name=\"register-button\"]').removeClass('disabled');
        }else{
            $('button[name=\"register-button\"]').addClass('disabled');
        }
    })
", \yii\web\View::POS_END);
?>
<div class="register-box">
    <div class="register-logo">
        <a href="javascript:;"><b>Admin</b>LTE</a>
    </div>
    <div class="register-box-body">
        <!--        <p class="login-box-msg">Register a new membership</p>-->
        <?php $form = ActiveForm::begin(['id' => 'login-form', 'enableClientValidation' => true]); ?>

        <?= $form
            ->field($model, 'username', $fieldOptions('user'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('username')]) ?>

        <?= $form
            ->field($model, 'email', $fieldOptions('envelope'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('email')]) ?>

        <?= $form
            ->field($model, 'password', $fieldOptions('lock'))
            ->label(false)
            ->passwordInput(['placeholder' => $model->getAttributeLabel('password')]) ?>

        <?= $form
            ->field($model, 'repeatPassword', $fieldOptions('log-in'))
            ->label(false)
            ->passwordInput(['placeholder' => $model->getAttributeLabel('repeatPassword')]) ?>

        <?= $form
            ->field($model, 'citizenID',$fieldOptions('info-sign'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('citizenID')])?>

        <?= $form
            ->field($model,'organization', $fieldOptions('info-sign'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('organization')]) ?>

        <?= $form
            ->field($model,'name', $fieldOptions('info-sign'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('name')]) ?>

        <?= $form
            ->field($model,'landLine', $fieldOptions('phone-alt'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('landLine')]) ?>

        <?= $form
            ->field($model,'cellPhone', $fieldOptions('phone'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('cellPhone')]) ?>

        <?= $form
            ->field($model,'address', $fieldOptions('map-marker'))
            ->label(false)
            ->textInput(['placeholder' => $model->getAttributeLabel('address')]) ?>

        <div class="row">
            <div class="col-xs-8">
                <div class="checkbox icheck">
                    <label class="monitor">
                        <input type="checkbox" id="agree"> I agree to the <a href="javascript:;">terms</a>
                    </label>
                </div>
            </div>
            <!-- /.col -->
            <div class="col-xs-4">
                <?= Html::submitButton('注册', ['class' => 'btn btn-primary btn-block btn-flat disabled', 'name' => 'register-button']) ?>
            </div>
            <!-- /.col -->
        </div>
        <?php ActiveForm::end()?>
    </div>
</div>
