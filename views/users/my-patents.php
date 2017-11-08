<?php
use yii\widgets\LinkPager;

/* @var $this yii\web\View */

$this->title = Yii::t('app', 'My Patents');
// $this->params['breadcrumbs'][] = $this->title;
$this->title = false;

$this->registerJs('
var collapseToggle = function(idx){
        $("#" + idx).trigger("click");
    }
', \yii\web\View::POS_END);
$this->registerCss('
.box.box-solid>.box-header a:hover {
    background: none;
}
');
?>
<div class="my-patents">
    <?php
    if (!$patents) {
        echo '<div class="callout callout-warning"><p><i class="icon fa fa-warning"></i> 抱歉您暂时没有专利记录</p></div>';
    } else {
        foreach ($patents as $idx => $patent) {
            echo $this->render('/patents/patents-list', ['patent' => $patent, 'idx' => $idx + 1]);
        }
    }
    ?>
</div>
