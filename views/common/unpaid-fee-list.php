<?php
/**
 * User: Mr-mao
 * Date: 2017/10/8
 * Time: 21:35
 */

// 临时解决方案

echo \yii\grid\GridView::widget([
    'dataProvider' => $models,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        [
            'label' => '费用类型',
            'attribute' => 'fee_type'
        ],
        [
            'label' => '应缴金额（元）',
            'attribute' => 'amount'
        ],
        [
            'label' => '缴费截止日',
            'value' => function ($model) {
                return substr_replace(substr_replace($model->due_date,'-',4,0),'-',-2,0);
            }
        ],
    ]
]);
