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

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
