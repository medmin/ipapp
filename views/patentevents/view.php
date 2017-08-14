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
    <div class="box box-info">
        <div class="box-header">
            <p>
                <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->eventID], ['class' => 'btn btn-primary']) ?>
                <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->eventID], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]) ?>
            </p>
        </div>
        <div class="box-body">
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
                    'eventUserLiaisonID',
                    'eventUserLiaison',
                    'eventCreatPerson',
                    [
                        'attribute' => 'eventCreatUnixTS',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDatetime($model->eventCreatUnixTS);
                        }
                    ],
                    'eventFinishPerson',
                    [
                        'attribute' => 'eventFinishUnixTS',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDatetime($model->eventFinishUnixTS);
                        }
                    ],
                    'eventStatus',
                ],
            ]) ?>
        </div>
    </div>
</div>
