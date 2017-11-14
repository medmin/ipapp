<?php
/**
 * User: Mr-mao
 * Date: 2017/10/8
 * Time: 17:52
 */

/* @var $this yii\web\View */
$this->title = '用户年费监管';
$this->params['breadcrumbs'][] = $this->title;
$this->registerJs('
function search() {
  $.ajax({
    type: "POST",
    url: $("#searchForm").attr("action"),
    data: $("#searchForm").serialize(),
    success: function (d) {
      $(".patents-search-result").html(d);
    }
  });
}
$("body").on("click",".follow-patent",function(){
  var url = "/users/follow-patents?user_id=";
  var user_id = "'.Yii::$app->request->getQueryParam("user_id").'";
  var c_text = $(this);
  $.post(url+user_id, {application_no:$(this).data("id")}, function(d){
    if (d){
      c_text.text("监管中").addClass("disabled")
    }
  })
})
$("body").on("click",".delete-patent",function(){
  var url = "/users/unfollow-patent?application_no="+$(this).data("id")+"&user_id=";
  var user_id = "'.Yii::$app->request->getQueryParam("user_id").'";
  var d_tr = $(this).parents("tr");
  $.post(url+user_id, function(d){
    if (d){
      d_tr.hide(100)
    }
  })
})
', \yii\web\View::POS_END);
?>
<div class="box box-info collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"style="display: block;cursor: pointer;" onclick="$(this).next().children().click()" >添加监管</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="box-body" style="display: none">
        <div class="patents-search">
            <form id="searchForm" action="<?= \yii\helpers\Url::to('/users/patents-search') ?>" method="post">
                <div class="form-group col-md-6">
                    <label for="No">专利号</label>
                    <input type="text" class="form-control" placeholder="专利号" name="No" id="No">
                </div>
<!--                <div class="form-group col-md-6">-->
<!--                    <label for="institution">申请人</label>-->
<!--                    <input type="text" class="form-control" placeholder="申请人" name="institution" id="institution">-->
<!--                </div>-->
                <div class="form-group col-md-12">
                    <button class="btn btn-success" type="button" onclick="search()">搜索</button>
                </div>
            </form>
        </div>
        <div class="clearfix"></div>
        <div class="patents-search-result">

        </div>
    </div>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title" style="display: block;cursor: pointer;" onclick="$(this).next().children().click()">他的监管</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <?= \yii\grid\GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'label' => '专利名称',
                    'attribute' => 'title'
                ],
                [
                    'label' => '申请人',
                    'attribute' => 'applicants'
                ],
                [
                    'label' => '监管日期',
                    'value' => function ($model) {
                        return date('Y-m-d H:i', $model['monitor_date']);
                    }
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => Yii::t('app', 'Operation'),
                    'template' => '{delete}',
                    'buttons' => [
                        'delete' => function ($url, $model, $key) {
                            return \yii\helpers\Html::button('删除',['class' => 'btn btn-warning delete-patent', 'data-id' => $model['application_no']]);
                        }
                    ]
                ],
            ],
        ]);
        ?>
    </div>
</div>