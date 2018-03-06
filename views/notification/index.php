<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NotificationSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::$app->controller->action->id == 'wechat-log' ? '微信模板消息发送日志' : '留言信息';
$this->params['breadcrumbs'][] = $this->title;
$this->registerJs('
var searchToggle = function(){
  $("#toggleSearchBtn").trigger("click");
}
',\yii\web\View::POS_END);
?>
<div class="notification-index">
    <div class="box box-default collapsed-box">
        <div class="box-header with-border">
            <a href="javascript:;" onclick="searchToggle()" style="display: block;"><h3 class="box-title"><small>搜索</small></h3></a>
            <div class="box-tools box-right">
                <button id="toggleSearchBtn" type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <?php  echo $this->render('_search', ['model' => $searchModel, 'action' => Yii::$app->controller->action->id]); ?>
        </div>
    </div>
    <div class="box box-primary">
        <div class="box-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
//                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

//                  'id',
//                  'sender',
                    [
                        'attribute' => 'receiver',
                        'label' => '接收用户',
                        'value' => function ($model) {
                            return Html::a($model->receiverInfo->userUsername, ['/users/view', 'id' => $model->receiverInfo->userID]);
                        },
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'content',
                        'value' => function ($model) {
                            return str_replace('，','</br>',$model->content);
                        },
                        'format' => 'raw'
                    ],
//                   'type',
                    'createdAt:datetime',
//                    'status',

//            ['class' => 'yii\grid\ActionColumn'],
                ],
            ]); ?>
        </div>
    </div>

</div>
