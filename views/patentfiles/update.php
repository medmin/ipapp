<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Patentfiles */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Patentfiles',
]) . $model->fileID;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patentfiles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->fileID, 'url' => ['view', 'id' => $model->fileID]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="patentfiles-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
