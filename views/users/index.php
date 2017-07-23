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

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('app', 'Create Users'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'userID',
            'userUsername',
            'userPassword',
            'userOrganization',
            'userFullname',
            // 'userFirstname',
            // 'userGivenname',
            // 'userNationality',
            // 'userCitizenID',
            // 'userEmail:email',
            // 'userCellphone',
            // 'userLandline',
            // 'userAddress',
            // 'userLiasion',
            // 'userLiasionID',
            // 'userRole',
            // 'userNote:ntext',
            // 'authKey',
            // 'UnixTimestamp:datetime',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
