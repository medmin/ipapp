<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = $model->userID;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->userID], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->userID], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'userID',
            'userUsername',
            'userPassword',
            'userOrganization',
            'userFullname',
            'userFirstname',
            'userGivenname',
            'userNationality',
            'userCitizenID',
            'userEmail:email',
            'userCellphone',
            'userLandline',
            'userAddress',
            'userLiasion',
            'userLiasionID',
            'userRole',
            'userNote:ntext',
            'authKey',
            'UnixTimestamp:datetime',
        ],
    ]) ?>

</div>
