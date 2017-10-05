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
$this->title = false;

$this->registerJs('
function unfollow(w){
  if(window.confirm("确定取消监管?")){
    $.post("'. \yii\helpers\Url::to(['/users/unfollow-patent']) .'"+"?id="+$(w).data("id"),function(d){
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
}
?>
<div class="patents">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#" data-toggle="tab" aria-expanded="true">您有如下缴费项目</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="patents">
                <?php
                if (!$dataProvider->models) {
                    echo '<div class="callout callout-success"><p><i class="icon fa fa-warning"></i> 暂无待缴费项目</p></div>';
                } else {
                    foreach ($dataProvider->models as $idx => $model) {
//            echo $this->render('/common/follow-patent', ['model' => $model]);
                        echo $this->render('/common/follow-patent_2', ['model' => $model]);
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
</div>
