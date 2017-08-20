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
var upload = function (id) {
    var h = $.get("/patentfiles/upload?ajxxb_id=" + id, function(data){
        if (data) {
            var html = data;
            $("#filesModal .modal-body").html(html);
            $("#filesModal").modal("show");
        } else {
            console.log("error");
        }
    })
    
    
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
                        'template' => '
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    操作
                                    <span class="fa fa-caret-down"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li>{view}</li> 
                                    <li>{update}</li>
                                    <li>{upload}</li>
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
                            'upload' => function ($url, $model, $key) {
                                return Html::a('上传文件', 'javascript:upload("'. $model->patentAjxxbID .'")');
                            }
                        ],
                    ],
                ],
            ]); ?>
        </div>
        <div class="box-footer clearfix">
            <button type="button" class="export-excel btn btn-primary pull-right" style="margin-right: 5px;">
                <i class="fa fa-download"></i> 导出本页数据
            </button>
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