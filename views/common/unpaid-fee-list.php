<?php
/**
 * User: Mr-mao
 * Date: 2017/10/8
 * Time: 21:35
 */

// 临时解决方案

echo \yii\grid\GridView::widget([
    'dataProvider' => $models,
    'options' => [
        'id' => 'unpaid-fees',
        'class' => 'grid-view',
    ],
    'columns' => [
        [
            'class' => 'yii\grid\CheckboxColumn',
            'name' => 'id',
            'header' => '',
            'checkboxOptions' => function ($model, $key, $index, $column) {
                return [
                    'value' => $model['id'],
                    'data-amount' => $model['amount'],
                ];
            }
        ],
        [
            'label' => '费用类型',
            'attribute' => 'type'
        ],
        [
            'label' => '应缴金额（元）',
            'attribute' => 'amount'
        ],
        [
            'label' => '缴费截止日',
            'value' => 'due_date',
        ],
    ]
]);
