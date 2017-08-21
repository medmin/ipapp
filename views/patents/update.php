<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Patents */

$this->title = Yii::t('app', 'Update Patents') . '：' . $model->patentAjxxbID;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->patentID, 'url' => ['view', 'id' => $model->patentID]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="patents-update">
    <div class="box box-success">
        <div class="box-body">
            <div class="patents-form">

                <?php $form = ActiveForm::begin(); ?>

                <?//= $form->field($model, 'patentAjxxbID')->textInput(['maxlength' => true]) ?>

                <?= $form->field($model, 'patentEacCaseNo')->textInput(['maxlength' => true, 'disabled' => true]) ?>

                <?= $form->field($model, 'patentType')->textInput(['maxlength' => true, 'disabled' => true]) ?>

                <?= $form->field($model, 'patentUserID')->textInput() ?>

                <?= $form->field($model, 'patentUsername')->textInput(['maxlength' => true, 'disabled' => true]) ?>

                <?= $form->field($model, 'patentAgent')->textInput(['maxlength' => true]) ?>

                <?= $form->field($model, 'patentProcessManager')->textInput(['maxlength' => true]) ?>
                <?php

                $employees = app\models\Users::find()->select(['userID', 'userFullname'])->where(['userRole' => app\models\Users::ROLE_EMPLOYEE])->asArray()->all();
                $employees = [0 => 'N/A'] + array_column($employees, 'userFullname', 'userID');

                echo $form->field($model, 'patentUserLiaisonID')->dropDownList($employees, ['prompt' => Yii::t('app','Select An Employee')])->label(Yii::t('app','Patent User Liaison'));
                ?>

                <?= $form->field($model, 'patentTitle')->textInput(['maxlength' => true]) ?>

                <?= $form->field($model, 'patentApplicationNo')->textInput(['maxlength' => true, 'disabled' => true]) ?>

                <?= $form->field($model, 'patentPatentNo')->textInput(['maxlength' => true, 'disabled' => true]) ?>

                <?= $form->field($model, 'patentApplicationDate')->textInput(['maxlength' => true, 'disabled' => true]) ?>

                <?= $form->field($model, 'patentNote')->textarea(['rows' => 6]) ?>

                <?//= $form->field($model, 'UnixTimestamp')->textInput() ?>

                <div class="form-group">
                    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>
