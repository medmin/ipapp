<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\PatenteventsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="patentevents-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

<!--    --><?//= $form->field($model, 'eventID') ?>
<!---->
<!--    --><?//= $form->field($model, 'eventRwslID') ?>
<!---->
<!--    --><?//= $form->field($model, 'eventContentID') ?>

    <?= $form->field($model, 'eventContent', ['options' => ['class' => 'col-md-3']]) ?>

<!--    --><?//= $form->field($model, 'eventNote') ?>

    <?php  echo $form->field($model, 'patentAjxxbID', ['options' => ['class' => 'col-md-3']]) ?>

    <?php  echo $form->field($model, 'eventUserID', ['options' => ['class' => 'col-md-3']]) ?>

<!--    --><?php // echo $form->field($model, 'eventUsername', ['options' => ['class' => 'col-md-3']]) ?>

    <?php  echo $form->field($model, 'eventUserLiaisonID', ['options' => ['class' => 'col-md-3']]) ?>

<!--    --><?php // echo $form->field($model, 'eventUserLiaison', ['options' => ['class' => 'col-md-3']]) ?>

    <?php  echo $form->field($model, 'eventCreatPerson', ['options' => ['class' => 'col-md-3']]) ?>

    <?php // echo $form->field($model, 'eventCreatUnixTS') ?>

    <?php  echo $form->field($model, 'eventFinishPerson', ['options' => ['class' => 'col-md-3']]) ?>

    <?php // echo $form->field($model, 'eventFinishUnixTS') ?>

    <?php  echo $form->field($model, 'eventStatus', ['options' => ['class' => 'col-md-3']])
    ->dropDownList(['ACTIVE' => Yii::t('app','ACTIVE'), 'INACTIVE' => Yii::t('app','INACTIVE'), 'PENDING' => Yii::t('app','PENDING')]) ?>

    <div class="form-group col-md-12">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
