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
$(".export-excel").click(function(){
    var array = new Array();
    $(".grid-view tbody tr").each(function(){
        array.push($(this).children("td").eq(0).html());
    })
    if (array.length == 0) {
        alert("暂无数据可以导出");
        return false;
    } else {
        window.location.href = "'. \yii\helpers\Url::to('export') .'" + "?rows=" + JSON.stringify(array)
    }
});
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
                    'patentAjxxbID',
                    'patentEacCaseNo',
                    'patentType',
                    'patentUserID',
                    'patentUsername',
                    // 'patentUserLiaisonID',
                    'patentUserLiaison',
                    'patentAgent',
                    'patentProcessManager',
                    'patentTitle',
                    'patentApplicationNo',
//                    'patentPatentNo',
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
            <button type="button" class="export-excel btn btn-primary pull-right" style="margin-right: 5px;">
                <i class="fa fa-download"></i> 导出本页数据
            </button>
        </div>
    </div>
</div>
