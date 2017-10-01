<?php
/**
 * User: Mr-mao
 * Date: 2017/9/19
 * Time: 20:42
 */

use yii\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $dataProvider  */

$this->title = '阳光惠远 | 专利查找';
$this->title = false;

$this->registerJs('
// 年费监管
function follow(which){
  url = "'. \yii\helpers\Url::to(['/users/follow-patents']) .'";
  id = $(which).data("id");
  $.post(url,{id:id},function(d){
    if(d == true) {
      $(which).removeClass("btn-primary").addClass("btn-success disabled").attr("disabled", true).text("监管中");
    }
  });
}
// 取消监管
function unfollow(which){
  url = "'. \yii\helpers\Url::to(['/users/unfollow-patent']) .'"+"?id="+$(which).data("id");
  $.post(url,function(d){
    if(d == false){
      console.log("没有监管过该专利");
    }else{
      $(which).removeClass("btn-warning").addClass("btn-primary").attr("onclick", "follow(this)").text("添加监管");
    }
  });
}
', \yii\web\View::POS_END);
?>
<div class="patents-search">
    <div class="box box-info">
        <form class="form-inline">
            <div class="box-body">
                <div class="form-group col-sm-5">
                    <label for="No" class="">专利申请号</label>
                    <input name="No" type="text" class="form-control" id="No" placeholder="专利申请号">
                </div>
                <div class="form-group col-sm-5">
                    <label for="inventor" class="">发明人</label>
                    <input name="inventor" type="text" class="form-control" id="inventor" placeholder="发明人">
                </div>
                <div class="form-group col-sm-2">
                    <button type="submit" class="btn btn-info">查找</button>
                    <a href="<?= \yii\helpers\Url::to(['patents'])?>" class="btn btn-info pull-right">我的监管</a>
                </div>
            </div>
        </form>
    </div>
    <?php
    if (isset($dataProvider)) {
        $html = '<div class="box box-primary">';
        if (!$dataProvider->models) {
            $html .= '<div class="box-header">没有找到相关专利</div>';
            $html .= '<div class="box-body"></div>';
        } else {
            $html .= "<div class=\"box-header with-border\">共查找到：<span class=\"text-red\">{$dataProvider->count}</span> 条记录</div>";
            $html .= '<div class="box-body">';
            foreach ($dataProvider->models as $model) {
                $html .= $this->render('/common/follow-patent-search', ['model' => $model]);
            }
            $html .= '</div>';
            $html .= '<div class="box-footer clearfix" style="padding: 0 10px;">';
            $html .=  LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'hideOnSinglePage' => true,
                'options' => ['style' => 'margin: 10px 0 0 0;', 'class' => 'pagination']
            ]);
            $html .= '</div>';
        }
        $html .= '</div>';
        echo $html;
    }
    ?>

</div>
