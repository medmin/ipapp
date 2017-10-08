<?php

use yii\helpers\Url;
use yii\helpers\Html;

/* @var $this yii\web\View */

$this->title = '';
?>
<?php if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_CLIENT): ?>
    <?php
    $events = app\models\Patentevents::find()->where(['eventUserID' => Yii::$app->user->id])->orderBy(['eventCreatUnixTS' => SORT_DESC])->all();
    $patents = \app\models\Patents::find()->where(['patentUserID' => Yii::$app->user->id])->count();
    if (!$events && !$patents) {
        $html = '<div class="alert alert-warning alert-dismissible">';
//        $html .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>';
        $html .= '<h4><i class="icon fa fa-warning"></i> 提示!</h4>';
        $html .= '<p>如果您已经在我司办理专利相关业务，请' . \yii\helpers\Html::a('联系我们', ['site/contact'], ['title' => '反馈']) . '来绑定您的专利信息，以便查看您所有的专利进度。</p><p>如果您尚未在我司办理专利业务，欢迎拨打<a href="tel:0451-88084686">0451-88084686</a>进行咨询。</p>';
        $html .= '</div>';
        echo $html;
        // 没有进度的时候可以显示一些新闻之类的 TODO
    } elseif (!$events && $patents) {
        echo '';
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
                    <span class="info-box-text">新增用户</span>
                    <span class="info-box-number">
                        <?php
                        $count = \app\models\Users::find()->where(['>', 'UnixTimestamp', strtotime(date('Y-m-d ')) * 1000])->count();
                        echo $count == 0 ? $count : Html::a($count, ['/users/index', 'UsersSearch[UnixTimestamp]' => strtotime(date('Ymd')) * 1000]);
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
                        $count = \app\models\Patents::find()->where(['>', 'UnixTimestamp', strtotime(date('Y-m-d ')) * 1000])->count();
                        echo $count == 0 ? $count : Html::a($count, ['/patents/index', 'PatentsSearch[UnixTimestamp]' => strtotime(date('Ymd')) * 1000])
                        ?>
                    </span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
<!--        <div class="col-md-3 col-sm-6 col-xs-12">-->
<!--            <div class="info-box">-->
<!--                <span class="info-box-icon bg-green"><i class="fa fa-rmb"></i></span>-->
<!--                <div class="info-box-content">-->
<!--                    <span class="info-box-text">缴费提醒</span>-->
<!--                    <b>-->
<!--                        <span class="info-box-text">-->
<!--                            今日待缴： <b class="text-red">0</b>-->
<!--                        </span>-->
<!--                        <span class="info-box-text">-->
<!--                            7日待缴：0-->
<!--                        </span>-->
<!--                        <span class="info-box-text">-->
<!--                            15日待缴：0-->
<!--                        </span>-->
<!--                    </b>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
    </div>
</div>
<?php endif; ?>
