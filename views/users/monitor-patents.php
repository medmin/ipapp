<?php
/**
 * User: Mr-mao
 * Date: 2017/9/19
 * Time: 19:19
 */

use yii\widgets\LinkPager;

$this->title = '阳光惠远 | 年费监管';
// $this->params['breadcrumbs'][] = $this->title;
$this->title = false;

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

if ($this->context->isMicroMessage) {
    $js = '
    $(\'#pay-btn\').click(function(){
	var url = "'. \yii\helpers\Url::to(["pay/payment"]).'";
        $.post(url, {pay_type:\'WXPAY\',id:$(this).data(\'id\')}, function(d) {
            if(d.done == true) {
                $(\'#wxJS\').html(d.data);
                callpay();
            }
        },\'json\');
    })';
    $this->registerJs($js);
} else {
$this->registerJs('
$(".pay-link").click(function(){
  application_no = $(this).data("application_no");
  url = "/pay/wx-qrcode?application_no="+application_no;
  html = "<p style=\"text-align: center\"><span class=\"badge\" style=\"background: #fff;color: #113521\">使用微信支付</span></p><img src=\'"+url+"\'>";
  $(this).parent().children(".pay-qrcode").show().html(html);
});
');
}
// 共有的js
$this->registerJs('
$(".c_c").click(function(){
  $.get("show-unpaid-fee?application_no="+$(this).data("application_no"),function(d){
    if (d) {
      $("#showFees").modal("show");
      $("#showFees .modal-title").text("未缴费信息");
      $("#showFees .modal-body").html(d);
    }
  });
})
$("#showFees").on("hidden.bs.modal", function (e) {
  $("#showFees .modal-title").text("")
  $("#showFees .modal-body").html("");
})
');
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
<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="orderDetailModal" id="showFees">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="showFees">Modal title</h4>
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