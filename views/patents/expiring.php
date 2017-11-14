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
<div class="modal fade" id="filesModal" tabindex="-1" role="dialog" aria-labelledby="filesModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="filesModalLabel">上传文件</h4>
            </div>
            <div class="modal-body">

            </div>
<!--            <div class="modal-footer">-->
<!--                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>-->
<!--                <button type="button" class="btn btn-primary">Save changes</button>-->
<!--            </div>-->
        </div>
    </div>
</div>
