<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Patentevents */

$this->title = $model->eventID;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patentevents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="patentevents-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->eventID], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->eventID], [
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
            'eventID',
            'eventRwslID',
            'eventContentID',
            'eventContent:ntext',
            'eventNote:ntext',
            'patentAjxxbID',
            'eventUserID',
            'eventUsername',
            'eventUserLiasionID',
            'eventUserLiasion',
            'eventCreatPerson',
            'eventCreatUnixTS',
            'eventFinishPerson',
            'eventFinishUnixTS',
            'eventSatus',
        ],
    ]) ?>

</div>
