<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Patentfiles */

$this->title = Yii::t('app', 'Create Patentfiles');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patentfiles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="patentfiles-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
