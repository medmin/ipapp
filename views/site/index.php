<?php

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

    <div class="jumbotron">
        <h1>Congratulations!</h1>

        <p class="lead">You have successfully created your Yii-powered application.</p>

        <p><a class="btn btn-lg btn-success" href="http://www.yiiframework.com">Get started with Yii</a></p>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-4">
                <h2>Heading</h2>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                    fugiat nulla pariatur.</p>

                <p><a class="btn btn-default" href="http://www.yiiframework.com/doc/">Yii Documentation &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h2>Heading</h2>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                    fugiat nulla pariatur.</p>

                <p><a class="btn btn-default" href="http://www.yiiframework.com/forum/">Yii Forum &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h2>Heading</h2>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                    fugiat nulla pariatur.</p>

                <p><a class="btn btn-default" href="http://www.yiiframework.com/extensions/">Yii Extensions &raquo;</a></p>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>