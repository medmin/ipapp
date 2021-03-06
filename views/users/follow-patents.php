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
$this->blocks['content-header'] = '';

$this->registerJs('
// 年费监管
function follow(which){
  url = "'. \yii\helpers\Url::to(['/users/follow-patents']) .'";
  application_no = $(which).data("application_no");
  $.post(url,{application_no:application_no},function(d){
    if(d == true) {
      $(which).removeClass("btn-primary").addClass("btn-success disabled").attr("disabled", true).text("监管中");
    }
  });
}
// 取消监管
function unfollow(which){
  url = "'. \yii\helpers\Url::to(['/users/unfollow-patent']) .'"+"?application_no="+$(which).data("application_no");
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
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="">
                <a href="<?= \yii\helpers\Url::to('/users/monitor-patents')?>">我的监管</a>
            </li>
            <li class="active" data-toggle="tab" aria-expanded="true">
                <a href="#">添加监管</a>
            </li>
            <li class="">
                <a href="<?= \yii\helpers\Url::to('/users/records')?>">缴费记录</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="patents-search">
                <div class="box box-info">
                    <form class="form-inline">
                        <div class="box-body">
                            <!-- <div class="form-group col-sm-3">
                                <label for="title" class="">专利名称</label>
                                <input name="title" type="text" class="form-control" id="title" placeholder="专利名称">
                            </div> -->
                            <div class="form-group col-sm-3">
                                <label for="application_no" class="">专利申请号</label>
                                <input name="application_no" type="text" class="form-control" id="application_no" placeholder="专利申请号">
                            </div>
                            <!-- <div class="form-group col-sm-3">
                                <label for="institution" class="">申请人</label>
                                <input name="institution" type="text" class="form-control" id="institution" placeholder="专利申请人">
                            </div>
                            <div class="form-group col-sm-3">
                                <label for="inventor" class="">发明人</label>
                                <input name="inventor" type="text" class="form-control" id="inventor" placeholder="发明人">
                            </div> -->

                        </div>
                        <div class="box-body">
                            <div class="form-group col-sm-3">
                                <button type="submit" class="btn btn-info">查找</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php
                if (isset($patents)) {
                    $html = '<div class="box box-primary">';
                    if (!$patents) {
                        $html .= '<div class="box-header">没有找到相关专利</div>';
                        $html .= '<div class="box-body"></div>';
                    } else {
                        $html .= "<div class=\"box-header with-border\">共查找到：<span class=\"text-red\">".count($patents)."</span> 条记录</div>";
                        $html .= '<div class="box-body">';
                        foreach ($patents as $patent) {
                            $html .= $this->render('/common/follow-patent-search', ['patent' => $patent]);
                        }
                        $html .= '</div>';
                        $html .= '<div class="box-footer clearfix" style="padding: 0 10px;">';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    echo $html;
                }
                ?>
            </div>
        </div>
    </div>
</div>
