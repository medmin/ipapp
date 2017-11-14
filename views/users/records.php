<?php
/**
 * User: Mr-mao
 * Date: 2017/10/4
 * Time: 20:34
 */

use yii\widgets\LinkPager;

$this->title = '阳光惠远 | 缴费记录';
 $this->params['breadcrumbs'][] = $this->title;
?>
<div class="records">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="">
                <a href="<?= \yii\helpers\Url::to('/users/monitor-patents')?>">我的监管</a>
            </li>
            <li class="">
                <a href="<?= \yii\helpers\Url::to('/users/follow-patents')?>">添加监管</a>
            </li>
            <li class="active" data-toggle="tab" aria-expanded="true">
                <a href="#">缴费记录</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active table-responsive" id="records">
                <?= \yii\grid\GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'label' => '申请号',
                            'attribute' => 'goods_id'
                        ],
                        [
                            'label' => '金额',
                            'attribute' => 'amount'
                        ],
                        [
                            'label' => '支付状态',
                            'value' => function ($model) {
                                return \app\models\Orders::status()[$model->status];
                            }
                        ],
                        [
                            'label' => '支付时间',
                            'value' => function ($model) {
                                return date('Y-m-d H:i',$model->paid_at);
                            }
                        ],
                        [
                            'label' => '订单详情',
                            'format' => 'raw',
                            'value' => function ($model) {
                                $fees = json_decode($model->detailed_expenses,true);
                                $html = '';
                                foreach ($fees as $fee) {
                                    $html .= '<p>' . $fee['type'] . '<span style="margin-left: 30px">' . $fee['amount'] . '</span>' . '元</p>';
                                }
                                return $html;
                            }
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
