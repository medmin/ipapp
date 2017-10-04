<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */

$this->title = '更新订单：'. $model->trade_no;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Orders'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->trade_no, 'url' => ['view', 'id' => $model->trade_no]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="orders-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
