<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PatenteventsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Patentevents');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="patentevents-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('app', 'Create Patentevents'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'eventID',
            'eventRwslID',
            'eventContentID',
            'eventContent:ntext',
            'eventNote:ntext',
            // 'patentAjxxbID',
            // 'eventUserID',
            // 'eventUsername',
            // 'eventUserLiasionID',
            // 'eventUserLiasion',
            // 'eventCreatPerson',
            // 'eventCreatUnixTS',
            // 'eventFinishPerson',
            // 'eventFinishUnixTS',
            // 'eventSatus',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
