<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = Yii::t('app', 'Update Users');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('yii', 'Update');
$this->params['breadcrumbs'][] = $model->userID;
?>
<div class="users-update">
    <div class="box box-primary">
        <div class="box-body">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>
</div>
