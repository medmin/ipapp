<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Patentevents */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Patentevents',
]) . $model->eventID;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patentevents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->eventID, 'url' => ['view', 'id' => $model->eventID]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="patentevents-update">
    <div class="box box-success">
        <div class="box-body">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>
</div>
