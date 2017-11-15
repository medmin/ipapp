<?php
/**
 * User: Mr-mao
 * Date: 2017/9/19
 * Time: 19:19
 */

use yii\widgets\LinkPager;

$this->title = '阳光惠远 | 年费监管';
// $this->params['breadcrumbs'][] = $this->title;
$this->blocks['content-header'] = '';

$this->registerJs('
function unfollow(w){
  if(window.confirm("确定取消监管?")){
    $.post("'. \yii\helpers\Url::to(['/users/unfollow-patent']) .'"+"?application_no="+$(w).data("application_no"),function(d){
      if(d){
        $(w).parents(".patent-info").hide(1000);
      }
    });
  }
}
', \yii\web\View::POS_END);

$this->registerCss('
//.pay-link {
//    color: #00a1ff;
//    font-size: 12px;
//}
//.pay-link:hover, .pay-link:active, .pay-link:focus{
//    color: #00a1ff;
//}
.c_c {
  text-decoration:underline;
  color:blue;
  cursor:pointer;
  margin-left:5px;
}
a[class="pay-link-disabled"] {
    color: #dd4b39;
    text-decoration: underline;
    pointer-events：none;
    font-size: 12px;
}
');

// 共有的js
$this->registerJs('
$(".c_c").click(function(){
  var application_no = $(this).data("application_no");
  $.get("show-unpaid-fee?application_no="+application_no,function(d){
    if (d) {
      $("#showFees").modal("show");
      $("#showFees .modal-title").text("未缴费信息");
      $("#showFees input[name=\'annual-fee-application-no\']").val(application_no)
      $("#showFees .modal-body").html(d);
    }
  });
})
$("#showFees").on("hidden.bs.modal", function (e) {
  $("#showFees .modal-title").text("")
  $("#showFees .modal-body").html("");
  $("#showFees input[name=\'annual-fee-application-no\']").val("");
})
');
// 缴费页面复选框
$this->registerJs('
$("body").on("click", "#unpaid-fees tr td", function(){
  if ($(this).index() > 0) {
      var checkbox = $(this).parent("tr").find(":checkbox");
      checkbox.prop("checked", !checkbox.prop("checked"));
  }
});
$("body").on("click", "#unpaid-fees tr", function () {
  var sum = 0;
  $("#unpaid-fees input:checkbox:checked").each(function(){
      sum += $(this).data("amount");
  });
  $("#annual-fees-total").text(sum);
});
$(".pay-link-o").click(function(){
  var id_values = [];
  var application_no = $("#showFees input[name=\'annual-fee-application-no\']").val();
  $("#unpaid-fees input:checkbox:checked").each(function(){
      id_values.push($(this).val())
  });
  if (id_values.length == 0) {
    alert("请选择至少一个缴费项");
    return false;
  }
  
  var url = "/pay/submit?application_no=" + application_no;
  $.post(url, {"ids[]":id_values}, function(d){
    if (d.done == false) {
      alter(d.msg)
    }
    // 如果是微信浏览器
    if (navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == "micromessenger") {
      $.getJSON("/pay/wx-pay?id="+d.data, function(r){
        if (r.done == true) {
          $("#wxJS").html(r.data);
          callpay();
        }
      });
    } else {
      window.location.href = d.data; 
    }
  }, "json");
})
', \yii\web\View::POS_END);
?>
<div class="patents">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#" data-toggle="tab" aria-expanded="true">我的监管</a>
            </li>
            <li class="">
                <a href="<?= \yii\helpers\Url::to('/users/follow-patents')?>">添加监管</a>
            </li>
            <li class="">
                <a href="<?= \yii\helpers\Url::to('/users/records')?>">缴费记录</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="patents">
                <?php
                if (!$patents) {
                    echo '<div class="callout callout-warning"><p><i class="icon fa fa-warning"></i> 暂无监管专利，<a href="'. \yii\helpers\Url::to(['follow-patents']) .'">点击此处进行添加</a></p></div>';
                } else {
                    foreach ($patents as $idx => $patent) {
                        echo $this->render('/common/follow-patent_2', ['patent' => $patent]);
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>
<div id="wxJS"></div>
<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="orderDetailModal" id="showFees">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="showFees">Modal title</h4>
                <input type="text" name="annual-fee-application-no" hidden title="" value="">
            </div>
            <div class="modal-body">

            </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default">年费总计：<span id="annual-fees-total">0</span>元</button>
<!--                            <button type="button" class="btn btn-default">服务费总计：<span id="service-charge-total">0</span>元</button>-->
<!--                            <button type="button" class="btn btn-default">费用总计：<span id="total">0</span>元</button>-->
                            <button type="button" class="btn btn-primary pay-link-o">立即缴费</button>
                        </div>
        </div>
    </div>
</div>