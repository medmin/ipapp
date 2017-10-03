<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Orders;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OrdersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Orders');
$this->params['breadcrumbs'][] = $this->title;
$this->registerJs('
var searchToggle = function(){
  $("#toggleSearchBtn").trigger("click");
}
function finished(obj) {
  $.post("finish?id="+$(obj).data("id"),function(d){
    if (d) {
      $(obj).parents("td").prev().text("已完成");
    }
  });
}
',\yii\web\View::POS_END);
?>
<div class="orders-index">
    <div class="box box-default collapsed-box">
        <div class="box-header with-border">
            <a href="javascript:;" onclick="searchToggle()" style="display: block;"><h3 class="box-title"><small>搜索</small></h3></a>
            <div class="box-tools box-right">
                <button id="toggleSearchBtn" type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <?php  echo $this->render('_search', ['model' => $searchModel]); ?>
        </div>
    </div>
    <div class="box box-primary">
        <div class="box-body table-responsive">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
//            ['class' => 'yii\grid\SerialColumn'],

                    [
                        'attribute' => 'trade_no',
                        'format' => 'html',
                        'value' => function ($model) {
                            return Html::a($model->trade_no, ['view', 'id' => $model->trade_no]);
                        }
                    ],
                    'out_trade_no',
                    'user_id',
                    'goods_id',
                    [
                        'attribute' => 'goods_type',
                        'value' => function ($model) {
                            return $model->goods_type == Orders::USE_PATENT ? '专利' : ($model->goods_type == Orders::USE_TM ? '商标' : '其他');
                        }
                    ],
                    [
                        'attribute' => 'payment_type',
                        'value' => function ($model) {
                            return $model->payment_type == Orders::TYPE_ALIPAY ? '支付宝' : ($model->payment_type == Orders::TYPE_WXPAY ? '微信' : '其他');
                        }
                    ],
                    'amount',
                    'created_at:datetime',
//                    'updated_at',
                    [
                        'attribute' => 'status',
                        'value' => function ($model) {
                            return Orders::status()[$model->status];
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
                                <ul class="dropdown-menu pull-right" role="menu">
                                    <li>{update}</li>
                                    <li>{finish}</li>
                                </ul>
                            </div>
                        ',
                        'buttons' => [
                            'update' => function ($url, $model, $key) {
                                return Html::a('更新', $url);
                            },
                            'finish' => function ($url, $model, $key) {
                                if ($model->status == Orders::STATUS_PAID) {
                                    return Html::a('交易完成', 'javascript:void(0);',['onclick' => 'finished(this)', 'data-id' => $key]);
                                } else {
                                    return '';
                                }
                            }
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
