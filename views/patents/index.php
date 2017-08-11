<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PatentsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Patents');
$this->params['breadcrumbs'][] = $this->title;
$this->registerJs('
var searchToggle = function(){
        $("#toggleSearchBtn").trigger("click");
    }
',\yii\web\View::POS_END);
?>
<div class="patents-index">
    <div class="box box-default collapsed-box">
        <div class="box-header with-border">
            <a href="javascript:void(0)" onclick="searchToggle()" style="display: block;"><h3 class="box-title"><small>搜索</small></h3></a>

            <div class="box-tools pull-right">
                <button id="toggleSearchBtn" type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <?php echo $this->render('_search', ['model' => $searchModel]); ?>
        </div>
    </div>
    <div class="box box-primary">
        <div class="box-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
//        'filterModel' => $searchModel,
                'columns' => [
//                    ['class' => 'yii\grid\SerialColumn'],

//            'patentID',
//            'patentAjxxbID',
                    'patentEacCaseNo',
                    'patentType',
//            'patentUserID',
                    'patentUsername',
                    // 'patentUserLiaisonID',
                    'patentUserLiaison',
                    'patentAgent',
                    'patentProcessManager',
                    'patentTitle',
                    'patentApplicationNo',
                    'patentPatentNo',
//                    'patentNote:ntext',
                    // 'UnixTimestamp:datetime',

                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => Yii::t('app', 'Operation'),
                        'template' => '{view} {update} '/* . 'associate' */,
                        'buttons' => [
                            'associate' => function($url, $model, $key) {
                                return Html::a('<span class="fa fa-user-plus"></span>', 'javascript:;', ['title' => '关联用户', 'data-toggle' => 'tooltip']);
                            }
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
