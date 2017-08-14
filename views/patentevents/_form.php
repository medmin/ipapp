<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Patentevents */
/* @var $form yii\widgets\ActiveForm */
$this->registerJs('
$("#finishDateTime").change(function(){
    var date = new Date($(this).val())
    $("#patentevents-eventfinishunixts").val(Date.parse(date))
})
',\yii\web\View::POS_END);
?>

<div class="patentevents-form">

    <?php $form = ActiveForm::begin(); ?>

    <?//= $form->field($model, 'eventRwslID')->textInput(['maxlength' => true]) ?>

    <?//= $form->field($model, 'eventContentID')->textInput(['maxlength' => true]) ?>
    <div class="col-md-6">
        <?= $form->field($model, 'eventContent')->textarea(['rows' => 3]) ?>

        <?= $form->field($model, 'eventNote')->textarea(['rows' => 3]) ?>

        <?= $form->field($model, 'patentAjxxbID')->textInput(['maxlength' => true, 'disabled' => $model->patentAjxxbID ? true : false]) ?>
    </div>


    <?//= $form->field($model, 'eventUserID')->textInput() ?>

    <?//= $form->field($model, 'eventUsername')->textInput(['maxlength' => true]) ?>

    <?//= $form->field($model, 'eventUserLiaisonID')->textInput() ?>

    <?//= $form->field($model, 'eventUserLiaison')->textInput(['maxlength' => true]) ?>
    <div class="col-md-6">
        <?= $form->field($model, 'eventCreatPerson')->textInput(['maxlength' => true]) ?>

        <?//= $form->field($model, 'eventCreatUnixTS')->hiddenInput()->label(false) ?>

        <?= $form->field($model, 'eventFinishPerson')->textInput(['maxlength' => true]) ?>

        <label for="" class="control-label">到期时间</label>
        <?= \kartik\datetime\DateTimePicker::widget([
            'options' => ['placeholder' => '设置一个到期时间'],
            'name' => 'datetime',
            'id' => 'finishDateTime',
            'value' => $model->eventFinishUnixTS == 0 ? '' : date('Y-m-d H:i', $model->eventFinishUnixTS / 1000),
            'pluginOptions' => [
                'autoclose' =>true,
                'todayHighLight' => true,
                'format' => 'yyyy-mm-dd hh:ii'
            ]
        ]) ; ?>
        <?= $form->field($model, 'eventFinishUnixTS')->hiddenInput()->label(false) ?>

        <?= $form->field($model, 'eventStatus')->dropDownList(['ACTIVE' => Yii::t('app','ACTIVE'), 'INACTIVE' => Yii::t('app','INACTIVE'), 'PENDING' => Yii::t('app','PENDING')]) ?>
    </div>


    <div class="form-group col-md-12">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
