<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Patents */

$this->title = Yii::t('app', 'Create Patents');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="patents-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
