<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\PatentsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="patents-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?//= $form->field($model, 'patentID') ?>

    <?= $form->field($model, 'patentAjxxbID', ['options' => ['class' => 'col-md-6']]) ?>

    <?= $form->field($model, 'patentEacCaseNo', ['options' => ['class' => 'col-md-6']]) ?>

    <?//= $form->field($model, 'patentType') ?>

    <?= $form->field($model, 'patentUserID', ['options' => ['class' => 'col-md-3']]) ?>

    <?= $form->field($model, 'patentUsername', ['options' => ['class' => 'col-md-3']]) ?>

    <?= $form->field($model, 'patentUserLiaisonID', ['options' => ['class' => 'col-md-3']]) ?>

    <?= $form->field($model, 'patentUserLiaison', ['options' => ['class' => 'col-md-3']]) ?>

    <?= $form->field($model, 'organization', ['options' => ['class' => 'col-md-5']])->label('公司名称') ?>

    <?php // echo $form->field($model, 'patentAgent') ?>

    <?php // echo $form->field($model, 'patentProcessManager') ?>

    <?php // echo $form->field($model, 'patentTitle') ?>

    <?php // echo $form->field($model, 'patentApplicationNo') ?>

    <?php // echo $form->field($model, 'patentPatentNo') ?>

    <?php // echo $form->field($model, 'patentNote') ?>

    <?php // echo $form->field($model, 'UnixTimestamp') ?>

    <div class="form-group col-md-12">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
