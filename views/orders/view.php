<?php

use app\models\Orders;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */

$this->title = '订单详情';
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Orders'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['breadcrumbs'][] = $model->trade_no;
?>
<div class="orders-view">
    <div class="box box-info">
        <div class="box-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'trade_no',
                    'out_trade_no',
                    [
                        'attribute' => 'payment_type',
                        'value' => function ($model) {
                            return $model->payment_type == Orders::TYPE_ALIPAY ? '支付宝' : ($model->payment_type == Orders::TYPE_WXPAY ? '微信' : '其他');
                        }
                    ],
                    'user_id',
                    'goods_id',
                    [
                        'label' => '专利名称',
                        'value' => function ($model) {
                            $client = new \GuzzleHttp\Client(['base_uri' => Yii::$app->params['api_base_uri']]);
                            try {
                                $response = $client->request('GET', '/patents/view/'.$model->goods_id);
                                $patent = json_decode($response->getBody(), true);
                            } catch (\Exception $e) {
                                Yii::error($e->getMessage());
                                $patent = '';
                            }
                            return $patent ? $patent['title'] : null;
                        }
                    ],
                    [
                        'attribute' => 'goods_type',
                        'value' => function ($model) {
                            return $model->goods_type == Orders::USE_PATENT ? '专利' : ($model->goods_type == Orders::USE_TM ? '商标' : '其他');
                        }
                    ],
                    'amount',
                    'created_at:datetime',
                    'updated_at:datetime',
                    [
                        'attribute' => 'status',
                        'value' => function ($model) {
                            return Orders::status()[$model->status];
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

</div>
