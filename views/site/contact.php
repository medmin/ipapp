<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ContactForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;

$this->title = '反馈';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-contact">
    <div class="box box-default">
        <div class="box-body">
            <?php if (Yii::$app->session->hasFlash('contactFormSubmitted')): ?>

                <div class="alert alert-success">
                    感谢您的反馈，我们会及时处理。<?= Html::a('点此返回主页', ['/'])?>
                </div>

            <?php else: ?>

                <p>
                    如果您在使用过程中遇到任何问题或者有任何的建议请及时联系我们，谢谢！
                </p>

                <div class="row">
                    <div class="col-lg-5">

                        <?php $form = ActiveForm::begin(['id' => 'contact-form']); ?>

                        <?//= $form->field($model, 'name')->textInput(['autofocus' => true]) ?>

                        <?//= $form->field($model, 'email') ?>

                        <?//= $form->field($model, 'subject') ?>

                        <?= $form->field($model, 'body')->textarea(['rows' => 6])->label(false) ?>

                        <?= $form->field($model, 'verifyCode')->widget(Captcha::className(), [
                            'template' => '<div class="row"><div class="col-sm-2">{image}</div><div class="col-sm-8">{input}</div></div>',
                            'imageOptions' => [
                                'style' => 'cursor: pointer',
                                'title' => '点击刷新'
                            ]
                        ]) ?>

                        <div class="form-group">
                            <?= Html::submitButton(Yii::t('app', 'Submit'), ['class' => 'btn btn-primary', 'name' => 'contact-button']) ?>
                        </div>

                        <?php ActiveForm::end(); ?>

                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>
