<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Patentevents */

$this->title = Yii::t('app', 'Create Patentevents');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patentevents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="patentevents-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
