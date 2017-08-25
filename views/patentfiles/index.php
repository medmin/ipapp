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
var download = function(id) {
    var u = navigator.userAgent;
    var isMicromessager = u.toLowerCase().match(/MicroMessenger/i) == "micromessenger";
    var isAndroid = u.indexOf(\'Android\') > -1 || u.indexOf(\'Adr\') > -1;
    if (isMicromessager && isAndroid) {
        iziToast.show({
                message: "安卓微信暂不支持下载文件，请在手机浏览器中打开并下载",
                position: "center",
                progressBar: false,
                transitionInMobile: "fadeDown",
                transitionOutMobile: "flipOutX",
                theme: "dark",
                timeout: 6000,
            });
    } else {
        window.location.href = "'. \yii\helpers\Url::to(['patentfiles/download']) .'?id=" + id;
    }
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
        <div class="box-body table-responsive">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
        //        'filterModel' => $searchModel,
                'columns' => [
//                    ['class' => 'yii\grid\SerialColumn'],

                    'fileID',
                    'patentAjxxbID',
                    'fileName',
        //            'filePath',
                    [
                        'attribute' => 'fileUploadUserID',
                        'label' => '文件上传人',
                        'value' => function ($model) {
                            return $model->uploadUser->userFullname ?? '';
                        }
                    ],
                    [
                        'attribute' => 'fileUploadedAt',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDatetime($model->fileUploadedAt);
                        }
                    ],
                    [
                        'attribute' => 'fileUpdateUserID',
                        'label' => '文件更新人',
                        'value' => function ($model) {
                            return $model->updateUser->userFullname ?? '';
                        }
                    ],
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
                                        <ul class="dropdown-menu pull-right" role="menu">
                                            <li>{view}</li> 
                                            <li>{update}</li>
                                            <li>{delete}</li>
                                            <li>{download}</li>
                                        </ul>
                                    </div>
                                ',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('查看', $url);
                            },
                            'update' => function ($url, $model, $key) {
                                return Html::a('更新', $url);
                            },
                            'delete' => function ($url, $model, $key) {
                                return Html::a('删除', $url, ['data-method' => 'post', 'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?')]);
                            },
                            'download' => function($url, $model, $key){
                                return Html::a('下载文件', 'javascript:download("'. $model->fileID .'")');
                            }
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
