<?php
/**
 * User: Mr-mao
 * Date: 2017/10/8
 * Time: 20:06
 */

// 临时解决方案

echo \yii\grid\GridView::widget([
    'dataProvider' => $patents,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        [
            'label' => '专利名称',
            'format' => 'raw',
            'value' => function ($model) {
                return \yii\helpers\Html::a($model->patentTitle,\yii\helpers\Url::to(['patents/view', 'id' => $model->patentID]));
            }
        ],
        [
            'label' => '申请人',
            'value' => function ($model) {
                return $model->patentApplicationInstitution;
            }
        ],
        [
            'label' => '申请号',
            'value' => function ($model) {
                return $model->patentApplicationNo;
            }
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'header' => Yii::t('app', 'Operation'),
            'template' => '{add}',
            'buttons' => [
                'add' => function ($url, $model, $key) {
                    return \yii\helpers\Html::button('监管', ['class' => 'btn btn-success follow-patent', 'data-id' => $key]);
                }
            ]
        ],
    ],
]);