<?php
/**
 * User: Mr-mao
 * Date: 2017/8/23
 * Time: 13:04
 */

/* @var $username */
/* @var $events \app\models\Patents */

if ($this->context->action->id == 'events-schedule') {
    $this->title = $username . '的主页(专利进度)';
    $this->params['breadcrumbs'][] = '用户主页';
}

?>
<div class="box box-default">
    <div class="box-body">
        <?php
        if (!$events) {
            echo '暂无专利动态';
        } else {
            echo $this->render('/patents/timeline', ['models' => $events, 'link' => true]);
        }
        ?>
    </div>
</div>
