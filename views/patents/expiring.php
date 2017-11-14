<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Expiring');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="patents-index">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="<?php if ($date_type == 1): ?>active<? endif; ?>">
                <a href="<?= \yii\helpers\Url::to(['/patents/expiring', 'date_type'=>1])?>">最近一周</a>
            </li>
            <li class="<?php if ($date_type == 2): ?>active<? endif; ?>">
                <a href="<?= \yii\helpers\Url::to(['/patents/expiring', 'date_type'=>2])?>">最近半个月</a>
            </li>
            <li class="<?php if ($date_type == 3): ?>active<? endif; ?>">
                <a href="<?= \yii\helpers\Url::to(['/patents/expiring', 'date_type'=>3])?>">最近一个月</a>
            </li>
        </ul>
        <div class="tab-content">
            <?php
                echo GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'attribute' => 'patentAjxxbID',
                            'format' => 'html',
                            'value' => function ($model) {
                                return Html::a($model->patentAjxxbID, ['view', 'id' => $model->patentID]);
                            }
                        ],
                        'patentEacCaseNo',
                        'patentUsername',
                        'patentTitle',
                        'patentApplicationNo',
                        'patentFeeDueDate',
                    ],
                ]);
            ?>
        </div>
    </div>
</div>
