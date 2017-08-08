<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Patents */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Patents',
]) . $model->patentID;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->patentID, 'url' => ['view', 'id' => $model->patentID]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="patents-update">
    <div class="box box-success">
        <div class="box-body">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>
</div>
