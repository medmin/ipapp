<?php
/**
 * User: Mr-mao
 * Date: 2017/10/4
 * Time: 20:34
 */

use yii\widgets\LinkPager;

$this->title = '阳光惠远 | 年费监管';
// $this->params['breadcrumbs'][] = $this->title;
$this->title = false;
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
                            'label' => '专利名称',
                            'value' => function ($model) {
                                return $model->patent->patentTitle;
                            }
                        ],
                        [
                            'label' => '缴费类型',
                            'value' => function ($model) {
                                return $model->fee_type;
                            }
                        ],
                        [
                            'label' => '金额',
                            'value' => function ($model) {
                                return $model->amount;
                            }
                        ],
                        [
                            'label' => '支付状态',
                            'value' => function ($model) {
                                return $model->status == \app\models\UnpaidAnnualFee::FINISHED ? '已完成' : '正在处理';
                            }
                        ],
                        [
                            'label' => '支付时间',
                            'value' => function ($model) {
                                return date('Y-m-d H:i',$model->paid_at);
                            }
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
