<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PatentfilesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Patentfiles');
$this->params['breadcrumbs'][] = $this->title;
$this->registerJs('
var searchToggle = function(){
        $("#toggleSearchBtn").trigger("click");
    }
',\yii\web\View::POS_END);
?>
<div class="patentfiles-index">

    <div class="box box-default collapsed-box">
        <div class="box-header with-border">
            <a href="javascript:void(0)" onclick="searchToggle()" style="display: block;"><h3 class="box-title"><small>搜索</small></h3></a>

            <div class="box-tools pull-right">
                <button id="toggleSearchBtn" type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <?php  echo $this->render('_search', ['model' => $searchModel]); ?>
        </div>
    </div>
    <div class="box box-default">
        <div class="box-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
        //        'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

                    'fileID',
                    'patentAjxxbID',
                    'fileName',
        //            'filePath',
                    'fileUploadUserID',
                    [
                        'attribute' => 'fileUploadedAt',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDatetime($model->fileUploadedAt);
                        }
                    ],
                     'fileUpdateUserID',
                    [
                        'attribute' => 'fileUpdatedAt',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDatetime($model->fileUploadedAt);
                        }
                    ],
                     'fileNote',

                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => Yii::t('app', 'Operation'),
                        'template' => '
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                            操作
                                            <span class="fa fa-caret-down"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li>{view}</li> 
                                            <li>{update}</li>
                                            <li>{delete}</li>
                                            <li>{download}</li>
                                        </ul>
                                    </div>
                                ',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('查看', $url, ['target' => '_blank']);
                            },
                            'update' => function ($url, $model, $key) {
                                return Html::a('更新', $url, ['target' => '_blank']);
                            },
                            'delete' => function ($url, $model, $key) {
                                return Html::a('删除文件', 'javascript:delete("'. $model->patentAjxxbID .'")');
                            },
                            'download' => function($url, $model, $key){
                                return Html::a('下载文件', 'javascript:download("'. $model->patentAjxxbID .'")');
                            }
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
