<?php
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

// treeview没有被active的这个问题在AdminLte3.0 将会被解决，暂时先这么处理
$this->registerJs('
var url = window.location;

$(\'ul.sidebar-menu a\').filter(function() {
  return this.href == url;
}).parent().addClass(\'active\');

$(\'ul.treeview-menu a\').filter(function() {
  return this.href == url;
}).closest(\'.treeview\').addClass(\'active\');
');
?>

<header class="main-header">

    <?= Html::a('<span class="logo-mini">APP</span><span class="logo-lg">' . Yii::$app->name . '</span>', Yii::$app->homeUrl, ['class' => 'logo']) ?>

    <nav class="navbar navbar-static-top" role="navigation">

        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>

        <div class="navbar-custom-menu">

            <ul class="nav navbar-nav">

                <?php if (!Yii::$app->user->isGuest): ?>
                    <?php if (Yii::$app->user->identity->userRole !== \app\models\Users::ROLE_CLIENT): ?>
                        <li>
                            <a href=<?= \yii\helpers\Url::to(['users/notify']) ?>>
                                <i class="fa fa-bell-o"></i>
                                <?php
                                $count = \app\models\Notification::find()->where(['receiver' => Yii::$app->user->id, 'status' => 0])->count();
                                echo $count == 0 ? '' : ('<span class="label label-warning">' . $count . '</span>');
                                ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <img src="<?= $directoryAsset ?>/img/user2-160x160.jpg" class="user-image" alt="User Image"/>
                            <span class="hidden-xs"><?= Html::encode(Yii::$app->user->identity->userUsername) ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <!-- User image -->
                            <li class="user-header">
                                <img src="<?= $directoryAsset ?>/img/user2-160x160.jpg" class="img-circle"
                                     alt="User Image"/>

                                <p>
                                    <?= '真实姓名:' . Html::encode(Yii::$app->user->identity->userFullname) ?>
                                    <!--                                <small>Member since Nov. 2012</small>-->
                                </p>
                            </li>
                            <!-- Menu Body -->
                            <!--                        <li class="user-body">-->
                            <!--                            <div class="col-xs-4 text-center">-->
                            <!--                                <a href="#">Followers</a>-->
                            <!--                            </div>-->
                            <!--                            <div class="col-xs-4 text-center">-->
                            <!--                                <a href="#">Sales</a>-->
                            <!--                            </div>-->
                            <!--                            <div class="col-xs-4 text-center">-->
                            <!--                                <a href="#">Friends</a>-->
                            <!--                            </div>-->
                            <!--                        </li>-->
                            <!-- Menu Footer-->
                            <li class="user-footer">
                                <div class="pull-left">
                                    <a href="<?= \yii\helpers\Url::to(['users/personal-settings'])?>" class="btn btn-default btn-flat"><?= Yii::t('app','Your Profile')?></a>
                                </div>
                                <div class="pull-right">
                                    <?= Html::a(
                                        Yii::t('app','Logout'),
                                        ['/site/logout'],
                                        ['data-method' => 'post', 'class' => 'btn btn-default btn-flat']
                                    ) ?>
                                </div>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li>
                        <a style="margin-right: 10px" href="<?= \yii\helpers\Url::to(['login']) ?>"><i class="fa fa-paper-plane-o"> 登录</i></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>