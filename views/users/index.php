<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UsersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-index">
    <div class="box box-default">

        <?php if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_ADMIN) {
            echo '<div class="box-header with-border"><p>' . Html::a(Yii::t('app', 'Create Users'), ['create'], ['class' => 'btn btn-success']) . '</p></div>';
        }?>
        <div class="box-body">
            <?php // echo $this->render('_search', ['model' => $searchModel]); ?>


            <?= GridView::widget([
                'dataProvider' => $dataProvider,
//                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

//                    'userID',
                    'userUsername',
//                    'userPassword',
                    'userOrganization',
                    'userFullname',
                    // 'userFirstname',
                    // 'userGivenname',
                    // 'userNationality',
                    // 'userCitizenID',
                     'userEmail:email',
                     'userCellphone',
                     'userLandline',
                     'userAddress',
                     'userLiaison',
                    // 'userLiaisonID',
                     'userRole',
                     'userNote:ntext',
                    // 'authKey',
                    // 'UnixTimestamp:datetime',

                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => Yii::t('app', 'Operation'),
                        'template' => '{view}' . (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_ADMIN ? '{update} {delete}' : '')
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
