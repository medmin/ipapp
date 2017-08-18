<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PatenteventsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

if (Yii::$app->controller->action->id == 'todo') {
    $this->title = Yii::t('app', 'Patentevents TODO');
} else {
    $this->title = Yii::t('app', 'Patentevents');
}
$this->params['breadcrumbs'][] = $this->title;
$this->registerJs('
var searchToggle = function(){
        $("#toggleSearchBtn").trigger("click");
    }
',\yii\web\View::POS_END);
?>
<div class="patentevents-index">
    <div class="box box-default collapsed-box">
        <div class="box-header with-border">
            <a href="javascript:void(0)" onclick="searchToggle()" style="display: block;"><h3 class="box-title"><small>搜索</small></h3></a>

            <div class="box-tools pull-right">
                <button id="toggleSearchBtn" type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <?php echo $this->render('_search', ['model' => $searchModel]); ?>
        </div>
    </div>
    <div class="box box-primary">
        <div class="box-header">
            <p><?= Html::a(Yii::t('app', 'Create Patentevents'), ['create'], ['class' => 'btn btn-success']) ?></p>
        </div>
        <div class="box-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
//        'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

//                    'eventID',
//            'eventRwslID',
//            'eventContentID',
                    'eventContent:ntext',
                    'eventNote:ntext',
                    // 'patentAjxxbID',
                    // 'eventUserID',
                    'eventUsername',
                    // 'eventUserLiaisonID',
                    'eventUserLiaison',
                    'eventCreatPerson',
                    [
                        'attribute' => 'eventCreatUnixTS',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDatetime($model->eventCreatUnixTS / 1000);
                        }
                    ],

                    'eventFinishPerson',
                    [
                        'attribute' => 'eventFinishUnixTS',
                        'value' => function ($model) {
                            return $model->eventFinishUnixTS == 0 ? '<span class="text-red">暂未设置</span>' : Yii::$app->formatter->asDatetime($model->eventFinishUnixTS / 1000);
                        },
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'eventStatus',
                        'value' => function ($model) {
                            return Yii::t('app', $model->eventStatus);
                        }
                    ],

                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => Yii::t('app', 'Operation'),
                        'template' => '
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    操作
                                    <span class="fa fa-caret-down"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li>{view}</li>
                                    <li>{update}</li>
                                    <li>{delete}</li>
                                </li>
                            </div>
                        ',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('查看', $url, ['target' => '_blank']);
                            },
                            'update' => function ($url, $model, $key) {
                                return Html::a('更新', $url, ['target' => '_blank']);
                            },
                            'delete' => function ($url, $model, $key) {
                                return Html::a('删除', $url, ['data-method' => 'post', 'data-confirm' => Yii::t('yii', '确认删除此专利事件？')]);
                            }
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
