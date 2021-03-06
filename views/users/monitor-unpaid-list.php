<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-10-05
 * Time: 21:03
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
use yii\widgets\LinkPager;

$this->title = '阳光惠远 | 缴费清单';
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
  id = $(this).data("id");
  url = "/pay/wx-qrcode?id="+id;
  html = "<p style=\"text-align: center\"><span class=\"badge\" style=\"background: #fff;color: #113521\">使用微信支付</span></p><img src=\'"+url+"\'>";
  $(this).parent().children(".pay-qrcode").show().html(html);
});
');
}
?>
<div class="patents">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">
                您有如下缴费项目
            </h3>
        </div>
        <div class="box-body">
            <?php
            if (!$dataProvider->models) {
                echo '<div class="callout callout-success"><p><i class="icon fa fa-warning"></i> 暂无待缴费项目</p></div>';
            } else {
                foreach ($dataProvider->models as $idx => $model) {
//            echo $this->render('/common/follow-patent', ['model' => $model]);
                    echo $this->render('/common/unpaid-patent', ['model' => $model]);
                }
                echo LinkPager::widget([
                    'pagination'=>$dataProvider->pagination,
                    'options' => ['style' => 'margin: 0;', 'class' => 'pagination']
                ]);
            }
            ?>
        </div>
    </div>
</div>
