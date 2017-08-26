<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

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
var upload = function(id) {
    var h = $.get("'.\yii\helpers\Url::to(['patentfiles/upload']).'?ajxxb_id=" + id, function(data){
        if (data) {
            var html = data;
            $("#filesModal .modal-body").html(html);
            $("#filesModal").modal("show");
        } else {
            console.log("error");
        }
    })
};
var download = function(id) {
    var url = "'.\yii\helpers\Url::to(['patentfiles/download-group']).'?ajxxb_id=" + id;
    $.get(url, function(data) {
        if (!data) {
            iziToast.show({
                message: "该专利暂无文件可下载",
                position: "topCenter",
                progressBar: false,
                transitionIn: "fadeDown",
                theme: "dark",
                timeout: 4000
            });
        } else {
           window.location.href = url;
        }
    });
};
$("#uploadform-patentfiles").on("change",function(){
    $("#filesCover").val($(this).val());
});
$("body").on("submit", "#files-upload-form", function(e){
    // console.log($("#files-upload-form")[0]);
    e.preventDefault(); // 阻止默认行为
    var files_size = 0;
    var files = $("#files-upload-form #uploadform-patentfiles")[0]; // 注意这个数组0
    for (var i=0; i < files.files.length; i++) {
        files_size += files.files[i].size;
    }
    if (files_size == 0) {
        $("#filesModal").modal("hide");
        return iziToast.show({
            message: "没有上传任何文件",
            position: "topCenter",
            progressBar: false,
            theme: "dark",
            timeout: 4000,
        });
    }
    if (files_size > 52428800) {
        return iziToast.show({
            message: "文件要求50MB以内",
            position: "topCenter",
            progressBar: false,
            theme: "dark",
            timeout: 4000,
        });
    }
    $(".progress").css("display", "block");
    var formData = new FormData($("#files-upload-form")[0]);
//    console.log(formData)
    $.ajax({
        type: "POST",
        url: $("#files-upload-form").attr("action"),
        data: formData,
        xhr: function(e){
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(e) {
                if (e.lengthComputable) {
//                    console.log("Bytes Loaded:" + e.loaded);
//                    console.log("Total Size:" + e.total);
//                    console.log("Percentage Uploaded:" + (e.loaded / e.total));
                    var percent = Math.round(e.loaded / e.total * 100);

                    $("#files-progress-bar").attr("aria-valuenow", percent).css("width", percent + "%").text(percent + "%");
                }
            });
            return xhr;
        },
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (data) {
            if (data["code"] == 0){
                $("#filesModal").modal("hide");
            }
            return iziToast.show({
                message: data["msg"],
                position: "topCenter",
                progressBar: false,
                theme: "dark",
                timeout: (data["code"] == 0) ? 5000 : 20000,
            });
        }
    });
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
        <div class="box-body table-responsive">
            <?php
            if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_EMPLOYEE) {
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
//                        'patentUserLiaison',
//                        'patentAgent',
//                        'patentProcessManager',
                        'patentTitle',
//                        'patentType',
                        'patentApplicationNo',
//                        'patentApplicationDate',
                    ],
                ]);
            } else {
                echo GridView::widget([
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
                        'patentApplicationDate',
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
                                <ul class="dropdown-menu pull-right" role="menu">
                                    <li>{view}</li>
                                    <li>{update}</li>
                                    <li>{create-event}</li>
                                    <li>{schedule}</li>
                                    <li>{upload}</li>
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
                                'upload' => function ($url, $model, $key) {
                                    return Html::a('上传文件', 'javascript:upload("'. $model->patentAjxxbID .'")');
                                },
                                'download' => function ($url, $model, $key) {
                                    return Html::a('下载文件', 'javascript:download("'. $model->patentAjxxbID .'")');
                                },
                                'schedule' => function ($url, $model, $key) {
                                    return Html::a('进度', Url::to(['main', 'id' => $model->patentAjxxbID]));
                                },
                                'create-event' => function ($url, $model, $key) {
                                    return Html::a('添加事件', Url::to(['/patentevents/create', 'ajxxb_id' => $model->patentAjxxbID ]));
                                }
                            ],
                        ],
                    ],
                ]);
            }
            ?>
        </div>
        <?php if (Yii::$app->user->identity->userRole !== \app\models\Users::ROLE_EMPLOYEE): ?>
        <div class="box-footer clearfix">
            <button type="button" class="export-excel btn btn-primary pull-right" style="margin-right: 5px;">
                <i class="fa fa-download"></i> 导出本页数据
            </button>
        </div>
        <?php endif; ?>
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
