<?php
/* @var $this yii\web\View */
/* @var $models app\models\Notification */
$this->title = Yii::t('app', 'Notify');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-notify">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#newNotifies" data-toggle="tab" aria-expanded="true">未读消息</a>
            </li>
            <li class="">
                <a href="#allNotifies" data-toggle="tab" aria-expanded="true">所有消息</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="newNotifies">
            <?php
                if (!$models) {
                    echo '暂时没有未读消息';
                } else {
                    foreach ($models as $model) {
                        $html = <<<HTML
<div class="box box-default">
    <div class="box-header with-border">
        {$model->uses->userUsername}发送于:{$model->createdAt}
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
        </div>
        <div class="box-body">
            <div class="direct-chat-msg">
                <img class="direct-chat-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user7-128x128.jpg" alt="Message User Image">
                <div class="direct-chat-text">
                    {$model->content}
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
                        echo $html;
                    }
                }
            ?>
            </div>
            <div class="tab-pane" id="allNotifies">

            </div>
        </div>
    </div>
</div>
