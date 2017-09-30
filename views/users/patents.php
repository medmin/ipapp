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
    $.post("'. \yii\helpers\Url::to(['/users/unfollow-patent']) .'"+"?id="+$(w).data("id"),function(d){
      if(d){
        $(w).parents(".patent-info").hide(1000);
      }
    });
  }
}
', \yii\web\View::POS_END);

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
    <?php
    if (!$dataProvider->models) {
        echo '<div class="callout callout-warning"><p><i class="icon fa fa-warning"></i> 暂无监管专利，<a href="'. \yii\helpers\Url::to(['follow-patents']) .'">点击此处进行添加</a></p></div>';
    } else {
        echo '<p><a class="btn btn-primary btn-sm btn-flat" href="'. \yii\helpers\Url::to(['follow-patents']) .'">添加监管</a></p>';
        foreach ($dataProvider->models as $idx => $model) {
            echo $this->render('/common/follow-patent', ['model' => $model]);
        }
        echo LinkPager::widget([
            'pagination'=>$dataProvider->pagination,
            'options' => ['style' => 'margin: 0;', 'class' => 'pagination']
        ]);
    }
    ?>
</div>
