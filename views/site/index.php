<?php

use yii\helpers\Url;
use yii\helpers\Html;

/* @var $this yii\web\View */

$this->title = '';
?>
<?php if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_CLIENT): ?>
    <?php
    $events = app\models\Patentevents::find()->where(['eventUserID' => Yii::$app->user->id])->orderBy(['eventCreatUnixTS' => SORT_DESC])->all();
    if (!$events) {
        $html = '<div class="alert alert-warning alert-dismissible">';
//        $html .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>';
        $html .= '<h4><i class="icon fa fa-warning"></i> 提示!</h4>';
        $html .= '您未绑定专利，可以' . \yii\helpers\Html::a('点此', ['site/contact'], ['title' => '反馈']) . '进行反馈或者致电客服进行绑定';
        $html .= '</div>';
        echo $html;
        // 没有进度的时候可以显示一些新闻之类的 TODO
    } else {
        echo $this->render('/patents/timeline', ['models' => $events, 'link' => true]);
    }
    ?>
<?php else: ?>
<div class="site-index">
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-users"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">今日新增</span>
                    <span class="info-box-number">
                        <?php
                        echo \app\models\Users::find()->where(['>', 'UnixTimestamp', strtotime(date('Y-m-d ')) * 1000])->count();
                        ?>
                    </span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-maroon"><i class="fa fa-hand-paper-o"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">新增专利</span>
                    <span class="info-box-number">
                        <?php
                        echo \app\models\Patents::find()->where(['>', 'UnixTimestamp', strtotime(date('Y-m-d ')) * 1000])->count();
                        ?>
                    </span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
    </div>
</div>
<?php endif; ?>