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
    <div class="box box-info">
        <div class="box-header with-border">
            <p>
                <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->userID], ['class' => 'btn btn-primary']) ?>
                <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->userID], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]) ?>
            </p>
        </div>
        <div class="bpx-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'userID',
                    'userUsername',
//                    'userPassword',
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
                    'userLiaison',
                    'userLiaisonID',
                    'userRole',
                    'userNote:ntext',
//                    'authKey',
//                    'UnixTimestamp:datetime',
                ],
            ]) ?>
        </div>
    </div>
</div>
