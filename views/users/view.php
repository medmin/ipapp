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
                <?php
                if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_EMPLOYEE){
                    echo Html::a('他的专利', \yii\helpers\Url::to(['patents/index', 'PatentsSearch[patentUserID]' => $model->userID]), ['class' => 'btn btn-primary']);
                    echo Html::a('所有进度', \yii\helpers\Url::to(['users/events-schedule', 'user_id' => $model->userID]), ['class' => 'btn btn-info', 'style' => 'margin-left: 5px']);
                } else {
                    echo Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->userID], ['class' => 'btn btn-primary']);
                    echo Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->userID], [
                        'class' => 'btn btn-danger',
                        'style' => 'margin-left: 5px',
                        'data' => [
                            'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'method' => 'post',
                        ],
                    ]);
                }
                ?>
            </p>
        </div>
        <div class="box-body">
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
